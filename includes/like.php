<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['tweet_id'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$tweet_id = (int) $_GET['tweet_id'];

$check_query = mysqli_query($conn, "SELECT * FROM likes WHERE user_id = $user_id AND tweet_id = $tweet_id");

if (mysqli_num_rows($check_query) > 0) {
    mysqli_query($conn, "DELETE FROM likes WHERE user_id = $user_id AND tweet_id = $tweet_id");
} else {
    mysqli_query($conn, "INSERT INTO likes (user_id, tweet_id) VALUES ($user_id, $tweet_id)");
}

if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ' . project_url('index.php'));
}
exit();
