<?php
require '../db.php';
require '../shared/config.php';
include '../shared/sidebar.php';

$customer_id = (int)($_GET['customer_id'] ?? 0);
$success = isset($_GET['success']);

// Get customer details
$customer = null;
if ($customer_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all customers for dropdown
$customers = $pdo->query("SELECT id, name, code FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get communication history for selected customer
$communications = [];
$tickets = [];
if ($customer_id > 0) {
    // Get all communications
    $comm_stmt = $pdo->prepare("
        SELECT c.*, t.ticket_number, t.subject as ticket_subject, t.status as ticket_status
        FROM communications c
        LEFT JOIN support_tickets t ON c.ticket_id = t.id
        WHERE c.customer_id = ?
        ORDER BY c.created_at DESC
    ");
    $comm_stmt->execute([$customer_id]);
    $communications = $comm_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all tickets for this customer
    $ticket_stmt = $pdo->prepare("
        SELECT id, ticket_number, subject, status, priority, created_at
        FROM support_tickets
        WHERE customer_id = ?
        ORDER BY created_at DESC
    ");
    $ticket_stmt->execute([$customer_id]);
    $tickets = $ticket_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Communication stats
$stats = ['total' => 0, 'agent_msgs' => 0, 'customer_msgs' => 0, 'open_tickets' => 0];
if ($customer_id > 0) {
    $stats['total'] = count($communications);
    foreach ($communications as $c) {
        if ($c['sender'] === 'agent') $stats['agent_msgs']++;
        else $stats['customer_msgs']++;
    }
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE customer_id = ? AND status IN ('open', 'in_progress')");
    $count_stmt->execute([$customer_id]);
    $stats['open_tickets'] = $count_stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Communication History</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .customer-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
        }
        .info-box strong {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .info-box span {
            font-size: 1.2rem;
            color: #e79696;
            font-weight: 600;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e79696;
            border: 3px solid white;
        }
        .timeline-agent::before {
            background: #4CAF50;
        }
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        .sender-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-agent {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .badge-customer {
            background: #fff3e0;
            color: #e65100;
        }
        .message-content {
            color: #333;
            line-height: 1.6;
        }
        .ticket-link {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.25rem 0.5rem;
            background: #f0f0f0;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            color: #666;
        }
        .ticket-link:hover {
            background: #e0e0e0;
        }
        .template-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .template-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .template-btn:hover {
            background: #e79696;
            color: white;
            border-color: #e79696;
        }
        .no-customer {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        .success-msg {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .status-open { background: #fff3e0; color: #e65100; }
        .status-in_progress { background: #e3f2fd; color: #1565c0; }
        .status-resolved { background: #e8f5e9; color: #2e7d32; }
        .status-closed { background: #f5f5f5; color: #616161; }
        .ticket-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Customer Communication History</h1>

        <?php if ($success): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> Follow-up message sent successfully!
            </div>
        <?php endif; ?>

        <div class="filter-section">
            <form method="GET">
                <label>Select Customer:
                    <select name="customer_id" onchange="this.form.submit()">
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $customer_id == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </div>

        <?php if ($customer): ?>
            <div class="card">
                <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($customer['name']); ?></h2>
                <div class="customer-info">
                    <div class="info-box">
                        <strong>Total Messages</strong>
                        <span><?php echo $stats['total']; ?></span>
                    </div>
                    <div class="info-box">
                        <strong>Agent Messages</strong>
                        <span><?php echo $stats['agent_msgs']; ?></span>
                    </div>
                    <div class="info-box">
                        <strong>Customer Messages</strong>
                        <span><?php echo $stats['customer_msgs']; ?></span>
                    </div>
                    <div class="info-box">
                        <strong>Open Tickets</strong>
                        <span><?php echo $stats['open_tickets']; ?></span>
                    </div>
                </div>

                <div class="tickets-summary">
                    <h3>Recent Tickets</h3>
                    <?php if (!empty($tickets)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($tickets, 0, 5) as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['ticket_number']); ?></td>
                                        <td><?php echo htmlspecialchars($t['subject']); ?></td>
                                        <td>
                                            <span class="ticket-badge status-<?php echo $t['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                        <td>
                                            <a href="view_ticket.php?id=<?php echo $t['id']; ?>" class="btn-link">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No tickets found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-comments"></i> Communication Timeline</h2>
                
                <?php if (!empty($communications)): ?>
                    <div class="timeline">
                        <?php foreach ($communications as $comm): ?>
                            <div class="timeline-item <?php echo $comm['sender'] === 'agent' ? 'timeline-agent' : ''; ?>">
                                <div class="timeline-header">
                                    <div>
                                        <span class="sender-badge <?php echo $comm['sender'] === 'agent' ? 'badge-agent' : 'badge-customer'; ?>">
                                            <i class="fas fa-<?php echo $comm['sender'] === 'agent' ? 'headset' : 'user'; ?>"></i>
                                            <?php echo ucfirst($comm['sender']); ?>
                                        </span>
                                        <?php if ($comm['ticket_number']): ?>
                                            <a href="view_ticket.php?id=<?php echo $comm['ticket_id']; ?>" class="ticket-link">
                                                <i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($comm['ticket_number']); ?>
                                                - <?php echo htmlspecialchars($comm['ticket_subject']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <small style="color: #999;">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y g:i A', strtotime($comm['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($comm['message'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">No communication history found for this customer.</p>
                <?php endif; ?>
            </div>

            <div class="card" id="followup">
                <h2><i class="fas fa-reply"></i> Send Follow-Up Message</h2>
                
                <div class="template-buttons">
                    <button type="button" class="template-btn" onclick="insertTemplate('followup')">
                        <i class="fas fa-clock"></i> Follow-up
                    </button>
                    <button type="button" class="template-btn" onclick="insertTemplate('thankyou')">
                        <i class="fas fa-heart"></i> Thank You
                    </button>
                    <button type="button" class="template-btn" onclick="insertTemplate('resolution')">
                        <i class="fas fa-check-circle"></i> Resolution Confirmation
                    </button>
                    <button type="button" class="template-btn" onclick="insertTemplate('feedback')">
                        <i class="fas fa-star"></i> Request Feedback
                    </button>
                </div>

                <form action="api/tickets.php" method="POST" onsubmit="return confirm('Send this follow-up message?');">
                    <input type="hidden" name="action" value="add_communication">
                    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                    <input type="hidden" name="redirect" value="<?php echo BASE_URL; ?>Module2/customer_communications.php?customer_id=<?php echo $customer_id; ?>&success=1">
                    
                    <label>Select Ticket (Optional):
                        <select name="ticket_id">
                            <option value="">-- General Message --</option>
                            <?php foreach ($tickets as $t): ?>
                                <option value="<?php echo $t['id']; ?>">
                                    <?php echo htmlspecialchars($t['ticket_number']); ?> - <?php echo htmlspecialchars($t['subject']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Message:
                        <textarea name="message" id="followup-message" rows="6" required placeholder="Type your follow-up message here..."></textarea>
                    </label>

                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Send Follow-Up
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="card no-customer">
                <i class="fas fa-user-circle" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>Select a Customer</h3>
                <p>Choose a customer from the dropdown above to view their communication history.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const templates = {
            followup: "Hello! I'm following up on your recent inquiry. How can I assist you further?",
            thankyou: "Thank you for contacting us! We appreciate your business and hope we've resolved your concern satisfactorily.",
            resolution: "Your ticket has been resolved. Please let us know if you need any additional assistance.",
            feedback: "We'd love to hear your feedback about your recent support experience. How did we do?"
        };

        function insertTemplate(type) {
            const textarea = document.getElementById('followup-message');
            textarea.value = templates[type];
            textarea.focus();
        }
    </script>
</body>
</html>