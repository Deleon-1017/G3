<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  if ($action === 'list') {
    $stmt = $pdo->query("SELECT q.*, c.name as customer_name FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id ORDER BY q.id DESC");
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }
  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?"); $stmt->execute([$id]);
    $q = $stmt->fetch(PDO::FETCH_ASSOC);
    $customer_stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $customer_stmt->execute([$q['customer_id']]);
    $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
    $q['customer_name'] = $customer['name'] ?? '';
    $it = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?"); $it->execute([$id]);
    echo json_encode(['status'=>'success','data'=>$q,'items'=>$it->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }
  if ($action === 'save') {
    $id = $_POST['id'] ?? null;
    $quote_number = $_POST['quote_number'] ?? null;
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $subtotal = (float)($_POST['subtotal'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $total = (float)($_POST['total'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? null;
    $status = $_POST['status'] ?? 'draft';
    $notes = $_POST['notes'] ?? null;
    $terms = $_POST['terms'] ?? null;
    $created_by = $_POST['created_by'] ?? null;
    if (!$quote_number) {
      $year = date('Y');
      $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM quotations WHERE YEAR(created_at) = $year");
      $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] + 1;
      $quote_number = 'Q-' . $year . '-' . str_pad($cnt, 4, '0', STR_PAD_LEFT);
    }
    if ($id) {
      $pdo->prepare("UPDATE quotations SET quote_number=?, customer_id=?, subtotal=?, discount=?, tax=?, total=?, expiry_date=?, status=?, notes=?, terms=?, created_by=? WHERE id = ?")
        ->execute([$quote_number,$customer_id,$subtotal,$discount,$tax,$total,$expiry_date,$status,$notes,$terms,$created_by,$id]);
    } else {
      $pdo->prepare("INSERT INTO quotations (quote_number,customer_id,subtotal,discount,tax,total,expiry_date,status,notes,terms,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$quote_number,$customer_id,$subtotal,$discount,$tax,$total,$expiry_date,$status,$notes,$terms,$created_by]);
      $id = $pdo->lastInsertId();
    }
    // items
    $items = json_decode($_POST['items'] ?? '[]', true);
    if ($items) {
      $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);
      $ins = $pdo->prepare("INSERT INTO quotation_items (quotation_id,product_id,description,qty,unit_price,line_total) VALUES (?,?,?,?,?,?)");
      foreach ($items as $it) {
        // Fetch unit_price from products table
        $prod_stmt = $pdo->prepare("SELECT unit_price FROM products WHERE id = ?");
        $prod_stmt->execute([$it['product_id']]);
        $prod = $prod_stmt->fetch(PDO::FETCH_ASSOC);
        $unit_price = $prod ? (float)$prod['unit_price'] : 0;
        $line_total = $unit_price * (float)$it['qty'];
        $ins->execute([$id,$it['product_id'],$it['description'],$it['qty'],$unit_price,$line_total]);
      }
    }
    echo json_encode(['status'=>'success','id'=>$id]);
    exit;
  }
  if ($action === 'delete') { $id = (int)($_POST['id'] ?? 0); $pdo->prepare("DELETE FROM quotations WHERE id = ?")->execute([$id]); echo json_encode(['status'=>'success']); exit; }
  echo json_encode(['status'=>'error','message'=>'Invalid action']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
