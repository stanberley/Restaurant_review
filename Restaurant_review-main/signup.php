<?php
$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === '1';
$currentRole = isset($_GET['role']) ? $_GET['role'] : 'guest';

$name = '';
$email = '';
$userType = 'diner';
$pwd_hashed = '';
$errorMsg = '';
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $userType = isset($_POST['userType']) ? trim($_POST['userType']) : 'diner';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

    if ($name === '' || $email === '') {
        $errorMsg = 'Please provide your name and email.';
        $success = false;
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
        $success = false;
    }
    else if (!in_array($userType, ['diner', 'restaurant'], true)) {
        $errorMsg = 'Invalid user type selected.';
        $success = false;
    }
    else if ($password === '' || $confirmPassword === '') {
        $errorMsg = 'Please enter your password and confirmation password.';
        $success = false;
    }
    else if (strlen($password) < 6) {
        $errorMsg = 'Your password must be at least 6 characters long.';
        $success = false;
    }
    else if ($password !== $confirmPassword) {
        $errorMsg = 'Password and confirmation password do not match.';
        $success = false;
    }

    if ($success) {
        $pwd_hashed = password_hash($password, PASSWORD_DEFAULT);
        saveUserToDB();

        if ($success) {
            header('Location: login-handler.php?role=' . urlencode($userType) . '&email=' . urlencode($email) . '&name=' . urlencode($name));
            exit();
        }
    }
}

