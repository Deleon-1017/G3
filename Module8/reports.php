<?php
require '../db.php';
require '../shared/config.php';

// Sales by product (last 12 months)
$salesByProduct = $pdo->query("SELECT soi.product_id, p.name, SUM(soi.qty) as total_qty, SUM(soi.line_total) as total_amount FROM sales_order_items soi JOIN products p ON p.id = soi.product_id JOIN sales_orders so ON so.id = soi.sales_order_id WHERE so.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY soi.product_id ORDER BY total_amount DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Sales by customer
$salesByCustomer = $pdo->query("SELECT so.customer_id, c.name, SUM(so.total) as total_amount FROM sales_orders so LEFT JOIN customers c ON c.id = so.customer_id GROUP BY so.customer_id ORDER BY total_amount DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Simple forecasting: use average monthly total for last 6 months and project next month
$monthly = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') as ym, SUM(total) as total FROM sales_orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym ASC")->fetchAll(PDO::FETCH_ASSOC);
$sum=0; foreach($monthly as $m) $sum += $m['total'];
$forecast_next = count($monthly) ? $sum / count($monthly) : 0;

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales Reports</title>
  <link rel="stylesheet" href="styles.css">
  <base href="<?php echo BASE_URL; ?>">
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <h1>Reports & Forecast</h1>
    <div class="card">
      <h3>Top Products (12mo)</h3>
      <table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody><?php foreach($salesByProduct as $s): ?><tr><td><?php echo htmlspecialchars($s['name']); ?></td><td><?php echo (int)$s['total_qty']; ?></td><td>₱<?php echo number_format($s['total_amount'],2); ?></td></tr><?php endforeach; ?></tbody></table>
    </div>

    <div class="card">
      <h3>Top Customers</h3>
      <table class="table"><thead><tr><th>Customer</th><th>Amount</th></tr></thead><tbody><?php foreach($salesByCustomer as $s): ?><tr><td><?php echo htmlspecialchars($s['name']); ?></td><td>₱<?php echo number_format($s['total_amount'],2); ?></td></tr><?php endforeach; ?></tbody></table>
    </div>

    <div class="card">
      <h3>Forecast (next month estimate)</h3>
      <p>Estimated revenue next month (based on last <?php echo count($monthly); ?> months average): <strong>₱<?php echo number_format($forecast_next,2); ?></strong></p>
    </div>
  </div>
</body>
</html>
