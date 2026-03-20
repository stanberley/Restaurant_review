<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['name'] ?? 'Admin';

$totalUsers = (isset($_SESSION['users']) && is_array($_SESSION['users'])) ? count($_SESSION['users']) : 128;
$totalRestaurants = (isset($_SESSION['restaurants']) && is_array($_SESSION['restaurants'])) ? count($_SESSION['restaurants']) : 42;
$flaggedReviews = (isset($_SESSION['flagged_reviews']) && is_array($_SESSION['flagged_reviews'])) ? count($_SESSION['flagged_reviews']) : 9;
$activeSessions = 18;

$statCards = [
    [
        'title' => 'Total Users',
        'value' => $totalUsers,
        'icon'  => 'bi-people-fill',
        'desc'  => 'Registered platform users'
    ],
    [
        'title' => 'Restaurants',
        'value' => $totalRestaurants,
        'icon'  => 'bi-shop',
        'desc'  => 'Business listings managed'
    ],
    [
        'title' => 'Flagged Reviews',
        'value' => $flaggedReviews,
        'icon'  => 'bi-flag-fill',
        'desc'  => 'Items needing moderation'
    ],
    [
        'title' => 'Active Sessions',
        'value' => $activeSessions,
        'icon'  => 'bi-shield-lock-fill',
        'desc'  => 'Currently active logins'
    ]
];

$quickActions = [
    [
        'title' => 'Moderation Panel',
        'desc'  => 'View all users and restaurants, then remove items when necessary.',
        'icon'  => 'bi-kanban-fill',
        'link'  => 'moderation.php',
        'button'=> 'Open Moderation',
        'class' => 'primary'
    ],
    [
        'title' => 'Edit Admin Profile',
        'desc'  => 'Update admin information, profile details, and account preferences.',
        'icon'  => 'bi-person-circle',
        'link'  => 'edit-profile.php',
        'button'=> 'Edit Profile',
        'class' => 'dark'
    ],
    [
        'title' => 'Review Homepage',
        'desc'  => 'Check the public-facing restaurant experience from the user perspective.',
        'icon'  => 'bi-window-stack',
        'link'  => 'index.php',
        'button'=> 'Open Homepage',
        'class' => 'light'
    ],
    [
        'title' => 'Log Out',
        'desc'  => 'End the current admin session securely.',
        'icon'  => 'bi-box-arrow-right',
        'link'  => 'logout.php',
        'button'=> 'Log Out',
        'class' => 'danger'
    ]
];

$recentActivities = [
    [
        'title' => 'New restaurant submitted',
        'meta'  => 'Pasta House • 12 minutes ago',
        'status'=> 'Pending Review'
    ],
    [
        'title' => 'User account removed',
        'meta'  => 'Action by admin • 45 minutes ago',
        'status'=> 'Completed'
    ],
    [
        'title' => 'Review flagged by community',
        'meta'  => 'Tokyo Flame review • 1 hour ago',
        'status'=> 'Needs Attention'
    ],
    [
        'title' => 'Restaurant profile updated',
        'meta'  => 'Nasi & Co. • 2 hours ago',
        'status'=> 'Synced'
    ]
];

