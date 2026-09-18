<?php
// Live search endpoint for the Job List page.
// Returns raw <tr> HTML fragments (not JSON) so the same job_rows.php
// partial can be reused for both the initial page load and AJAX search.

require '../includes/auth.php';
requireLogin();
require '../config/db.php';

// Same whitelist used by jobs.php and job_details.php.
$statuses = ['Received', 'Checking', 'Repairing', 'Ready', 'Delivered'];

$term = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
if (!in_array($statusFilter, $statuses, true)) {
    $statusFilter = '';
}

$where  = [];
$params = [];

if ($term !== '') {
    $where[] = '(j.job_no LIKE ? OR c.name LIKE ? OR c.mobile LIKE ?)';
    $like = '%' . $term . '%';
    array_push($params, $like, $like, $like);
}

if ($statusFilter !== '') {
    $where[] = 'j.status = ?';
    $params[] = $statusFilter;
}

$sql = 'SELECT j.id, j.job_no, j.device_name, j.model, j.status,
               c.name AS customer_name, c.mobile AS customer_mobile
        FROM jobs j
        JOIN customers c ON c.id = j.customer_id';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY j.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

require '../includes/job_rows.php';