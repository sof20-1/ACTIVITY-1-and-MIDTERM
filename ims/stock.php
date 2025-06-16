<?php
include 'auth.php';
include 'db.php';

// Check if user is logged in
checkLogin();

// Add Stock
if (isset($_POST['add'])) {
    $productID = $_POST['product_id'];
    $supplierID = $_POST['supplier_id'];
    $quantity = $_POST['quantity'];
    $dateAdded = $_POST['date_added'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Add to stock history
        $stmt = $pdo->prepare("INSERT INTO Stock (ProductID, SupplierID, QuantityAdded, DateAdded) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productID, $supplierID, $quantity, $dateAdded]);
        
        // Update product stock level
        $stmt = $pdo->prepare("UPDATE Products SET StockLevel = StockLevel + ? WHERE ProductID = ?");
        $stmt->execute([$quantity, $productID]);
        
        $pdo->commit();
        header("Location: stock.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Fetch Products and Suppliers for Form Selection
$productsQuery = $pdo->query("SELECT * FROM Products");
$suppliersQuery = $pdo->query("SELECT * FROM Suppliers");

// Fetch Stock Entries with product names
$stockQuery = $pdo->query("
    SELECT s.StockID, p.Name AS ProductName, sup.Name AS SupplierName, 
           s.QuantityAdded, s.DateAdded, p.StockLevel
    FROM Stock s
    JOIN Products p ON s.ProductID = p.ProductID
    JOIN Suppliers sup ON s.SupplierID = sup.SupplierID
    ORDER BY s.DateAdded DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Stock</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="management-container">
    <div class="page-header">
        <h1 class="page-title">Stock Management</h1>
        <div class="action-bar">
            <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
        </div>
    </div>

    <!-- Add Stock Form -->
    <div class="card">
        <h2>Add Stock</h2>
        <form method="POST" class="form-modern">
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select Product</option>
                    <?php while ($row = $productsQuery->fetch()): ?>
                        <option value="<?= $row['ProductID'] ?>"><?= $row['Name'] ?> (Current Stock: <?= $row['StockLevel'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php while ($row = $suppliersQuery->fetch()): ?>
                        <option value="<?= $row['SupplierID'] ?>"><?= $row['Name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="date_added">Date</label>
                <input type="date" id="date_added" name="date_added" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add" class="btn-primary">Add Stock</button>
            </div>
        </form>
    </div>

    <!-- Stock Table -->
    <div class="card">
        <h2>Stock History</h2>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Supplier</th>
                    <th>Quantity Added</th>
                    <th>Date Added</th>
                    <th>Current Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stockQuery->fetch()): ?>
                    <tr>
                        <td><?= $row['StockID'] ?></td>
                        <td><?= $row['ProductName'] ?></td>
                        <td><?= $row['SupplierName'] ?></td>
                        <td><?= $row['QuantityAdded'] ?></td>
                        <td><?= date('M d, Y', strtotime($row['DateAdded'])) ?></td>
                        <td><?= $row['StockLevel'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>