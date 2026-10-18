<?php
// User Auth Check - Include sa top ng protected user pages
function checkUserAuth() {
    if(!isset($_SESSION['user_id'])) {
        setAlert('error', 'Please login first!');
        redirect('../login.php');
    }
}

// Admin Auth Check - Include sa top ng admin pages
function checkAdminAuth() {
    if(!isset($_SESSION['admin_id'])) {
        setAlert('error', 'Admin access required!');
        redirect('login.php');
    }
}

// Check if logged in user
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin logged in
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current admin ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}
?>