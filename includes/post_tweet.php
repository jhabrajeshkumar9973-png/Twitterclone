<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['tweet_content'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$content = trim($_POST['tweet_content']);

if ($content !== '') {
    $escaped = mysqli_real_escape_string($conn, $content);
    mysqli_query($conn, "INSERT INTO tweets (user_id, content) VALUES ('$user_id', '$escaped')");
}

header('Location: ' . project_url('index.php'));
exit();
