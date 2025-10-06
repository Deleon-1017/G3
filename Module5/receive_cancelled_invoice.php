<?php
include '../db.php'; // ✅ must match other Finance files
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['invoice_number'])) {
    echo json_encode(["status" => "error", "message" => "Missing invoice_number"]);
    exit;
}

$invoice_number = trim($data['invoice_number']);

try {
    // ✅ Step 1: Check if the invoice exists (case-insensitive)
    $check = $pdo->prepare("SELECT id, status FROM accounts_payable WHERE LOWER(invoice_number) = LOWER(?)");
    $check->execute([$invoice_number]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(["status" => "warning", "message" => "Invoice not found in Finance."]);
        exit;
    }

    // ✅ Step 2: Mark as cancelled (soft delete)
    $stmt = $pdo->prepare("
        UPDATE accounts_payable
        SET status = 'cancelled'
        WHERE LOWER(invoice_number) = LOWER(?)
    ");
    $stmt->execute([$invoice_number]);

    echo json_encode([
        "status" => "success",
        "message" => "Invoice marked as cancelled in Finance.",
        "invoice_number" => $invoice_number
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
