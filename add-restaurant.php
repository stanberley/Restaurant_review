<?php
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === '1';
$currentRole = isset($_GET['role']) ? $_GET['role'] : 'guest';

if (!$isAuthenticated || $currentRole !== 'restaurant') {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requiredFields = [
        'restaurant_name' => 'restaurant name',
        'owner_name' => 'owner name',
        'address' => 'address',
        'phone' => 'phone number',
        'cuisine' => 'type of cuisine',
        'hours' => 'opening hours',
        'price_range' => 'price range',
        'front_image' => 'restaurant front image',
        'menu_images' => 'menu images'
    ];

    foreach ($requiredFields as $field => $label) {
        if (trim($_POST[$field] ?? '') === '') {
            $errorMessage = 'Please enter the ' . $label . ' before adding a restaurant.';
            break;
        }
    }

    if ($errorMessage === '') {
        $successMessage = 'Your additional restaurant has been added successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Add Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                <div>
                                    <h1 class="h3 mb-2">Add Restaurant</h1>
                                    <p class="text-muted mb-0">Create an additional restaurant listing under your restaurant-owner account.</p>
                                </div>
                                <a href="dashboard.php?auth=1&role=restaurant" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>

                            <?php if ($errorMessage !== ''): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($successMessage !== ''): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                            <?php endif; ?>

                            <form method="post" action="add-restaurant.php?auth=1&role=restaurant">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="restaurant_name" class="form-label">Restaurant Name</label>
                                        <input type="text" class="form-control" id="restaurant_name" name="restaurant_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="owner_name" class="form-label">Owner Name</label>
                                        <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" name="phone" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cuisine" class="form-label">Type of Cuisine</label>
                                        <input type="text" class="form-control" id="cuisine" name="cuisine" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="hours" class="form-label">Opening Hours</label>
                                        <input type="text" class="form-control" id="hours" name="hours" placeholder="e.g. 10:00 AM - 10:00 PM" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="price_range" class="form-label">Price Range</label>
                                        <select class="form-select" id="price_range" name="price_range" required>
                                            <option value="">Select a price range</option>
                                            <option value="$">$</option>
                                            <option value="$$">$$</option>
                                            <option value="$$$">$$$</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="front_image" class="form-label">Restaurant Front Image URL</label>
                                        <input type="url" class="form-control" id="front_image" name="front_image" placeholder="https://" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="menu_images" class="form-label">Menu Image URLs</label>
                                        <textarea class="form-control" id="menu_images" name="menu_images" rows="3" placeholder="Enter one or more menu image URLs, separated by commas" required></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex gap-3 flex-wrap">
                                    <button type="submit" class="btn btn-primary">Add Restaurant</button>
                                    <a href="edit-profile.php?auth=1&role=restaurant" class="btn btn-outline-secondary">Edit Existing Restaurant</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include('includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
