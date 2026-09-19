<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

// Total counts
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalJobs      = (int) $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

// Counts per status (whitelist order, same statuses used in status update/filter)
$statuses = ['Received', 'Checking', 'Repairing', 'Ready', 'Delivered'];
$statusCounts = array_fill_keys($statuses, 0);

$stmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM jobs GROUP BY status");
while ($row = $stmt->fetch()) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int) $row['cnt'];
    }
}

// Total pending payment across all jobs with an outstanding balance
$pendingPayment = (float) $pdo->query(
    "SELECT COALESCE(SUM(final_cost - paid_amount), 0) FROM jobs WHERE final_cost > paid_amount"
)->fetchColumn();

include 'includes/header.php';
?>

<h1>Dashboard</h1>

<div class="dashboard-cards">
    <div class="card">
        <h2><?= $totalCustomers ?></h2>
        <p>Total Customers</p>
    </div>
    <div class="card">
        <h2><?= $totalJobs ?></h2>
        <p>Total Jobs</p>
    </div>
    <?php foreach ($statuses as $s): ?>
    <div class="card status-<?= strtolower($s) ?>">
        <h2><?= $statusCounts[$s] ?></h2>
        <p><?= htmlspecialchars($s) ?></p>
    </div>
    <?php endforeach; ?>
    <div class="card highlight">
        <h2>&#8377;<?= number_format($pendingPayment, 2) ?></h2>
        <p>Pending Payment</p>
    </div>
</div>