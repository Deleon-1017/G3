<?php
require '../db.php';
require '../shared/config.php';
$id = (int)($_GET['id'] ?? 0);
$ticket = null;
if ($id) {
  $stmt = $pdo->prepare("SELECT t.*, c.name as customer_name FROM support_tickets t LEFT JOIN customers c ON c.id = t.customer_id WHERE t.id = ?");
  $stmt->execute([$id]);
  $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
  $com = $pdo->prepare("SELECT * FROM communications WHERE ticket_id = ? ORDER BY id ASC"); $com->execute([$id]);
  $comms = $com->fetchAll(PDO::FETCH_ASSOC);
} else { die('Ticket not found'); }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ticket <?php echo htmlspecialchars($ticket['ticket_number']); ?></title>
  <link rel="stylesheet" href="styles.css">
  <base href="<?php echo BASE_URL; ?>">
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <h1>Ticket: <?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
    <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></p>
    <p><strong>Customer:</strong> <?php echo htmlspecialchars($ticket['customer_name'] ?: '-'); ?></p>
    <hr>
    <div id="comms">
      <?php foreach ($comms as $c): ?>
        <div style="border:1px solid #ddd;padding:8px;margin:8px 0;"><strong><?php echo htmlspecialchars($c['sender']); ?></strong> <em><?php echo $c['created_at']; ?></em><div><?php echo nl2br(htmlspecialchars($c['message'])); ?></div></div>
      <?php endforeach; ?>
    </div>
    <div>
      <h3>Add Message</h3>
      <form id="msgForm">
        <textarea name="message" id="message" rows="4" style="width:100%"></textarea>
        <br>
        <button class="btn" type="submit">Send</button>
      </form>
    </div>
  </div>
  <script>
  document.getElementById('msgForm').addEventListener('submit', async function(e){ e.preventDefault(); const m=document.getElementById('message').value; const fd=new URLSearchParams({action:'add_message',ticket_id:'<?php echo $id; ?>',customer_id:'<?php echo $ticket['customer_id'] ?? 0; ?>',message:m,sender:'agent'}); await fetch('Module8/api/support_tickets.php',{method:'POST',body:fd}); location.reload(); });
  </script>
</body>
</html>
