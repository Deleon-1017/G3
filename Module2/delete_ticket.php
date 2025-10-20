<?php
require '../db.php';
require '../shared/config.php';
include '../shared/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch ticket for confirmation
$stmt = $pdo->prepare("SELECT ticket_number, subject FROM support_tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Ticket #<?php echo $ticket['ticket_number']; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Delete Ticket #<?php echo $ticket['ticket_number']; ?></h1>
        <div class="card">
            <p>Are you sure you want to delete the ticket: "<?php echo htmlspecialchars($ticket['subject']); ?>"?</p>
            <form action="api/tickets.php" method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button type="submit" style="background: #ff4444; color: white;">Confirm Delete</button>
            </form>
            <a href="index.php" style="margin-left: 1rem;">Cancel</a>
        </div>
    </div>
</body>
</html>