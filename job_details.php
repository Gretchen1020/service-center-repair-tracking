<?php
require 'includes/auth.php';
requireLogin();
require 'config/db.php';

$jobId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Allowed statuses for the repair status flow (Day 5). Kept as a PHP
// whitelist rather than a DB lookup table, per the "only 3 tables" rule.
$statuses = ['Received', 'Checking', 'Repairing', 'Ready', 'Delivered'];

// ---- Handle status update (redirect-after-POST, same pattern as customers.php/jobcard.php) ----
if ($jobId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    if (in_array($_POST['status'], $statuses, true)) {
        $stmt = $pdo->prepare('UPDATE jobs SET status = ? WHERE id = ?');
        $stmt->execute([$_POST['status'], $jobId]);
        header('Location: job_details.php?id=' . $jobId . '&updated=1');
        exit;
    }
    $statusError = 'Invalid status selected.';
}

// ---- Handle cost & payment update (Day 6) ----
$paymentErrors = [];
if ($jobId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $finalCost  = (float) ($_POST['final_cost'] ?? -1);
    $paidAmount = (float) ($_POST['paid_amount'] ?? -1);

    if ($finalCost < 0) {
        $paymentErrors[] = 'Final cost cannot be negative.';
    }
    if ($paidAmount < 0) {
        $paymentErrors[] = 'Paid amount cannot be negative.';
    }
    if ($paidAmount > $finalCost) {
        $paymentErrors[] = 'Paid amount cannot exceed final cost.';
    }

    if (empty($paymentErrors)) {
        $stmt = $pdo->prepare('UPDATE jobs SET final_cost = ?, paid_amount = ? WHERE id = ?');
        $stmt->execute([$finalCost, $paidAmount, $jobId]);
        header('Location: job_details.php?id=' . $jobId . '&paymentUpdated=1');
        exit;
    }
}

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

        <?php if (isset($_GET['updated'])): ?>
            <div class="success">Status updated successfully.</div>
        <?php endif; ?>
        <?php if (!empty($statusError)): ?>
            <div class="error"><?= htmlspecialchars($statusError) ?></div>
        <?php endif; ?>

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

        <form method="post" action="job_details.php?id=<?= (int) $jobId ?>" id="statusForm" class="status-form">
            <label for="statusSelect">Update Status</label>
            <select name="status" id="statusSelect">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= $s === $job['status'] ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Update Status</button>
        </form>

        <?php if (isset($_GET['paymentUpdated'])): ?>
            <div class="success">Payment details updated.</div>
        <?php endif; ?>
        <?php if (!empty($paymentErrors)): ?>
            <div class="error">
                <?php foreach ($paymentErrors as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="job_details.php?id=<?= (int) $jobId ?>" id="paymentForm" class="payment-form">
            <input type="hidden" name="action" value="update_payment">

            <label for="final_cost">Final Cost</label>
            <input type="number" step="0.01" min="0" name="final_cost" id="final_cost"
                   value="<?= htmlspecialchars($job['final_cost']) ?>" required>

            <label for="paid_amount">Paid Amount (total to date)</label>
            <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount"
                   value="<?= htmlspecialchars($job['paid_amount']) ?>" required>

            <p><strong>Balance: &#8377;<span id="balanceDisplay"><?= number_format((float) $balance, 2) ?></span></strong></p>

            <button type="submit">Update Payment</button>
        </form>

        <p><a href="jobs.php" class="btn">Back to Job List</a></p>
    <?php endif; ?>
</div>

<script src="assets/js/status-confirm.js"></script>
<script src="assets/js/cost-payment.js"></script>

<?php require 'includes/footer.php'; ?>