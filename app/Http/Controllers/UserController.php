<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PosAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserController extends Controller
{
    public function __construct(
        private readonly PosAuditLogger $auditLogger,
        private readonly InventoryService $inventoryService
    ) {
    }

    /**
     * Companies and locations belong to the Inventory system, so the chosen
     * location is looked up there and its names are stored with the user.
     * Returns the three user columns, or a JSON error response.
     */
    private function resolveLocation(?int $locationId): array|JsonResponse
    {
        if (!$locationId) {
            return ['location_id' => null, 'location_name' => null, 'company_name' => null];
        }

        try {
            $location = collect($this->inventoryService->getLocations())
                ->first(fn ($location) => (int) ($location['id'] ?? 0) === $locationId);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Can\'t load locations from the Inventory system right now. Try again, or save without a location.',
            ], 503);
        }

        if (!$location) {
            return response()->json(['message' => 'That location no longer exists in the Inventory system.'], 422);
        }

        return [
            'location_id' => $locationId,
            'location_name' => $location['name'] ?? null,
            'company_name' => $location['company']['name'] ?? null,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can view users.'], 403);
        }

        return response()->json([
            'data' => User::query()
                ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'location_id', 'location_name', 'company_name'])
                ->when($user->accessLocationId(), fn ($query, $locationId) => $query->where('location_id', $locationId))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can create users.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:cashier,manager,admin'],
            'location_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $requestedRole = $validated['role'] ?? 'cashier';

        if ($requestedRole !== 'cashier' && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can create manager or admin accounts.',
            ], 403);
        }

        $requestedLocation = $user->accessLocationId() ?? (isset($validated['location_id']) ? (int) $validated['location_id'] : null);
        $location = $this->resolveLocation($requestedLocation);

        if ($location instanceof JsonResponse) {
            return $location;
        }

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $requestedRole,
        ] + $location);

        return response()->json([
            'data' => $newUser->only(['id', 'name', 'email', 'role', 'location_id', 'location_name', 'company_name']),
        ], 201);
    }

    public function updateRole(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can change roles.'], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:cashier,manager,admin'],
        ]);

        if ($denied = $this->outsideLocation($user, $targetUser)) {
            return $denied;
        }

        if ($targetUser->isAdmin() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can change an admin\'s role.',
            ], 403);
        }

        if ($validated['role'] !== 'cashier' && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can promote a user to manager or admin.',
            ], 403);
        }

        $oldRole = $targetUser->role;
        $targetUser->role = $validated['role'];
        $targetUser->save();

        if ($targetUser->role !== $oldRole) {
            $this->auditLogger->roleChangedByAdmin($user, $targetUser, $oldRole, $targetUser->role);
        }

        return response()->json([
            'data' => $targetUser->only(['id', 'name', 'email', 'role']),
        ]);
    }

    /**
     * Edit another user's name/email. Role and active status are handled
     * by the existing updateRole/deactivate/reactivate endpoints — this
     * only covers the fields not already served by those.
     */
    public function update(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can edit users.'], 403);
        }

        if ($targetUser->id === $user->id) {
            return response()->json([
                'message' => 'Use your Account page to edit your own profile.',
            ], 422);
        }

        if ($denied = $this->outsideLocation($user, $targetUser)) {
            return $denied;
        }

        if ($targetUser->isAdmin() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can edit an admin account.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $targetUser->id],
            'location_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $oldEmail = $targetUser->email;

        $targetUser->name = $validated['name'];
        $targetUser->email = $validated['email'];

        // Only touch the location when the form sent one (or sent it empty
        // to clear it), and only look it up when it actually changed.
        if ($request->has('location_id')) {
            $newLocationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;

            if ($newLocationId !== ($targetUser->location_id === null ? null : (int) $targetUser->location_id)) {
                $location = $this->resolveLocation($newLocationId);

                if ($location instanceof JsonResponse) {
                    return $location;
                }

                $targetUser->fill($location);
            }
        }

        $targetUser->save();

        if ($targetUser->email !== $oldEmail) {
            $this->auditLogger->emailChangedByAdmin($user, $targetUser, $oldEmail, $targetUser->email);
        }

        return response()->json([
            'data' => $targetUser->only(['id', 'name', 'email', 'role', 'location_id', 'location_name', 'company_name']),
        ]);
    }

    public function resetPassword(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can reset passwords.'], 403);
        }

        if ($targetUser->id === $user->id) {
            return response()->json([
                'message' => 'Use your Account page to change your own password.',
            ], 422);
        }

        if ($denied = $this->outsideLocation($user, $targetUser)) {
            return $denied;
        }

        if ($targetUser->isAdmin() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can reset an admin\'s password.',
            ], 403);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'require_password_change' => ['nullable', 'boolean'],
        ]);

        $mustChangePassword = $request->boolean('require_password_change');

        $targetUser->password = Hash::make($validated['password']);
        $targetUser->must_change_password = $mustChangePassword;
        $targetUser->save();

        // A freshly-reset password means any existing session should not
        // be trusted to continue silently.
        $targetUser->tokens()->delete();

        $this->auditLogger->passwordResetByAdmin($user, $targetUser, $mustChangePassword);

        return response()->json([
            'message' => 'Password reset for ' . $targetUser->name . '.',
        ]);
    }

    public function deactivate(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can deactivate accounts.'], 403);
        }

        if ($targetUser->id === $user->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        if ($denied = $this->outsideLocation($user, $targetUser)) {
            return $denied;
        }

        if ($targetUser->isAdmin() && !$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can deactivate an admin account.'], 403);
        }

        if (!$targetUser->isActive()) {
            return response()->json(['message' => 'This account is already deactivated.'], 422);
        }

        $targetUser->is_active = false;
        $targetUser->deactivated_at = now();
        $targetUser->save();

        // Revoke all existing sessions immediately, not just future logins.
        $targetUser->tokens()->delete();

        $this->auditLogger->statusChangedByAdmin($user, $targetUser, false);

        return response()->json([
            'data' => $targetUser->only(['id', 'name', 'email', 'role', 'is_active', 'deactivated_at']),
        ]);
    }

    public function reactivate(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->isManager()) {
            return response()->json(['message' => 'Only managers can reactivate accounts.'], 403);
        }

        if ($denied = $this->outsideLocation($user, $targetUser)) {
            return $denied;
        }

        if ($targetUser->isAdmin() && !$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can reactivate an admin account.'], 403);
        }

        if ($targetUser->isActive()) {
            return response()->json(['message' => 'This account is already active.'], 422);
        }

        $targetUser->is_active = true;
        $targetUser->deactivated_at = null;
        $targetUser->save();

        $this->auditLogger->statusChangedByAdmin($user, $targetUser, true);

        return response()->json([
            'data' => $targetUser->only(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    /** A manager with a location only manages people at that location. */
    private function outsideLocation(User $actor, User $target): ?JsonResponse
    {
        $locationId = $actor->accessLocationId();

        if ($locationId !== null && (int) $target->location_id !== $locationId) {
            return response()->json(['message' => 'You can only manage people at your own location.'], 403);
        }

        return null;
    }
}
