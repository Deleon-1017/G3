<?php
require '../db.php';
header('Content-Type: text/plain');

try {
    // Check for locations table
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locations'");
    $stmt->execute();
    $has_locations = (bool)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if (!$has_locations) {
        echo "locations table not found in current database. Please create it first.\n";
        exit;
    }

    // Check if foreign key already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_items' AND COLUMN_NAME = 'location_id' AND REFERENCED_TABLE_NAME = 'locations'");
    $stmt->execute();
    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
        echo "Foreign key on sales_order_items.location_id already exists.\n";
        exit;
    }

    // Create index if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_items' AND INDEX_NAME = 'idx_soi_location'");
    $stmt->execute();
    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] == 0) {
        $pdo->exec("CREATE INDEX idx_soi_location ON sales_order_items(location_id)");
        echo "Created index idx_soi_location.\n";
    } else {
        echo "Index idx_soi_location already exists.\n";
    }

    // Add foreign key
    $pdo->exec("ALTER TABLE sales_order_items ADD CONSTRAINT fk_soi_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL ON UPDATE CASCADE");
    echo "Foreign key fk_soi_location added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
