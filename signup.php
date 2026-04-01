<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation: Check empty fields
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Validation: Check if email already exists
        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // Password security: Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Database Insert
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($insert_stmt->execute()) {
                // Success: Redirect to login.php
                header("Location: login.php?signup=success");
                exit();
            } else {
                $error = "An error occurred during registration. Please try again.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Create Account</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .error-msg {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.15);
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 107, 107, 0.3);
            text-align: center;
        }
        
        /* Smooth Entrance Animation */
        .fade-in {
            animation: fadeIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <h1 style="display:flex; align-items:center; justify-content:center;">
            <img src="assets/logo.png" alt="SoulMate Logo" height="40" style="margin-right: 15px; border-radius: 8px;">
            Create Account
        </h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <input type="text" name="name" placeholder="Name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Create Account</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
    <script src="assets/script.js"></script>
</body>
</html>
