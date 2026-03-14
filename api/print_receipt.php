<?php
session_start();
require_once 'db.php';
require_once '../vendor/autoload.php';
require_once 'report_functions.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$sale_id = $_GET['id'] ?? null;
if (!$sale_id) {
    die("Sale ID required");
}

// Fetch Sale Data
$stmt = $pdo->prepare("SELECT s.*, u.username, c.name as customer_name, c.email as customer_email, c.tin as customer_tin, c.vrn as customer_vrn, c.address as customer_address 
                       FROM sales s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN customers c ON s.customer_id = c.id 
                       WHERE s.id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Sale not found");
}

$itemsStmt = $pdo->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$itemsStmt->execute([$sale_id]);
$items = $itemsStmt->fetchAll();

$settings = get_report_settings($pdo);
$currency = $settings['company_currency_code'] ?? 'USD';

$format = isset($_GET['download']) ? 'excel' : 'pdf'; // Simplified logic, usually receipts are PDF

$meta = [
    'Receipt ID' => '#' . $sale['id'],
    'Date / Time' => $sale['sale_date'],
    'Payment Method' => $sale['payment_method'],
    'Cashier' => $sale['username'],
    'Customer' => $sale['customer_name'] ?: 'Walk-in Customer'
];

if (!empty($sale['customer_tin'])) {
    $meta['Customer TIN'] = $sale['customer_tin'];
}

$customer_html = '';
if ($sale['customer_id']) {
    $show_tin = ($settings['receipt_show_tin'] ?? 'yes') === 'yes';
    $customer_html = '<div style="margin: 15px 0; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc;">';
    $customer_html .= '<div style="font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 2px;">' . strtoupper($sale['customer_name']) . '</div>';
    if ($show_tin && !empty($sale['customer_tin'])) {
        $customer_html .= '<div style="font-size: 11px; color: #64748b;"><strong>TAX ID / TIN:</strong> ' . $sale['customer_tin'] . '</div>';
    }
    if (!empty($sale['customer_address'])) {
        $customer_html .= '<div style="font-size: 10px; color: #64748b; margin-top: 4px;">' . $sale['customer_address'] . '</div>';
    }
    $customer_html .= '</div>';
}

$customer_pos = $settings['receipt_customer_pos'] ?? 'top';

ob_start();
echo render_report_header("Official Sales Receipt", $settings, 'pdf', $meta);

// Customer Top Position
if ($customer_pos === 'top') echo $customer_html;

if (!empty($settings['receipt_header'])) {
    echo '<div style="margin: 15px 0; font-style: italic; color: #555; text-align: center; border: 1px dashed #ddd; padding: 10px; border-radius: 5px;">' . nl2br($settings['receipt_header']) . '</div>';
}
?>

<table class="table">
    <thead>
        <tr>
            <th>Item Description</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($items as $item): ?>
            <tr>
                <td><strong><?php echo $item['name']; ?></strong></td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><?php echo format_amount($item['price']); ?></td>
                <td class="text-right fw-bold"><?php echo format_amount($item['subtotal']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php 
    $tax_rate = floatval($settings['tax_percent'] ?? 0);
    $total = floatval($sale['total_amount']);
    $tax_amount = ($total * $tax_rate) / (100 + $tax_rate); // Inclusive
    $subtotal = $total - $tax_amount;
?>

<div style="float: right; width: 300px; margin-top: 20px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 5px 0;">Subtotal (Tax Excl.)</td>
            <td class="text-right"><?php echo format_amount($subtotal); ?></td>
        </tr>
        <tr>
            <td style="padding: 5px 0;">VAT / Tax (<?php echo $tax_rate; ?>%)</td>
            <td class="text-right"><?php echo format_amount($tax_amount); ?></td>
        </tr>
        <tr style="font-size: 16px;">
            <td style="padding: 10px 0; border-top: 2px solid #333; font-weight: bold;">TOTAL PAID</td>
            <td class="text-right fw-bold" style="padding: 10px 0; border-top: 2px solid #333; color: #4361ee;">
                <?php echo $currency . ' ' . format_amount($total); ?>
            </td>
        </tr>
        <?php if ($sale['payment_method'] === 'CASH'): ?>
        <tr>
            <td style="padding: 5px 0; color: #666;">Cash Tendered</td>
            <td class="text-right text-muted"><?php echo format_amount($sale['amount_paid']); ?></td>
        </tr>
        <tr>
            <td style="padding: 5px 0; color: #666;">Change Returned</td>
            <td class="text-right text-muted"><?php echo format_amount($sale['change_amount']); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
<div style="clear: both;"></div>

<!-- Customer Bottom Position -->
<?php if ($customer_pos === 'bottom') echo $customer_html; ?>

<?php 
if(!empty($settings['receipt_footer'])) {
    echo '<div style="margin-top: 30px; text-align: center; font-weight: bold; color: #333;">' . nl2br($settings['receipt_footer']) . '</div>';
}
?>

<?php
echo render_report_footer($settings);
$html = ob_get_clean();

$fileName = "Receipt_#{$sale_id}_" . date('Ymd');
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    if (isset($return_content) && $return_content) {
        $pdf_output = export_to_excel($html, $fileName . ".xls", "Sales Receipt", false);
    } else {
        export_to_excel($html, $fileName . ".xls", "Sales Receipt");
    }
} else {
    if (isset($return_content) && $return_content) {
        $pdf_output = export_to_pdf($html, $fileName . ".pdf", 'portrait', false);
    } else {
        $pdf_content = export_to_pdf($html, $fileName . ".pdf", 'portrait', false);
        
        if (!empty($pdf_content)) {
            // Save copy to receipts folder
            $savePath = __DIR__ . "/../receipts/" . $fileName . ".pdf";
            if (!is_dir(__DIR__ . "/../receipts/")) {
                mkdir(__DIR__ . "/../receipts/", 0777, true);
            }
            file_put_contents($savePath, $pdf_content);
        }
        
        // Output to browser
        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=\"$fileName.pdf\"");
        echo $pdf_content;
    }
}
