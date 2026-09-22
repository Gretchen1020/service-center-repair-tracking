<?php
// Shared partial: renders <tr> rows for the job list table.
// Expects $jobs (array of rows with id, job_no, device_name, model, status,
// customer_name, customer_mobile) to be set by whichever page includes this
// file (jobs.php or ajax/search_jobs.php).

if (empty($jobs)):
?>
    <!-- no rows; "No jobs found" message is shown by the including page -->
<?php
else:
    foreach ($jobs as $j):
?>
    <tr>
        <td data-label="Job No"><?= htmlspecialchars($j['job_no']) ?></td>
        <td data-label="Customer"><?= htmlspecialchars($j['customer_name']) ?><br>
            <small><?= htmlspecialchars($j['customer_mobile']) ?></small></td>
        <td data-label="Device"><?= htmlspecialchars($j['device_name']) ?><?= $j['model'] ? ' (' . htmlspecialchars($j['model']) . ')' : '' ?></td>
        <td data-label="Status"><?= htmlspecialchars($j['status']) ?></td>
        <td>
            <a href="job_details.php?id=<?= (int) $j['id'] ?>" class="btn">View</a>
        </td>
    </tr>
<?php
    endforeach;
endif;