<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the shared database bootstrap from the project root
require_once __DIR__ . '/../config/db.php';

$project_url_base = get_project_url_base();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($email) && !empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $hashedPassword);
        
        if (mysqli_stmt_execute($stmt)) {
            // Registration successful -> redirect to the login page
            header('Location: ' . project_url('login.php'));
            exit();
        } else {
            $error = 'That email or username already exists.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $project_url_base; ?>/">
    <!-- Corrected path to root app CSS folder -->
    <link rel="stylesheet" href="css/style.css">
    <title>Register / Twitter Clone</title>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">🐦</div>
            <h2>Join Twitter Clone</h2>
            <p class="auth-subtitle">Create your account and start sharing what's on your mind.</p>

            <?php if (!empty($error)): ?>
                <div class="auth-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="text" class="auth-input" name="username" placeholder="Username" required>
                <input type="email" class="auth-input" name="email" placeholder="Email" required>
                <input type="password" class="auth-input" name="password" placeholder="Password" required>
                <div class="auth-actions">
                    <button type="submit" class="tweet-btn auth-btn">Create account</button>
                </div>
            </form>

            <!-- Links straight to login.php since they reside in the same auth folder -->
            <p class="auth-help">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
    
    <!-- Corrected path to root app JavaScript folder -->
    <script src="js/main.js"></script>
</body>
</html>