<?php
include '../db.php';

 $message = ''; // store sync feedback

// Handle status update
if (isset($_GET['action']) && in_array($_GET['action'], ['mark_paid', 'mark_overdue', 'cancel']) && isset($_GET['invoice_number'])) {
    $status_map = [
        'mark_paid' => 'paid',
        'mark_overdue' => 'overdue',
        'cancel' => 'cancelled'
    ];
    
    try {
        $new_status = $status_map[$_GET['action']];
        
        // Update status in accounts_payable table
        $stmt = $pdo->prepare("UPDATE accounts_payable SET status = ? WHERE invoice_number = ?");
        $stmt->execute([$new_status, $_GET['invoice_number']]);
        
        // Also update status in invoices table in procurement module
        try {
            $procurement_api_url = "http://localhost/GROUP3/Module3/receive_status_update.php";
            $update_data = ["invoice_number" => $_GET['invoice_number'], "status" => $new_status];

            $options = [
                "http" => [
                    "header"  => "Content-Type: application/json\r\n",
                    "method"  => "POST",
                    "content" => json_encode($update_data)
                ]
            ];
            $context  = stream_context_create($options);
            $result = file_get_contents($procurement_api_url, false, $context);
            
            // Log the update
            file_put_contents(__DIR__ . '/status_update_log.txt', date('Y-m-d H:i:s') . " | Updated invoice: " . $_GET['invoice_number'] . " to status: " . $new_status . " | Result: $result\n", FILE_APPEND);
        } catch (Exception $ex) {
            file_put_contents(__DIR__ . '/status_update_log.txt', date('Y-m-d H:i:s') . " | Status update to procurement failed: " . $ex->getMessage() . "\n", FILE_APPEND);
        }
        
        $message = "Invoice status updated successfully!";
        header("Location: finance_dashboard.php?message=" . urlencode($message));
        exit;
    } catch (Exception $e) {
        $error = "Error updating invoice status: " . $e->getMessage();
        header("Location: finance_dashboard.php?error=" . urlencode($error));
        exit;
    }
}

