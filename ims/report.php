<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Set default date filter (current month)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get sales by product
$productSalesQuery = $pdo->prepare("
    SELECT p.Name AS ProductName, 
           SUM(s.QuantitySold) AS TotalSold, 
           SUM(s.TotalAmount) AS TotalRevenue
    FROM Sales s
    JOIN Products p ON s.ProductID = p.ProductID
    WHERE s.SaleDate BETWEEN ? AND ?
    GROUP BY p.ProductID
    ORDER BY TotalRevenue DESC
");
$productSalesQuery->execute([$startDate, $endDate]);
$productSales = $productSalesQuery->fetchAll();

// Get total sales amount
$totalSalesQuery = $pdo->prepare("
    SELECT SUM(TotalAmount) AS TotalSales,
           COUNT(*) AS TotalTransactions
    FROM Sales
    WHERE SaleDate BETWEEN ? AND ?
");
$totalSalesQuery->execute([$startDate, $endDate]);
$totalSales = $totalSalesQuery->fetch();

// Get top categories
$categorySalesQuery = $pdo->prepare("
    SELECT p.Category, 
           SUM(s.TotalAmount) AS TotalRevenue
    FROM Sales s
    JOIN Products p ON s.ProductID = p.ProductID
    WHERE s.SaleDate BETWEEN ? AND ?
    GROUP BY p.Category
    ORDER BY TotalRevenue DESC
");
$categorySalesQuery->execute([$startDate, $endDate]);
$categorySales = $categorySalesQuery->fetchAll();

// Get daily sales trend
$dailySalesQuery = $pdo->prepare("
    SELECT DATE(s.SaleDate) AS Date,
           SUM(s.TotalAmount) AS DailyRevenue
    FROM Sales s
    WHERE s.SaleDate BETWEEN ? AND ?
    GROUP BY DATE(s.SaleDate)
    ORDER BY Date
");
$dailySalesQuery->execute([$startDate, $endDate]);
$dailySales = $dailySalesQuery->fetchAll();

// Get supplier contribution
$supplierContributionQuery = $pdo->prepare("
    SELECT sup.Name AS SupplierName,
           SUM(s.QuantitySold) AS TotalSold
    FROM Sales s
    JOIN Products p ON s.ProductID = p.ProductID
    JOIN Stock st ON p.ProductID = st.ProductID
    JOIN Suppliers sup ON st.SupplierID = sup.SupplierID
    WHERE s.SaleDate BETWEEN ? AND ?
    GROUP BY sup.SupplierID
    ORDER BY TotalSold DESC
");
$supplierContributionQuery->execute([$startDate, $endDate]);
$supplierContribution = $supplierContributionQuery->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Add Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        .chart-container {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            height: 400px;
        }
        .chart-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-column {
            flex: 1;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <div class="management-container">
        <div class="page-header">
            <h1 class="page-title">Sales Reports & Analytics</h1>
            <div class="action-bar">
                <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="filter-container">
            <h2>Filter Report</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>">
                </div>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </form>
        </div>
        
        <!-- Summary Section -->
        <div class="summary-stats">
            <h2>Sales Summary</h2>
            <div class="dashboard-stats">
                <div class="stat-box">
                    <h3>Total Revenue</h3>
                    <p class="large-stat">₱<?= number_format($totalSales['TotalSales'] ?? 0, 2) ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Transactions</h3>
                    <p class="large-stat"><?= $totalSales['TotalTransactions'] ?? 0 ?></p>
                </div>
                <div class="stat-box">
                    <h3>Report Period</h3>
                    <p><?= date('M d, Y', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Visualization Section -->
        <h2>Data Visualization</h2>
        <div class="chart-row">
            <div class="chart-column">
                <div class="chart-container">
                    <h3>Daily Sales Trend</h3>
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
            <div class="chart-column">
                <div class="chart-container pie-chart">
                    <h3>Category Revenue Distribution</h3>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="chart-row">
            <div class="chart-column">
                <div class="chart-container">
                    <h3>Top Products by Revenue</h3>
                    <canvas id="productRevenueChart"></canvas>
                </div>
            </div>
            <div class="chart-column">
                <div class="chart-container pie-chart">
                    <h3>Supplier Contribution</h3>
                    <canvas id="supplierChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Product Sales Table -->
        <div class="table-container">
            <h2>Sales by Product</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productSales)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">No sales data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productSales as $product): ?>
                            <tr>
                                <td><?= $product['ProductName'] ?></td>
                                <td><?= $product['TotalSold'] ?></td>
                                <td>₱<?= number_format($product['TotalRevenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Category Sales Table -->
        <div class="table-container">
            <h2>Sales by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorySales)): ?>
                        <tr>
                            <td colspan="2" style="text-align: center;">No category data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorySales as $category): ?>
                            <tr>
                                <td><?= $category['Category'] ?></td>
                                <td>₱<?= number_format($category['TotalRevenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Supplier Contribution Table -->
        <div class="table-container">
            <h2>Supplier Contribution</h2>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supplierContribution)): ?>
                        <tr>
                            <td colspan="2" style="text-align: center;">No supplier data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($supplierContribution as $supplier): ?>
                            <tr>
                                <td><?= $supplier['SupplierName'] ?></td>
                                <td><?= $supplier['TotalSold'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
    
    <script>
    // Prepare data for Daily Sales Chart
    const dailySalesData = {
        labels: [<?php 
            $labels = [];
            foreach ($dailySales as $data) {
                $labels[] = "'" . date('M d', strtotime($data['Date'])) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Daily Revenue (₱)',
            data: [<?php 
                $values = [];
                foreach ($dailySales as $data) {
                    $values[] = $data['DailyRevenue'];
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            tension: 0.4
        }]
    };
    
    // Prepare data for Category Chart
    const categoryData = {
        labels: [<?php 
            $labels = [];
            foreach ($categorySales as $data) {
                $labels[] = "'" . addslashes($data['Category']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Revenue by Category (₱)',
            data: [<?php 
                $values = [];
                foreach ($categorySales as $data) {
                    $values[] = $data['TotalRevenue'];
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
    
    // Prepare data for Product Revenue Chart
    const productRevenueData = {
        labels: [<?php 
            $topProducts = array_slice($productSales, 0, 5); // Get top 5 products
            $labels = [];
            foreach ($topProducts as $data) {
                $labels[] = "'" . addslashes($data['ProductName']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Revenue (₱)',
            data: [<?php 
                $values = [];
                foreach ($topProducts as $data) {
                    $values[] = $data['TotalRevenue'];
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)'
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
    
    // Prepare data for Supplier Chart
    const supplierData = {
        labels: [<?php 
            $topSuppliers = array_slice($supplierContribution, 0, 5); // Get top 5 suppliers
            $labels = [];
            foreach ($topSuppliers as $data) {
                $labels[] = "'" . addslashes($data['SupplierName']) . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Units Sold',
            data: [<?php 
                $values = [];
                foreach ($topSuppliers as $data) {
                    $values[] = $data['TotalSold'];
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',   // Teal
                'rgba(255, 159, 64, 0.8)',   // Orange
                'rgba(54, 162, 235, 0.8)',   // Blue
                'rgba(153, 102, 255, 0.8)',  // Purple
                'rgba(255, 99, 132, 0.8)'    // Pink
            ],
            borderWidth: 1
        }]
    };

    // Create Daily Sales Chart
    const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
    const dailySalesChart = new Chart(dailySalesCtx, {
        type: 'line',
        data: dailySalesData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            return label + ': ₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Create Product Revenue Chart
    const productRevenueCtx = document.getElementById('productRevenueChart').getContext('2d');
    const productRevenueChart = new Chart(productRevenueCtx, {
        type: 'bar',
        data: productRevenueData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Create Supplier Chart
    const supplierCtx = document.getElementById('supplierChart').getContext('2d');
    const supplierChart = new Chart(supplierCtx, {
        type: 'doughnut',
        data: supplierData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    </script>
</body>
</html>