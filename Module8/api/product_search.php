<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
	$q = $_GET['q'] ?? '';
	if (!$q) {
		echo json_encode(['status'=>'error','message'=>'Missing query']);
		exit;
	}

	$stmt = $pdo->prepare("SELECT p.id, p.sku, p.name, p.unit_price, COALESCE((SELECT SUM(pl.quantity) FROM product_locations pl WHERE pl.product_id = p.id),0) as qty FROM products p WHERE p.name LIKE ? OR p.sku LIKE ? LIMIT 20");
	$like = "%$q%";
	$stmt->execute([$like,$like]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode(['status'=>'success','data'=>$rows]);
} catch (Exception $e) {
	echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
