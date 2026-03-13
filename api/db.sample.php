<?php
// api/db.php - Distribution Sample
// Rename this to db.php and update credentials

$host = 'localhost';
$dbname = 'justsale';
$username = 'root';
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database connection failed. Please check your config.']));
}

// Licensing Gatekeeper
require_once 'license_check_middleware.php';
?>
