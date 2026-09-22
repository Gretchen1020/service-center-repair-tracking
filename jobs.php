<?php
require 'includes/auth.php';
requireLogin();
require 'config/db.php';

// Allowed statuses - same whitelist used on job_details.php's status update form.
$statuses = ['Received', 'Checking', 'Repairing', 'Ready', 'Delivered'];

$statusFilter = $_GET['status'] ?? '';
if (!in_array($statusFilter, $statuses, true)) {
    $statusFilter = '';
}

// ---- Initial job list (full list, or filtered by status; JS takes over for live search) ----
// Joined with customers so the list shows a name/mobile instead of a bare customer_id.
if ($statusFilter !== '') {
    $stmt = $pdo->prepare(
        'SELECT j.id, j.job_no, j.device_name, j.model, j.status,
                c.name AS customer_name, c.mobile AS customer_mobile
         FROM jobs j
         JOIN customers c ON c.id = j.customer_id
         WHERE j.status = ?
         ORDER BY j.id DESC'
    );
    $stmt->execute([$statusFilter]);
    $jobs = $stmt->fetchAll();
} else {
    $jobs = $pdo->query(
        'SELECT j.id, j.job_no, j.device_name, j.model, j.status,
                c.name AS customer_name, c.mobile AS customer_mobile
         FROM jobs j
         JOIN customers c ON c.id = j.customer_id
         ORDER BY j.id DESC'
    )->fetchAll();
}

require 'includes/header.php';
?>

<div class="card">
    <h2>Job List</h2>

    <input type="text" id="jobSearch" placeholder="Job no, name or mobile..." autocomplete="off">

    
    <form method="get" class="filter-form">
        <label for="statusFilterSelect" class="filter-label">Filter by status</label>
        <select name="status" id="statusFilterSelect" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Filter</button></noscript>
    </form>

    <div class="table-wrap">
    <table class="data-table stack-table" id="jobTable">
        <thead>
            <tr>
                <th>Job No</th>
                <th>Customer</th>
                <th>Device</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="jobTableBody">
            <?php include 'includes/job_rows.php'; ?>
        </tbody>
    </table>
    </div>
    <p id="noJobResults" style="display:none; color:#666;">No jobs found.</p>
</div>

<script src="assets/js/job-search.js"></script>

<?php require 'includes/footer.php'; ?>