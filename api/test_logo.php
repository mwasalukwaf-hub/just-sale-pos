<?php
require_once 'db.php';
$stmt = $pdo->query('SELECT company_logo FROM settings LIMIT 1');
$settings = $stmt->fetch();
$logo = $settings['company_logo'];
echo "Logo: $logo\n";
$logo_path = realpath(__DIR__ . '/../' . $logo);
echo "Path: $logo_path\n";
if ($logo_path && file_exists($logo_path)) {
    echo "File exists.\n";
    $mime_type = mime_content_type($logo_path);
    echo "Mime: $mime_type\n";
} else {
    echo "File does not exist or invalid path.\n";
}
