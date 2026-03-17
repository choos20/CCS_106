<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if not logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../ALL_PHP/index copy.php");
        exit;
    }
}

// Check if current user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Optional helper to force admin-only pages
function requireAdmin() {
    if (!isAdmin()) {
        die("Unauthorized: Admins only.");
    }
}
?>
