<?php
require '../db.php';

// Check GET params
$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: index.php');
    exit;
}

// Fetch product info including current warehouse
$stmt = $pdo->prepare("
    SELECT p.*, 
           pl.quantity AS current_qty,
           l.id AS warehouse_id,
           l.name AS warehouse_name
    FROM products p
    LEFT JOIN product_locations pl ON pl.product_id = p.id
    LEFT JOIN locations l ON l.id = pl.location_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Handle Stock-In POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int)($_POST['quantity'] ?? 0);
    if ($qty <= 0) {
        $error = "Quantity must be greater than zero.";
    } else {
        // Update product_locations quantity
        $stmt = $pdo->prepare("
            INSERT INTO product_locations (product_id, location_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$product_id, $product['warehouse_id'], $qty]);

        // FIXED: Update total_quantity in products table
        $stmt = $pdo->prepare("
            UPDATE products 
            SET total_quantity = total_quantity + ?
            WHERE id = ?
        ");
        $stmt->execute([$qty, $product_id]);

        // Add to Module1/stock_in.php after successful stock-in
        // Inside the else block after successful stock-in

        // Prepare data for Finance module
        $finance_data = [
            'product_id' => $product_id,
            'quantity' => $qty,
            'transaction_type' => 'IN',
            'transaction_date' => date('Y-m-d H:i:s'),
            'warehouse_id' => $product['warehouse_id'],
            'reference_id' => null, // Could be populated with goods receipt ID if available
            'reference_type' => null
        ];

        // Send to Finance module API
        $ch = curl_init('http://your-domain.com/Module5/api_stock_transaction.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($finance_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log response for debugging
        error_log("Finance API Response: $response (HTTP $http_code)");

        echo "<script>
        alert('Stock-in successful!');
        window.location.href = 'index.php';
      </script>";
        exit;
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Stock-In: <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <h1>Stock-In Product</h1>
        <p><strong>Product:</strong> <?php echo htmlspecialchars($product['sku'] . ' - ' . $product['name']); ?></p>
        <p><strong>Warehouse:</strong> <?php echo htmlspecialchars($product['warehouse_name']); ?></p>
        <p><strong>Current Quantity:</strong> <?php echo (int)$product['current_qty']; ?></p>

        <?php if (isset($error)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <label>
                Quantity to Add:
                <input type="number" name="quantity" value="0" min="1" required>
            </label>
            <br><br>
            <button class="btn btn-primary" type="submit">Stock In</button>
            <a class="btn" href="index.php">Cancel</a>
        </form>
    </div>
</body>

</html>