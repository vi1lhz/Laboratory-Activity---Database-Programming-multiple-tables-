<?php
// config/db.php
// Configuration and single shared PDO connection instance

require_once __DIR__ . '/../classes/Database.php';

// Define base URL for consistent asset and navigation resolution
if (!defined('BASE_URL')) {
    define('BASE_URL', '/training_enrollment/');
}

$dsn = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    // Obtain the single shared PDO connection via Database Singleton
    $db = Database::getInstance($dsn, $user, $pass);
} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}
