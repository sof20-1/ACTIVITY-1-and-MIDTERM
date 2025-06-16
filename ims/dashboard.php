<?php
include 'auth.php';
include 'db.php';

// Check if user is an admin
checkAdmin();

// Get summary data
try {
    // Count Products
    $stmt = $pdo->query("SELECT COUNT(*) FROM Products");
    $productCount = $stmt->fetchColumn();
    
    // Count Sales
    $stmt = $pdo->query("SELECT COUNT(*) FROM Sales");
    $salesCount = $stmt->fetchColumn();
    
    // Count Suppliers
    $stmt = $pdo->query("SELECT COUNT(*) FROM Suppliers");
    $supplierCount = $stmt->fetchColumn();
    
    // Count Low Stock Items
    $lowStockQuery = $pdo->query("SELECT COUNT(*) FROM Products WHERE StockLevel < 10");
    $lowStockCount = $lowStockQuery->fetchColumn();
    
    // Get recent activities
    $stmt = $pdo->query("SELECT p.Name as ProductName, s.QuantitySold, s.SaleDate FROM Sales s
                         JOIN Products p ON s.ProductID = p.ProductID
                         ORDER BY s.SaleDate DESC LIMIT 5");
    $recentSales = $stmt->fetchAll();

    // Get data for sales chart (last 7 days)
    $salesChartQuery = $pdo->query("
        SELECT DATE(SaleDate) as date, SUM(TotalAmount) as total 
        FROM Sales 
        WHERE SaleDate >= DATE(NOW()) - INTERVAL 7 DAY 
        GROUP BY DATE(SaleDate) 
        ORDER BY date"
    );
    $salesChartData = $salesChartQuery->fetchAll();

    // Get data for top products chart
    $topProductsQuery = $pdo->query("
        SELECT p.Name, SUM(s.TotalAmount) as total 
        FROM Sales s 
        JOIN Products p ON s.ProductID = p.ProductID 
        GROUP BY p.ProductID 
        ORDER BY total DESC 
        LIMIT 5"
    );
    $topProductsData = $topProductsQuery->fetchAll();

    // Get data for category distribution
    $categoryQuery = $pdo->query("
        SELECT Category, COUNT(*) as count 
        FROM Products 
        GROUP BY Category"
    );
    $categoryData = $categoryQuery->fetchAll();
    
} catch (PDOException $e) {
    // Handle database errors
    $productCount = 0;
    $salesCount = 0;
    $supplierCount = 0;
    $lowStockCount = 0;
    $recentSales = [];
    $salesChartData = [];
    $topProductsData = [];
    $categoryData = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Add Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, Admin!</h1>
            <nav>
                <ul>
                    <li><a href="products.php">Manage Products</a></li>
                    <li><a href="suppliers.php">Manage Suppliers</a></li>
                    <li><a href="stock.php">Manage Stock</a></li>
                    <li><a href="sales.php">Record/View Sales</a></li>
                    <li><a href="report.php">View Reports</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="logout.php" class="logout">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="dashboard-summary">
                <h2>Summary</h2>
                <p>Welcome to your admin dashboard. From here, you can manage products, suppliers, stock, sales, and view reports.</p>
                <div class="dashboard-stats">
                    <div class="stat-box">
                        <h3>Total Products</h3>
                        <p><?php echo $productCount; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Total Sales</h3>
                        <p><?php echo $salesCount; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Total Suppliers</h3>
                        <p><?php echo $supplierCount; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Low Stock Items</h3>
                        <p><?= $lowStockCount ?></p>
                    </div>
                </div>
            </section>

            <!-- Charts Section 
            <section class="dashboard-charts">
                <div class="chart-container">
                    <h2>Sales Last 7 Days</h2>
                    <canvas id="salesChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h2>Top 5 Products by Revenue</h2>
                    <canvas id="topProductsChart"></canvas>
                </div>
                
                <div class="chart-container pie-chart">
                    <h2>Product Categories</h2>
                    <canvas id="categoryChart"></canvas>
                </div>
            </section>
            -->

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
    
    <script>
    // Prepare data for Sales Chart
    const salesChartData = {
        labels: [<?php 
            $labels = [];
            foreach ($salesChartData as $data) {
                $labels[] = "'" . date('M d', strtotime($data['date'])) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Sales (₱)',
            data: [<?php 
                $values = [];
                foreach ($salesChartData as $data) {
                    $values[] = $data['total'];
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };
    
    // Prepare data for Top Products Chart
    const topProductsData = {
        labels: [<?php 
            $labels = [];
            foreach ($topProductsData as $data) {
                $labels[] = "'" . addslashes($data['Name']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Revenue (₱)',
            data: [<?php 
                $values = [];
                foreach ($topProductsData as $data) {
                    $values[] = $data['total'];
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    // Prepare data for Category Chart
    const categoryData = {
        labels: [<?php 
            $labels = [];
            foreach ($categoryData as $data) {
                $labels[] = "'" . addslashes($data['Category']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Number of Products',
            data: [<?php 
                $values = [];
                foreach ($categoryData as $data) {
                    $values[] = $data['count'];
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',   // Teal
                'rgba(255, 159, 64, 0.8)',   // Orange
                'rgba(54, 162, 235, 0.8)',   // Blue
                'rgba(153, 102, 255, 0.8)',  // Purple
                'rgba(255, 99, 132, 0.8)',   // Pink
                'rgba(255, 206, 86, 0.8)'    // Yellow
            ],
            borderWidth: 1
        }]
    };

    // Create Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: salesChartData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Create Top Products Chart
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProductsChart = new Chart(topProductsCtx, {
        type: 'bar',
        data: topProductsData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Create Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: categoryData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
    </script>
</body>
</html>