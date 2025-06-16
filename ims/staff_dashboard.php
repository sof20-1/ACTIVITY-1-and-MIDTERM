<?php
include 'auth.php';
include 'db.php';

// Only check for login, no role verification needed here
checkLogin();

// Prevent admin users from accessing staff dashboard
if (isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Get summary data
try {
    // Count Products
    $stmt = $pdo->query("SELECT COUNT(*) FROM Products");
    $productCount = $stmt->fetchColumn();
    
    // Get low stock items
    $lowStockQuery = $pdo->query("SELECT COUNT(*) FROM Products WHERE StockLevel < 10");
    $lowStockCount = $lowStockQuery->fetchColumn();
    
    // Get recent sales activities
    $stmt = $pdo->query("SELECT p.Name as ProductName, s.QuantitySold, s.SaleDate 
                         FROM Sales s
                         JOIN Products p ON s.ProductID = p.ProductID
                         ORDER BY s.SaleDate DESC LIMIT 5");
    $recentSales = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $productCount = 0;
    $lowStockCount = 0;
    $recentSales = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, Staff!</h1>
            <nav>
                <ul>
                    <li><a href="stock.php">View Stock</a></li>
                    <li><a href="sales.php">Record Sales</a></li>
                    <li><a href="logout.php" class="logout">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="dashboard-summary">
                <h2>Summary</h2>
                <p>Welcome to your staff dashboard. You can view stock levels and record sales.</p>
                <div class="dashboard-stats">
                    <div class="stat-box">
                        <h3>Total Products</h3>
                        <p><?php echo $productCount; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Low Stock Items</h3>
                        <p><?php echo $lowStockCount; ?></p>
                    </div>
                </div>
            </section>

            <section class="recent-activities">
                <h2>Recent Activities</h2>
                <ul>
                    <?php if (empty($recentSales)): ?>
                        <li>No recent sales activities to display</li>
                    <?php else: ?>
                        <?php foreach ($recentSales as $sale): ?>
                            <li>Sold <?php echo $sale['QuantitySold']; ?> units of "<?php echo $sale['ProductName']; ?>" on <?php echo date('M d, Y', strtotime($sale['SaleDate'])); ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>
        </main>
    </div>
</body>
</html>
