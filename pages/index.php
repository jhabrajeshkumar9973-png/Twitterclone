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
    ['tag' => 'Tech', 'title' => 'AI agents', 'count' => '18.2K posts'],
    ['tag' => 'Design', 'title' => 'Dark mode UI', 'count' => '9.8K posts'],
    ['tag' => 'Culture', 'title' => 'Weekend plans', 'count' => '6.4K posts'],
    ['tag' => 'Sports', 'title' => 'Champions League', 'count' => '5.1K posts']
];

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($request_method === 'POST' && isset($_POST['tweet_content'])) {
    $content = trim($_POST['tweet_content']);
    $media_path = null;
    $media_type = null;

    if (!empty($_FILES['tweet_media']['name'])) {
        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_video_types = ['video/mp4', 'video/webm'];
        $max_size = 10 * 1024 * 1024;
        $upload = $_FILES['tweet_media'];

        if ($upload['error'] === UPLOAD_ERR_OK && $upload['size'] <= $max_size) {
            if (in_array($upload['type'], $allowed_image_types, true)) {
                $media_type = 'image';
            } elseif (in_array($upload['type'], $allowed_video_types, true)) {
                $media_type = 'video';
            }

            if ($media_type !== null) {
                $extension = pathinfo($upload['name'], PATHINFO_EXTENSION);
                $filename = uniqid('tweet_', true) . '.' . $extension;
                $target_path = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($upload['tmp_name'], $target_path)) {
                    $media_path = $filename;
                }
            }
        }
    }

    if ($content !== '' || $media_path !== null) {
        $escaped = mysqli_real_escape_string($conn, $content);
        $media_sql = $media_path !== null ? ", '$media_path', '$media_type'" : ', NULL, NULL';
        mysqli_query($conn, "INSERT INTO tweets (user_id, content, media_path, media_type) VALUES ('$my_id', '$escaped'$media_sql)");
    }
    header('Location: ' . project_url('index.php'));
    exit();
}

if ($search_query !== '') {
    $escaped = mysqli_real_escape_string($conn, $search_query);
    $search_result = mysqli_query($conn, "SELECT id, username, profile_pic FROM users WHERE username LIKE '%$escaped%' AND id != '$my_id' LIMIT 6");
    if ($search_result) {
        $search_results = mysqli_fetch_all($search_result, MYSQLI_ASSOC);
    }
}

