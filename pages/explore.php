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
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'everyone' ? 'everyone' : 'following';
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_results = [];
$trending_topics = [
    ['tag' => 'Music', 'title' => 'New album drops', 'count' => '12.9K posts'],
    ['tag' => 'Travel', 'title' => 'Summer escapes', 'count' => '8.4K posts'],
    ['tag' => 'Tech', 'title' => 'Open-source builds', 'count' => '7.1K posts'],
    ['tag' => 'Food', 'title' => 'Street food night', 'count' => '4.9K posts']
];

if ($search_query !== '') {
    $escaped = mysqli_real_escape_string($conn, $search_query);
    $result = mysqli_query($conn, "SELECT id, username, profile_pic FROM users WHERE username LIKE '%$escaped%' AND id != '$my_id' LIMIT 6");
    if ($result) {
        $search_results = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

function render_explore_feed($conn, $my_id, $mode) {
    $where_clause = $mode === 'following'
        ? "WHERE tweets.user_id = '$my_id' OR tweets.user_id IN (SELECT following_id FROM follows WHERE follower_id = '$my_id')"
        : "";

    $sql = "SELECT tweets.*, users.username, users.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE likes.tweet_id = tweets.id) AS like_count,
            (SELECT COUNT(*) FROM reposts WHERE reposts.tweet_id = tweets.id) AS repost_count,
            (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.tweet_id = tweets.id) AS bookmark_count,
            (SELECT COUNT(*) FROM likes WHERE likes.tweet_id = tweets.id AND likes.user_id = '$my_id') AS user_liked,
            (SELECT COUNT(*) FROM reposts WHERE reposts.tweet_id = tweets.id AND reposts.user_id = '$my_id') AS user_reposted,
            (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.tweet_id = tweets.id AND bookmarks.user_id = '$my_id') AS user_bookmarked
            FROM tweets
            JOIN users ON tweets.user_id = users.id
            $where_clause
            ORDER BY tweets.created_at DESC";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$following_feed = render_explore_feed($conn, $my_id, 'following');
$everyone_feed = render_explore_feed($conn, $my_id, 'everyone');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $project_url_base; ?>/">
    <link rel="stylesheet" href="css/style.css">
    <title>Explore / Twitter Clone</title>
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
            <a href="<?php echo project_url('explore.php'); ?>" class="nav-item active"><span class="nav-icon">🧭</span> Explore</a>
            <a href="<?php echo project_url('profile.php'); ?>" class="nav-item"><span class="nav-icon">👤</span> Profile</a>
            <a href="<?php echo project_url('bookmarks.php'); ?>" class="nav-item"><span class="nav-icon">🔖</span> Bookmarks</a>
            <a href="<?php echo project_url('notifications.php'); ?>" class="nav-item"><span class="nav-icon">🔔</span> Notifications</a>
            <a href="<?php echo project_url('chat.php'); ?>" class="nav-item"><span class="nav-icon">💬</span> Messages</a>
        </nav>
        <a href="<?php echo project_url('logout.php'); ?>" class="logout-btn">🔓 Log out</a>
    </aside>

    <main class="timeline">
        <div class="feed-header">
            <h2>Explore</h2>
        </div>

        <div class="explore-summary">
            <a class="explore-card<?php echo $active_tab === 'following' ? ' active' : ''; ?>" href="<?php echo project_url('explore.php?tab=following'); ?>">
                <h3>Following</h3>
                <p>See the latest posts from the people you follow.</p>
            </a>
            <a class="explore-card<?php echo $active_tab === 'everyone' ? ' active' : ''; ?>" href="<?php echo project_url('explore.php?tab=everyone'); ?>">
                <h3>Everyone</h3>
                <p>Browse the broader conversation happening across the app.</p>
            </a>
        </div>

        <div class="explore-section<?php echo $active_tab === 'following' ? ' active' : ''; ?>">
            <?php if (empty($following_feed)): ?>
                <div class="empty-state">No posts from your network yet.</div>
            <?php else: ?>
                <?php foreach ($following_feed as $row): ?>
                    <div class="tweet">
                        <div class="tweet-header" style="display: flex; align-items: center; margin-bottom: 8px;">
                            <img src="uploads/<?php echo !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : 'default.png'; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;" alt="DP">
                            <div>
                                <a href="profile.php?user_id=<?php echo (int) $row['user_id']; ?>" style="font-weight: 700; color: #e7e9ea; text-decoration: none;"><?php echo htmlspecialchars($row['username']); ?></a>
                                <div style="color: #8b98a5; font-size: 13px;"><?php echo htmlspecialchars($row['created_at']); ?></div>
                            </div>
                        </div>
                        <p style="margin-bottom: 10px; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($row['content']); ?></p>
                        <div class="tweet-actions">
                            <a href="like.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn like<?php echo ((int) $row['user_liked'] > 0) ? ' active' : ''; ?>">♡ <span class="count"><?php echo (int) $row['like_count']; ?></span></a>
                            <a href="repost.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn repost<?php echo ((int) $row['user_reposted'] > 0) ? ' active' : ''; ?>">🔁 <span class="count"><?php echo (int) $row['repost_count']; ?></span></a>
                            <a href="bookmark.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn save<?php echo ((int) $row['user_bookmarked'] > 0) ? ' active' : ''; ?>">🔖 <span class="count"><?php echo (int) $row['bookmark_count']; ?></span></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="explore-section<?php echo $active_tab === 'everyone' ? ' active' : ''; ?>">
            <?php if (empty($everyone_feed)): ?>
                <div class="empty-state">No posts available yet.</div>
            <?php else: ?>
                <?php foreach ($everyone_feed as $row): ?>
                    <div class="tweet">
                        <div class="tweet-header" style="display: flex; align-items: center; margin-bottom: 8px;">
                            <img src="uploads/<?php echo !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : 'default.png'; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;" alt="DP">
                            <div>
                                <a href="profile.php?user_id=<?php echo (int) $row['user_id']; ?>" style="font-weight: 700; color: #e7e9ea; text-decoration: none;"><?php echo htmlspecialchars($row['username']); ?></a>
                                <div style="color: #8b98a5; font-size: 13px;"><?php echo htmlspecialchars($row['created_at']); ?></div>
                            </div>
                        </div>
                        <p style="margin-bottom: 10px; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($row['content']); ?></p>
                        <div class="tweet-actions">
                            <a href="like.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn like<?php echo ((int) $row['user_liked'] > 0) ? ' active' : ''; ?>">♡ <span class="count"><?php echo (int) $row['like_count']; ?></span></a>
                            <a href="repost.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn repost<?php echo ((int) $row['user_reposted'] > 0) ? ' active' : ''; ?>">🔁 <span class="count"><?php echo (int) $row['repost_count']; ?></span></a>
                            <a href="bookmark.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn save<?php echo ((int) $row['user_bookmarked'] > 0) ? ' active' : ''; ?>">🔖 <span class="count"><?php echo (int) $row['bookmark_count']; ?></span></a>
                        </div>
                    </div>
                <?php endforeach; ?>
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
