<?php
require '../../db.php';
require '../../shared/config.php';

header('Content-Type: application/json');

try {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
    exit;
  }

  // Check customer credit status
  $stmt = $pdo->prepare("SELECT credit_limit, outstanding_balance FROM customer_finance WHERE customer_id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    // If no record, assume good credit
    echo json_encode(['status' => 'success']);
  } elseif ($row['outstanding_balance'] >= $row['credit_limit']) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient credit']);
  } else {
    echo json_encode(['status' => 'success']);
  }
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
