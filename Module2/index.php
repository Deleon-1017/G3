<?php
require '../db.php';
require '../shared/config.php';
include '../shared/sidebar.php';

// Stats
$openTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
$pendingTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'in_progress'")->fetchColumn();

// Recent tickets
$recentTickets = $pdo->query("
    SELECT t.id, t.ticket_number, t.subject, t.status, t.priority, c.name as customer_name
    FROM support_tickets t
    LEFT JOIN customers c ON t.customer_id = c.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Portal analytics
$ratings = $pdo->query("
    SELECT s.title, s.category,
           COUNT(CASE WHEN f.rating = 'helpful' THEN 1 END) AS helpful,
           COUNT(CASE WHEN f.rating = 'unhelpful' THEN 1 END) AS unhelpful,
           ROUND((COUNT(CASE WHEN f.rating = 'helpful' THEN 1 END) / NULLIF(COUNT(f.id), 0)) * 100, 1) AS helpful_percentage
    FROM solutions s
    LEFT JOIN solution_feedback f ON s.id = f.solution_id
    GROUP BY s.id
    ORDER BY helpful_percentage DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Customer Service Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Open Tickets</h3>
                <p><?php echo $openTickets; ?></p>
            </div>
            <div class="stat-card">
                <h3>In Progress</h3>
                <p><?php echo $pendingTickets; ?></p>
            </div>
        </div>

        <div class="card">
            <h2>Recent Tickets</h2>
            <?php if (!empty($recentTickets)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['ticket_number']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo ucfirst($ticket['status']); ?></td>
                                <td><?php echo ucfirst($ticket['priority'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-link">View</a> |
                                    <a href="update_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-link">Edit</a> |
                                    <a href="delete_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-link" onclick="return confirm('Delete ticket?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No recent tickets.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Portal Analytics</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Helpful</th>
                        <th>Unhelpful</th>
                        <th>% Helpful</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ratings as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['title']); ?></td>
                            <td><?php echo $r['helpful']; ?></td>
                            <td><?php echo $r['unhelpful']; ?></td>
                            <td><?php echo $r['helpful_percentage'] ?: '0'; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Quick Actions</h2>
            <div class="quick-actions">
                <a href="add_ticket.php" class="quick-action"><i class="fas fa-plus"></i> Create Ticket</a>
                <a href="solutions.php" class="quick-action"><i class="fas fa-book"></i> Knowledge Base</a>
                <a href="manage_solutions.php" class="quick-action"><i class="fas fa-edit"></i> Manage Articles</a>
            </div>
        </div>
    </div>
</body>
</html>