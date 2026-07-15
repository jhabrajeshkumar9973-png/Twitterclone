<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$project_url_base = get_project_url_base();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . project_url('login.php'));
    exit();
}

$my_id = (int) $_SESSION['user_id'];
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_results = [];
$trending_topics = [
    ['tag' => 'Community', 'title' => 'Local meetups', 'count' => '6.8K posts'],
    ['tag' => 'Design', 'title' => 'Minimal product UI', 'count' => '5.2K posts'],
    ['tag' => 'Lifestyle', 'title' => 'Sunday reset', 'count' => '4.1K posts'],
    ['tag' => 'Gaming', 'title' => 'Co-op weekend', 'count' => '3.4K posts']
];

if ($search_query !== '') {
    $escaped = mysqli_real_escape_string($conn, $search_query);
    $result = mysqli_query($conn, "SELECT id, username, profile_pic FROM users WHERE username LIKE '%$escaped%' AND id != '$my_id' LIMIT 6");
    if ($result) {
        $search_results = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

$sql = "SELECT notifications.*, users.username
        FROM notifications
        JOIN users ON notifications.sender_id = users.id
        WHERE notifications.user_id = '$my_id'
        ORDER BY notifications.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $project_url_base; ?>/">
    <link rel="stylesheet" href="css/style.css">
    <title>Notifications / Twitter Clone</title>
</head>
<body>
<div class="main-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo">🕊️</div>
            <div class="sidebar-user-chip">
                <span class="sidebar-user-label">Welcome</span>
                <strong>@<?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?php echo project_url('index.php'); ?>" class="nav-item"><span class="nav-icon">🏠</span> Home</a>
            <a href="<?php echo project_url('explore.php'); ?>" class="nav-item"><span class="nav-icon">🧭</span> Explore</a>
            <a href="<?php echo project_url('profile.php'); ?>" class="nav-item"><span class="nav-icon">👤</span> Profile</a>
            <a href="<?php echo project_url('bookmarks.php'); ?>" class="nav-item"><span class="nav-icon">🔖</span> Bookmarks</a>
            <a href="<?php echo project_url('notifications.php'); ?>" class="nav-item active"><span class="nav-icon">🔔</span> Notifications</a>
            <a href="<?php echo project_url('chat.php'); ?>" class="nav-item"><span class="nav-icon">💬</span> Messages</a>
        </nav>
        <a href="<?php echo project_url('logout.php'); ?>" class="logout-btn">🔓 Log out</a>
    </aside>

    <main class="timeline">
        <div class="feed-header">
            <h2>Notifications</h2>
        </div>
        <div class="tweets-container">
            <?php if (!$result || mysqli_num_rows($result) === 0): ?>
                <div class="empty-state">You have no notifications yet.</div>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="tweet">
                        <p>
                            <strong>@<?php echo htmlspecialchars($row['username']); ?></strong>
                            <?php echo htmlspecialchars($row['type']); ?>
                            <?php if ($row['type'] === 'like'): ?>
                                liked your post.
                            <?php elseif ($row['type'] === 'follow'): ?>
                                followed you.
                            <?php else: ?>
                                commented on your post.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>
    <aside class="right-sidebar">
        <div class="right-panel-card">
            <form class="search-box" method="GET" action="<?php echo project_url(basename($_SERVER['PHP_SELF'])); ?>">
                <input type="text" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search users">
                <button type="submit" class="tweet-btn">Search</button>
            </form>
            <div class="search-results">
                <?php if (!empty($search_results)): ?>
                    <?php foreach ($search_results as $user): ?>
                        <a href="<?php echo project_url('profile.php?user_id=' . (int) $user['id']); ?>" class="search-user-card">
                            <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;" alt="DP">
                            <span>@<?php echo htmlspecialchars($user['username']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php elseif ($search_query !== ''): ?>
                    <div class="empty-state">No users found.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="right-panel-card">
            <div class="panel-card-header">
                <h3>Trending now</h3>
                <span>Live</span>
            </div>
            <div class="trending-list">
                <?php foreach ($trending_topics as $topic): ?>
                    <a href="<?php echo project_url('explore.php'); ?>" class="trending-item">
                        <span class="trend-label">#<?php echo htmlspecialchars($topic['tag']); ?></span>
                        <strong><?php echo htmlspecialchars($topic['title']); ?></strong>
                        <small><?php echo htmlspecialchars($topic['count']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</div>
<script src="js/main.js"></script>
</body>
</html>