// If sync button is clicked
if (isset($_GET['sync']) && $_GET['sync'] === '1') {
    // Fetch all invoices from Procurement
    $stmt = $pdo->query("
        SELECT i.invoice_number, i.po_id, i.supplier_id, i.total_amount, i.due_date, i.status
        FROM invoices i
    ");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($invoices)) {
        $message = "✅ No invoices found to sync.";
    } else {
        // Prepare insert/update query
        $insertOrUpdate = $pdo->prepare("
            INSERT INTO accounts_payable (invoice_number, po_id, supplier_id, amount, due_date, status)
            VALUES (:invoice_number, :po_id, :supplier_id, :amount, :due_date, :status)
            ON DUPLICATE KEY UPDATE
                po_id = VALUES(po_id),
                supplier_id = VALUES(supplier_id),
                amount = VALUES(amount),
                due_date = VALUES(due_date),
                status = VALUES(status)
        ");

        $count = 0;
        foreach ($invoices as $row) {
            $insertOrUpdate->execute([
                ':invoice_number' => $row['invoice_number'],
                ':po_id'          => $row['po_id'],
                ':supplier_id'    => $row['supplier_id'],
                ':amount'         => $row['total_amount'],
                ':due_date'       => $row['due_date'],
                ':status'         => $row['status']
            ]);
            $count++;
        }
        $message = "✅ Synced $count invoice(s) from Procurement (new and updated).";
    }
}

// Fetch accounts_payable
 $stmt = $pdo->query("
    SELECT ap.*, s.name AS supplier_name, po.po_number
    FROM accounts_payable ap
    LEFT JOIN suppliers s ON ap.supplier_id = s.id
    LEFT JOIN purchase_orders po ON ap.po_id = po.id
    ORDER BY ap.created_at DESC
    LIMIT 1000
");
 $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
 $totalsStmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN LOWER(status)='pending' THEN amount ELSE 0 END) AS total_pending,
        SUM(CASE WHEN LOWER(status)='paid' THEN amount ELSE 0 END) AS total_paid,
        SUM(CASE WHEN LOWER(status)='cancelled' THEN amount ELSE 0 END) AS total_cancelled,
        SUM(amount) AS total_amount
    FROM accounts_payable
");
 $totals = $totalsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Finance Dashboard — Accounts Payable</title>
<style>
  :root{
    --bg:#f6f8fb;
    --card:#fff;
    --muted:#6b7280;
    --accent:#11698e;
    --danger:#ef4444;
    --ok:#16a34a;
    --cancel:#9ca3af;
    --table-head:#eef2f6;
  }
  body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial; background:var(--bg); margin:0; padding:32px; color:#0f172a;}
  .container {
    margin-left: 260px;
    padding: 30px 40px;
    transition: margin-left 0.3s ease;
  }

  @media (max-width: 900px) {
    .container {
      margin-left: 0;
      padding: 20px;
    }
  }
  .card{
    background:var(--card);
    padding:18px;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(14,20,30,0.06); 
    margin-bottom:16px;
  }
  h1{
    margin:0 0 6px 0;
    font-size:20px;
  }
  .subtitle{
    color:var(--muted);
    font-size:13px;
    margin-bottom:12px;
  }
  .summary{display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
  }
  .summary .item{
    flex:1;
    min-width:180px;
    background:#fbfdff;
    padding:12px;
    border-radius:10px;
    border:1px solid #eef4f7;
  }
  .summary .val{
    font-weight:700;
    font-size:18px;
  }
  .table-wrap{
    overflow:auto;
    border-radius:10px;
  }
  table{
    width:100%;
    border-collapse:collapse;
    background:var(--card);
  }
  thead th{
    background:var(--table-head);
    text-align:left;
    padding:12px;
    font-size:13px;
    color:#0f172a;
    position:sticky;
    top:0;
  }
  tbody td{
    padding:12px;
    border-bottom:1px solid #f1f5f9;
    font-size:14px;
    color:#0f172a;
  }
  tbody tr:hover{
    background:#fbfdff;
  }
  .badge{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    color:#fff;
    font-weight:600;
  }
  .badge.pending{
    background:var(--accent);
  }
  .badge.paid{
    background:var(--ok);
  }
  .badge.overdue{
    background:var(--danger);
  }
  .badge.cancelled{
    background:var(--cancel);
  }
  .small{
    font-size:13px;
    color:var(--muted);
  }
  .right{
    text-align:right;
  }
  .actions{
    display:flex;
    gap:8px;
    align-items:center;
  }
  .btn{
    padding:8px 12px;
    border-radius:8px;
    border:0;
    background:var(--accent);
    color:#fff;
    font-weight:600;
    cursor:pointer;
  }
  .btn.sync{
    background:#0d9488;
  }
  .btn-success {
    background-color: #2ecc71;
  }
  .btn-warning {
    background-color: #f39c12;
  }
  .btn-danger {
    background-color: #e74c3c;
  }
  .muted{
    color:var(--muted);
  }
  .message{
    background:#ecfdf5;
    border:1px solid #a7f3d0;
    padding:10px;
    border-radius:8px;
    color:#065f46;
    font-size:14px;
    margin-bottom:14px;
  }
  .error{
    background:#f8d7da;
    border:1px solid #f5c6cb;
    padding:10px;
    border-radius:8px;
    color:#721c24;
    font-size:14px;
    margin-bottom:14px;
  }
  
  /* Dropdown menu styles */
  .dropdown {
    position: relative;
    display: inline-block;
  }
  
  .dropdown-toggle {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    color: #555;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    transition: background-color 0.2s;
  }
  
  .dropdown-toggle:hover {
    background-color: #f1f1f1;
  }
  
  .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1000;
    border-radius: 4px;
    overflow: hidden;
    /* Prevent dropdown from causing page scroll */
    max-height: calc(100vh - 40px);
    overflow-y: auto;
  }
  
  .dropdown-menu a {
    color: #333;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
    font-size: 14px;
    transition: background-color 0.2s;
  }
  
  .dropdown-menu a:hover {
    background-color: #f1f1f1;
  }
  
  .dropdown-menu .btn {
    width: 100%;
    text-align: left;
    border-radius: 0;
    margin: 0;
    border: none;
    background: none;
    color: #333;
    padding: 10px 15px;
    font-size: 14px;
  }
  
  .dropdown-menu .btn:hover {
    background-color: #f1f1f1;
  }
  
  .dropdown-menu .btn-danger {
    color: #e74c3c;
  }
  
  .dropdown-menu .btn-danger:hover {
    background-color: #f8d7da;
  }
  
  .dropdown-menu .btn-success {
    color: #2ecc71;
  }
  
  .dropdown-menu .btn-success:hover {
    background-color: #d4edda;
  }
  
  .dropdown-menu .btn-warning {
    color: #f39c12;
  }
  
  .dropdown-menu .btn-warning:hover {
    background-color: #fff3cd;
  }
  
  .dropdown-divider {
    height: 1px;
    margin: 5px 0;
    overflow: hidden;
    background-color: #e9ecef;
  }
  
  .show {
    display: block;
  }
  
  /* Position dropdown above if there's not enough space below */
  .dropdown-menu.dropup {
    bottom: 100%;
    top: auto;
  }
  
  /* Paid indicator */
  .paid-indicator {
    color: var(--ok);
    font-size: 14px;
    font-weight: 600;
  }
  
  @media (max-width:700px){
    .summary{flex-direction:column;}
    thead th:nth-child(4), tbody td:nth-child(4),
    thead th:nth-child(5), tbody td:nth-child(5) {display:none;}
  }
