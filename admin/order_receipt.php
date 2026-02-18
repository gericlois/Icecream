<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name, u.address as customer_address,
           a.full_name as agent_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN users a ON o.agent_id = a.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { die('Order not found.'); }

$items = $conn->query("SELECT * FROM order_items WHERE order_id = $id ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt - <?php echo $order['order_number']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .receipt-container { max-width: 700px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { font-size: 16px; margin-bottom: 2px; }
        .header p { font-size: 11px; color: #555; }
        .receipt-title { text-align: center; font-size: 14px; font-weight: bold; margin: 15px 0; text-decoration: underline; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; }
        .info-row .label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f0f0f0; font-weight: bold; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; font-size: 12px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sig-block { text-align: center; width: 45%; }
        .sig-block .name { font-weight: bold; margin-bottom: 2px; }
        .sig-block .title { font-size: 11px; color: #555; }
        .sig-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        .notice { font-size: 10px; font-style: italic; margin-top: 20px; }
        .customer-sig { margin-top: 30px; }
        .customer-sig .sig-line { width: 200px; }
        .no-print { margin-bottom: 20px; text-align: center; }
        .no-print button { padding: 10px 30px; font-size: 14px; background: #E91E63; color: white; border: none; border-radius: 5px; cursor: pointer; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print Receipt</button>
        <button onclick="window.close()" style="background: #666; margin-left: 10px;">Close</button>
    </div>

    <div class="receipt-container">
        <div class="header">
            <h2><?php echo APP_NAME; ?></h2>
            <p><?php echo COMPANY_ADDRESS; ?></p>
            <p>TIN: <?php echo COMPANY_TIN; ?> &nbsp; | &nbsp; Customer Hotline: <?php echo COMPANY_HOTLINE; ?></p>
        </div>

        <div class="receipt-title">DELIVERY RECEIPT</div>

        <div class="info-row">
            <span><span class="label">Customer Name:</span> <?php echo sanitize($order['customer_name']); ?></span>
            <span><span class="label">Date:</span> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
        </div>
        <div class="info-row">
            <span><span class="label">Address:</span> <?php echo sanitize($order['customer_address'] ?? ''); ?></span>
        </div>
        <div class="info-row">
            <span><span class="label">Sales Agent:</span> <?php echo sanitize($order['agent_name'] ?? '-'); ?></span>
            <span><span class="label">Order #:</span> <?php echo sanitize($order['order_number']); ?></span>
        </div>
        <?php if ($order['delivery_start_date']): ?>
        <div class="info-row">
            <span><span class="label">Scheduled Delivery:</span> <?php echo date('M d', strtotime($order['delivery_start_date'])) . '-' . date('d, Y', strtotime($order['delivery_end_date'])); ?></span>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:80px;">QUANTITY</th>
                    <th>DESCRIPTION</th>
                    <th class="text-right" style="width:100px;">UNIT PRICE</th>
                    <th class="text-right" style="width:100px;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $item['quantity_units']; ?></td>
                    <td><?php echo sanitize($item['product_name'] . ' - ' . $item['flavor_name']); ?>
                        <br><small>(<?php echo $item['quantity_packs']; ?> pack<?php echo $item['quantity_packs'] > 1 ? 's' : ''; ?> x <?php echo $item['qty_per_pack']; ?>/pack)</small>
                    </td>
                    <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['line_total'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <?php if ($order['discount_amount'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-right">Subtotal:</td>
                    <td class="text-right"><?php echo number_format($order['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Discount (<?php echo $order['discount_percent']; ?>%):</td>
                    <td class="text-right">-<?php echo number_format($order['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total Amount:</td>
                    <td class="text-right"><?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Payment Method:</td>
                    <td class="text-right"><?php echo strtoupper($order['payment_method']); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="signatures">
            <div class="sig-block">
                <p class="label">Prepared by:</p>
                <div class="sig-line">
                    <p class="name">K C B</p>
                    <p class="title">Branch Control</p>
                </div>
            </div>
            <div class="sig-block">
                <p class="label">Delivered by:</p>
                <div class="sig-line">
                    <p class="name">J. Lumanog</p>
                    <p class="title">Delivery Crew</p>
                </div>
            </div>
        </div>

        <p class="notice">*Received the above items in good and quality conditions.</p>

        <div class="customer-sig">
            <div class="sig-line" style="width: 200px;">
                <p class="text-center">Customer Signature</p>
            </div>
        </div>
    </div>
</body>
</html>
