<?php
require '../db.php';
require '../shared/config.php';
include '../shared/sidebar.php';

$solutions = $pdo->query("SELECT * FROM solutions ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service Portal</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Self-Service Portal</h1>
        <div class="card">
            <h2>Help Articles</h2>
            <form method="GET" class="search-form">
                <input type="text" name="q" placeholder="Search articles..." value="<?php echo $_GET['q'] ?? ''; ?>" class="search-input">
                <button type="submit" class="search-btn">Search</button>
            </form>
            <?php
            $query = trim($_GET['q'] ?? '');
            if ($query) {
                $stmt = $pdo->prepare("SELECT * FROM solutions WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC");
                $stmt->execute(["%$query%", "%$query%"]);
                $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            ?>
            <?php if (!empty($solutions)): ?>
                <div class="articles-grid">
                    <?php foreach ($solutions as $sol): ?>
                        <?php
                        $rating_stmt = $pdo->prepare("SELECT rating, COUNT(*) as count FROM solution_feedback WHERE solution_id = ? GROUP BY rating");
                        $rating_stmt->execute([$sol['id']]);
                        $ratings = $rating_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        $helpful = $ratings['helpful'] ?? 0;
                        $unhelpful = $ratings['unhelpful'] ?? 0;
                        ?>
                        <div class="article-card">
                            <h3><?php echo htmlspecialchars($sol['title']); ?> <span class="category">(<?php echo htmlspecialchars($sol['category'] ?: 'Uncategorized'); ?>)</span></h3>
                            <p><?php echo nl2br(htmlspecialchars($sol['content'])); ?></p>
                            <form method="POST" action="api/solutions.php" class="feedback-form">
                                <input type="hidden" name="action" value="feedback">
                                <input type="hidden" name="id" value="<?php echo $sol['id']; ?>">
                                <select name="rating" class="rating-select">
                                    <option value="helpful">Helpful</option>
                                    <option value="unhelpful">Unhelpful</option>
                                </select>
                                <button type="submit" class="feedback-btn">Rate</button>
                            </form>
                            <p class="rating-stats">Helpful: <?php echo $helpful; ?> | Unhelpful: <?php echo $unhelpful; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-data">No articles found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>