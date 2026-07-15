<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    header('Location: login.php');
    exit();
}

$sender_id = (int) $_SESSION['user_id'];
$receiver_id = (int) $_POST['receiver_id'];
$message = trim($_POST['message']);

if ($message !== '') {
    $escaped = mysqli_real_escape_string($conn, $message);
    mysqli_query($conn, "INSERT INTO messages (sender_id, receiver_id, message) VALUES ('$sender_id', '$receiver_id', '$escaped')");
}

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($is_ajax) {
    echo 'success';
    exit();
}

header('Location: ' . project_url('chat.php?user_id=' . $receiver_id));
exit();
