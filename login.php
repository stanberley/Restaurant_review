<?php
$isAuthenticated = isset($_SESSION['role']);
$currentRole =  $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="text-center mb-4">
                    <h2>Login</h2>
                    <p class="text-muted mb-0">Sign in to access your dashboard, search tools, and role-based features.</p>
                </div>

                <div id="loginError" class="alert alert-danger d-none"></div>

                <form id="loginForm" class="shadow-sm rounded bg-white p-4">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Email</label>
                        <input type="email" id="loginEmail" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label for="loginPassword" class="form-label">Password</label>
                        <input type="password" id="loginPassword" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                    <p class="text-center text-muted small mb-0">Role is determined automatically based on your account.</p>
                </form>
            </div>
        </div>
    </div>
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginError = document.getElementById('loginError');

        function showLoginError(message) {
            loginError.textContent = message;
            loginError.classList.remove('d-none');
        }

        function hideLoginError() {
            loginError.classList.add('d-none');
        }

        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();
            hideLoginError();

            const email = document.getElementById('loginEmail').value.trim().toLowerCase();
            const password = document.getElementById('loginPassword').value;

            if (!email || !password) {
                showLoginError('Please enter your email and password before logging in.');
                return;
            }

            if (password.length < 6) {
                showLoginError('Password must contain at least 6 characters.');
                return;
            }

            let mappedRole = 'diner';
            if (email === 'admin' || email === 'admin@foodview.com') {
                mappedRole = 'admin';
            } else if (email.includes('owner') || email.includes('restaurant')) {
                mappedRole = 'restaurant';
            }

            //TO BE CHANGED ONCE BACKEND IS IMPLEMENTED
            const name = email.split('@')[0];

            window.location.href =
            'login-handler.php?role=' + encodeURIComponent(mappedRole) +
            '&email=' + encodeURIComponent(email) +
            '&name=' + encodeURIComponent(name);
            });
    </script>
    <?php include("includes/footer.php"); ?>

</body>
</html>
