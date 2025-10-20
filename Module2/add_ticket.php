<?php
require '../db.php';
require '../shared/config.php';
include '../shared/sidebar.php';

// Fetch customers for dropdown
$ch = curl_init(BASE_URL . 'Module8/api/customers.php?action=list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$customers = json_decode(curl_exec($ch), true)['data'] ?? [];
curl_close($ch);

// Fetch employees for assignment
$employees = $pdo->query("SELECT id, first_name, last_name FROM hr_employees ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Ticket</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Create New Ticket</h1>
        <div class="card">
            <form action="api/tickets.php" method="POST">
                <input type="hidden" name="action" value="save">
                <label>Customer:
                    <select name="customer_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['id']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Subject:
                    <input type="text" name="subject" required placeholder="Enter ticket subject">
                </label>
                <label>Description:
                    <textarea name="description" required placeholder="Enter ticket description"></textarea>
                </label>
                <label>Priority:
                    <select name="priority" required>
                        <option value="">Select Priority</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </label>
                <label>Assign To:
                    <select name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?php echo htmlspecialchars($e['id']); ?>"><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Submit Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>