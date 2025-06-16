<?php
include 'auth.php';
include 'db.php';

// Check if user is an admin
checkAdmin();

// Add User
if (isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $roleID = $_POST['role_id'];
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $error = "Username already exists";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, RoleID) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $hashedPassword, $roleID])) {
            $success = "User created successfully!";
        } else {
            $error = "Error creating user";
        }
    }
}

// Delete User
if (isset($_GET['delete'])) {
    // Don't allow deleting yourself
    if ($_GET['delete'] != $_SESSION['user_id']) {
        $id = $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ?");
        $stmt->execute([$id]);
        $success = "User deleted successfully!";
    } else {
        $error = "You cannot delete your own account!";
    }
}

// Update User
if (isset($_POST['update'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $roleID = $_POST['role_id'];
    
    // Check if username exists for other users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ? AND UserID != ?");
    $stmt->execute([$username, $id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $error = "Username already exists";
    } else {
        if (!empty($_POST['password'])) {
            // Update with new password
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE Users SET Username = ?, Password = ?, RoleID = ? WHERE UserID = ?");
            $stmt->execute([$username, $hashedPassword, $roleID, $id]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE Users SET Username = ?, RoleID = ? WHERE UserID = ?");
            $stmt->execute([$username, $roleID, $id]);
        }
        $success = "User updated successfully!";
    }
}

// Fetch All Users
$stmt = $pdo->query("SELECT u.UserID, u.Username, u.RoleID, r.RoleName 
                      FROM Users u
                      JOIN Roles r ON u.RoleID = r.RoleID");
$users = $stmt->fetchAll();

// Fetch Roles for Dropdown
$stmt = $pdo->query("SELECT * FROM Roles");
$roles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="management-container">
    <div class="page-header">
        <h1 class="page-title">User Management</h1>
        <div class="action-bar">
            <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="card">
        <h2>Add New User</h2>
        <form method="POST" class="form-modern">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role_id">Role</label>
                <select id="role_id" name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['RoleID'] ?>"><?= $role['RoleName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" name="add" class="btn-primary">Add User</button>
            </div>
        </form>
    </div>

    <!-- Edit User Form -->
    <?php if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE UserID = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(); ?>
        <div class="form-container">
            <h2>Edit User</h2>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                <div class="form-group">
                    <label for="edit-username">Username</label>
                    <input type="text" id="edit-username" name="username" value="<?= $user['Username'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-password">Password (leave blank to keep unchanged)</label>
                    <input type="password" id="edit-password" name="password" placeholder="New password (optional)">
                </div>
                <div class="form-group">
                    <label for="edit-role_id">Role</label>
                    <select id="edit-role_id" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['RoleID'] ?>" <?= ($user['RoleID'] == $role['RoleID']) ? 'selected' : '' ?>>
                                <?= $role['RoleName'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="update" class="btn-primary">Update User</button>
                <a href="users.php" class="btn-secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="card">
        <h2>User List</h2>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?= $row['UserID'] ?></td>
                        <td><?= $row['Username'] ?></td>
                        <td><?= $row['RoleName'] ?></td>
                        <td class="action-buttons">
                            <a href="users.php?edit=<?= $row['UserID'] ?>" class="btn-edit">Edit</a>
                            <?php if ($row['UserID'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?= $row['UserID'] ?>" class="btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>