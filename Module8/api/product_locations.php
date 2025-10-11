<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
    $product_id = (int)($_GET['product_id'] ?? 0);
    if (!$product_id) {
        echo json_encode(['status'=>'error','message'=>'Missing product_id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT pl.location_id, pl.quantity, l.code as location_code, l.name as location_name FROM product_locations pl LEFT JOIN locations l ON l.id = pl.location_id WHERE pl.product_id = ? AND pl.quantity > 0 ORDER BY pl.quantity DESC");
    $stmt->execute([$product_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success','data'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
