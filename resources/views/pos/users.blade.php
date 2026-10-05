<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Manage Users</title>
    <link rel="stylesheet" href="{{ asset('pos-assets/style.css') }}?v={{ filemtime(public_path('pos-assets/style.css')) }}">
    <style>
        .account-field { margin-bottom: 14px; }
        .account-field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: #374151;
        }
        .account-field input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }
        .account-field.checkbox-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .account-field.checkbox-field input {
            width: auto;
        }
        .account-field.checkbox-field label {
            margin-bottom: 0;
            font-weight: normal;
        }
    </style>
</head>

<body class="pos-body">

    <div id="page-loader" class="page-loader">
        <div class="spinner"></div>
        <p>Loading Manage Users...</p>
    </div>

    <div id="app" hidden>

        <!-- TOP BAR -->
        <header class="pos-header">
            <div class="brand-area">
                <span class="header-eyebrow">MANAGER</span>
                <strong class="header-title">Manage Users</strong>
            </div>

            <div class="header-center"></div>

            <div class="header-actions">
                <a href="/pos/manager" class="header-btn">Manager Console</a>
                <a href="/pos/manager/audit-log" class="header-btn">Audit Log</a>
                <a href="/pos" class="header-btn">Back to POS</a>
                <a href="/pos/account" class="header-btn">My Account</a>
                <span class="logged-in-badge">Logged in: <strong id="cashier-name">User</strong></span>
                <button id="logout-btn" class="header-btn logout-btn">Logout</button>
            </div>
        </header>

        <!-- ERROR / NOTICE -->
        <div id="error-banner" class="error-banner" hidden></div>

        <div class="page-intro">
            <div>
                <h1>Staff accounts</h1>
                <p>Add accounts, set each person's role, reset a forgotten password, or deactivate someone who has left. Use the Add User button to create a new account.</p>
            </div>
        </div>

        <!-- USERS -->
        <section class="reports-section" style="margin-top:20px;">
            <div class="section-heading-row report-heading-row">
                <p class="section-eyebrow">All Users</p>
                <button type="button" id="add-user-open-btn" class="primary-action-btn">+ Add User</button>
            </div>

            <div class="table-card">
                <div class="table-scroll">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Company / Location</th>
                                <th>Password</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <tr>
                                <td colspan="7" class="table-empty">Loading users...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

    </div>

    <!-- ADD USER MODAL -->
    <div id="add-user-modal" class="modal-overlay" hidden>
        <div class="register-modal">
            <h2 style="margin-top:0;">Add User</h2>

            <form id="add-user-form" novalidate>
                <div class="account-field">
                    <label for="add-user-name">Full name</label>
                    <input type="text" id="add-user-name" maxlength="255" required>
                </div>

                <div class="account-field">
                    <label for="add-user-email">Email</label>
                    <input type="email" id="add-user-email" placeholder="email@store.com" required>
                </div>

                <div class="account-field">
                    <label for="add-user-password">Temporary password</label>
                    <input type="password" id="add-user-password" placeholder="Min. 8 characters" minlength="8" required>
                </div>

                <div class="account-field">
                    <label for="add-user-role">Role</label>
                    <select id="add-user-role">
                        <option value="cashier">Cashier</option>
                        <option value="manager" data-admin-only hidden>Manager</option>
                        <option value="admin" data-admin-only hidden>Admin</option>
                    </select>
                </div>

                <div class="account-field">
                    <label for="add-user-company">Company</label>
                    <select id="add-user-company">
                        <option value="">Select a company</option>
                    </select>
                </div>

                <div class="account-field">
                    <label for="add-user-location">Location</label>
                    <select id="add-user-location" disabled>
                        <option value="">Select a company first</option>
                    </select>
                </div>

                <div id="add-user-error" class="modal-error" hidden></div>

                <div class="modal-actions">
                    <button type="button" id="add-user-cancel-btn" class="secondary-btn">Cancel</button>
                    <button type="submit" class="secondary-btn">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div id="edit-user-modal" class="modal-overlay" hidden>
        <div class="register-modal">
            <h2 style="margin-top:0;">Edit User</h2>

            <form id="edit-user-form">
                <input type="hidden" id="edit-user-id">

                <div class="account-field">
                    <label for="edit-user-name">Name</label>
                    <input type="text" id="edit-user-name" required>
                </div>

                <div class="account-field">
                    <label for="edit-user-email">Email</label>
                    <input type="email" id="edit-user-email" required>
                </div>

                <div class="account-field">
                    <label for="edit-user-company">Company</label>
                    <select id="edit-user-company">
                        <option value="">No company</option>
                    </select>
                </div>

                <div class="account-field">
                    <label for="edit-user-location">Location</label>
                    <select id="edit-user-location">
                        <option value="">No location</option>
                    </select>
                </div>

                <div id="edit-user-error" class="modal-error" hidden></div>

                <div class="modal-actions">
                    <button type="button" id="edit-user-cancel-btn" class="secondary-btn">Cancel</button>
                    <button type="submit" class="secondary-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RESET PASSWORD MODAL -->
    <div id="reset-password-modal" class="modal-overlay" hidden>
        <div class="register-modal">
            <h2 style="margin-top:0;">Reset Password</h2>
            <p id="reset-password-user-label" style="color:#64748b;"></p>

            <form id="reset-password-form">
                <input type="hidden" id="reset-password-user-id">

                <div class="account-field">
                    <label for="reset-password-new">New Password</label>
                    <input type="password" id="reset-password-new" minlength="8" required>
                </div>

                <div class="account-field">
                    <label for="reset-password-confirm">Confirm New Password</label>
                    <input type="password" id="reset-password-confirm" minlength="8" required>
                </div>

                <div class="account-field checkbox-field">
                    <input type="checkbox" id="reset-password-force-change">
                    <label for="reset-password-force-change">Require password change on next login</label>
                </div>

                <div id="reset-password-error" class="modal-error" hidden></div>

                <div class="modal-actions">
                    <button type="button" id="reset-password-cancel-btn" class="secondary-btn">Cancel</button>
                    <button type="submit" class="secondary-btn">Reset Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/pos-assets/app.js?v={{ filemtime(public_path('pos-assets/app.js')) }}"></script>
    <script>
        Pos.initUsersPage();
    </script>

    @include('pos.partials.confirm-modal')

</body>
</html>
