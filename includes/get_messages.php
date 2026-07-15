<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$my_id = (int) $_SESSION['user_id'];
$other_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($other_id <= 0) {
    die('No chat selected.');
}

$sql = "SELECT * FROM messages WHERE (sender_id = '$my_id' AND receiver_id = '$other_id') OR (sender_id = '$other_id' AND receiver_id = '$my_id') ORDER BY created_at ASC";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo '<div class="msg-wrapper ' . ($row['sender_id'] == $my_id ? 'sent' : 'received') . '"><div class="msg-text">' . htmlspecialchars($row['message']) . '</div></div>';
}
