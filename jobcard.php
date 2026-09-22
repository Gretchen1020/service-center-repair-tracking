<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

$errors = [];
$success = '';

// Customers must already exist (added via the Customer module) before a job card can be created
$customers = $pdo->query("SELECT id, name, mobile FROM customers ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id  = $_POST['customer_id'] ?? '';
    $device_name  = trim($_POST['device_name'] ?? '');
    $model        = trim($_POST['model'] ?? '');
    $complaint    = trim($_POST['complaint'] ?? '');
    $technician   = trim($_POST['technician'] ?? '');
    $estimate     = $_POST['estimate'] ?? '';

    if ($customer_id === '' || !ctype_digit((string) $customer_id) || (int) $customer_id < 1) {
        $errors[] = "Please select a customer.";
    }
    if ($device_name === '') {
        $errors[] = "Device name is required.";
    }
    if ($complaint === '') {
        $errors[] = "Complaint is required.";
    }
    if (mb_strlen($device_name) > 100 || mb_strlen($model) > 100 || mb_strlen($technician) > 100) {
        $errors[] = "Device name, model and technician must each be 100 characters or fewer.";
    }
    if ($estimate === '' || !is_numeric($estimate) || $estimate < 0) {
        $errors[] = "Estimate must be a valid non-negative number.";
    } elseif ($estimate > 99999999.99) {
        $errors[] = "Estimate is too large.";
    }

    if (empty($errors)) {
        try {
            // Insert first with a placeholder job_no, then turn the new id into a
            // readable unique job number (JC-0001, JC-0002, ...). Keeps the DB to
            // just the 3 required tables - no separate sequence table needed.
            $stmt = $pdo->prepare("INSERT INTO jobs
                (job_no, customer_id, device_name, model, complaint, technician, estimate, final_cost, paid_amount, status)
                VALUES
                (:job_no, :customer_id, :device_name, :model, :complaint, :technician, :estimate, 0, 0, 'Received')");

            $stmt->execute([
                ':job_no'      => 'TEMP',
                ':customer_id' => $customer_id,
                ':device_name' => $device_name,
                ':model'       => $model,
                ':complaint'   => $complaint,
                ':technician'  => $technician,
                ':estimate'    => $estimate,
            ]);

            $newId = $pdo->lastInsertId();
            $job_no = 'JC-' . str_pad($newId, 4, '0', STR_PAD_LEFT);

            $update = $pdo->prepare("UPDATE jobs SET job_no = :job_no WHERE id = :id");
            $update->execute([':job_no' => $job_no, ':id' => $newId]);

            // Redirect-after-POST, same pattern as customers.php
            header("Location: jobcard.php?success=" . urlencode($job_no));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Could not save job card. Please try again.";
        }
    }
}

if (isset($_GET['success'])) {
    $success = "Job card created successfully. Job No: " . htmlspecialchars($_GET['success']);
}

require_once 'includes/header.php';
?>

<div class="card">
<h2>New Job Card</h2>

<?php if ($success): ?>
    <p class="success"><?= $success ?></p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($customers)): ?>
    <p>No customers found. Please <a href="customers.php">add a customer</a> first before creating a job card.</p>
<?php else: ?>

<form method="POST" id="jobcard-form" novalidate>
    <label for="customer_id">Customer *</label>
    <select name="customer_id" id="customer_id" required>
        <option value="">-- Select customer --</option>
        <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"
                <?= (isset($_POST['customer_id']) && $_POST['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['mobile']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label for="device_name">Device Name *</label>
    <input type="text" name="device_name" id="device_name" maxlength="100"
           value="<?= htmlspecialchars($_POST['device_name'] ?? '') ?>" required>

    <label for="model">Model</label>
    <input type="text" name="model" id="model" maxlength="100"
           value="<?= htmlspecialchars($_POST['model'] ?? '') ?>">

    <label for="complaint">Complaint *</label>
    <textarea name="complaint" id="complaint" required><?= htmlspecialchars($_POST['complaint'] ?? '') ?></textarea>

    <label for="technician">Technician</label>
    <input type="text" name="technician" id="technician" maxlength="100"
           value="<?= htmlspecialchars($_POST['technician'] ?? '') ?>">

    <label for="estimate">Estimate (₹) *</label>
    <input type="number" name="estimate" id="estimate" step="0.01" min="0"
           value="<?= htmlspecialchars($_POST['estimate'] ?? '') ?>" required>

    <button type="submit">Create Job Card</button>
</form>

<?php endif; ?>
</div>

<script src="assets/js/jobcard-validate.js"></script>

<?php require_once 'includes/footer.php'; ?>