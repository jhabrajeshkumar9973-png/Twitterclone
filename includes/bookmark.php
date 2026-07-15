<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// 1. Security Check: Agar user logged in nahi hai ya tweet_id missing hai, toh login par bhejo
if (!isset($_SESSION['user_id']) || !isset($_GET['tweet_id'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$tweet_id = (int)$_GET['tweet_id'];

// 2. Verification: Check karein ki kya yeh post pehle se bookmarked hai?
$check_query = "SELECT * FROM bookmarks WHERE user_id = '$user_id' AND tweet_id = '$tweet_id'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    // CASE A: Agar pehle se saved hai, toh ab UNSAVE (Delete) karein
    $delete_sql = "DELETE FROM bookmarks WHERE user_id = '$user_id' AND tweet_id = '$tweet_id'";
    mysqli_query($conn, $delete_sql);
} else {
    // CASE B: Agar pehle se saved nahi hai, toh ab SAVE (Insert) karein
    $insert_sql = "INSERT INTO bookmarks (user_id, tweet_id) VALUES ('$user_id', '$tweet_id')";
    mysqli_query($conn, $insert_sql);
}

// 3. Redirect Back: User ko wapas usi page par bhejein jahan se usne click kiya tha
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ' . project_url('index.php')); // Fallback agar reference page na mile
}
exit();
?>