<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Query to check user security
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Verify hash against inserted pass
            if (password_verify($password, $row['password'])) {
                // Login Success -> Bind logic to Session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                
                // Immediately transition user inside the structure
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with that email.";
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
    <title>SoulMate - Login</title>
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
        .success-msg {
            color: #4cd137;
            background: rgba(76, 209, 55, 0.15);
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(76, 209, 55, 0.3);
            text-align: center;
        }
        .fade-in { animation: fadeIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <h1 style="display:flex; align-items:center; justify-content:center;">
            <img src="assets/logo.png" alt="SoulMate Logo" height="40" style="margin-right: 15px; border-radius: 8px;">
            Welcome Back
        </h1>
        
        <?php 
        // Display Signup Success if redirected directly from signup.php seamlessly
        if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
            <div class="success-msg">Account created successfully! Please login.</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="signup.php">Sign up</a></p>
    </div>
    <script src="assets/script.js"></script>
</body>
</html>
