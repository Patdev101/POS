<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'must_change_password', 'location_id', 'location_name', 'company_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }
    public function sales(): HasMany
{
    return $this->hasMany(Sale::class);
}

public function cashSessions(): HasMany
{
    return $this->hasMany(CashSession::class);
}





    /**
     * The location a manager or cashier is limited to, or null when they
     * are not limited (admins, or an account with no location yet).
     */
    public function accessLocationId(): ?int
    {
        return !$this->isAdmin() && $this->location_id ? (int) $this->location_id : null;
    }
}
