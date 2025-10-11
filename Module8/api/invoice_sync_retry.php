<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
  // Find orders with failed or pending sync in the last 7 days
  $stmt = $pdo->query("SELECT id, order_number, customer_id, total FROM sales_orders WHERE finance_sync_status IN ('failed','pending') ORDER BY id DESC LIMIT 50");
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $results = [];
  foreach ($orders as $o) {
    $payload = ['order_id'=>$o['id'],'order_number'=>$o['order_number'],'customer_id'=>$o['customer_id'],'total'=>$o['total']];
    $ch = curl_init(BASE_URL . 'Module5/api/receive_sales_invoice.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($resp, true);
    $success = ($http_code == 200 && is_array($decoded) && isset($decoded['invoice_number']));
    $finance_txn_id = $success ? $decoded['invoice_number'] : null;
    $error_msg = $success ? null : substr($resp ?: '', 0, 2000);

    $upd = $pdo->prepare("UPDATE sales_orders SET finance_transaction_id = ?, finance_sync_status = ?, finance_sync_error = ? WHERE id = ?");
    $upd->execute([$finance_txn_id, $success ? 'synced' : 'failed', $error_msg, $o['id']]);

    $ins = $pdo->prepare("INSERT INTO invoice_sync_logs (sales_order_id, http_code, response, success) VALUES (?,?,?,?)");
    $ins->execute([$o['id'],$http_code,$resp,$success ? 1 : 0]);

    $results[] = ['order_id'=>$o['id'],'success'=>$success,'http_code'=>$http_code,'response'=>$decoded ?? $resp];
  }

  echo json_encode(['status'=>'success','results'=>$results]);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
