<?php
require 'db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM settings');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
