<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'list') {
        $stmt = $pdo->query("SELECT t.*, c.name as customer_name FROM support_tickets t LEFT JOIN customers c ON c.id = t.customer_id ORDER BY t.id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT t.*, c.name as customer_name FROM support_tickets t LEFT JOIN customers c ON c.id = t.customer_id WHERE t.id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        $comm_stmt = $pdo->prepare("SELECT * FROM communications WHERE ticket_id = ? ORDER BY created_at ASC");
        $comm_stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'data' => $ticket, 'communications' => $comm_stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'save') {
        $id = $_POST['id'] ?? null;
        $ticket_number = $_POST['ticket_number'] ?? 'T-' . time();
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'open';
        $priority = $_POST['priority'] ?? 'medium';
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);

        if (empty($subject) || $customer_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE support_tickets SET ticket_number=?, customer_id=?, subject=?, description=?, status=?, priority=?, assigned_to=? WHERE id=?");
            $stmt->execute([$ticket_number, $customer_id, $subject, $description, $status, $priority, $assigned_to, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO support_tickets (ticket_number, customer_id, subject, description, status, priority, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ticket_number, $customer_id, $subject, $description, $status, $priority, $assigned_to]);
            $id = $pdo->lastInsertId();
        }
        echo json_encode(['status' => 'success', 'id' => $id]);
        exit;
    }

    if ($action === 'add_communication') {
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $sender = $_POST['sender'] ?? 'agent';

        if ($ticket_id <= 0 || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO communications (ticket_id, customer_id, message, sender) SELECT ?, customer_id, ?, ? FROM support_tickets WHERE id = ?");
        $stmt->execute([$ticket_id, $message, $sender, $ticket_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}