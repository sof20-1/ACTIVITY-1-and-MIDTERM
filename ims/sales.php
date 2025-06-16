<?php
include 'auth.php';
include 'db.php';

// Check if user is logged in
checkLogin();

// Add Sale
if (isset($_POST['add'])) {
    $productID = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $saleDate = $_POST['sale_date'];

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Get current stock and price
        $stmt = $pdo->prepare("SELECT StockLevel, Price FROM Products WHERE ProductID = ?");
        $stmt->execute([$productID]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        $currentStock = $product['StockLevel'];
        $price = $product['Price'];

        if ($currentStock < $quantity) {
            throw new Exception("Not enough stock available. Only {$currentStock} units left.");
        }

        // Calculate total automatically
        $totalAmount = $price * $quantity;

        // Add to sales history
        $stmt = $pdo->prepare("INSERT INTO Sales (ProductID, QuantitySold, SaleDate, TotalAmount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productID, $quantity, $saleDate, $totalAmount]);

        // Update product stock level
        $stmt = $pdo->prepare("UPDATE Products SET StockLevel = StockLevel - ? WHERE ProductID = ?");
        $stmt->execute([$quantity, $productID]);

        $pdo->commit();
        $message = "Sale recorded successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch Products for Form Selection
$productsQuery = $pdo->query("SELECT * FROM Products WHERE StockLevel > 0");

// Fetch Sales Entries with product names
$salesQuery = $pdo->query("
    SELECT s.SaleID, p.Name AS ProductName, s.QuantitySold, 
           s.SaleDate, s.TotalAmount, p.StockLevel
    FROM Sales s
    JOIN Products p ON s.ProductID = p.ProductID
    ORDER BY s.SaleDate DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Sales</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="management-container">
    <div class="page-header">
        <h1 class="page-title">Sales Management</h1>
        <div class="action-bar">
            <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($message)): ?>
        <div class="success-message"><?= $message ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <!-- Add Sale Form -->
    <div class="card">
        <h2>Record New Sale</h2>
        <form method="POST" class="form-modern">
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select Product</option>
                    <?php while ($row = $productsQuery->fetch()): ?>
                        <option value="<?= $row['ProductID'] ?>"><?= $row['Name'] ?> (Available: <?= $row['StockLevel'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity Sold</label>
                <input type="number" id="quantity" name="quantity" min="1" required>
            </div>

            <div class="form-group">
                <label for="sale_date">Sale Date</label>
                <input type="date" id="sale_date" name="sale_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="add" class="btn-primary">Record Sale</button>
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="card">
        <h2>Sales History</h2>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th>Sale Date</th>
                    <th>Total Amount</th>
                    <th>Remaining Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $salesQuery->fetch()): ?>
                    <tr>
                        <td><?= $row['SaleID'] ?></td>
                        <td><?= $row['ProductName'] ?></td>
                        <td><?= $row['QuantitySold'] ?></td>
                        <td><?= date('M d, Y', strtotime($row['SaleDate'])) ?></td>
                        <td>₱<?= number_format($row['TotalAmount'], 2) ?></td>
                        <td><?= $row['StockLevel'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