/*
* Helper function to write the user data to the database.
*/
function saveUserToDB()
{
    global $name, $email, $userType, $pwd_hashed, $errorMsg, $success;

    if (!class_exists('mysqli')) {
        $errorMsg = 'MySQLi extension is not available on the server.';
        $success = false;
        return;
    }

    // Create database connection
    $config = @parse_ini_file('/var/www/private/db-config.ini');

    if (!$config) {
        $errorMsg = "Failed to read database config file.";
        $success = false;
    }
    else {

        if (!isset($config['servername'], $config['username'], $config['password'], $config['dbname'])) {
            $errorMsg = 'Database config file is missing one or more required keys.';
            $success = false;
            return;
        }

        try {
            $conn = new mysqli(
                $config['servername'],
                $config['username'],
                $config['password'],
                $config['dbname']
            );

            // Check connection
            if ($conn->connect_error) {
                $errorMsg = "Connection failed: " . $conn->connect_error;
                $success = false;
            }
            else {

                // Prepare SQL statement
                $stmt = $conn->prepare(
                    "INSERT INTO users (email, userType, password, name)
                     VALUES (?, ?, ?, ?)"
                );

                if (!$stmt) {
                    $errorMsg = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                    $success = false;
                }
                else {

                    // Bind parameters
                    $stmt->bind_param("ssss", $email, $userType, $pwd_hashed, $name);

                    // Execute
                    if (!$stmt->execute()) {
                        $errorMsg = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                        $success = false;
                    }

                    $stmt->close();
                }
            }

            $conn->close();
        }
        catch (Throwable $e) {
            $errorMsg = 'Database operation failed: ' . $e->getMessage();
            $success = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4 text-center">
                    <h2>Create Your Account</h2>
                    <p class="text-muted mb-0">Start by choosing your account type, then continue to complete the rest of your registration details.</p>
                </div>

                <div id="signupError" class="alert alert-danger<?php echo $errorMsg !== '' ? '' : ' d-none'; ?>"><?php echo htmlspecialchars($errorMsg); ?></div>
                <div id="signupSuccess" class="alert alert-success d-none"></div>

                <form id="signupForm" class="shadow-sm rounded bg-white p-4" method="post" action="signup.php">
                    <div id="stepOneSection">
                        <h4 class="mb-3">Step 1: Basic Account Setup</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label d-block mb-2">User Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="userType" id="userTypeDiner" value="diner" <?php echo $userType === 'diner' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="userTypeDiner">Diner</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="userType" id="userTypeRestaurant" value="restaurant" <?php echo $userType === 'restaurant' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="userTypeRestaurant">Restaurant Owner</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="signupName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="signupName" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="signupEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="signupEmail" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-primary" id="continueToStepTwo">Continue</button>
                        </div>
                    </div>

                    <div id="stepTwoSection" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Step 2: Complete Your Account</h4>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="backToStepOne">Back</button>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="signupPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="signupPassword" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="signupConfirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="signupConfirmPassword" name="confirmPassword" required>
                            </div>
                        </div>

                        <div id="dinerFields">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Diner ID</label>
                                    <input type="text" class="form-control" value="Assigned automatically after account creation" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Registered Name</label>
                                    <input type="text" class="form-control" id="dinerNameMirror" disabled>
                                </div>
                            </div>
                        </div>

                        <div id="restaurantFields" class="d-none">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="restIdPreview" class="form-label">Restaurant ID</label>
                                    <input type="text" class="form-control" id="restIdPreview" value="Assigned automatically after account creation" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantName" class="form-label">Restaurant Name</label>
                                    <input type="text" class="form-control" id="restaurantName">
                                </div>
                                <div class="col-md-6">
                                    <label for="ownerName" class="form-label">Owner Name</label>
                                    <input type="text" class="form-control" id="ownerName">
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantPhone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="restaurantPhone">
                                </div>
                                <div class="col-md-12">
                                    <label for="restaurantAddress" class="form-label">Address</label>
                                    <textarea class="form-control" id="restaurantAddress" rows="3"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantCuisine" class="form-label">Type of Cuisine</label>
                                    <input type="text" class="form-control" id="restaurantCuisine">
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantHours" class="form-label">Opening Hours</label>
                                    <input type="text" class="form-control" id="restaurantHours" placeholder="e.g. 10:00 AM - 10:00 PM">
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantPriceRange" class="form-label">Price Range</label>
                                    <select class="form-select" id="restaurantPriceRange">
                                        <option value="">Select a price range</option>
                                        <option value="$">$</option>
                                        <option value="$$">$$</option>
                                        <option value="$$$">$$$</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantFrontImage" class="form-label">Restaurant Front Image URL</label>
                                    <input type="url" class="form-control" id="restaurantFrontImage" placeholder="https://">
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantMenuImages" class="form-label">Menu Image URLs</label>
                                    <input type="text" class="form-control" id="restaurantMenuImages" placeholder="Comma-separated URLs">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Your account type, name, and email from step one will be used for this registration.</span>
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const signupForm = document.getElementById('signupForm');
        const stepOneSection = document.getElementById('stepOneSection');
        const stepTwoSection = document.getElementById('stepTwoSection');
        const dinerFields = document.getElementById('dinerFields');
        const restaurantFields = document.getElementById('restaurantFields');
        const signupError = document.getElementById('signupError');
        const signupSuccess = document.getElementById('signupSuccess');
        const dinerNameMirror = document.getElementById('dinerNameMirror');

        function showSignupError(message) {
            signupSuccess.classList.add('d-none');
            signupError.textContent = message;
            signupError.classList.remove('d-none');
        }

        function hideSignupMessages() {
            signupError.classList.add('d-none');
            signupSuccess.classList.add('d-none');
        }

        function getSelectedUserType() {
            return document.querySelector('input[name="userType"]:checked').value;
        }

        function updateStepTwoFields() {
            const selectedType = getSelectedUserType();
            dinerFields.classList.toggle('d-none', selectedType !== 'diner');
            restaurantFields.classList.toggle('d-none', selectedType !== 'restaurant');
            dinerNameMirror.value = document.getElementById('signupName').value.trim();
            if (selectedType === 'restaurant') {
                document.getElementById('ownerName').value = document.getElementById('signupName').value.trim();
            }
        }

        document.getElementById('continueToStepTwo').addEventListener('click', function () {
            hideSignupMessages();

            const name = document.getElementById('signupName').value.trim();
            const email = document.getElementById('signupEmail').value.trim();

            if (!name || !email) {
                showSignupError('Please choose a user type and complete your name and email before continuing.');
                return;
            }

            updateStepTwoFields();
            stepOneSection.classList.add('d-none');
            stepTwoSection.classList.remove('d-none');
        });

        document.getElementById('backToStepOne').addEventListener('click', function () {
            hideSignupMessages();
            stepTwoSection.classList.add('d-none');
            stepOneSection.classList.remove('d-none');
        });

        document.querySelectorAll('input[name="userType"]').forEach(function (radio) {
            radio.addEventListener('change', updateStepTwoFields);
        });

        signupForm.addEventListener('submit', function (event) {
            hideSignupMessages();

            const selectedType = getSelectedUserType();
            const email = document.getElementById('signupEmail').value.trim();
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;

            if (!password || !confirmPassword) {
                event.preventDefault();
                showSignupError('Please enter your password and confirmation password.');
                return;
            }

            if (password.length < 6) {
                event.preventDefault();
                showSignupError('Your password must be at least 6 characters long.');
                return;
            }

            if (password !== confirmPassword) {
                event.preventDefault();
                showSignupError('Password and confirmation password do not match.');
                return;
            }

            if (selectedType === 'restaurant') {
                const requiredRestaurantFields = [
                    { id: 'restaurantName', label: 'restaurant name' },
                    { id: 'ownerName', label: 'owner name' },
                    { id: 'restaurantAddress', label: 'address' },
                    { id: 'restaurantPhone', label: 'phone number' },
                    { id: 'restaurantCuisine', label: 'type of cuisine' },
                    { id: 'restaurantHours', label: 'opening hours' },
                    { id: 'restaurantPriceRange', label: 'price range' },
                    { id: 'restaurantFrontImage', label: 'restaurant front image URL' },
                    { id: 'restaurantMenuImages', label: 'menu image URLs' }
                ];

                for (const field of requiredRestaurantFields) {
                    const value = document.getElementById(field.id).value.trim();
                    if (!value) {
                        event.preventDefault();
                        showSignupError('Please enter the ' + field.label + ' before creating a restaurant account.');
                        return;
                    }
                }
            }
        });
    </script>
</body>
</html>