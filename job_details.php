<?php
require 'includes/auth.php';
requireLogin();
require 'config/db.php';

$jobId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$job   = null;
$error = '';

if ($jobId > 0) {
    $stmt = $pdo->prepare(
        'SELECT j.id, j.job_no, j.device_name, j.model, j.complaint, j.technician,
                j.estimate, j.final_cost, j.paid_amount, j.status,
                c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address
         FROM jobs j
         JOIN customers c ON c.id = j.customer_id
         WHERE j.id = ?'
    );
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
}

if (!$job) {
    $error = 'Job not found.';
}

// Balance is derived in PHP, never stored (per project rules).
$balance = $job ? ($job['final_cost'] - $job['paid_amount']) : 0;

require 'includes/header.php';
?>

<div class="card">
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <a href="jobs.php" class="btn">Back to Job List</a>
    <?php else: ?>
        <h2>Job Card - <?= htmlspecialchars($job['job_no']) ?></h2>

        <table class="data-table">
            <tbody>
                <tr><th>Job No</th><td><?= htmlspecialchars($job['job_no']) ?></td></tr>
                <tr><th>Status</th><td><?= htmlspecialchars($job['status']) ?></td></tr>
                <tr><th>Customer</th><td><?= htmlspecialchars($job['customer_name']) ?></td></tr>
                <tr><th>Mobile</th><td><?= htmlspecialchars($job['customer_mobile']) ?></td></tr>
                <tr><th>Address</th><td><?= htmlspecialchars($job['customer_address'] ?? '') ?></td></tr>
                <tr><th>Device</th><td><?= htmlspecialchars($job['device_name']) ?></td></tr>
                <tr><th>Model</th><td><?= htmlspecialchars($job['model'] ?? '') ?></td></tr>
                <tr><th>Complaint</th><td><?= nl2br(htmlspecialchars($job['complaint'] ?? '')) ?></td></tr>
                <tr><th>Technician</th><td><?= htmlspecialchars($job['technician'] ?? '') ?></td></tr>
                <tr><th>Estimate</th><td>&#8377;<?= number_format((float) $job['estimate'], 2) ?></td></tr>
                <tr><th>Final Cost</th><td>&#8377;<?= number_format((float) $job['final_cost'], 2) ?></td></tr>
                <tr><th>Paid Amount</th><td>&#8377;<?= number_format((float) $job['paid_amount'], 2) ?></td></tr>
                <tr><th>Balance</th><td>&#8377;<?= number_format((float) $balance, 2) ?></td></tr>
            </tbody>
        </table>

        <p><a href="jobs.php" class="btn">Back to Job List</a></p>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>