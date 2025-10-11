<?php
require 'db.php';
require 'shared/config.php';

$sql = file_get_contents('Module8/migration.sql');

// Split by ; to execute multiple statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    try {
        $pdo->exec($stmt);
        echo "Executed: " . substr($stmt, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "Migration completed.\n";
?>
