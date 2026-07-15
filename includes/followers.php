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
$profile_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['user_id']) ? (int) $_GET['user_id'] : $my_id);

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$profile_id' LIMIT 1");
if (!$user_query || mysqli_num_rows($user_query) === 0) {
    die('User not found.');
}
$profile_user = mysqli_fetch_assoc($user_query);

$followers_result = mysqli_query($conn, "SELECT users.id, users.username, users.profile_pic FROM follows JOIN users ON follows.follower_id = users.id WHERE follows.following_id = '$profile_id' ORDER BY follows.id DESC");
$followers = $followers_result ? mysqli_fetch_all($followers_result, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Followers / Twitter Clone</title>
</head>
<body>
    <div class="main-layout">
        <aside class="sidebar">
            <div class="logo">TW</div>
            <div class="user-tag">@<?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <nav>
                <a href="index.php" class="nav-item">Home</a>
                <a href="explore.php" class="nav-item">Explore</a>
                <a href="profile.php" class="nav-item">Profile</a>
                <a href="chat.php" class="nav-item">Messages</a>
                <a href="bookmarks.php" class="nav-item">Bookmarks</a>
                <a href="notifications.php" class="nav-item">Notifications</a>
            </nav>
            <a href="logout.php" class="logout-btn">Log out</a>
        </aside>

        <main class="timeline">
            <section class="profile-header">
                <div class="profile-banner"></div>
                <div class="profile-card">
                    <img src="uploads/<?php echo !empty($profile_user['profile_pic']) ? htmlspecialchars($profile_user['profile_pic']) : 'default.png'; ?>" class="profile-dp" alt="Profile picture">
                    <div class="profile-card-info">
                        <div class="profile-name-row">
                            <div>
                                <h2>@<?php echo htmlspecialchars($profile_user['username']); ?></h2>
                                <p class="profile-role">Followers of <?php echo htmlspecialchars($profile_user['username']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="tweets-container">
                <?php if (empty($followers)): ?>
                    <div class="empty-state">No followers yet.</div>
                <?php else: ?>
                    <?php foreach ($followers as $follower): ?>
                        <article class="tweet">
                            <div class="tweet-header">
                                <img src="uploads/<?php echo !empty($follower['profile_pic']) ? htmlspecialchars($follower['profile_pic']) : 'default.png'; ?>" class="tweet-avatar" alt="Follower avatar">
                                <div class="tweet-meta">
                                    <div class="tweet-user">
                                        <a href="profile.php?user_id=<?php echo (int) $follower['id']; ?>" class="tweet-name"><?php echo htmlspecialchars($follower['username']); ?></a>
                                        <span class="tweet-handle">@<?php echo htmlspecialchars($follower['username']); ?></span>
                                    </div>
                                </div>
                                <a href="profile.php?user_id=<?php echo (int) $follower['id']; ?>" class="tweet-action-btn">View</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>

        <aside class="right-sidebar">
            <div class="notification-card">
                <h3>Follower list</h3>
                <p>See who is following this profile.</p>
            </div>
        </aside>
    </div>
</body>
</html>
