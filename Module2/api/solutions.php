<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    if ($action === 'feedback') {
        $id = (int)($_POST['id'] ?? 0);
        $rating = $_POST['rating'] ?? '';
        if ($id <= 0 || !in_array($rating, ['helpful', 'unhelpful'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO solution_feedback (solution_id, rating) VALUES (?, ?)");
        $stmt->execute([$id, $rating]);
        $pdo->prepare("UPDATE solutions SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Feedback recorded']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}