<?php
require '../../db.php';
require '../../shared/config.php';

function http_post($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $r];
}

// Create a test customer if none
$stmt = $pdo->query("SELECT id FROM customers LIMIT 1");
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    $pdo->exec("INSERT INTO customers (name, code) VALUES ('Test Customer', 'TEST')");
    $customer['id'] = $pdo->lastInsertId();
}
$customer_id = $customer['id'];

// Create a ticket
$data = [
    'action' => 'save',
    'customer_id' => $customer_id,
    'subject' => 'Smoke Test Ticket',
    'description' => 'Testing ticket creation.',
    'priority' => 'medium'
];
list($code, $response) = http_post(BASE_URL . 'Module2/api/tickets.php', $data);
$resp = json_decode($response, true);
if ($code != 200 || $resp['status'] != 'success') {
    echo "Ticket creation failed: $response\n";
    exit;
}
$ticket_id = $resp['id'];

// Verify integration: Get order history
$ch = curl_init(BASE_URL . 'Module8/api/sales_orders.php?action=list&customer_id=' . $customer_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$orders = json_decode(curl_exec($ch), true)['data'] ?? [];
curl_close($ch);
$has_orders = !empty($orders);

// Verify integration: Get finance data
$ch = curl_init(BASE_URL . 'Module5/api/customers.php?action=get&id=' . $customer_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$finance = json_decode(curl_exec($ch), true)['data'] ?? [];
curl_close($ch);
$has_finance = !empty($finance);

echo "Smoke Test Results:\n";
echo "Ticket Created: ID $ticket_id\n";
echo "Order History Available: " . ($has_orders ? 'Yes' : 'No') . "\n";
echo "Finance Data Available: " . ($has_finance ? 'Yes' : 'No') . "\n";

// Cleanup
$stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
echo "Cleanup: Ticket deleted.\n";