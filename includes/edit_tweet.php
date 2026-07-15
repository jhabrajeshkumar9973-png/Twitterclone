<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['tweet_id'], $_POST['tweet_content'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$tweet_id = (int) $_POST['tweet_id'];
$content = trim($_POST['tweet_content']);

if ($content === '') {
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? project_url('index.php'));
    exit();
}

$escaped = mysqli_real_escape_string($conn, $content);
mysqli_query($conn, "UPDATE tweets SET content = '$escaped' WHERE id = $tweet_id AND user_id = $user_id");

if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ' . project_url('index.php'));
}
exit();
