<?php
// Live search endpoint for the Job List page.
// Returns raw <tr> HTML fragments (not JSON) so the same job_rows.php
// partial can be reused for both the initial page load and AJAX search.

require '../includes/auth.php';
requireLogin();
require '../config/db.php';

$term = trim($_GET['q'] ?? '');

if ($term === '') {
    $jobs = $pdo->query(
        'SELECT j.id, j.job_no, j.device_name, j.model, j.status,
                c.name AS customer_name, c.mobile AS customer_mobile
         FROM jobs j
         JOIN customers c ON c.id = j.customer_id
         ORDER BY j.id DESC'
    )->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT j.id, j.job_no, j.device_name, j.model, j.status,
                c.name AS customer_name, c.mobile AS customer_mobile
         FROM jobs j
         JOIN customers c ON c.id = j.customer_id
         WHERE j.job_no LIKE ? OR c.name LIKE ? OR c.mobile LIKE ?
         ORDER BY j.id DESC'
    );
    $like = '%' . $term . '%';
    $stmt->execute([$like, $like, $like]);
    $jobs = $stmt->fetchAll();
}

require '../includes/job_rows.php';