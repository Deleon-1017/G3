<?php
require '../../db.php';
require '../../shared/config.php';

header('Content-Type: application/json');

try {
  $id = (int)($_GET['id'] ?? 0);
  $order_id = (int)($_GET['order_id'] ?? 0);
  $amount = (float)($_GET['amount'] ?? 0);
  if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
  }

  // Check customer credit status for the order amount
  $stmt = $pdo->prepare("SELECT credit_limit, outstanding_balance FROM customer_finance WHERE customer_id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'No credit record found for customer']);
    exit;
  }

  // Check if outstanding balance is less than the order total (after discount and tax)
  if ($row['outstanding_balance'] < $amount) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
    exit;
  }

  echo json_encode(['status' => 'success']);
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
