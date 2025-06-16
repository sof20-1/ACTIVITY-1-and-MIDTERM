<?php
include 'db.php';

try {
    // Create Roles table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS Roles (
        RoleID INT PRIMARY KEY AUTO_INCREMENT,
        RoleName VARCHAR(50) NOT NULL
    )");
    
    // Insert default roles if they don't exist
    $checkRoles = $pdo->query("SELECT COUNT(*) FROM Roles")->fetchColumn();
    if ($checkRoles == 0) {
        $pdo->exec("INSERT INTO Roles (RoleID, RoleName) VALUES 
            (1, 'Admin'),
            (2, 'Staff')
        ");
    }
    
    // Create Users table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS Users (
        UserID INT PRIMARY KEY AUTO_INCREMENT,
        Username VARCHAR(50) UNIQUE NOT NULL,
        Password VARCHAR(255) NOT NULL,
        RoleID INT NOT NULL DEFAULT 2,
        FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
    )");
    
    // Create Products table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS Products (
        ProductID INT PRIMARY KEY AUTO_INCREMENT,
        Name VARCHAR(100) NOT NULL,
        Category VARCHAR(50) NOT NULL,
        Price DECIMAL(10,2) NOT NULL,
        StockLevel INT NOT NULL DEFAULT 0
    )");
    
    // Create Suppliers table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS Suppliers (
        SupplierID INT PRIMARY KEY AUTO_INCREMENT,
        Name VARCHAR(100) NOT NULL,
        ContactInfo VARCHAR(255) NOT NULL
    )");
    
    // Create SupplierProducts table (new many-to-many relationship table)
    $pdo->exec("CREATE TABLE IF NOT EXISTS SupplierProducts (
        SupplierID INT NOT NULL,
        ProductID INT NOT NULL,
        PRIMARY KEY (SupplierID, ProductID),
        FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID) ON DELETE CASCADE,
        FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE CASCADE
    )");
    
    // Create Stock table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS Stock (
        StockID INT PRIMARY KEY AUTO_INCREMENT,
        ProductID INT NOT NULL,
        SupplierID INT NOT NULL,
        QuantityAdded INT NOT NULL,
        DateAdded DATE NOT NULL,
        FOREIGN KEY (ProductID) REFERENCES Products(ProductID),
        FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID)
    )");
    
    // Create Sales table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS Sales (
        SaleID INT PRIMARY KEY AUTO_INCREMENT,
        ProductID INT NOT NULL,
        QuantitySold INT NOT NULL,
        SaleDate DATE NOT NULL,
        TotalAmount DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
    )");
    
    // Check if the admin user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ?");
    $stmt->execute(['admin']);
    $userExists = $stmt->fetchColumn();
    
    if ($userExists == 0) {
        // Create default admin user if it doesn't exist
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, RoleID) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 1]);
        echo "Default admin user created with username: 'admin' and password: 'admin123'";
    } else {
        echo "Admin user already exists.";
    }
    
    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>