</style>
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container">
    <div class="card">
      <h1>Accounts Payable</h1>
      <div class="subtitle">View supplier invoices received from Procurement. This table reads from the <code>accounts_payable</code> table.</div>

      <?php if (isset($_GET['message'])): ?>
        <div class="message"><?= htmlspecialchars($_GET['message']) ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="summary">
        <div class="item"><div class="small">Total records</div><div class="val"><?= htmlspecialchars($totals['total_count'] ?? 0) ?></div></div>
        <div class="item"><div class="small">Total pending</div><div class="val">₱ <?= number_format((float)($totals['total_pending'] ?? 0),2) ?></div></div>
        <div class="item"><div class="small">Total paid</div><div class="val">₱ <?= number_format((float)($totals['total_paid'] ?? 0),2) ?></div></div>
        <div class="item"><div class="small">Total cancelled</div><div class="val">₱ <?= number_format((float)($totals['total_cancelled'] ?? 0),2) ?></div></div>
        <div class="item"><div class="small">All invoices total</div><div class="val">₱ <?= number_format((float)($totals['total_amount'] ?? 0),2) ?></div></div>
      </div>

      <div class="card" style="padding:10px; margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
          <div class="small muted">Last <?= count($rows) ?> entries (most recent first)</div>
          <div class="actions">
            <button class="btn sync" onclick="window.location='?sync=1'">Sync from Procurement</button>
          </div>
        </div>
      </div>

      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead>
            <tr>
              <th>Invoice No.</th>
              <th>PO Number</th>
              <th>Supplier</th>
              <th class="right">Amount</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$rows): ?>
              <tr><td colspan="8" class="small muted">No accounts payable records found.</td></tr>
            <?php else: ?>
              <?php foreach($rows as $r): ?>
                <?php
                  $status = strtolower(trim($r['status'] ?? 'pending'));
                  if ($status === 'paid') $badgeClass = 'paid';
                  elseif ($status === 'overdue') $badgeClass = 'overdue';
                  elseif ($status === 'cancelled') $badgeClass = 'cancelled';
                  else $badgeClass = 'pending';
                ?>
                <tr>
                  <td><?= htmlspecialchars($r['invoice_number']) ?></td>
                  <td class="small"><?= htmlspecialchars($r['po_number'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['supplier_name'] ?? ('ID: '.$r['supplier_id'])) ?></td>
                  <td class="right">₱ <?= number_format((float)$r['amount'],2) ?></td>
                  <td class="small"><?= htmlspecialchars($r['due_date'] ?? '-') ?></td>
                  <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                  <td class="small"><?= htmlspecialchars($r['created_at']) ?></td>
                  <td>
                    <?php if ($status === 'pending'): ?>
                      <div class="dropdown">
                        <button class="dropdown-toggle" onclick="toggleDropdown(event, '<?= htmlspecialchars($r['invoice_number']) ?>')">
                          &#8942;
                        </button>
                        <div id="dropdown-<?= htmlspecialchars($r['invoice_number']) ?>" class="dropdown-menu">
                          <button class="btn btn-success" onclick="updateStatus('<?= htmlspecialchars($r['invoice_number']) ?>', 'mark_paid')">
                            Mark as Paid
                          </button>
                          <button class="btn btn-warning" onclick="updateStatus('<?= htmlspecialchars($r['invoice_number']) ?>', 'mark_overdue')">
                            Mark as Overdue
                          </button>
                          <div class="dropdown-divider"></div>
                          <button class="btn btn-danger" onclick="updateStatus('<?= htmlspecialchars($r['invoice_number']) ?>', 'cancel')">
                            Cancel Invoice
                          </button>
                        </div>
                      </div>
                    <?php elseif ($status === 'overdue'): ?>
                      <div class="dropdown">
                        <button class="dropdown-toggle" onclick="toggleDropdown(event, '<?= htmlspecialchars($r['invoice_number']) ?>')">
                          &#8942;
                        </button>
                        <div id="dropdown-<?= htmlspecialchars($r['invoice_number']) ?>" class="dropdown-menu">
                          <button class="btn btn-success" onclick="updateStatus('<?= htmlspecialchars($r['invoice_number']) ?>', 'mark_paid')">
                            Mark as Paid
                          </button>
                          <div class="dropdown-divider"></div>
                          <button class="btn btn-danger" onclick="updateStatus('<?= htmlspecialchars($r['invoice_number']) ?>', 'cancel')">
                            Cancel Invoice
                          </button>
                        </div>
                      </div>
                    <?php elseif ($status === 'paid'): ?>
                      <span class="paid-indicator">✓ Paid</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
  // Toggle dropdown menu
  function toggleDropdown(event, id) {
    event.stopPropagation();
    closeAllDropdowns();
    
    const dropdown = document.getElementById(`dropdown-${id}`);
    dropdown.classList.toggle('show');
    
    // Check if dropdown goes off screen and adjust position
    if (dropdown.classList.contains('show')) {
      const rect = dropdown.getBoundingClientRect();
      const spaceBelow = window.innerHeight - rect.bottom;
      
      if (spaceBelow < 200) { // If not enough space below
        dropdown.classList.add('dropup');
      } else {
        dropdown.classList.remove('dropup');
      }
    }
  }

  // Close all dropdowns
  function closeAllDropdowns() {
    const dropdowns = document.getElementsByClassName('dropdown-menu');
    for (let i = 0; i < dropdowns.length; i++) {
      dropdowns[i].classList.remove('show');
      dropdowns[i].classList.remove('dropup');
    }
  }

  // Close dropdowns when clicking outside
  window.onclick = function(event) {
    if (!event.target.matches('.dropdown-toggle')) {
      closeAllDropdowns();
    }
  }

  function updateStatus(invoiceNumber, action) {
    const actionMap = {
      'mark_paid': 'mark this invoice as paid?',
      'mark_overdue': 'mark this invoice as overdue?',
      'cancel': 'cancel this invoice?'
    };
    
    if (confirm(`Are you sure you want to ${actionMap[action]}`)) {
      window.location.href = `finance_dashboard.php?action=${action}&invoice_number=${invoiceNumber}`;
    }
  }
  </script>
</body>
</html>