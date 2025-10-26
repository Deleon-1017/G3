<?php
require '../db.php';
require '../shared/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

include '../shared/sidebar.php';

// Get ticket details
$stmt = $pdo->prepare("SELECT t.*, c.name as customer_name FROM support_tickets t LEFT JOIN customers c ON c.id = t.customer_id WHERE t.id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) {
    header('Location: index.php');
    exit;
}

// Get communications
$comm_stmt = $pdo->prepare("SELECT * FROM communications WHERE ticket_id = ? ORDER BY created_at ASC");
$comm_stmt->execute([$id]);
$communications = $comm_stmt->fetchAll(PDO::FETCH_ASSOC);

// Integration: Get sales order history from Module8 (read)
$order_history = [];
$ch = curl_init(BASE_URL . 'Module8/api/sales_orders.php?action=list&customer_id=' . $ticket['customer_id']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$all_orders = json_decode($response, true)['data'] ?? [];
foreach ($all_orders as $order) {
    if ($order['customer_id'] == $ticket['customer_id']) {
        $order_history[] = $order;
    }
}

// Integration: Get finance status from Module5 (read)
$finance_status = [];
$ch = curl_init(BASE_URL . 'Module5/api/customers.php?action=get&id=' . $ticket['customer_id']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$finance_data = json_decode($response, true)['data'] ?? [];
$finance_status = ['outstanding_balance' => $finance_data['outstanding_balance'] ?? 0, 'credit_limit' => $finance_data['credit_limit'] ?? 10000];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?php echo $ticket['ticket_number']; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Ticket Details: <?php echo $ticket['subject']; ?></h1>
        
        <div class="card">
            <h2>Ticket Info</h2>
            <p><strong>Customer:</strong> <?php echo $ticket['customer_name']; ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($ticket['status']); ?></p>
            <p><strong>Priority:</strong> <?php echo ucfirst($ticket['priority']); ?></p>
            <p><strong>Description:</strong> <?php echo nl2br($ticket['description']); ?></p>
            <div class="action-links">
                <a href="update_ticket.php?id=<?php echo $id; ?>" class="btn-link">Edit</a>
                <a href="delete_ticket.php?id=<?php echo $id; ?>" class="btn-link" onclick="return confirm('Delete ticket?');">Delete</a>
            </div>
        </div>

        <div class="card">
            <h2>Communication History</h2>
            <?php foreach ($communications as $comm): ?>
                <div class="message <?php echo $comm['sender'] === 'agent' ? 'agent-msg' : 'customer-msg'; ?>">
                    <strong><?php echo ucfirst($comm['sender']); ?>:</strong> <?php echo nl2br($comm['message']); ?>
                    <small>(<?php echo $comm['created_at']; ?>)</small>
                </div>
            <?php endforeach; ?>
            <form action="api/tickets.php" method="POST" class="communication-form">
                <input type="hidden" name="action" value="add_communication">
                <input type="hidden" name="ticket_id" value="<?php echo $id; ?>">
                <textarea name="message" placeholder="Add note or reply..." required></textarea>
                <div>
                    <label><input type="radio" name="sender" value="agent" checked> Agent Note</label>
                    <label><input type="radio" name="sender" value="customer"> Customer Reply</label>
                </div>
                <button type="submit">Send</button>
            </form>
        </div>

        <div class="card">
            <h2>Order History (from Sales Module)</h2>
            <?php if (!empty($order_history)): ?>
                <table>
                    <thead><tr><th>Order #</th><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($order_history as $order): ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo $order['status']; ?></td>
                                <td>$<?php echo $order['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No orders found.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Finance Status (from Finance Module)</h2>
            <?php if (!empty($finance_status)): ?>
                <p><strong>Outstanding Balance:</strong> $<?php echo $finance_status['outstanding_balance']; ?></p>
                <p><strong>Credit Limit:</strong> $<?php echo $finance_status['credit_limit']; ?></p>
            <?php else: ?>
                <p class="no-data">No finance data available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>