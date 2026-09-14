<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Center Job Card</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/protect-bfcache.js"></script>
</head>
<body>
<header class="topbar">
    <h1>Service Center & Repair Tracking</h1>
    <?php if (isLoggedIn()): ?>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
