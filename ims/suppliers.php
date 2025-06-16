<?php
include 'auth.php';
include 'db.php';

// Check if user is an admin
checkAdmin();

// Fetch products for the dropdown
$productsQuery = $pdo->query("SELECT * FROM Products");
$products = $productsQuery->fetchAll();

// Modify the Add Supplier section
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $selectedProducts = isset($_POST['products']) ? $_POST['products'] : [];

    try {
        $pdo->beginTransaction();

        // Insert supplier
        $stmt = $pdo->prepare("INSERT INTO Suppliers (Name, ContactInfo) VALUES (?, ?)");
        $stmt->execute([$name, $contact]);
        $supplierId = $pdo->lastInsertId();

        // Insert product relationships
        if (!empty($selectedProducts)) {
            $productStmt = $pdo->prepare("INSERT INTO SupplierProducts (SupplierID, ProductID) VALUES (?, ?)");
            foreach ($selectedProducts as $productId) {
                $productStmt->execute([$supplierId, $productId]);
            }
        }

        $pdo->commit();
        header("Location: suppliers.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to add supplier: " . $e->getMessage();
    }
}

// Delete Supplier and Related Stock Entries
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete stock entries related to the supplier
        $stmt = $pdo->prepare("DELETE FROM Stock WHERE SupplierID = ?");
        $stmt->execute([$id]);

        // Now delete the supplier
        $stmt = $pdo->prepare("DELETE FROM Suppliers WHERE SupplierID = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        header("Location: suppliers.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to delete supplier: " . $e->getMessage();
    }
}


// Fetch Suppliers
$stmt = $pdo->query("SELECT * FROM Suppliers");
$suppliers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Suppliers</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="management-container">
    <div class="page-header">
        <h1 class="page-title">Suppliers</h1>
        <div class="action-bar">
            <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
        </div>
    </div>

    <!-- Add Supplier Form -->
    <div class="card">
        <h2>Add New Supplier</h2>
        <form method="POST" class="form-modern">
            <div class="form-group">
                <label for="name">Supplier Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact Information</label>
                <input type="text" id="contact" name="contact" required>
            </div>
            <div class="form-group">
                <label>Products</label>
                <div class="checkbox-container">
                    <?php foreach ($products as $product): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="products[]" value="<?= $product['ProductID'] ?>">
                            <?= $product['Name'] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="add" class="btn-primary">Add Supplier</button>
            </div>
        </form>
    </div>

    <!-- Supplier Table -->
    <div class="card">
        <h2>Supplier List</h2>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact Info</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $row): ?>
                    <tr>
                        <td><?= $row['SupplierID'] ?></td>
                        <td><?= $row['Name'] ?></td>
                        <td><?= $row['ContactInfo'] ?></td>
                        <td class="action-buttons">
                            <a href="suppliers.php?delete=<?= $row['SupplierID'] ?>" class="btn-delete" onclick="return confirm('Delete this supplier?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>