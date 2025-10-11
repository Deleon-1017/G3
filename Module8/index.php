<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales & Support Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <base href="<?php echo BASE_URL; ?>">
  <style>.card{padding:1rem;margin-bottom:1rem}</style>
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <header class="hero card">
      <h1>Sales & Support Dashboard</h1>
      <p class="small">Quick access to customer, quoting, sales orders and support tools.</p>
    </header>

    <section class="card">
      <h3>Quick Actions</h3>
      <div class="quick-grid" role="list">
        <a role="listitem" class="quick-card" href="Module8/customers.php">
          <div class="qc-icon">👥</div>
          <div class="qc-body"><strong>Customers</strong><div class="small">Manage customer records</div></div>
        </a>
        <a role="listitem" class="quick-card" href="Module8/quotes.php">
          <div class="qc-icon">🧾</div>
          <div class="qc-body"><strong>Quotes</strong><div class="small">Create & send quotations</div></div>
        </a>
        <a role="listitem" class="quick-card" href="Module8/sales_orders.php">
          <div class="qc-icon">🛒</div>
          <div class="qc-body"><strong>Sales Order</strong><div class="small">New sales & processing</div></div>
        </a>
        <a role="listitem" class="quick-card" href="Module8/support_tickets.php">
          <div class="qc-icon">🎧</div>
          <div class="qc-body"><strong>Support</strong><div class="small">Customer tickets & logs</div></div>
        </a>
        <a role="listitem" class="quick-card" href="Module8/reports.php">
          <div class="qc-icon">📊</div>
          <div class="qc-body"><strong>Reports</strong><div class="small">Sales & forecasting</div></div>
        </a>
      </div>
    </section>

    <section class="card">
      <h3>Recent Sales Orders</h3>
      <div id="orders"></div>
    </section>
  </div>

  <script>
  async function loadOrders(){
    const res = await fetch('Module8/api/sales_orders.php?action=list');
    const j = await res.json();
    const container = document.getElementById('orders');
    if (j.status==='success'){
      container.innerHTML = '<table class="table"><thead><tr><th>Order #</th><th>Customer</th><th>Status</th><th>Total</th></tr></thead><tbody>' + j.data.slice(0,10).map(o=>`<tr><td>${o.order_number}</td><td>${o.customer_name}</td><td><span class="status-${o.status.toLowerCase()}">${o.status}</span></td><td>₱${o.total}</td></tr>`).join('') + '</tbody></table>';
    }
  }
  loadOrders();
  </script>
</body>
</html>
