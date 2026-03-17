<?php
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === '1';
$currentRole = isset($_GET['role']) ? $_GET['role'] : 'diner';

if (!$isAuthenticated) {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

$dinerProfile = [
    'name' => 'Sample Diner',
    'email' => 'diner@example.com',
    'password' => 'password123'
];

$restaurantProfile = [
    'restaurant_name' => 'Nasi & Co.',
    'owner_name' => 'Amir Hassan',
    'email' => 'owner@foodview.com',
    'address' => '12 Jalan Merdeka, Kuala Lumpur',
    'phone' => '+60 12-345 6789',
    'cuisine' => 'Malaysian',
    'hours' => '10:00 AM - 10:00 PM',
    'price_range' => '$$',
    'menu_image' => 'menu-sample.jpg',
    'front_image' => 'front-sample.jpg'
];

$adminProfile = [
    'name' => 'Foodview Admin',
    'email' => 'admin@foodview.com',
    'password' => 'admin123'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentRole === 'restaurant') {
        $restaurantName = trim($_POST['restaurant_name'] ?? '');
        $ownerName = trim($_POST['owner_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cuisine = trim($_POST['cuisine'] ?? '');
        $hours = trim($_POST['hours'] ?? '');
        $priceRange = trim($_POST['price_range'] ?? '');

        if ($restaurantName === '' || $ownerName === '' || $email === '' || $address === '' || $phone === '' || $cuisine === '' || $hours === '' || $priceRange === '') {
            $errorMessage = 'Please complete all restaurant fields before saving changes.';
        } else {
            $restaurantProfile = [
                'restaurant_name' => $restaurantName,
                'owner_name' => $ownerName,
                'email' => $email,
                'address' => $address,
                'phone' => $phone,
                'cuisine' => $cuisine,
                'hours' => $hours,
                'price_range' => $priceRange,
                'menu_image' => trim($_POST['menu_image'] ?? ''),
                'front_image' => trim($_POST['front_image'] ?? '')
            ];
            $successMessage = 'Restaurant information updated successfully.';
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $errorMessage = 'Please complete all profile fields before saving changes.';
        } else {
            if ($currentRole === 'admin') {
                $adminProfile = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password
                ];
            } else {
                $dinerProfile = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password
                ];
            }
            $successMessage = 'Profile updated successfully.';
        }
    }
}

$pageTitle = 'Edit Profile';
$pageDescription = 'Update your account details below.';

if ($currentRole === 'restaurant') {
    $pageTitle = 'Edit Restaurant Details';
    $pageDescription = 'Update your restaurant details, including business information, contact details, menu image references, and front image references.';
} elseif ($currentRole === 'admin') {
    $pageTitle = 'Edit Admin Profile';
    $pageDescription = 'Update your administrator account information.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - <?php echo htmlspecialchars($pageTitle); ?></title>
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
                            <h1 class="h3 mb-3"><?php echo htmlspecialchars($pageTitle); ?></h1>
                            <?php if ($currentRole === 'restaurant'): ?>
                                <div class="alert alert-info">This page is for editing your restaurant details, not a separate owner personal profile.</div>
                            <?php endif; ?>
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($pageDescription); ?></p>

                            <?php if ($errorMessage !== ''): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($successMessage !== ''): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                            <?php endif; ?>

                            <form method="post" action="edit-profile.php?auth=1&role=<?php echo urlencode($currentRole); ?>">
                                <?php if ($currentRole === 'restaurant'): ?>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="restaurant_name" class="form-label">Restaurant Name</label>
                                            <input type="text" class="form-control" id="restaurant_name" name="restaurant_name" value="<?php echo htmlspecialchars($restaurantProfile['restaurant_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="owner_name" class="form-label">Restaurant Owner Name</label>
                                            <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($restaurantProfile['owner_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($restaurantProfile['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($restaurantProfile['phone']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($restaurantProfile['address']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cuisine" class="form-label">Cuisine Type</label>
                                            <input type="text" class="form-control" id="cuisine" name="cuisine" value="<?php echo htmlspecialchars($restaurantProfile['cuisine']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="hours" class="form-label">Opening Hours</label>
                                            <input type="text" class="form-control" id="hours" name="hours" value="<?php echo htmlspecialchars($restaurantProfile['hours']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="price_range" class="form-label">Price Range</label>
                                            <input type="text" class="form-control" id="price_range" name="price_range" value="<?php echo htmlspecialchars($restaurantProfile['price_range']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="menu_image" class="form-label">Menu Image Reference</label>
                                            <input type="text" class="form-control" id="menu_image" name="menu_image" value="<?php echo htmlspecialchars($restaurantProfile['menu_image']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="front_image" class="form-label">Restaurant Front Image</label>
                                            <input type="text" class="form-control" id="front_image" name="front_image" value="<?php echo htmlspecialchars($restaurantProfile['front_image']); ?>">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php $profileData = $currentRole === 'admin' ? $adminProfile : $dinerProfile; ?>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($profileData['name']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profileData['email']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($profileData['password']); ?>" required>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-3 flex-wrap mt-4">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <?php if ($currentRole === 'admin'): ?>
                                        <a href="moderation.php?auth=1&role=admin" class="btn btn-outline-secondary">Go to Moderation</a>
                                    <?php endif; ?>
                                    <a href="dashboard.php?auth=1&role=<?php echo urlencode($currentRole); ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
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
