<?php
// Simple smoke test: create an order and confirm it, verifying stock deduction
require '../../db.php';

function http_post($url, $data){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $r];
}

// Find a product with available stock (sum across locations)
$stmt = $pdo->query("SELECT p.id as product_id, COALESCE(SUM(pl.quantity),0) as total_qty FROM products p JOIN product_locations pl ON p.id = pl.product_id WHERE pl.quantity > 0 GROUP BY p.id HAVING total_qty > 0 LIMIT 1");
$prod = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prod) { echo "No product with stock available for smoke test\n"; exit; }
$product_id = $prod['product_id'];
$orig_qty = (int)$prod['total_qty'];

// Create a temporary customer
$pdo->exec("INSERT INTO customers (name, code, notes) VALUES ('SMOKE_TEST','SMK','temp')");
$customer_id = $pdo->lastInsertId();

// Prepare order payload
$order_number = 'SMK-'.time();
$items = [[
    'product_id' => $product_id,
    'description' => 'Smoke product',
    'qty' => 1,
    'unit_price' => 1.00,
    'line_total' => 1.00
]];

$saveData = [
    'customer_id' => $customer_id,
    'order_number' => $order_number,
    'subtotal' => 1.00,
    'discount' => 0,
    'tax' => 0,
    'total' => 1.00,
    'items' => json_encode($items)
];
list($c,$r) = http_post('http://localhost/Group3/Module8/api/sales_orders.php?action=save', $saveData);
$resp = json_decode($r, true);
if (!isset($resp['status']) || $resp['status'] !== 'success') { echo "Save failed: $r\n"; exit; }
$order_id = $resp['id'];

// Confirm order
list($c,$r) = http_post('http://localhost/Group3/Module8/api/sales_orders.php?action=confirm', ['id' => $order_id]);
$resp = json_decode($r, true);
if (!isset($resp['status']) || $resp['status'] !== 'success') { echo "Confirm failed: $r\n"; // attempt cleanup
    // cleanup
    $pdo->prepare('DELETE FROM sales_orders WHERE id = ?')->execute([$order_id]);
    $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$customer_id]);
    exit;
}

// Check total quantity decreased by 1 (sum across locations)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM product_locations WHERE product_id = ?");
$stmt->execute([$product_id]);
$new_qty = (int)$stmt->fetchColumn();

echo "Original total qty: $orig_qty, New total qty: $new_qty\n";
if ($new_qty === $orig_qty - 1) {
    echo "Smoke test passed: total quantity decreased by 1\n";
} else {
    echo "Smoke test WARNING: expected decrease by 1, got difference " . ($orig_qty - $new_qty) . "\n";
}

// Cleanup: restore stock by adding back 1 across locations (simple approach: add back to first location row)
$stmt = $pdo->prepare("SELECT id FROM product_locations WHERE product_id = ? ORDER BY quantity DESC LIMIT 1");
$stmt->execute([$product_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $pdo->prepare('UPDATE product_locations SET quantity = quantity + 1 WHERE id = ?')->execute([$row['id']]);
    $pdo->prepare('UPDATE products SET total_quantity = total_quantity + 1 WHERE id = ?')->execute([$product_id]);
    echo "Restored stock by +1 to product_locations.id {$row['id']}\n";
}

// Cleanup: delete order and customer
$pdo->prepare('DELETE FROM sales_orders WHERE id = ?')->execute([$order_id]);
$pdo->prepare('DELETE FROM sales_order_items WHERE sales_order_id = ?')->execute([$order_id]);
$pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$customer_id]);

?>
