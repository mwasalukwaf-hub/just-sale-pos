<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Accounts')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'sales_summary') {
        $stmt = $pdo->query("SELECT DATE(sale_date) as date, COUNT(id) as total_sales, SUM(total_amount) as revenue FROM sales GROUP BY DATE(sale_date) ORDER BY date DESC LIMIT 30");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'purchase_report') {
        $supplier_id = $_GET['supplier_id'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        
        $sql = "SELECT p.*, s.name as supplier_name, u.username as creator
                FROM purchases p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                JOIN users u ON p.user_id = u.id
                WHERE 1=1";
        $params = [];
        
        if ($supplier_id) {
            $sql .= " AND p.supplier_id = ?";
            $params[] = $supplier_id;
        }
        if ($start_date) {
            $sql .= " AND DATE(p.purchase_date) >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $sql .= " AND DATE(p.purchase_date) <= ?";
            $params[] = $end_date;
        }
        
        $sql .= " ORDER BY p.purchase_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($action === 'sales_report') {
        $user_id = $_GET['user_id'] ?? '';
        $customer_id = $_GET['customer_id'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $month = $_GET['month'] ?? ''; // YYYY-MM
        
        $sql = "SELECT s.*, u.username, c.name as customer_name, sh.opening_time as shift_time
                FROM sales s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shifts sh ON s.shift_id = sh.id
                WHERE 1=1";
        $params = [];
        
        if ($user_id) {
            $sql .= " AND s.user_id = ?";
            $params[] = $user_id;
        }
        if ($customer_id) {
            $sql .= " AND s.customer_id = ?";
            $params[] = $customer_id;
        }
        if ($start_date) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $end_date;
        }
        if ($month) {
            $sql .= " AND DATE_FORMAT(s.sale_date, '%Y-%m') = ?";
            $params[] = $month;
        }
        
        $sql .= " ORDER BY s.sale_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($action === 'profit_loss') {
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // 1. Total Revenue (Sales)
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total_sales FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $sales = $stmt->fetch()['total_sales'] ?? 0;
        
        // 2. Cost of Goods Sold (COGS) - based on sale_items and product cost at time (simplified to current cost)
        // Ideally we should track cost at time of sale, but for now we'll use product's current cost_price
        $stmt = $pdo->prepare("SELECT SUM(si.quantity * p.cost_price) as cogs 
                               FROM sale_items si 
                               JOIN sales s ON si.sale_id = s.id 
                               JOIN products p ON si.product_id = p.id 
                               WHERE DATE(s.sale_date) BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $cogs = $stmt->fetch()['cogs'] ?? 0;
        
        // 3. Operating Expenses / Purchases (Total non-cancelled purchases)
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total_purchases FROM purchases 
                               WHERE status != 'Cancelled' AND DATE(purchase_date) BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $purchases = $stmt->fetch()['total_purchases'] ?? 0;
        
        $gross_profit = $sales - $cogs;
        $net_profit = $sales - $purchases; // Simplistic P&L: Revenue - Actual Spend
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_revenue' => $sales,
                'cogs' => $cogs,
                'gross_profit' => $gross_profit,
                'total_purchases' => $purchases,
                'net_profit' => $net_profit,
                'period' => ['start' => $start_date, 'end' => $end_date]
            ]
        ]);

    } elseif ($action === 'shift_report') {
        $stmt = $pdo->query("SELECT sh.*, u.username, 
                            (SELECT SUM(amount_paid - change_amount) FROM sales sa WHERE sa.shift_id = sh.id AND payment_method='Cash') as expected_cash,
                            (SELECT SUM(total_amount) FROM sales sa WHERE sa.shift_id = sh.id) as expected_revenue 
                            FROM shifts sh JOIN users u ON sh.user_id = u.id ORDER BY sh.opening_time DESC LIMIT 50");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($action === 'inventory_valuation') {
        $stmt = $pdo->query("SELECT SUM(stock_quantity * cost_price) as total_cost_value, SUM(stock_quantity * selling_price) as total_retail_value FROM products");
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
    } elseif ($action === 'dashboard_stats') {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales WHERE DATE(sale_date) = ?");
        $stmt->execute([$today]);
        $todaySales = $stmt->fetch()['total'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as total, 
                             SUM(CASE WHEN stock_quantity <= 5 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                             SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                             SUM(stock_quantity * cost_price) as total_cost_value
                             FROM products");
        $inventory = $stmt->fetch();

        $start = date('Y-m-01');
        $end = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as sales FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
        $stmt->execute([$start, $end]);
        $mtdSales = $stmt->fetch()['sales'] ?? 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'today_sales' => $todaySales,
                'total_skus' => $inventory['total'] ?? 0,
                'low_stock' => $inventory['low_stock'] ?? 0,
                'out_of_stock' => $inventory['out_of_stock'] ?? 0,
                'total_cost_value' => $inventory['total_cost_value'] ?? 0,
                'mtd_sales' => $mtdSales
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown GET action']);
    }
}
?>
