<?php
require '../db.php';
require '../shared/config.php';
include '../shared/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch ticket details
$ch = curl_init(BASE_URL . 'Module2/api/tickets.php?action=get&id=' . $id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$ticket = json_decode($response, true)['data'] ?? null;
if (!$ticket) {
    header('Location: index.php');
    exit;
}

// Fetch customers and employees
$ch_customers = curl_init(BASE_URL . 'Module8/api/customers.php?action=list');
curl_setopt($ch_customers, CURLOPT_RETURNTRANSFER, true);
$customers = json_decode(curl_exec($ch_customers), true)['data'] ?? [];
curl_close($ch_customers);

$employees = $pdo->query("SELECT id, first_name, last_name FROM hr_employees ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Update Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
        <div class="card">
            <form action="api/tickets.php" method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <label>Customer:
                    <select name="customer_id" required>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['id']); ?>" <?php echo $c['id'] == $ticket['customer_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Subject:
                    <input type="text" name="subject" value="<?php echo htmlspecialchars($ticket['subject']); ?>" required placeholder="Enter ticket subject">
                </label>
                <label>Description:
                    <textarea name="description" required placeholder="Enter ticket description"><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                </label>
                <label>Status:
                    <select name="status" required>
                        <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </label>
                <label>Priority:
                    <select name="priority" required>
                        <option value="low" <?php echo $ticket['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $ticket['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $ticket['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </label>
                <label>Assign To:
                    <select name="assigned_to">
                        <option value="" <?php echo !$ticket['assigned_to'] ? 'selected' : ''; ?>>Unassigned</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?php echo htmlspecialchars($e['id']); ?>" <?php echo $ticket['assigned_to'] == $e['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Update Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>