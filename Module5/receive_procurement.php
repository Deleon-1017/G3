<?php
// receive_procurement.php
// Receives JSON from Procurement and inserts into accounts_payable
// Place in a path reachable by your Procurement module (adjust include path to db.php if needed)

header('Content-Type: application/json');
try {
    include '../db.php'; // <-- change to ../../db.php if your structure places this file in a subfolder
} catch (Exception $e) {
    echo json_encode(["status"=>"error","message"=>"Database connection error: ".$e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status"=>"error","message"=>"Invalid JSON payload."]);
    exit;
}

// Required fields
$required = ['invoice_number','po_id','supplier_id','amount'];
foreach ($required as $f) {
    if (!isset($data[$f])) {
        echo json_encode(["status"=>"error","message"=>"Missing field: $f"]);
        exit;
    }
}

// sanitize & defaults
$invoice_number = trim($data['invoice_number']);
$po_id = (int)$data['po_id'];
$supplier_id = (int)$data['supplier_id'];
$amount = number_format((float)$data['amount'], 2, '.', '');
$due_date = !empty($data['due_date']) ? $data['due_date'] : null;
$status = isset($data['status']) ? $data['status'] : 'pending';

try {
    $stmt = $pdo->prepare("
        INSERT INTO accounts_payable
          (invoice_number, po_id, supplier_id, amount, due_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $invoice_number,
        $po_id,
        $supplier_id,
        $amount,
        $due_date,
        $status
    ]);

    $id = $pdo->lastInsertId();

    echo json_encode([
        "status" => "success",
        "message" => "Inserted into accounts_payable",
        "id" => $id
    ]);
} catch (Exception $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