$systemStatus = [
    [
        'label' => 'Authentication',
        'value' => 'Operational',
        'good'  => true
    ],
    [
        'label' => 'Moderation Queue',
        'value' => '9 items open',
        'good'  => false
    ],
    [
        'label' => 'User Sessions',
        'value' => 'Stable',
        'good'  => true
    ],
    [
        'label' => 'Platform Visibility',
        'value' => 'Public pages live',
        'good'  => true
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Admin Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-dashboard-page">

<?php include("includes/header.php"); ?>

<div class="container py-5">
    <section class="admin-hero mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="admin-badge mb-3 d-inline-flex align-items-center">
                    <i class="bi bi-shield-check me-2"></i> Admin Control Center
                </span>
                <h1 class="admin-hero-title mb-2">
                    Welcome back, <?php echo htmlspecialchars($adminName); ?>
                </h1>
                <p class="admin-hero-text mb-0">
                    Manage users, restaurants, reviews, and platform activity from one clean dashboard.
                </p>
            </div>

            <div class="col-lg-4">
                <div class="admin-hero-actions">
                    <a href="moderation.php" class="btn btn-admin-primary w-100 mb-2">
                        <i class="bi bi-kanban-fill me-2"></i> Go to Moderation
                    </a>
                    <a href="edit-profile.php" class="btn btn-admin-outline w-100">
                        <i class="bi bi-pencil-square me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-4">
            <?php foreach ($statCards as $card): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card h-100">
                        <div class="admin-stat-icon">
                            <i class="bi <?php echo htmlspecialchars($card['icon']); ?>"></i>
                        </div>
                        <div>
                            <p class="admin-stat-label mb-1"><?php echo htmlspecialchars($card['title']); ?></p>
                            <h3 class="admin-stat-value mb-1"><?php echo htmlspecialchars((string)$card['value']); ?></h3>
                            <p class="admin-stat-desc mb-0"><?php echo htmlspecialchars($card['desc']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="admin-panel mb-4">
                <div class="admin-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="admin-section-title mb-1">Quick Actions</h2>
                        <p class="admin-section-subtitle mb-0">Fast access to the most important admin tasks.</p>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <?php foreach ($quickActions as $action): ?>
                        <div class="col-md-6">
                            <div class="admin-action-card h-100">
                                <div class="admin-action-icon <?php echo htmlspecialchars($action['class']); ?>">
                                    <i class="bi <?php echo htmlspecialchars($action['icon']); ?>"></i>
                                </div>
                                <h3 class="admin-action-title"><?php echo htmlspecialchars($action['title']); ?></h3>
                                <p class="admin-action-text"><?php echo htmlspecialchars($action['desc']); ?></p>
                                <a href="<?php echo htmlspecialchars($action['link']); ?>" class="btn btn-action mt-auto">
                                    <?php echo htmlspecialchars($action['button']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel mb-4">
                <div class="admin-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="admin-section-title mb-1">Management Overview</h2>
                        <p class="admin-section-subtitle mb-0">Keep the platform clean, safe, and organized.</p>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="admin-mini-panel h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mini-panel-icon me-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <h3 class="h5 mb-1">User Management</h3>
                                    <p class="text-muted mb-0">Review accounts and remove invalid users.</p>
                                </div>
                            </div>
                            <ul class="admin-check-list">
                                <li>Display all users</li>
                                <li>Delete users when needed</li>
                                <li>Monitor suspicious activity</li>
                            </ul>
                            <a href="moderation.php" class="btn btn-sm btn-admin-outline">Manage Users</a>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="admin-mini-panel h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mini-panel-icon me-3">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <h3 class="h5 mb-1">Restaurant Management</h3>
                                    <p class="text-muted mb-0">Check listings and remove invalid restaurants.</p>
                                </div>
                            </div>
                            <ul class="admin-check-list">
                                <li>Display all restaurant listings</li>
                                <li>Delete inappropriate entries</li>
                                <li>Review listing quality</li>
                            </ul>
                            <a href="moderation.php" class="btn btn-sm btn-admin-outline">Manage Restaurants</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="admin-section-title mb-1">Recent Activity</h2>
                        <p class="admin-section-subtitle mb-0">A simple view of the latest admin-relevant actions.</p>
                    </div>
                </div>

                <div class="activity-list mt-3">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-dot"></div>
                            <div class="activity-content">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h3 class="activity-title mb-1"><?php echo htmlspecialchars($activity['title']); ?></h3>
                                        <p class="activity-meta mb-0"><?php echo htmlspecialchars($activity['meta']); ?></p>
                                    </div>
                                    <span class="activity-badge"><?php echo htmlspecialchars($activity['status']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="admin-side-card mb-4">
                <div class="admin-profile-block text-center">
                    <div class="admin-avatar mx-auto mb-3">
                        <i class="bi bi-person-fill-gear"></i>
                    </div>
                    <h2 class="h4 mb-1"><?php echo htmlspecialchars($adminName); ?></h2>
                    <p class="text-muted mb-3">Platform Administrator</p>

                    <div class="d-grid gap-2">
                        <a href="edit-profile.php" class="btn btn-admin-primary">
                            <i class="bi bi-pencil-square me-2"></i> Edit Profile
                        </a>
                        <a href="logout.php" class="btn btn-danger-subtle-custom">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-side-card mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">System Status</h2>
                    <i class="bi bi-cpu"></i>
                </div>

                <?php foreach ($systemStatus as $item): ?>
                    <div class="status-row">
                        <div>
                            <p class="status-label mb-0"><?php echo htmlspecialchars($item['label']); ?></p>
                        </div>
                        <span class="status-pill <?php echo $item['good'] ? 'good' : 'warn'; ?>">
                            <?php echo htmlspecialchars($item['value']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="admin-side-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">Admin Notes</h2>
                    <i class="bi bi-lightbulb"></i>
                </div>

                <div class="admin-note-box">
                    <h3 class="h6 mb-2">Recommended workflow</h3>
                    <p class="mb-3 text-muted">
                        Start with moderation, clear flagged items, then check public-facing pages before logging out.
                    </p>

                    <div class="admin-note-item">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Review users first
                    </div>
                    <div class="admin-note-item">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Verify restaurant listings
                    </div>
                    <div class="admin-note-item">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Confirm homepage looks clean
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>