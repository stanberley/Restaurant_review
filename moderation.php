<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';

if (!$isAuthenticated || $currentRole !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/restaurant-db.php';

$actionMessage = '';
$dbErr = '';
$dbConn = getDatabaseConnection($dbErr);

$users = [];
$restaurants = [];

if ($dbConn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string) ($_POST['moderation_action'] ?? ''));

        if ($action === 'delete_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $currentAdminId = (int) ($_SESSION['user_id'] ?? 0);

            if ($userId < 1) {
                $dbErr = 'Invalid user selected for deletion.';
            } elseif ($currentAdminId > 0 && $userId === $currentAdminId) {
                $dbErr = 'You cannot delete the admin account currently in use.';
            } else {
                if (deleteUserRecordById($dbConn, $userId, $dbErr)) {
                    $actionMessage = 'User #' . $userId . ' was deleted successfully.';
                }
            }
        } elseif ($action === 'delete_restaurant') {
            $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
            if ($restaurantId < 1) {
                $dbErr = 'Invalid restaurant selected for deletion.';
            } else {
                if (deleteRestaurantRecordById($dbConn, $restaurantId, $dbErr)) {
                    $actionMessage = 'Restaurant #' . $restaurantId . ' was deleted successfully.';
                }
            }
        }
    }

    // Fetch all users
    $uResult = $dbConn->query(
        'SELECT idusers, name, email, userType FROM users ORDER BY idusers ASC'
    );
    if ($uResult) {
        while ($row = $uResult->fetch_assoc()) {
            $users[] = $row;
        }
        $uResult->free();
    }

    // Fetch all restaurants with owner name
    $rResult = $dbConn->query(
        'SELECT r.idRestaurants, r.RestaurantName, r.CusineType, r.PriceRange,
                u.name AS ownerName
         FROM   Restaurants r
         LEFT   JOIN users u ON u.idusers = r.OwnerId
         ORDER  BY r.idRestaurants ASC'
    );
    if ($rResult) {
        while ($row = $rResult->fetch_assoc()) {
            $restaurants[] = $row;
        }
        $rResult->free();
    }

    $dbConn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Moderation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <main class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-2">Admin Moderation</h1>
                    <p class="text-muted mb-0">Review user and restaurant records, then use the delete controls to moderate the platform interface.</p>
                </div>
                <a href="admin-dashboard.php" class="btn btn-outline-secondary">Back to Admin Dashboard</a>
            </div>

            <?php if ($dbErr !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($dbErr); ?></div>
            <?php endif; ?>

            <?php if ($actionMessage !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($actionMessage); ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">All Users (<?php echo count($users); ?>)</h2>
                            <?php if (empty($users)): ?>
                                <p class="text-muted">No users found in the database.</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo (int) $user['idusers']; ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['userType']); ?></td>
                                                <td class="text-end">
                                                    <form method="post" action="moderation.php" class="d-inline"
                                                          onsubmit="return confirm('Delete this user and related records? This cannot be undone.');">
                                                        <input type="hidden" name="moderation_action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['idusers']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">All Restaurants (<?php echo count($restaurants); ?>)</h2>
                            <?php if (empty($restaurants)): ?>
                                <p class="text-muted">No restaurants found in the database.</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Cuisine</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($restaurants as $restaurant): ?>
                                            <tr>
                                                <td><?php echo (int) $restaurant['idRestaurants']; ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($restaurant['RestaurantName']); ?></div>
                                                    <div class="text-muted small">Owner: <?php echo htmlspecialchars($restaurant['ownerName'] ?? 'Unknown'); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($restaurant['CusineType']); ?> &middot; <?php echo htmlspecialchars($restaurant['PriceRange']); ?></td>
                                                <td class="text-end">
                                                    <form method="post" action="moderation.php" class="d-inline"
                                                          onsubmit="return confirm('Delete this restaurant and all related reviews/images? This cannot be undone.');">
                                                        <input type="hidden" name="moderation_action" value="delete_restaurant">
                                                        <input type="hidden" name="restaurant_id" value="<?php echo (int) $restaurant['idRestaurants']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include('includes/footer.php'); ?>
</body>
</html>
