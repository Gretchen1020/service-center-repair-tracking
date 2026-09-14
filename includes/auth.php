<?php
// Session helpers - include this at the top of every protected page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirects to login.php if there is no logged-in admin in the session.
 * Call this at the top of any page that requires login.
 */
function requireLogin() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * True if an admin is currently logged in.
 */
function isLoggedIn() {
    return !empty($_SESSION['admin_id']);
}
