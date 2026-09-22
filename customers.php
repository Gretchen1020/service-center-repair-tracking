<?php
require 'includes/auth.php';
requireLogin();
require 'config/db.php';

$error   = '';
$success = '';

// ---- Editing an existing customer? (?edit=ID) ----
$editId       = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCustomer = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, mobile, address FROM customers WHERE id = ?');
    $stmt->execute([$editId]);
    $editCustomer = $stmt->fetch();

    if (!$editCustomer) {
        $error  = 'Customer not found.';
        $editId = 0;
    }
}

// ---- Handle Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int) ($_POST['delete_id'] ?? 0);

    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
            $stmt->execute([$deleteId]);
            header('Location: customers.php?deleted=1');
            exit;
        } catch (PDOException $e) {
            // Foreign key violation - customer still has job cards referencing them.
            if ($e->getCode() === '23000') {
                $error = 'Cannot delete this customer - they still have job cards on record.';
            } else {
                throw $e;
            }
        }
    }
}

// ---- Handle Add / Update submit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
    $customerId = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
    $name       = trim($_POST['name'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    if ($name === '' || $mobile === '') {
        $error = 'Name and mobile number are required.';
    } 
    elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $mobile)) {
        $error = 'Please enter a valid mobile number.';
    } 
    elseif (mb_strlen($name) > 100) {
        $error = 'Name must be 100 characters or fewer.';
    } 
    elseif (mb_strlen($address) > 255) {
        $error = 'Address must be 255 characters or fewer.';
    } 
    else {
        if ($customerId > 0) {
            // Update existing customer
            $stmt = $pdo->prepare('UPDATE customers SET name = ?, mobile = ?, address = ? WHERE id = ?');
            $stmt->execute([$name, $mobile, $address !== '' ? $address : null, $customerId]);
            $success = 'Customer updated successfully.';
        } 
        else {
            // Insert new customer
            $stmt = $pdo->prepare('INSERT INTO customers (name, mobile, address) VALUES (?, ?, ?)');
            $stmt->execute([$name, $mobile, $address !== '' ? $address : null]);
            $success = 'Customer added successfully.';
        }

        // Redirect after successful save (avoids re-submit on refresh, clears edit mode)
        header('Location: customers.php?saved=1');
        exit;
    }

    // On validation error, keep form pre-filled with submitted values
    $editId = $customerId;
    $editCustomer = ['id' => $customerId, 'name' => $name, 'mobile' => $mobile, 'address' => $address];
}

if (isset($_GET['saved'])) {
    $success = 'Customer saved successfully.';
}
if (isset($_GET['deleted'])) {
    $success = 'Customer deleted successfully.';
}

// After a redirect from a successful save/delete, the form is back to its
// empty Add state - showing "Add Customer" right next to a success message
// like "Customer updated successfully" reads as a mismatch, so use a neutral
// heading for that moment instead.
$formHeading = 'Add Customer';
if ($editId > 0) {
    $formHeading = 'Edit Customer';
} elseif (isset($_GET['saved']) || isset($_GET['deleted'])) {
    $formHeading = 'Customer Form';
}

// ---- Initial customer list (full list on page load; JS takes over for live search) ----
$customers = $pdo->query('SELECT id, name, mobile, address FROM customers ORDER BY name ASC')->fetchAll();

require 'includes/header.php';
?>

<div class="card">
    <h2><?= htmlspecialchars($formHeading) ?></h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="customers.php" id="customerForm">
        <input type="hidden" name="customer_id" value="<?= (int) ($editCustomer['id'] ?? 0) ?>">

        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required maxlength="100"
               value="<?= htmlspecialchars($editCustomer['name'] ?? '') ?>">

        <label for="mobile">Mobile *</label>
        <input type="text" id="mobile" name="mobile" required
               pattern="[0-9+\-\s]{7,20}" title="Enter a valid mobile number"
               value="<?= htmlspecialchars($editCustomer['mobile'] ?? '') ?>">

        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2" maxlength="255"><?= htmlspecialchars($editCustomer['address'] ?? '') ?></textarea>

        <div class="form-actions">
            <button type="submit"><?= $editId > 0 ? 'Update Customer' : 'Add Customer' ?></button>
            <?php if ($editId > 0): ?>
                <a href="customers.php" class="btn">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Customers</h2>

    <input type="text" id="customerSearch" placeholder="Search by name or mobile..." autocomplete="off">

    <div class="table-wrap">
    <table class="data-table stack-table" id="customerTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Mobile</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="customerTableBody">
            <?php include 'includes/customer_rows.php'; ?>
        </tbody>
    </table>
    </div>
    <p id="noResults" style="display:none; color:#666;">No customers found.</p>
</div>

<script src="assets/js/customer-search.js"></script>

<?php require 'includes/footer.php'; ?>