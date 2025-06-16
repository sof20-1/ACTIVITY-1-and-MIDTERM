<?php
include 'auth.php';
include 'db.php';

// Check if user is an admin
checkAdmin();

// Fetch suppliers for the dropdown
$suppliersQuery = $pdo->query("SELECT * FROM Suppliers");
$suppliers = $suppliersQuery->fetchAll();

// Modify the Add Product section
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stockLevel = $_POST['stockLevel'];
    $selectedSuppliers = isset($_POST['suppliers']) ? $_POST['suppliers'] : [];

    try {
        $pdo->beginTransaction();

        // Insert product
        $stmt = $pdo->prepare("INSERT INTO Products (Name, Category, Price, StockLevel) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $category, $price, $stockLevel]);
        $productId = $pdo->lastInsertId();

        // Insert supplier relationships
        if (!empty($selectedSuppliers)) {
            $supplierStmt = $pdo->prepare("INSERT INTO SupplierProducts (SupplierID, ProductID) VALUES (?, ?)");
            foreach ($selectedSuppliers as $supplierId) {
                $supplierStmt->execute([$supplierId, $productId]);
            }
        }

        $pdo->commit();
        header("Location: products.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to add product: " . $e->getMessage();
    }
}

// Delete Product and Related Records
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete from Sales table first
        $stmt = $pdo->prepare("DELETE FROM Sales WHERE ProductID = ?");
        $stmt->execute([$id]);

        // Delete from Stock table
        $stmt = $pdo->prepare("DELETE FROM Stock WHERE ProductID = ?");
        $stmt->execute([$id]);

        // Now delete from Products table
        $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        header("Location: products.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to delete product: " . $e->getMessage();
    }
}


// Update Product
if (isset($_POST['update'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stockLevel = $_POST['stockLevel'];

    $stmt = $pdo->prepare("UPDATE Products SET Name = ?, Category = ?, Price = ?, StockLevel = ? WHERE ProductID = ?");
    $stmt->execute([$name, $category, $price, $stockLevel, $id]);
    header("Location: products.php");
    exit();
}

// Fetch All Products
$stmt = $pdo->query("SELECT * FROM Products");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="management-container">
    <div class="page-header">
        <h1 class="page-title">Products Management</h1>
        <div class="action-bar">
            <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
        </div>
    </div>

    <!-- Add Product Form -->
    <div class="card">
        <h2>Add New Product</h2>
        <form method="POST" class="form-modern">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" id="price" name="price" required>
            </div>
            <div class="form-group">
                <label for="stockLevel">Stock Level</label>
                <input type="number" id="stockLevel" name="stockLevel" required>
            </div>
            <div class="form-group">
                <label>Suppliers</label>
                <div class="checkbox-container">
                    <?php if (empty($suppliers)): ?>
                        <div class="no-data-message">No Suppliers Yet</div>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="suppliers[]" value="<?= $supplier['SupplierID'] ?>">
                                <?= $supplier['Name'] ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="add" class="btn-primary">Add Product</button>
            </div>
        </form>
    </div>

    <!-- Edit Product Form -->
    <?php if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM Products WHERE ProductID = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(); ?>
        <div class="form-container">
            <h2>Edit Product</h2>
            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                <div class="form-group">
                    <label for="edit-name">Product Name</label>
                    <input type="text" id="edit-name" name="name" value="<?= $product['Name'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-category">Category</label>
                    <input type="text" id="edit-category" name="category" value="<?= $product['Category'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-price">Price</label>
                    <input type="number" step="0.01" id="edit-price" name="price" value="<?= $product['Price'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-stockLevel">Stock Level</label>
                    <input type="number" id="edit-stockLevel" name="stockLevel" value="<?= $product['StockLevel'] ?>" required>
                </div>
                <button type="submit" name="update" class="btn-primary">Update Product</button>
                <a href="products.php" class="btn-secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- Product Table -->
    <div class="card">
        <h2>Product List</h2>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (₱)</th>
                    <th>Stock Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?= $row['ProductID'] ?></td>
                        <td><?= $row['Name'] ?></td>
                        <td><?= $row['Category'] ?></td>
                        <td><?= number_format($row['Price'], 2) ?></td>
                        <td>
                            <span class="status-indicator <?= $row['StockLevel'] < 10 ? 'status-low' : 'status-good' ?>">
                                <?= $row['StockLevel'] ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="products.php?edit=<?= $row['ProductID'] ?>" class="btn-edit">Edit</a>
                            <a href="products.php?delete=<?= $row['ProductID'] ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>