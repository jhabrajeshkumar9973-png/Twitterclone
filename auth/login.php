<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use the shared database bootstrap from the project root
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google_oauth.php';

$project_url_base = get_project_url_base();
$oauth_config = get_google_oauth_config();

$error = '';
$ajax_request = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (!empty($_SESSION['auth_error'])) {
    $error = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($conn, 'SELECT id, username, password, profile_pic FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];

            if ($ajax_request) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Login successful']);
                exit();
            }

            header('Location: ' . project_url('index.php'));
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }

    if ($ajax_request) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $project_url_base; ?>/">
    <!-- Reaches back to the root app folder to find the CSS -->
    <link rel="stylesheet" href="css/style.css">
    <title>Login / Twitter Clone</title>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">🐦</div>
            <h2>Welcome back</h2>
            <p class="auth-subtitle">Sign in to continue to your timeline.</p>

            <?php if (!empty($error)): ?>
                <div class="auth-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="loginForm" action="login.php">
                <input type="email" class="auth-input" name="email" placeholder="Email" required>
                <input type="password" class="auth-input" name="password" placeholder="Password" required>
                <div class="auth-actions">
                    <button type="submit" class="tweet-btn auth-btn">Log in</button>
                    <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?php echo urlencode($oauth_config['client_id']); ?>&redirect_uri=<?php echo urlencode($oauth_config['redirect_uri']); ?>&response_type=code&scope=openid%20email%20profile" class="tweet-btn auth-btn" style="text-decoration:none; text-align:center; display:inline-block; background:linear-gradient(135deg,#4285f4,#34a853); box-shadow:none;">Continue with Google</a>
                </div>
            </form>

            <!-- Links directly to register.php since they live in the same auth folder -->
            <p class="auth-help">New here? <a href="register.php">Create an account</a></p>
        </div>
    </div>
    
    <!-- Reaches back to the root app folder to find your JS assets -->
    <script src="js/main.js"></script>
</body>
</html>