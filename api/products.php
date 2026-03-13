<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT p.*, c.name as category_name, u.short_name as unit_name FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             LEFT JOIN units u ON p.unit_id = u.id 
                             ORDER BY p.name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'list_categories') {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'list_units') {
        $stmt = $pdo->query("SELECT * FROM units ORDER BY name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'stats') {
        $stats = [
            'total_items' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
            'stock_value' => $pdo->query("SELECT SUM(cost_price * stock_quantity) FROM products")->fetchColumn() ?: 0,
            'low_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level AND stock_quantity > 0")->fetchColumn(),
            'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn()
        ];
        echo json_encode(['success' => true, 'data' => $stats]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_category') {
        $name = $_POST['name'] ?? '';
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            echo json_encode(['success' => true, 'message' => 'Category created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category name required']);
        }
    } elseif ($action === 'update_category') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        if ($id && $name) {
            $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
            $stmt->execute([$name, $id]);
            echo json_encode(['success' => true, 'message' => 'Category updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID and Name required']);
        }
    } elseif ($action === 'delete_category') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Category deleted']);
        }
    } elseif ($action === 'create_unit') {
        $name = $_POST['name'] ?? '';
        $short = $_POST['short_name'] ?? '';
        if ($name && $short) {
            $stmt = $pdo->prepare("INSERT INTO units (name, short_name) VALUES (?, ?)");
            $stmt->execute([$name, $short]);
            echo json_encode(['success' => true, 'message' => 'Unit created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Name and Short Name required']);
        }
    } elseif ($action === 'update_unit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $short = $_POST['short_name'] ?? '';
        if ($id && $name && $short) {
            $stmt = $pdo->prepare("UPDATE units SET name=?, short_name=? WHERE id=?");
            $stmt->execute([$name, $short, $id]);
            echo json_encode(['success' => true, 'message' => 'Unit updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID, Name and Short Name required']);
        }
    } elseif ($action === 'delete_unit') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM units WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Unit deleted']);
        }
    } elseif ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $barcode = !empty($_POST['barcode']) ? $_POST['barcode'] : null;
        $sku = $_POST['sku'] ?? '';
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : null;
        $cost_price = str_replace(',', '', $_POST['cost_price'] ?? 0);
        $selling_price = str_replace(',', '', $_POST['selling_price'] ?? 0);
        $min_stock = $_POST['min_stock_level'] ?? 5;
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Product name required']);
            exit;
        }

        // Auto SKU Generation if empty
        if (empty($sku)) {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'sku_template'");
            $template = $stmt->fetchColumn() ?: 'PROD-{MMYYYY}-00000';
            
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'sku_next_number'");
            $nextNum = (int)($stmt->fetchColumn() ?: 1);
            
            // 1. Mask Date placeholders temporarily to avoid zero confusion
            $sku = str_replace(['{MMYYYY}', '{MM}', '{YYYY}', '{YY}'], ['[MMYYYY]', '[MM]', '[YYYY]', '[YY]'], $template);
            
            // 2. Handle Serial Zeros (matches 00000 or {00000})
            if (preg_match('/\{?(0+)\}?/', $sku, $matches)) {
                $fullMatch = $matches[0];
                $zeros = $matches[1];
                
                $serial = str_pad($nextNum, strlen($zeros), '0', STR_PAD_LEFT);
                $sku = str_replace($fullMatch, $serial, $sku);
            }
            
            // 3. Unmask and replace with real dates
            $sku = str_replace(['[MMYYYY]', '[MM]', '[YYYY]', '[YY]'], [date('m').date('Y'), date('m'), date('Y'), date('y')], $sku);
            
            // Update next number
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'sku_next_number'")->execute([$nextNum + 1]);
        }

        $stmt = $pdo->prepare("INSERT INTO products (barcode, sku, name, category_id, unit_id, cost_price, selling_price, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$barcode, $sku, $name, $category_id, $unit_id, $cost_price, $selling_price, $min_stock]);
            echo json_encode(['success' => true, 'message' => 'Product created', 'sku' => $sku]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $barcode = !empty($_POST['barcode']) ? $_POST['barcode'] : null;
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : null;
        $cost_price = str_replace(',', '', $_POST['cost_price'] ?? 0);
        $selling_price = str_replace(',', '', $_POST['selling_price'] ?? 0);
        $min_stock = $_POST['min_stock_level'] ?? 5;
        
        if (!$id || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'ID and Name required']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE products SET barcode=?, name=?, category_id=?, unit_id=?, cost_price=?, selling_price=?, min_stock_level=? WHERE id=?");
        try {
            $stmt->execute([$barcode, $name, $category_id, $unit_id, $cost_price, $selling_price, $min_stock, $id]);
            echo json_encode(['success' => true, 'message' => 'Product updated']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Product deleted']);
        }
    } elseif ($action === 'import') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle === false) {
            echo json_encode(['success' => false, 'message' => 'Could not open file.']);
            exit;
        }

        // Optional: Skip header row if needed. Let's assume there's a header.
        $header = fgetcsv($handle); 

        $total = 0;
        $success = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue; // Skip empty rows

            $total++;
            $name = trim($row[0]);
            $sku = trim($row[1] ?? '');
            $barcode = trim($row[2] ?? NULL);
            $catName = trim($row[3] ?? '');
            $unitName = trim($row[4] ?? '');
            $cost = floatval(str_replace(',', '', $row[5] ?? 0));
            $selling = floatval(str_replace(',', '', $row[6] ?? 0));
            $minStock = intval($row[7] ?? 5);

            if (empty($name)) {
                $failed++;
                continue;
            }

            try {
                $pdo->beginTransaction();

                // 1. Resolve Category
                $categoryId = null;
                if ($catName) {
                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                    $stmt->execute([$catName]);
                    $categoryId = $stmt->fetchColumn();
                    if (!$categoryId) {
                        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$catName]);
                        $categoryId = $pdo->lastInsertId();
                    }
                }

                // 2. Resolve Unit
                $unitId = null;
                if ($unitName) {
                    $stmt = $pdo->prepare("SELECT id FROM units WHERE name = ? OR short_name = ?");
                    $stmt->execute([$unitName, $unitName]);
                    $unitId = $stmt->fetchColumn();
                    if (!$unitId) {
                        $short = substr($unitName, 0, 3);
                        $pdo->prepare("INSERT INTO units (name, short_name) VALUES (?, ?)")->execute([$unitName, $short]);
                        $unitId = $pdo->lastInsertId();
                    }
                }

                // 3. Insert Product
                $stmt = $pdo->prepare("INSERT INTO products (name, sku, barcode, category_id, unit_id, cost_price, selling_price, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $sku, $barcode ?: NULL, $categoryId, $unitId, $cost, $selling, $minStock]);

                $pdo->commit();
                $success++;
            } catch (Exception $e) {
                $pdo->rollBack();
                $failed++;
            }
        }
        fclose($handle);

        echo json_encode([
            'success' => true,
            'message' => "Import completed: $success successful, $failed failed.",
            'details' => ['total' => $total, 'success' => $success, 'failed' => $failed]
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown POST action']);
    }
}
