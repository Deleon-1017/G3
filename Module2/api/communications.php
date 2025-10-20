<?php
require '../../db.php';
require '../../shared/config.php';
header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_followup') {
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $ticket_id = !empty($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : null;
        $message = trim($_POST['message'] ?? '');
        $automated = !empty($_POST['automated']);

        if ($customer_id <= 0 || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }

        // Insert the follow-up message
        $sender = 'agent';
        if ($ticket_id) {
            // Link to specific ticket
            $stmt = $pdo->prepare("INSERT INTO communications (ticket_id, customer_id, message, sender) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $customer_id, $message, $sender]);
        } else {
            // General message not linked to a ticket
            $stmt = $pdo->prepare("INSERT INTO communications (customer_id, message, sender) VALUES (?, ?, ?)");
            $stmt->execute([$customer_id, $message, $sender]);
        }

        // Redirect back to the communication history page
        header('Location: ../customer_communications.php?customer_id=' . $customer_id . '&success=1');
        exit;
    }

    if ($action === 'get_history') {
        $customer_id = (int)($_GET['customer_id'] ?? 0);
        
        if ($customer_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.*, t.ticket_number, t.subject as ticket_subject
            FROM communications c
            LEFT JOIN support_tickets t ON c.ticket_id = t.id
            WHERE c.customer_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $communications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $communications]);
        exit;
    }

    if ($action === 'bulk_followup') {
        // Send follow-up to multiple customers
        $customer_ids = $_POST['customer_ids'] ?? [];
        $message = trim($_POST['message'] ?? '');

        if (empty($customer_ids) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }

        $count = 0;
        foreach ($customer_ids as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                $stmt = $pdo->prepare("INSERT INTO communications (customer_id, message, sender) VALUES (?, ?, 'agent')");
                $stmt->execute([$cid, $message]);
                $count++;
            }
        }

        echo json_encode(['status' => 'success', 'message' => "Sent follow-ups to $count customers"]);
        exit;
    }

    if ($action === 'export_history') {
        $customer_id = (int)($_GET['customer_id'] ?? 0);
        
        if ($customer_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.*, t.ticket_number, t.subject as ticket_subject, cu.name as customer_name
            FROM communications c
            LEFT JOIN support_tickets t ON c.ticket_id = t.id
            LEFT JOIN customers cu ON c.customer_id = cu.id
            WHERE c.customer_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $communications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customer_communications_' . $customer_id . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Sender', 'Ticket #', 'Subject', 'Message']);
        
        foreach ($communications as $comm) {
            fputcsv($output, [
                $comm['created_at'],
                ucfirst($comm['sender']),
                $comm['ticket_number'] ?? 'N/A',
                $comm['ticket_subject'] ?? 'General',
                $comm['message']
            ]);
        }
        
        fclose($output);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>