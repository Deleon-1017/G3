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
  } elseif ($row['outstanding_balance'] == 0.00) {
    echo json_encode(['status' => 'error', 'message' => 'Outstanding balance is zero, cannot confirm order']);
  } elseif ($order_id > 0) {
    // Check if outstanding balance is less than any unit price in the order
    $stmt_items = $pdo->prepare("SELECT unit_price FROM sales_order_items WHERE sales_order_id = ?");
    $stmt_items->execute([$order_id]);
    $has_insufficient = false;
    while ($item = $stmt_items->fetch(PDO::FETCH_ASSOC)) {
      if ($row['outstanding_balance'] < $item['unit_price']) {
        $has_insufficient = true;
        break;
      }
    }
    if ($has_insufficient) {
      echo json_encode(['status' => 'error', 'message' => 'Outstanding balance is less than unit price of some products']);
    } elseif ($row['outstanding_balance'] + $amount > $row['credit_limit']) {
      echo json_encode(['status' => 'error', 'message' => 'Insufficient credit for this order']);
    } else {
      echo json_encode(['status' => 'success']);
    }
  } else {
    // Fallback if no order_id provided
    if ($row['outstanding_balance'] + $amount > $row['credit_limit']) {
      echo json_encode(['status' => 'error', 'message' => 'Insufficient credit for this order']);
    } else {
      echo json_encode(['status' => 'success']);
    }
  }
} catch (Exception $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
