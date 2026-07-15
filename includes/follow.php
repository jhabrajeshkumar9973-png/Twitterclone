<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$follower_id = (int) $_SESSION['user_id'];
$following_id = (int) $_GET['user_id'];

if ($follower_id !== $following_id) {
    $check = mysqli_query($conn, "SELECT * FROM follows WHERE follower_id = '$follower_id' AND following_id = '$following_id'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM follows WHERE follower_id = '$follower_id' AND following_id = '$following_id'");
    } else {
        mysqli_query($conn, "INSERT INTO follows (follower_id, following_id) VALUES ('$follower_id', '$following_id')");
        mysqli_query($conn, "INSERT INTO notifications (user_id, sender_id, type) VALUES ('$following_id', '$follower_id', 'follow')");
    }
}

header('Location: ' . project_url('profile.php?user_id=' . $following_id));
exit();
