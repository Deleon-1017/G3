<?php
require '../db.php';
require '../shared/config.php';

// Handle actions before any output
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? null;

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO solutions (title, content, category) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $category]);
        header('Location: manage_solutions.php');
        exit;
    }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("UPDATE solutions SET title = ?, content = ?, category = ? WHERE id = ?");
        $stmt->execute([$title, $content, $category, $id]);
        header('Location: manage_solutions.php');
        exit;
    }
}

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare("DELETE FROM solutions WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_solutions.php');
    exit;
}

// Include sidebar and proceed with page render
include '../shared/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module2/styles.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body>
    <div class="main-container">
        <h1 class="dashboard-title">Manage Articles</h1>

        <div class="card">
            <h2>Add New Article</h2>
            <form method="POST" action="manage_solutions.php">
                <input type="hidden" name="action" value="add">
                <label>Title:
                    <input type="text" name="title" required placeholder="Enter article title">
                </label>
                <label>Content:
                    <textarea name="content" required placeholder="Enter article content"></textarea>
                </label>
                <label>Category:
                    <input type="text" name="category" placeholder="Enter category (e.g., Orders)">
                </label>
                <button type="submit">Add Article</button>
            </form>
        </div>

        <div class="card">
            <h2>Existing Articles</h2>
            <?php
            $solutions = $pdo->query("SELECT * FROM solutions ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($solutions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solutions as $sol): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sol['title']); ?></td>
                                <td><?php echo htmlspecialchars($sol['category'] ?: 'Uncategorized'); ?></td>
                                <td>
                                    <a href="manage_solutions.php?action=edit&id=<?php echo $sol['id']; ?>" class="btn-link">Edit</a> |
                                    <a href="manage_solutions.php?action=delete&id=<?php echo $sol['id']; ?>" class="btn-link" onclick="return confirm('Delete article?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No articles available.</p>
            <?php endif; ?>
        </div>

        <?php
        if ($action === 'edit' && $id) {
            $stmt = $pdo->prepare("SELECT * FROM solutions WHERE id = ?");
            $stmt->execute([$id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($solution): ?>
                <div class="card">
                    <h2>Edit Article</h2>
                    <form method="POST" action="manage_solutions.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <label>Title:
                            <input type="text" name="title" value="<?php echo htmlspecialchars($solution['title']); ?>" required>
                        </label>
                        <label>Content:
                            <textarea name="content" required><?php echo htmlspecialchars($solution['content']); ?></textarea>
                        </label>
                        <label>Category:
                            <input type="text" name="category" value="<?php echo htmlspecialchars($solution['category'] ?: ''); ?>" placeholder="Enter category (e.g., Orders)">
                        </label>
                        <button type="submit">Update Article</button>
                    </form>
                </div>
            <?php endif;
        }
        ?>
    </div>
</body>
</html>