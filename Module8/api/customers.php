<?php
require '../../db.php';
require '../../shared/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
  if ($action === 'list') {
    $q = $_GET['q'] ?? '';
    if ($q) {
      $stmt = $pdo->prepare("SELECT c.*, cf.outstanding_balance FROM customers c LEFT JOIN customer_finance cf ON c.id = cf.customer_id WHERE c.name LIKE ? OR c.code LIKE ? ORDER BY c.id DESC");
      $stmt->execute(["%$q%","%$q%"]);
    } else {
      $stmt = $pdo->query("SELECT c.*, cf.outstanding_balance FROM customers c LEFT JOIN customer_finance cf ON c.id = cf.customer_id ORDER BY c.id DESC");
    }
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT c.*, cf.credit_limit, cf.outstanding_balance FROM customers c LEFT JOIN customer_finance cf ON c.id = cf.customer_id WHERE c.id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
      $data['credit_limit'] = $data['credit_limit'] ?? 10000;
      $data['outstanding_balance'] = $data['outstanding_balance'] ?? 0;
    }
    echo json_encode(['status'=>'success','data'=>$data]);
    exit;
  }

  if ($action === 'save') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $credit_limit = (float)($_POST['credit_limit'] ?? 10000);
    $outstanding_balance = (float)($_POST['outstanding_balance'] ?? 0);
    $code = $_POST['code'] ?? null;
    $notes = $_POST['notes'] ?? null; // legacy

    // Basic validation: name required, and at least one contact (email or phone)
    if ($name === '') {
      echo json_encode(['status'=>'error','message'=>'Name is required']); exit;
    }
    if ($email === '' && $phone === '') {
      echo json_encode(['status'=>'error','message'=>'Provide at least an email or phone number']); exit;
    }

    if ($id) {
      $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
      $stmt->execute([$name,$email,$phone,$address,$id]);
      // Update or insert customer_finance
      $stmt2 = $pdo->prepare("INSERT INTO customer_finance (customer_id, credit_limit, outstanding_balance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE credit_limit = VALUES(credit_limit), outstanding_balance = VALUES(outstanding_balance)");
      $stmt2->execute([$id, $credit_limit, $outstanding_balance]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO customers (name,email,phone,address,notes) VALUES (?,?,?,?,?)");
      $stmt->execute([$name,$email,$phone,$address,$notes]);
      $new_id = $pdo->lastInsertId();
      $code = $code ?: 'CUST-' . str_pad($new_id, 3, '0', STR_PAD_LEFT);
      $stmt = $pdo->prepare("UPDATE customers SET code=? WHERE id=?");
      $stmt->execute([$code, $new_id]);
      // Insert customer_finance
      $stmt2 = $pdo->prepare("INSERT INTO customer_finance (customer_id, credit_limit, outstanding_balance) VALUES (?, ?, ?)");
      $stmt2->execute([$new_id, $credit_limit, $outstanding_balance]);
    }
    echo json_encode(['status'=>'success']);
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success']);
    exit;
  }

  echo json_encode(['status'=>'error','message'=>'Invalid action']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

