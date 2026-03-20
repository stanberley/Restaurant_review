<?php
session_start();

if ($_SESSION['role'] === 'admin') {
    header("Location: admin-dashboard.php");
    exit();
}

if (isset($_GET['email']) && isset($_GET['name']) && isset($_GET['role'])) {

    $_SESSION['email'] = $_GET['email'];
    $_SESSION['name'] = $_GET['name'];
    $_SESSION['role'] = $_GET['role'];

    header("Location: dashboard.php");
    exit();
}

header("Location: login.php");
exit();