<?php
session_start();
include('db.php');
include('auth.php');  // Add this line to include auth.php

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN) {
        header("Location: dashboard.php");
    } else {
        header("Location: staff_dashboard.php");
    }
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query the database for the user
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Verify the password
        if (password_verify($password, $user['Password'])) {
            // If password is correct, set session and redirect
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role_id'] = $user['RoleID'];
            
            // Redirect based on role
            if ($user['RoleID'] == ROLE_ADMIN) {
                header("Location: dashboard.php");
            } else {
                header("Location: staff_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "No user found with that username.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        @font-face {
            font-family: 'Thunderstrike';
            src: url('fonts/thunderstrike.ttf') format('truetype');
        }
        
        h1 {
            font-family: 'Thunderstrike', sans-serif;
            color: #0066cc;
            text-align: center;
            font-size: 4rem;
            margin-bottom: 2rem;
            border: none;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AIM</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="login-form">
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Your password" required>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="login" class="btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>