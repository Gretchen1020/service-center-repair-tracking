<?php
// Live search endpoint for the Customers page.
// Returns raw <tr> HTML fragments (not JSON) so the same customer_rows.php
// partial can be reused for both the initial page load and AJAX search.

require '../includes/auth.php';
requireLogin();
require '../config/db.php';

$term = trim($_GET['q'] ?? '');

if ($term === '') {
    $customers = $pdo->query('SELECT id, name, mobile, address FROM customers ORDER BY name ASC')->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT id, name, mobile, address FROM customers
         WHERE name LIKE ? OR mobile LIKE ?
         ORDER BY name ASC'
    );
    $like = '%' . $term . '%';
    $stmt->execute([$like, $like]);
    $customers = $stmt->fetchAll();
}

require '../includes/customer_rows.php';
