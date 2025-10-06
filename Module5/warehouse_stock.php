<?php
require '../db.php';
require '../shared/config.php';

// Fetch all warehouses
$warehouses = $pdo->query("SELECT id, code, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Prepare product data for each warehouse
$warehouseData = [];
foreach ($warehouses as $warehouse) {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.sku,
            p.name,
            p.unit,
            p.expiration_date,
            p.unit_price,
            COALESCE(pl.quantity, 0) AS quantity
        FROM products p
        LEFT JOIN product_locations pl ON p.id = pl.product_id AND pl.location_id = ?
        WHERE p.warehouse_id = ? OR pl.location_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$warehouse['id'], $warehouse['id'], $warehouse['id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalProducts = count($products);
    $totalQuantity = array_sum(array_column($products, 'quantity'));
    $totalCost = 0;

    foreach ($products as $p) {
        $totalCost += $p['quantity'] * $p['unit_price'];
    }


    $warehouseData[] = [
        'warehouse' => $warehouse,
        'products' => $products,
        'totalProducts' => $totalProducts,
        'totalQuantity' => $totalQuantity,
        'totalCost' => $totalCost
    ];
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Warehouse Stock Report</title>
    <link rel="stylesheet" href="styles.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        .warehouse-section {
            margin-bottom: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }

        .warehouse-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .warehouse-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .warehouse-totals {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .warehouse-table {
            width: 100%;
            border-collapse: collapse;
        }

        .warehouse-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #dee2e6;
        }

        .warehouse-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .warehouse-table tr:last-child td {
            border-bottom: none;
        }

        .expired {
            color: #e74c3c;
            font-weight: bold;
        }

        .expiring-soon {
            color: #f39c12;
        }

        .no-products {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
        }

        .refresh-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .refresh-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>

<body>

    <?php include '../shared/sidebar.php'; ?>

    <div class="container" style="margin-left: 18rem;">
        <div class="header">
            <h1>Warehouse Stock Report</h1>
            <div>
                <button class="refresh-btn" onclick="window.location.reload()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <?php if (empty($warehouseData)): ?>
            <div class="card">
                <p class="no-products">No warehouses found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($warehouseData as $data): ?>
                <div class="warehouse-section">
                    <div class="warehouse-header">
                        <div class="warehouse-title">
                            <?php echo htmlspecialchars($data['warehouse']['code'] . ' - ' . $data['warehouse']['name']); ?>
                        </div>
                        <div class="warehouse-totals">
                            Products: <?php echo $data['totalProducts']; ?> |
                            Total Quantity: <?php echo $data['totalQuantity']; ?> |
                            Total Cost: ₱<?php echo number_format($data['totalCost'], 2); ?>
                        </div>

                    </div>

                    <?php if (empty($data['products'])): ?>
                        <p class="no-products">No products in this warehouse.</p>
                    <?php else: ?>
                        <table class="table warehouse-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Product Name</th>
                                    <th>Unit</th>
                                    <th>Expiration Date</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['products'] as $product): ?>
                                    <?php
                                    $expirationClass = '';
                                    $today = new DateTime();
                                    $expDate = $product['expiration_date'] ? new DateTime($product['expiration_date']) : null;

                                    if ($expDate) {
                                        $interval = $today->diff($expDate);
                                        if ($interval->invert) {
                                            $expirationClass = 'expired';
                                        } elseif ($interval->days <= 30) {
                                            $expirationClass = 'expiring-soon';
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                        <td class="<?php echo $expirationClass; ?>">
                                            <?php echo $product['expiration_date'] ? htmlspecialchars($product['expiration_date']) : 'N/A'; ?>
                                        </td>
                                        <td><?php echo (int)$product['quantity']; ?></td>
                                        <td>₱<?php echo (int)$product['unit_price']; ?></td>
                                        <td>₱<?php echo number_format($product['quantity'] * $product['unit_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>