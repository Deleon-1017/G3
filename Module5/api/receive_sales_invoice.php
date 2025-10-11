<?php
// Receives sales invoice data from Sales module (Module8) and inserts into accounts_receivable
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode(['status'=>'error','message'=>'Invalid JSON']);
        exit;
    }

    // minimal required fields
    $required = ['order_id','order_number','customer_id','total'];
    foreach ($required as $f) {
        if (!isset($data[$f])) {
            echo json_encode(['status'=>'error','message'=>'Missing field: '.$f]);
            exit;
        }
    }

    $inv = 'SI-' . date('Ymd') . '-' . str_pad(rand(1,999),3,'0',STR_PAD_LEFT);
    $order_id = (int)$data['order_id'];
    $customer_id = (int)$data['customer_id'];
    $amount = number_format((float)$data['total'],2,'.','');

    // Insert into accounts_receivable (create table if missing)
    $pdo->exec("CREATE TABLE IF NOT EXISTS accounts_receivable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(50) NOT NULL,
        order_id INT DEFAULT NULL,
        customer_id INT DEFAULT NULL,
        amount DECIMAL(12,2) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'pending',
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY (invoice_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare("INSERT INTO accounts_receivable (invoice_number, order_id, customer_id, amount, status, created_at) VALUES (?,?,?,?,?,NOW())");
    $stmt->execute([$inv,$order_id,$customer_id,$amount,'pending']);

    echo json_encode(['status'=>'success','invoice_number'=>$inv]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
