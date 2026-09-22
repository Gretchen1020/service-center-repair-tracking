<?php
// Session helpers - include this at the top of every protected page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Root-relative base path for this app (used for redirects so they work the
// same whether requireLogin() is called from the site root, e.g. customers.php,
// or from a subfolder, e.g. ajax/search_customers.php).
// Worked out from where this folder sits under the web server's document root,
// so the project folder can be named anything (service_center, repo name, ...).
// Falls back to '/service_center' if the folder is outside the document root
// (e.g. symlinked or served through an alias).
$appDir  = realpath(__DIR__ . '/..');
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appBase = '/service_center';
if ($appDir !== false && $docRoot !== false) {
    $appDir  = str_replace('\\', '/', $appDir);
    $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
    if (strcasecmp($appDir, $docRoot) === 0) {
        $appBase = '';                       // app is the document root itself
    } elseif (stripos($appDir, $docRoot . '/') === 0) {
        $appBase = substr($appDir, strlen($docRoot));
    }
}
define('APP_BASE', $appBase);
unset($appDir, $docRoot, $appBase);

/**
 * Redirects to login.php if there is no logged-in admin in the session.
 * Call this at the top of any page that requires login.
 */
function requireLogin() {
    // Prevent the browser from serving a cached copy of this page after
    // logout (e.g. via the Back button) instead of re-checking the session.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_BASE . '/login.php');
        exit;
    }
}


//True if an admin is currently logged in.
function isLoggedIn() {
    return !empty($_SESSION['admin_id']);
}