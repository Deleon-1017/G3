<?php
include '../db.php'; // adjust path if needed

echo "<h2>Syncing old invoices to Finance Module...</h2>";

// 1. Get all invoices that are not yet in accounts_payable
$stmt = $pdo->query("
    SELECT i.invoice_number, i.po_id, i.supplier_id, i.total_amount, i.due_date, i.status
    FROM invoices i
    WHERE NOT EXISTS (
        SELECT 1 FROM accounts_payable a WHERE a.invoice_number = i.invoice_number
    )
");

$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($invoices)) {
    echo "<p>✅ All invoices are already synced with Finance.</p>";
    exit;
}

// 2. Insert each invoice into accounts_payable
$insert = $pdo->prepare("
    INSERT INTO accounts_payable (invoice_number, po_id, supplier_id, amount, due_date, status)
    VALUES (?, ?, ?, ?, ?, ?)
");

$count = 0;
foreach ($invoices as $row) {
    $insert->execute([
        $row['invoice_number'],
        $row['po_id'],
        $row['supplier_id'],
        $row['total_amount'],
        $row['due_date'],
        $row['status']
    ]);
    $count++;
}

echo "<p>✅ Successfully synced $count invoice(s) to Finance.</p>";
echo "<a href='finance_dashboard.php'>Go to Finance Dashboard</a>";
?>
