<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define constants for role IDs
define('ROLE_ADMIN', 1); // Adjust if your admin role ID is different

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if the current page is the login page
function isLoginPage() {
    return basename($_SERVER['PHP_SELF']) === 'index.php';
}

// Function to check if the user is an admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN;
}

// Function to redirect to login page if not logged in
function checkLogin() {
    if (!isLoggedIn() && !isLoginPage()) {
        header("Location: index.php");
        exit();
    }
}

// Function to check if user is admin, redirect if not
function checkAdmin() {
    // First check if logged in
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
    
    // Then check if admin
    if (!isAdmin()) {
        // Create a non-admin page to redirect to instead of dashboard
        // This breaks the redirect loop
        header("Location: staff_dashboard.php"); // You'll need to create this page
        exit();
    }
}

// Only run automatic login check if this file is included in a non-login page
if (!isLoginPage()) {
    checkLogin();
}
?>