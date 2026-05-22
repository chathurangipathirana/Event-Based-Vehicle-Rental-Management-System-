<?php
session_start();

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdminRole() {
    requireAdminLogin();
    if ($_SESSION['admin_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}
?>