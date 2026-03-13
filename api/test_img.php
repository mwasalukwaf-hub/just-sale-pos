<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='company_logo'");
$row = $stmt->fetch();
$logo = $row['setting_value'] ?? '';
echo "Logo path in db: '$logo'\n";

if (!empty($logo)) {
    $logo_path = realpath(__DIR__ . '/../' . $logo);
    echo "Real path: '$logo_path'\n";
    if ($logo_path && file_exists($logo_path)) {
        echo "File exists! Size: " . filesize($logo_path) . "\n";
        $mime_type = mime_content_type($logo_path);
        echo "Mime Type: $mime_type\n";
    } else {
        echo "File DOES NOT exist.\n";
    }
} else {
    echo "Logo is empty in db.\n";
}
