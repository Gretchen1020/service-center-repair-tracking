<?php
// print_job.php - print-friendly job card / receipt (Day 8)
require 'includes/auth.php';
requireLogin();
require 'config/db.php';

$allowedStatuses = ['Received', 'Checking', 'Repairing', 'Ready', 'Delivered'];

// Validate id: must be present and a positive integer
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$job = false;

if ($id !== false && $id !== null && $id > 0) {
    $stmt = $pdo->prepare(
        'SELECT j.*, c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address
         FROM jobs j
         JOIN customers c ON c.id = j.customer_id
         WHERE j.id = ?'
    );
    $stmt->execute([$id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return '&#8377;' . number_format((float)$v, 2); }
function orDash($v) { return trim((string)$v) === '' ? '-' : e($v); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title><?= $job ? 'JobCard_' . e($job['job_no']) : 'Job Card' ?></title>
    <link rel="stylesheet" href="assets/css/print.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/print.css') ?>">
</head>
<body>

<?php if (!$job): ?>
    <div class="sheet">
        <h1>Job not found</h1>
        <p>The job card you are looking for does not exist.</p>
        <p class="no-print"><a href="jobs.php" class="btn">Back to Jobs</a></p>
    </div>
<?php else:
    $balance = (float)$job['final_cost'] - (float)$job['paid_amount'];
?>
    <div class="toolbar no-print">
        <a href="job_details.php?id=<?= (int)$job['id'] ?>" class="btn">&larr; Back to Job</a>
        <button type="button" id="printBtn" class="btn btn-primary">Print</button>
    </div>

    <div class="sheet">
        <p class="printed-on">Printed: <span id="printedOn"></span></p>
        <header class="sheet-header">
            <div>
                <h1>Service Center</h1>
                <p class="sub">Repair Job Card / Receipt</p>
            </div>
            <div class="job-no">
                <span class="label">Job No</span>
                <strong><?= e($job['job_no']) ?></strong>
                <span class="status"><?= e($job['status']) ?></span>
            </div>
        </header>

        <section>
            <h2>Customer</h2>
            <table class="kv">
                <tr><th>Name</th><td><?= e($job['customer_name']) ?></td></tr>
                <tr><th>Mobile</th><td><?= e($job['customer_mobile']) ?></td></tr>
                <tr><th>Address</th><td><?= orDash($job['customer_address']) ?></td></tr>
            </table>
        </section>

        <section>
            <h2>Device &amp; Complaint</h2>
            <table class="kv">
                <tr><th>Device</th><td><?= e($job['device_name']) ?></td></tr>
                <tr><th>Model</th><td><?= orDash($job['model']) ?></td></tr>
                <tr><th>Technician</th><td><?= orDash($job['technician']) ?></td></tr>
                <tr><th>Complaint</th><td class="pre"><?= e($job['complaint']) ?></td></tr>
            </table>
        </section>

        <section>
            <h2>Cost &amp; Payment</h2>
            <table class="kv">
                <tr><th>Estimate</th><td class="num"><?= money($job['estimate']) ?></td></tr>
                <tr><th>Final Cost</th><td class="num"><?= money($job['final_cost']) ?></td></tr>
                <tr><th>Paid</th><td class="num"><?= money($job['paid_amount']) ?></td></tr>
                <tr class="total"><th>Balance</th><td class="num"><?= money($balance) ?></td></tr>
            </table>
        </section>

        <footer class="signatures">
            <div><span class="line"></span>Customer Signature</div>
            <div><span class="line"></span>Authorized Signature</div>
        </footer>
    </div>

    <script src="assets/js/job-print.js"></script>
<?php endif; ?>

</body>
</html>