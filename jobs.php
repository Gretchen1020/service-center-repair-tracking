<?php
require 'includes/auth.php';
requireLogin();
require 'config/db.php';

// ---- Initial job list (full list on page load; JS takes over for live search) ----
// Joined with customers so the list shows a name/mobile instead of a bare customer_id.
$jobs = $pdo->query(
    'SELECT j.id, j.job_no, j.device_name, j.model, j.status,
            c.name AS customer_name, c.mobile AS customer_mobile
     FROM jobs j
     JOIN customers c ON c.id = j.customer_id
     ORDER BY j.id DESC'
)->fetchAll();

require 'includes/header.php';
?>

<div class="card">
    <h2>Job List</h2>

    <input type="text" id="jobSearch" placeholder="Search by job no, customer name or mobile..." autocomplete="off">

    <table class="data-table" id="jobTable">
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
    <p id="noJobResults" style="display:none; color:#666;">No jobs found.</p>
</div>

<script src="assets/js/job-search.js"></script>

<?php require 'includes/footer.php'; ?>