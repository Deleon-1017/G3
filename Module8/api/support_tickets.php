<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  if ($action === 'list') {
    $stmt = $pdo->query("SELECT t.*, c.name as customer_name FROM support_tickets t LEFT JOIN customers c ON c.id = t.customer_id ORDER BY t.id DESC");
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }
  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $t = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ?"); $t->execute([$id]);
    $com = $pdo->prepare("SELECT * FROM communications WHERE ticket_id = ? ORDER BY id ASC"); $com->execute([$id]);
    echo json_encode(['status'=>'success','data'=>$t->fetch(PDO::FETCH_ASSOC),'communications'=>$com->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }
  if ($action === 'save') {
    $id = $_POST['id'] ?? null;
    $ticket_number = $_POST['ticket_number'] ?? 'T-' . time();
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $subject = $_POST['subject'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'open';
    $priority = $_POST['priority'] ?? 'medium';
    $assigned_to = $_POST['assigned_to'] ?? null;
    if ($id) {
      $pdo->prepare("UPDATE support_tickets SET ticket_number=?, customer_id=?, subject=?, description=?, status=?, priority=?, assigned_to=? WHERE id = ?")
        ->execute([$ticket_number,$customer_id,$subject,$description,$status,$priority,$assigned_to,$id]);
    } else {
      $pdo->prepare("INSERT INTO support_tickets (ticket_number,customer_id,subject,description,status,priority,assigned_to) VALUES (?,?,?,?,?,?,?)")
        ->execute([$ticket_number,$customer_id,$subject,$description,$status,$priority,$assigned_to]);
      $id = $pdo->lastInsertId();
    }
    echo json_encode(['status'=>'success','id'=>$id]);
    exit;
  }
  if ($action === 'add_message') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $message = $_POST['message'] ?? '';
    $sender = $_POST['sender'] ?? 'agent';
    $pdo->prepare("INSERT INTO communications (ticket_id,customer_id,message,sender) VALUES (?,?,?,?)")->execute([$ticket_id,$customer_id,$message,$sender]);
    echo json_encode(['status'=>'success']);
    exit;
  }
  if ($action === 'delete') { $id = (int)($_POST['id'] ?? 0); $pdo->prepare("DELETE FROM support_tickets WHERE id = ?")->execute([$id]); echo json_encode(['status'=>'success']); exit; }

  echo json_encode(['status'=>'error','message'=>'Invalid action']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
