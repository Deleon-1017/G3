<?php
require '../../db.php';
require '../../shared/config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['order_id'], $input['order_number'], $input['customer_id'], $input['total'])) {
        throw new Exception('Invalid input data');
    }

    $order_id = (int)$input['order_id'];
    $order_number = trim($input['order_number']);
    $customer_id = (int)$input['customer_id'];
    $amount = (float)$input['total'];

    // Generate invoice number
    $invoice_number = 'SI-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

    // Insert into accounts_receivable
    $stmt = $pdo->prepare("INSERT INTO accounts_receivable (invoice_number, order_id, customer_id, amount, status, created_at) VALUES (?, ?, ?, ?, 'unpaid', NOW())");
    $stmt->execute([$invoice_number, $order_id, $customer_id, $amount]);

    echo json_encode(['status' => 'success', 'invoice_number' => $invoice_number]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
