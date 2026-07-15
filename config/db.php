<?php
// 1. Session ko top par hi start kar dein taaki pure project me user tracking login state active rahe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_project_url_base() {
    $document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $project_dir = str_replace('\\', '/', dirname(__DIR__));
    $base = '';

    if ($document_root !== '' && strpos($project_dir, $document_root) === 0) {
        $base = substr($project_dir, strlen($document_root));
    } else {
        $base = '/' . ltrim(basename($project_dir), '/');
    }

    $base = trim($base, '/');

    return $base === '' ? '' : '/' . $base;
}

function project_url($path = '') {
    $base = get_project_url_base();
    return $base . '/' . ltrim($path, '/');
}

// 2. Database Server Connection Credentials
$host = "localhost";      // Aapka local server host
$user = "root";           // Default XAMPP/WAMP database username
$password = "";           // Default XAMPP password blank hota hai
$dbname = "twitter_clone"; // Aapke database ka naam

// 3. Connect to MySQL server first (without selecting the database yet)
$conn = mysqli_connect($host, $user, $password);

// 4. Connection Failure Check Guard
if (!$conn) {
    die("<div style='color:red; font-family:Arial; text-align:center; margin-top:50px;'>
            <h2>⚠️ Database Connection Failed!</h2>
            <p>" . mysqli_connect_error() . "</p>
         </div>");
}

// 5. Create the database if it doesn't already exist
$createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!mysqli_query($conn, $createDbSql)) {
    die("<div style='color:red; font-family:Arial; text-align:center; margin-top:50px;'>
            <h2>⚠️ Database Setup Failed!</h2>
            <p>" . mysqli_error($conn) . "</p>
         </div>");
}

// 6. Select the database and initialize the schema if needed
if (!mysqli_select_db($conn, $dbname)) {
    die("<div style='color:red; font-family:Arial; text-align:center; margin-top:50px;'>
            <h2>⚠️ Database Selection Failed!</h2>
            <p>" . mysqli_error($conn) . "</p>
         </div>");
}

$sqlFile = __DIR__ . '/../database.sql';
if (file_exists($sqlFile)) {
    $schemaSql = file_get_contents($sqlFile);
    if ($schemaSql !== false && !empty(trim($schemaSql))) {
        if (!mysqli_multi_query($conn, $schemaSql)) {
            die("<div style='color:red; font-family:Arial; text-align:center; margin-top:50px;'>
                    <h2>⚠️ Schema Import Failed!</h2>
                    <p>" . mysqli_error($conn) . "</p>
                 </div>");
        }

        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    }
}

mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(100) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(20) DEFAULT ''");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS hobbies TEXT DEFAULT ''");
mysqli_query($conn, "ALTER TABLE tweets ADD COLUMN IF NOT EXISTS media_path VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE tweets ADD COLUMN IF NOT EXISTS media_type ENUM('image','video') DEFAULT NULL");

// 7. Character encoding handler mapping (Emojis support ke liye)
mysqli_set_charset($conn, "utf8mb4");
?>