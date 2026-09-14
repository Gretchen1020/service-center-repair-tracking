<?php
// Database connection using PDO
// Update these values if your local MySQL setup differs (XAMPP/WAMP default shown)

define('DB_HOST', 'localhost');
define('DB_NAME', 'service_center_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP/WAMP root password is blank

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
