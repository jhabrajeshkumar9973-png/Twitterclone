<?php
if (!function_exists('render_page')) {
    function render_page($title, $activePage, $content) {
        global $conn;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . project_url('login.php'));
            exit();
        }

        $my_id = (int) $_SESSION['user_id'];
        $search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
        $search_results = [];

        if ($search_query !== '') {
            $escaped = mysqli_real_escape_string($conn, $search_query);
            $result = mysqli_query($conn, "SELECT id, username, profile_pic FROM users WHERE username LIKE '%$escaped%' AND id != '$my_id' LIMIT 6");
            if ($result) {
                $search_results = mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
        }

        $profilePic = !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.png';
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>$title / Twitter Clone</title>
</head>
<body>
    <div class="main-layout">
        <aside class="sidebar">
            <div class="logo" style="font-size: 30px; color: #1d9bf0; margin-bottom: 20px; padding-left: 10px;">🕊️</div>
            <h3>@{$_SESSION['username']}</h3>
            <a href="index.php" class="