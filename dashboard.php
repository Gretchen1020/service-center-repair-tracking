<?php
require 'includes/auth.php';
requireLogin();
require 'includes/header.php';
?>
<div class="card">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></h2>
    <p>You're logged in. Customer, job card, status, payment and dashboard modules will be added over the next few days.</p>
</div>
<?php require 'includes/footer.php'; ?>
