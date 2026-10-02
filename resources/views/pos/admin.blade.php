<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Admin Dashboard</title>
    <link rel="stylesheet" href="{{ asset('pos-assets/style.css') }}?v={{ filemtime(public_path('pos-assets/style.css')) }}">
    <style>
        .admin-columns { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        .admin-team { padding: 18px 20px; }
        .admin-team-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: var(--border); gap: 2px; }
        .admin-team-bar span { display: block; min-width: 0; }
        .admin-team-bar span[hidden] { display: none; }
        .seg-admin { background: var(--navy); }
        .seg-manager { background: var(--primary); }
        .seg-cashier { background: #6366f1; }
        .admin-team-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 14px;
        }
        .admin-team-row:first-of-type { margin-top: 8px; }
        .admin-team-row:last-of-type { border-bottom: 0; }
        .admin-team-row .dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 10px; }
        .admin-team-row strong { font-size: 16px; color: var(--navy); font-variant-numeric: tabular-nums; }
        .admin-team-action {
            display: block; margin-top: 14px; text-align: center; text-decoration: none;
            padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700;
            background: var(--primary); color: #fff;
        }
        .admin-team-action:hover { background: var(--primary-dark); }
        @media (max-width: 1000px) { .admin-columns { grid-template-columns: 1fr; } }
    </style>
</head>

<body class="pos-body">

    <div id="page-loader" class="page-loader">
        <div class="spinner"></div>
        <p>Loading Admin Dashboard...</p>
    </div>

    <div id="app" hidden>

        <header class="pos-header">
            <div class="brand-area">
                <span class="header-eyebrow">ADMIN</span>
                <strong class="header-title">Admin Dashboard</strong>
            </div>

            <div class="header-center"></div>

            <div class="header-actions">
                <a href="/pos/account" class="header-btn">My Account</a>
                <span class="logged-in-badge">Logged in: <strong id="cashier-name">User</strong></span>
                <button id="logout-btn" class="header-btn logout-btn">Logout</button>
            </div>
        </header>

        <div id="error-banner" class="error-banner" hidden></div>

        <div class="page-intro">
            <div>
                <h1 data-greeting>Welcome back</h1>
                <p>Here's how the store is doing and who's on the team.</p>
            </div>
            <span class="page-intro-date" data-today></span>
        </div>

        <div class="stats-row cols-4">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon" aria-hidden="true">₱</div>
                <div>
                    <div class="stat-label">Sales today</div>
                    <div class="stat-value" id="admin-stat-total">₱0.00</div>
                    <div class="stat-hint">Last 7 days: <strong id="admin-stat-week">₱0.00</strong></div>
                </div>
            </div>

            <div class="stat-card stat-card-success">
                <div class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <div>
                    <div class="stat-label">Completed sales</div>
                    <div class="stat-value" id="admin-stat-completed">0</div>
                    <div class="stat-hint">Transactions today</div>
                </div>
            </div>

            <div class="stat-card stat-card-danger">
                <div class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>
                </div>
                <div>
                    <div class="stat-label">Voided sales</div>
                    <div class="stat-value" id="admin-stat-voided">0</div>
                    <div class="stat-hint">Cancelled today</div>
                </div>
            </div>

            <div class="stat-card stat-card-navy">
                <div class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M16 4.6a3.5 3.5 0 010 6.8"/><path d="M18.5 14.5A6.5 6.5 0 0121.5 20"/></svg>
                </div>
                <div>
                    <div class="stat-label">Active accounts</div>
                    <div class="stat-value" id="admin-stat-active-users">0</div>
                    <div class="stat-hint"><span id="admin-stat-inactive-hint">0</span> deactivated</div>
                </div>
            </div>
        </div>

        <section class="reports-section">
            <div class="section-heading-row">
                <p class="section-eyebrow">Administration</p>
            </div>

            <div class="link-cards">
                <a href="/pos/manager/users" class="link-card">
                    <span class="link-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="8" r="3.5"/><path d="M3 20a7 7 0 0114 0"/><path d="M19 8v6M16 11h6"/></svg>
                    </span>
                    <span class="link-card-text">
                        <strong>Manage Users</strong>
                        <span>Add accounts, change roles, reset passwords.</span>
                    </span>
                    <span class="link-card-arrow" aria-hidden="true">→</span>
                </a>

                <a href="/pos/manager/audit-log" class="link-card">
                    <span class="link-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v4h4"/><path d="M10 12h6M10 16h6"/></svg>
                    </span>
                    <span class="link-card-text">
                        <strong>Audit Log</strong>
                        <span>Every sale, void, refund and account event.</span>
                    </span>
                    <span class="link-card-arrow" aria-hidden="true">→</span>
                </a>

                <a href="/pos/manager" class="link-card">
                    <span class="link-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                    </span>
                    <span class="link-card-text">
                        <strong>Sales Reports</strong>
                        <span>Daily sales, cashier performance, CSV export.</span>
                    </span>
                    <span class="link-card-arrow" aria-hidden="true">→</span>
                </a>
            </div>
        </section>

        <section class="reports-section" style="padding-bottom:40px;">
            <div class="admin-columns">
                <div>
                    <div class="section-heading-row report-heading-row">
                        <p class="section-eyebrow">Recent Activity</p>
                        <a href="/pos/manager/audit-log" class="refresh-link">View all</a>
                    </div>

                    <div class="table-card">
                        <div class="table-scroll" id="admin-activity-table">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Event</th>
                                        <th>User</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody id="audit-log-table-body"></tbody>
                            </table>
                        </div>

                        <div class="empty-state" id="admin-activity-empty" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            <strong>No activity yet</strong>
                            <span>Sales, register and account events will show up here as they happen.</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="section-heading-row">
                        <p class="section-eyebrow">Team</p>
                    </div>

                    <div class="table-card admin-team">
                        <div class="admin-team-bar" role="img" aria-label="Active accounts by role">
                            <span class="seg-admin" id="admin-seg-admin"></span>
                            <span class="seg-manager" id="admin-seg-manager"></span>
                            <span class="seg-cashier" id="admin-seg-cashier"></span>
                        </div>

                        <div class="admin-team-row"><span><i class="dot seg-admin"></i>Admins</span><strong id="admin-team-admins">0</strong></div>
                        <div class="admin-team-row"><span><i class="dot seg-manager"></i>Managers</span><strong id="admin-team-managers">0</strong></div>
                        <div class="admin-team-row"><span><i class="dot seg-cashier"></i>Cashiers</span><strong id="admin-team-cashiers">0</strong></div>

                        <a href="/pos/manager/users" class="admin-team-action">Manage users</a>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <script src="/pos-assets/app.js?v={{ filemtime(public_path('pos-assets/app.js')) }}"></script>
    <script>
        Pos.initAdminPage();
    </script>

</body>
</html>
