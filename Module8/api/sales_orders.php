<?php
require '../../db.php';
require '../../shared/config.php';

header('Content-Type: application/json');

// Helper: simple curl GET for other modules
function http_get($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  $r = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $r];
}

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  if ($action === 'list') {
    $stmt = $pdo->query("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON c.id = so.customer_id ORDER BY so.id DESC");
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT so.*, c.name as customer_name FROM sales_orders so LEFT JOIN customers c ON c.id = so.customer_id WHERE so.id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    $items = $pdo->prepare("SELECT * FROM sales_order_items WHERE sales_order_id = ?");
    $items->execute([$id]);
    echo json_encode(['status'=>'success','data'=>$order,'items'=>$items->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  if ($action === 'save') {
    // Save order (create or update). Does not finalize stock deduction.
    $id = $_POST['id'] ?? null;
    $order_number = $_POST['order_number'] ?? 'SO-' . time();
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $subtotal = (float)($_POST['subtotal'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $total = (float)($_POST['total'] ?? 0);

    if ($id) {
      $stmt = $pdo->prepare("UPDATE sales_orders SET order_number=?, customer_id=?, subtotal=?, discount=?, tax=?, total=? WHERE id=?");
      $stmt->execute([$order_number,$customer_id,$subtotal,$discount,$tax,$total,$id]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO sales_orders (order_number,customer_id,subtotal,discount,tax,total) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$order_number,$customer_id,$subtotal,$discount,$tax,$total]);
      $id = $pdo->lastInsertId();
    }

    // Save items: expect items[] as JSON
    $items = json_decode($_POST['items'] ?? '[]', true);
    if ($items) {
      // remove old
      $pdo->prepare("DELETE FROM sales_order_items WHERE sales_order_id = ?")->execute([$id]);
      $ins = $pdo->prepare("INSERT INTO sales_order_items (sales_order_id,product_id,description,qty,unit_price,line_total) VALUES (?,?,?,?,?,?)");
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

  if ($action === 'confirm') {
    // Confirm order: check inventory via Module1 and finance via Module5
    $id = (int)($_POST['id'] ?? 0);
    // Load order items
    $stmt = $pdo->prepare("SELECT * FROM sales_order_items WHERE sales_order_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check stock for each item using Module1 (assumes Module1 exposes an endpoint to get product qty)
    $insufficient = [];
    foreach ($items as $it) {
      // call Module1 inventory read — using product id
      $url = BASE_URL . "Module1/index.php?product_id=" . $it['product_id'];
      list($code,$resp) = http_get($url);
      // naive parsing: Module1 may render HTML; instead check product_locations table directly
      $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) as qty FROM product_locations WHERE product_id = ?");
      $stmt2->execute([$it['product_id']]);
      $row = $stmt2->fetch(PDO::FETCH_ASSOC);
      $available = (int)$row['qty'];
      if ($available < $it['qty']) {
        $insufficient[] = ['product_id'=>$it['product_id'],'available'=>$available,'requested'=>$it['qty']];
      }
    }

    if (count($insufficient)) {
      echo json_encode(['status'=>'error','message'=>'Insufficient stock','details'=>$insufficient]);
      exit;
    }

    // Query finance for customer credit/payment history — assumes Module5 provides API endpoint /Module5/api/customer_credit.php?id=...&amount=...
    $order = $pdo->prepare("SELECT * FROM sales_orders WHERE id = ?");
    $order->execute([$id]);
    $order = $order->fetch(PDO::FETCH_ASSOC);
    $custId = $order['customer_id'];
    // Check customer credit for the order total
    list($code,$finresp) = http_get(BASE_URL . "Module5/api/customer_credit.php?id={$custId}&order_id={$id}&amount={$order['total']}");
    if ($code == 200) {
      $fin = json_decode($finresp, true);
      if (isset($fin['status']) && $fin['status'] !== 'success') {
        echo json_encode(['status'=>'error','message'=>$fin['message'] ?? 'Credit check failed']);
        exit;
      }
    } else {
      echo json_encode(['status'=>'error','message'=>'Credit check service unavailable']);
      exit;
    }

    // Begin a DB transaction for atomicity
    $pdo->beginTransaction();
    try {
      // For each item, allocate stock possibly from multiple locations
      foreach ($items as $it) {
        $product_id = (int)$it['product_id'];
        $need = (int)$it['qty'];

        // allocate across product_locations (inventory module) as before: lock and deduct
        $allocated = 0;
        $stmtLocs = $pdo->prepare("SELECT location_id, quantity FROM product_locations WHERE product_id = ? AND quantity > 0 ORDER BY quantity DESC FOR UPDATE");
        $stmtLocs->execute([$product_id]);
        $locs = $stmtLocs->fetchAll(PDO::FETCH_ASSOC);
        foreach ($locs as $loc) {
          if ($allocated >= $need) break;
          $available = (int)$loc['quantity'];
          $take = min($available, $need - $allocated);
          if ($take <= 0) continue;
          $upd = $pdo->prepare("UPDATE product_locations SET quantity = quantity - ? WHERE product_id = ? AND location_id = ?");
          $upd->execute([$take, $product_id, $loc['location_id']]);
          $pdo->prepare("UPDATE products SET total_quantity = total_quantity - ? WHERE id = ?")->execute([$take, $product_id]);
          $pdo->prepare("INSERT INTO stock_transactions (product_id, location_from, location_to, qty, type, note, trans_date) VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$product_id, $loc['location_id'], NULL, $take, 'OUT', 'Sales order #'.$order['order_number']]);
          $allocated += $take;
        }

        if ($allocated < $need) {
          // Not enough stock despite earlier check (race condition or concurrent change)
          throw new Exception('Insufficient stock for product_id '.$product_id.' (needed '.$need.', allocated '.$allocated.')');
        }
      }

      // Mark order as processed and set confirmed_at
      $pdo->prepare("UPDATE sales_orders SET status='processed', confirmed_at = NOW() WHERE id = ?")->execute([$id]);

      // Commit transaction before calling external service (better to reflect DB final state first)
      $pdo->commit();

      // Generate invoice and notify Finance module (simple payload)
      $finance_payload = [
        'order_id' => $id,
        'order_number' => $order['order_number'],
        'customer_id' => $order['customer_id'],
        'total' => $order['total']
      ];
      $ch = curl_init(BASE_URL . 'Module5/api/receive_sales_invoice.php');
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($finance_payload));
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $resp = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      // Record sync outcome in sales_orders and invoice_sync_logs
      $success = ($http_code == 200);
      $finance_txn_id = null;
      $error_msg = null;
      $resp_json = null;
      $decoded = json_decode($resp, true);
      if (is_array($decoded) && isset($decoded['invoice_number'])) {
        $finance_txn_id = $decoded['invoice_number'];
        $resp_json = json_encode($decoded);
      } else {
        $error_msg = substr($resp ?: '', 0, 2000);
        $resp_json = $resp;
      }

      $upd = $pdo->prepare("UPDATE sales_orders SET finance_transaction_id = ?, finance_sync_status = ?, finance_sync_error = ? WHERE id = ?");
      $upd->execute([$finance_txn_id, $success ? 'synced' : 'failed', $error_msg, $id]);

      // Decrease outstanding balance by order total
      $reduce_balance = $pdo->prepare("UPDATE customer_finance SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE customer_id = ?");
      $reduce_balance->execute([$order['total'], $order['customer_id']]);

      $inslog = $pdo->prepare("INSERT INTO invoice_sync_logs (sales_order_id, http_code, response, success) VALUES (?,?,?,?)");
      $inslog->execute([$id, $http_code, $resp_json, $success ? 1 : 0]);

      echo json_encode(['status'=>'success','invoice_sync'=>$success,'http_code'=>$http_code,'response'=>$decoded ?? $resp]);
      exit;
    } catch (Exception $ex) {
      // Rollback and report
      $pdo->rollBack();
      echo json_encode(['status'=>'error','message'=>'Order confirmation failed: '.$ex->getMessage()]);
      exit;
    }
  }

  if ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    if (!in_array($new_status, ['shipped', 'delivered'])) {
      echo json_encode(['status'=>'error','message'=>'Invalid status']);
      exit;
    }
    $expected_status = ($new_status === 'shipped') ? 'processed' : 'shipped';
    $stmt = $pdo->prepare("UPDATE sales_orders SET status = ? WHERE id = ? AND status = ?");
    $stmt->execute([$new_status, $id, $expected_status]);
    if ($stmt->rowCount() > 0) {
      echo json_encode(['status'=>'success']);
    } else {
      echo json_encode(['status'=>'error','message'=>'Order not found or not in the correct status']);
    }
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // Check if order is pending
    $stmt = $pdo->prepare("SELECT status FROM sales_orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
      echo json_encode(['status'=>'error','message'=>'Order not found']);
      exit;
    }
    if ($order['status'] !== 'pending') {
      echo json_encode(['status'=>'error','message'=>'Only pending orders can be deleted']);
      exit;
    }
    // Delete items first
    $pdo->prepare("DELETE FROM sales_order_items WHERE sales_order_id = ?")->execute([$id]);
    // Delete order
    $pdo->prepare("DELETE FROM sales_orders WHERE id = ?")->execute([$id]);
    echo json_encode(['status'=>'success']);
    exit;
  }

  echo json_encode(['status'=>'error','message'=>'Invalid action']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
