<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/google_oauth.php';

$config = get_google_oauth_config();

if (empty($config['client_id']) || empty($config['client_secret'])) {
    $_SESSION['auth_error'] = 'Google login is not configured yet. Add your Google Client ID and Secret first.';
    header('Location: ' . project_url('auth/login.php'));
    exit();
}

if (empty($_GET['code'])) {
    $_SESSION['auth_error'] = 'Google sign-in was cancelled.';
    header('Location: ' . project_url('auth/login.php'));
    exit();
}

$token_request = [
    'code' => $_GET['code'],
    'client_id' => $config['client_id'],
    'client_secret' => $config['client_secret'],
    'redirect_uri' => $config['redirect_uri'],
    'grant_type' => 'authorization_code',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['token_endpoint']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_request));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);
if (empty($token_data['access_token'])) {
    $_SESSION['auth_error'] = 'Unable to authenticate with Google right now.';
    header('Location: ' . project_url('auth/login.php'));
    exit();
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['userinfo_endpoint'] . '?access_token=' . urlencode($token_data['access_token']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$user_info = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($user_info, true);
if (empty($google_user['email'])) {
    $_SESSION['auth_error'] = 'Google did not return your email address.';
    header('Location: ' . project_url('auth/login.php'));
    exit();
}

$email = trim($google_user['email']);
$username = trim($google_user['given_name'] ?: $google_user['name'] ?: 'google_user');
$profile_pic = $google_user['picture'] ?: '';

$stmt = mysqli_prepare($conn, 'SELECT id, username, profile_pic FROM users WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $safe_username = preg_replace('/[^a-zA-Z0-9_]+/', '', $username);
    $safe_username = $safe_username ?: 'google_user';

    $base_username = $safe_username;
    $counter = 1;
    while (true) {
        $check_stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($check_stmt, 's', $safe_username);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_num_rows($check_result) === 0) {
            break;
        }
        $safe_username = $base_username . $counter;
        $counter++;
    }

    $hashed_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $insert_stmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password, profile_pic) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($insert_stmt, 'ssss', $safe_username, $email, $hashed_password, $profile_pic);
    mysqli_stmt_execute($insert_stmt);

    $user_id = mysqli_insert_id($conn);
    $user = ['id' => $user_id, 'username' => $safe_username, 'profile_pic' => $profile_pic];
} else {
    $user_id = (int) $user['id'];
}

$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $user['username'] ?? $username;
$_SESSION['profile_pic'] = $user['profile_pic'] ?? $profile_pic;

header('Location: ' . project_url('index.php'));
exit();
