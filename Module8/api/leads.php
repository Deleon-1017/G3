<?php
require '../../db.php';
require '../../shared/config.php';

header('Content-Type: application/json');

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  if ($action === 'list') {
    $where = [];
    $params = [];

    $status = $_GET['status'] ?? '';
    if ($status) {
      $where[] = 'cl.status = ?';
      $params[] = $status;
    }

    $interest_level = $_GET['interest_level'] ?? '';
    if ($interest_level) {
      $where[] = 'cl.interest_level = ?';
      $params[] = $interest_level;
    }

    $search = $_GET['search'] ?? '';
    if ($search) {
      $where[] = '(cl.customer_name LIKE ? OR cl.email LIKE ? OR cl.company_name LIKE ?)';
      $params[] = "%$search%";
      $params[] = "%$search%";
      $params[] = "%$search%";
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT cl.*, u.name as assigned_name FROM customer_leads cl LEFT JOIN users u ON u.id = cl.assigned_to $whereClause ORDER BY cl.lead_id DESC");
    $stmt->execute($params);
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT cl.*, u.name as assigned_name FROM customer_leads cl LEFT JOIN users u ON u.id = cl.assigned_to WHERE cl.lead_id = ?");
    $stmt->execute([$id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lead) {
      echo json_encode(['status'=>'success','data'=>$lead]);
    } else {
      echo json_encode(['status'=>'error','message'=>'Lead not found']);
    }
    exit;
  }

  if ($action === 'add') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $lead_source = $_POST['lead_source'] ?? 'Website';
    $interest_level = $_POST['interest_level'] ?? 'Warm';
    $status = $_POST['status'] ?? 'New';
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$customer_name || !$email || !$contact_number || !$assigned_to) {
      echo json_encode(['status'=>'error','message'=>'Required fields missing']);
      exit;
    }

    $stmt = $pdo->prepare("INSERT INTO customer_leads (customer_name, company_name, email, contact_number, lead_source, interest_level, status, assigned_to, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$customer_name, $company_name, $email, $contact_number, $lead_source, $interest_level, $status, $assigned_to, $remarks]);
    $id = $pdo->lastInsertId();
    echo json_encode(['status'=>'success','id'=>$id]);
    exit;
  }

  if ($action === 'update') {
    $id = (int)($_POST['lead_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $lead_source = $_POST['lead_source'] ?? 'Website';
    $interest_level = $_POST['interest_level'] ?? 'Warm';
    $status = $_POST['status'] ?? 'New';
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    $converted_customer_id = isset($_POST['converted_customer_id']) ? (int)$_POST['converted_customer_id'] : null;

    if (!$id || !$customer_name || !$email || !$contact_number || !$assigned_to) {
      echo json_encode(['status'=>'error','message'=>'Required fields missing']);
      exit;
    }

    // If status is 'Converted' and no converted_customer_id is provided, create a new customer
    if ($status === 'Converted' && !$converted_customer_id) {
      // Check if a customer with this email already exists
      $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
      $checkStmt->execute([$email]);
      $existingCustomer = $checkStmt->fetch(PDO::FETCH_ASSOC);

      if ($existingCustomer) {
        $converted_customer_id = $existingCustomer['id'];
      } else {
        // Create new customer
        $insertStmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$customer_name, $email, $contact_number, $company_name]);
        $converted_customer_id = $pdo->lastInsertId();

        // Generate customer code
        $code = 'CUST-' . str_pad($converted_customer_id, 3, '0', STR_PAD_LEFT);
        $updateStmt = $pdo->prepare("UPDATE customers SET code = ? WHERE id = ?");
        $updateStmt->execute([$code, $converted_customer_id]);

        // Insert into customer_finance
        $financeStmt = $pdo->prepare("INSERT INTO customer_finance (customer_id, credit_limit, outstanding_balance) VALUES (?, 10000, 0)");
        $financeStmt->execute([$converted_customer_id]);
      }
    }

    $stmt = $pdo->prepare("UPDATE customer_leads SET customer_name=?, company_name=?, email=?, contact_number=?, lead_source=?, interest_level=?, status=?, assigned_to=?, remarks=?, converted_customer_id=? WHERE lead_id=?");
    $stmt->execute([$customer_name, $company_name, $email, $contact_number, $lead_source, $interest_level, $status, $assigned_to, $remarks, $converted_customer_id, $id]);
    echo json_encode(['status'=>'success']);
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['status'=>'error','message'=>'ID required']);
      exit;
    }
    $stmt = $pdo->prepare("DELETE FROM customer_leads WHERE lead_id=?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success']);
    exit;
  }

  if ($action === 'users') {
    $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  echo json_encode(['status'=>'error','message'=>'Invalid action']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
