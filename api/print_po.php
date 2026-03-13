<?php
session_start();
require_once 'db.php';
require_once '../vendor/autoload.php';
require_once 'report_functions.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$id = $_GET['id'] ?? null;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

if (!$id) {
    die("PO ID required");
}

// Get PO
$stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name, s.address as supplier_address, s.contact_person, s.phone, s.email, s.tin as supplier_tin, u.username as creator
                        FROM purchases p
                        LEFT JOIN suppliers s ON p.supplier_id = s.id
                        JOIN users u ON p.user_id = u.id
                        WHERE p.id = ?");
$stmt->execute([$id]);
$po = $stmt->fetch();

if (!$po) {
    die("PO not found");
}

// Get items
$stmt = $pdo->prepare("SELECT pi.*, pr.name as product_name, pr.barcode, pr.sku
                        FROM purchase_items pi
                        JOIN products pr ON pi.product_id = pr.id
                        WHERE pi.purchase_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$settings = get_report_settings($pdo);
$currency = $settings['company_currency_code'] ?? 'USD';

$meta = [
    'PO Number' => '#PO-' . str_pad($po['id'], 6, '0', STR_PAD_LEFT),
    'Order Date' => date('d M Y', strtotime($po['purchase_date'])),
    'Status' => $po['status'],
    'Supplier' => $po['supplier_name'],
    'Supplier TIN' => $po['supplier_tin'] ?: 'N/A',
    'Created By' => $po['creator']
];

ob_start();
echo render_report_header("Purchase Order", $settings, 'pdf', $meta);

if ($po['status'] === 'Cancelled') {
    echo '<div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 18px; margin-bottom: 20px; border: 2px solid #ef4444;">REVERSED / CANCELLED</div>';
}
?>

<div style="margin-bottom: 25px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <h4 style="margin: 0 0 5px 0; color: #666; font-size: 10px; text-transform: uppercase;">Supplier Details</h4>
                <strong><?php echo $po['supplier_name']; ?></strong><br>
                <?php echo $po['supplier_address'] ?: 'No address provided'; ?><br>
                Tel: <?php echo $po['phone'] ?: 'N/A'; ?><br>
                Email: <?php echo $po['email'] ?: 'N/A'; ?>
            </td>
            <td width="50%" style="vertical-align: top; text-align: right;">
                <h4 style="margin: 0 0 5px 0; color: #666; font-size: 10px; text-transform: uppercase;">Ship To</h4>
                <strong><?php echo $settings['company_name'] ?? 'Warehouse'; ?></strong><br>
                <?php echo $settings['company_address'] ?? ''; ?><br>
                <?php echo $settings['company_phone'] ?? ''; ?>
            </td>
        </tr>
    </table>
</div>

<table class="table">
    <thead>
        <tr>
            <th>SKU / Product</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Unit Cost</th>
            <th class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <strong><?php echo $item['product_name']; ?></strong><br>
                    <small style="color: #888;">SKU: <?php echo $item['sku'] ?: '-'; ?></small>
                </td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-right"><?php echo format_amount($item['cost_price']); ?></td>
                <td class="text-right fw-bold"><?php echo format_amount($item['subtotal']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="float: right; width: 250px; margin-top: 20px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="font-size: 16px;">
            <td style="padding: 10px 0; border-top: 2px solid #333; font-weight: bold;">TOTAL VALUE</td>
            <td class="text-right fw-bold" style="padding: 10px 0; border-top: 2px solid #333; color: #4361ee;">
                <?php echo $currency . ' ' . format_amount($po['total_amount']); ?>
            </td>
        </tr>
    </table>
</div>
<div style="clear: both;"></div>

<?php if (!empty($po['notes'])): ?>
<div style="margin-top: 40px;">
    <h4 style="margin: 0 0 5px 0; color: #666; font-size: 10px; text-transform: uppercase;">Notes / Remarks</h4>
    <div style="border: 1px solid #eee; padding: 10px; border-radius: 5px; background: #fafafa;">
        <?php echo nl2br($po['notes']); ?>
    </div>
</div>
<?php endif; ?>

<?php
echo render_report_footer($settings);
$html = ob_get_clean();

$fileName = "PO-".str_pad($po['id'], 6, '0', STR_PAD_LEFT);
if ($format === 'excel') {
    if (isset($return_content) && $return_content) {
        $pdf_output = export_to_excel($html, $fileName . ".xls", "Purchase Order", false);
    } else {
        export_to_excel($html, $fileName . ".xls", "Purchase Order");
    }
} else {
    if (isset($return_content) && $return_content) {
        $pdf_output = export_to_pdf($html, $fileName . ".pdf", 'portrait', false);
    } else {
        export_to_pdf($html, $fileName . ".pdf");
    }
}