$sql = "SELECT tweets.*, users.username, users.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE likes.tweet_id = tweets.id) AS like_count,
        (SELECT COUNT(*) FROM reposts WHERE reposts.tweet_id = tweets.id) AS repost_count,
        (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.tweet_id = tweets.id) AS bookmark_count,
        (SELECT COUNT(*) FROM likes WHERE likes.tweet_id = tweets.id AND likes.user_id = '$my_id') AS user_liked,
        (SELECT COUNT(*) FROM reposts WHERE reposts.tweet_id = tweets.id AND reposts.user_id = '$my_id') AS user_reposted,
        (SELECT COUNT(*) FROM bookmarks WHERE bookmarks.tweet_id = tweets.id AND bookmarks.user_id = '$my_id') AS user_bookmarked
        FROM tweets
        JOIN users ON tweets.user_id = users.id
        ORDER BY tweets.created_at DESC";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f1419">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <base href="<?php echo $project_url_base; ?>/">
    <link rel="stylesheet" href="css/style.css">
    <title>Home / Twitter Clone</title>
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
            <a href="<?php echo project_url('index.php'); ?>" class="nav-item active"><span class="nav-icon">🏠</span> Home</a>
            <a href="<?php echo project_url('explore.php'); ?>" class="nav-item"><span class="nav-icon">🧭</span> Explore</a>
            <a href="<?php echo project_url('profile.php'); ?>" class="nav-item"><span class="nav-icon">👤</span> Profile</a>
            <a href="<?php echo project_url('bookmarks.php'); ?>" class="nav-item"><span class="nav-icon">🔖</span> Bookmarks</a>
            <a href="<?php echo project_url('notifications.php'); ?>" class="nav-item"><span class="nav-icon">🔔</span> Notifications</a>
            <a href="<?php echo project_url('chat.php'); ?>" class="nav-item"><span class="nav-icon">💬</span> Messages</a>
        </nav>
        <a href="<?php echo project_url('logout.php'); ?>" class="logout-btn">🔓 Log out</a>
    </aside>

    <main class="timeline">
        <div class="feed-header">
            <div class="feed-header-inner">
                <div class="feed-title-row">
                    <div class="feed-icon">✦</div>
                    <h2>Home</h2>
                </div>
                <div class="feed-action-pill">For you</div>
            </div>
        </div>

        <div class="tweet-box">
            <form action="<?php echo project_url('post_tweet.php'); ?>" method="POST" enctype="multipart/form-data">
                <textarea name="tweet_content" maxlength="280" placeholder="What is happening?!" required></textarea>
                <div class="tweet-media-upload">
                    <label class="media-upload-label">
                        <input type="file" name="tweet_media" accept="image/png,image/jpeg,image/gif,video/mp4,video/webm">
                        Upload photo/video
                    </label>
                    <span class="media-hint">Max 10MB, JPG/PNG/GIF/MP4/WebM</span>
                </div>
                <div class="tweet-box-footer">
                    <span id="charCount">280</span>
                    <button type="submit" class="tweet-btn">Tweet</button>
                </div>
            </form>
        </div>

        <div class="tweets-container">
            <?php if (mysqli_num_rows($result) === 0): ?>
                <p class="empty-state">No tweets yet. Be the first to post.</p>
            <?php endif; ?>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php $is_owner = ((int) $row['user_id'] === $my_id); ?>
                <div class="tweet">
                    <div class="tweet-header">
                        <div class="tweet-author">
                            <img src="uploads/<?php echo !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : 'default.png'; ?>" class="tweet-avatar" alt="DP">
                            <div>
                                <a href="<?php echo project_url('profile.php?user_id=' . (int) $row['user_id']); ?>" class="tweet-name">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </a>
                                <div class="tweet-timestamp"><?php echo htmlspecialchars($row['created_at']); ?></div>
                            </div>
                        </div>
                        <?php if ($is_owner): ?>
                            <div class="tweet-menu-wrap">
                                <button type="button" class="tweet-menu-btn" aria-label="More options">⋯</button>
                                <div class="tweet-menu-dropdown">
                                    <button type="button" class="tweet-menu-action edit-action" data-tweet-id="<?php echo (int) $row['id']; ?>">
                                        <span class="tweet-menu-icon">✏️</span>
                                        <span>Edit post</span>
                                    </button>
                                    <a href="<?php echo project_url('delete_tweet.php?tweet_id=' . (int) $row['id']); ?>" class="tweet-menu-action delete-action" onclick="return confirm('Delete this tweet?');">
                                        <span class="tweet-menu-icon">🗑️</span>
                                        <span>Delete post</span>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form class="tweet-edit-form" action="<?php echo project_url('edit_tweet.php'); ?>" method="POST">
                        <div class="tweet-edit-header">Edit post</div>
                        <input type="hidden" name="tweet_id" value="<?php echo (int) $row['id']; ?>">
                        <textarea name="tweet_content" maxlength="280" required><?php echo htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <div class="tweet-edit-actions">
                            <button type="submit" class="tweet-btn small-btn">Save</button>
                            <button type="button" class="tweet-cancel-btn">Cancel</button>
                        </div>
                    </form>
                    <p class="tweet-text"><?php echo htmlspecialchars($row['content']); ?></p>
                    <div class="tweet-actions">
                        <a href="like.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn like<?php echo ((int) $row['user_liked'] > 0) ? ' active' : ''; ?>">
                            <span class="icon">♡</span>
                            <span class="count"><?php echo (int) $row['like_count']; ?></span>
                        </a>
                        <a href="repost.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn repost<?php echo ((int) $row['user_reposted'] > 0) ? ' active' : ''; ?>">
                            <span class="icon">🔁</span>
                            <span class="count"><?php echo (int) $row['repost_count']; ?></span>
                        </a>
                        <a href="bookmark.php?tweet_id=<?php echo (int) $row['id']; ?>" class="tweet-action-btn save<?php echo ((int) $row['user_bookmarked'] > 0) ? ' active' : ''; ?>">
                            <span class="icon">🔖</span>
                            <span class="count"><?php echo (int) $row['bookmark_count']; ?></span>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
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
<nav class="mobile-nav" aria-label="Mobile navigation">
    <a href="<?php echo project_url('index.php'); ?>" class="mobile-nav-item active">🏠<span>Home</span></a>
    <a href="<?php echo project_url('explore.php'); ?>" class="mobile-nav-item">🧭<span>Explore</span></a>
    <a href="<?php echo project_url('chat.php'); ?>" class="mobile-nav-item">💬<span>Messages</span></a>
    <a href="<?php echo project_url('profile.php'); ?>" class="mobile-nav-item">👤<span>Profile</span></a>
    <a href="<?php echo project_url('bookmarks.php'); ?>" class="mobile-nav-item">🔖<span>Saved</span></a>
</nav>
<script src="js/main.js"></script>
</body>
</html>
