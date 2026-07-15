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
$profile_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : $my_id;
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$search_results = [];
$trending_topics = [
    ['tag' => 'Creators', 'title' => 'Creator economy', 'count' => '15.7K posts'],
    ['tag' => 'Work', 'title' => 'Remote work', 'count' => '11.3K posts'],
    ['tag' => 'Games', 'title' => 'Indie game launch', 'count' => '8.6K posts'],
    ['tag' => 'News', 'title' => 'Weekend update', 'count' => '6.2K posts']
];

if ($search_query !== '') {
    $escaped = mysqli_real_escape_string($conn, $search_query);
    $result = mysqli_query($conn, "SELECT id, username, profile_pic FROM users WHERE username LIKE '%$escaped%' AND id != '$my_id' LIMIT 6");
    if ($result) {
        $search_results = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$profile_id' LIMIT 1");
if (!$user_query || mysqli_num_rows($user_query) === 0) {
    die('User not found.');
}
$profile_user = mysqli_fetch_assoc($user_query);
$bio_text = !empty($profile_user['bio']) ? $profile_user['bio'] : 'No bio added yet.';
$location_text = !empty($profile_user['location']) ? $profile_user['location'] : 'Not shared yet.';
$gender_text = !empty($profile_user['gender']) ? $profile_user['gender'] : 'Not shared yet.';
$hobbies_text = !empty($profile_user['hobbies']) ? $profile_user['hobbies'] : 'No hobbies added yet.';

$follower_count = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM follows WHERE following_id = '$profile_id'"))['count'];
$following_count = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM follows WHERE follower_id = '$profile_id'"))['count'];
$followed_by_me = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM follows WHERE follower_id = '$my_id' AND following_id = '$profile_id'"))['count'];

$posts = mysqli_fetch_all(mysqli_query($conn, "SELECT tweets.*, users.username, users.profile_pic FROM tweets JOIN users ON tweets.user_id = users.id WHERE tweets.user_id = '$profile_id' ORDER BY tweets.created_at DESC"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $project_url_base; ?>/">
    <link rel="stylesheet" href="css/style.css">
    <title>Profile / Twitter Clone</title>
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
            <a href="<?php echo project_url('profile.php'); ?>" class="nav-item active"><span class="nav-icon">👤</span> Profile</a>
            <a href="<?php echo project_url('bookmarks.php'); ?>" class="nav-item"><span class="nav-icon">🔖</span> Bookmarks</a>
            <a href="<?php echo project_url('notifications.php'); ?>" class="nav-item"><span class="nav-icon">🔔</span> Notifications</a>
            <a href="<?php echo project_url('chat.php'); ?>" class="nav-item"><span class="nav-icon">💬</span> Messages</a>
        </nav>
        <a href="<?php echo project_url('logout.php'); ?>" class="logout-btn">🔓 Log out</a>
    </aside>

    <main class="timeline">
        <div class="profile-header">
            <div class="profile-hero">
                <div class="profile-banner"></div>
                <div class="profile-card">
                    <div class="profile-card-top">
                        <div class="profile-avatar-stack">
                            <img src="uploads/<?php echo !empty($profile_user['profile_pic']) ? htmlspecialchars($profile_user['profile_pic']) : 'default.png'; ?>" class="profile-dp" alt="DP">
                            <span class="profile-status-pill">● Active</span>
                        </div>
                        <div class="profile-action-row">
                            <?php if ($profile_id === $my_id): ?>
                                <a href="<?php echo project_url('edit_profile.php'); ?>" class="tweet-btn" style="text-decoration: none; display: inline-block;">Edit profile</a>
                            <?php else: ?>
                                <a href="<?php echo project_url('follow.php?user_id=' . $profile_id); ?>" class="tweet-btn" style="text-decoration: none; display: inline-block;"><?php echo $followed_by_me ? 'Unfollow' : 'Follow'; ?></a>
                                <a href="<?php echo project_url('chat.php?user_id=' . $profile_id); ?>" class="profile-secondary-btn">Message</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-card-info">
                        <div class="profile-name-row">
                            <div>
                                <h2><?php echo htmlspecialchars($profile_user['username']); ?></h2>
                                <div class="profile-role">@<?php echo htmlspecialchars($profile_user['username']); ?> • Member</div>
                            </div>
                        </div>

                        <p class="profile-bio"><?php echo htmlspecialchars($bio_text); ?></p>

                        <div class="profile-details">
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon">📍</span>
                                <div>
                                    <strong>Location</strong>
                                    <span><?php echo htmlspecialchars($location_text); ?></span>
                                </div>
                            </div>
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon">⚧</span>
                                <div>
                                    <strong>Gender</strong>
                                    <span><?php echo htmlspecialchars($gender_text); ?></span>
                                </div>
                            </div>
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon">🎯</span>
                                <div>
                                    <strong>Hobbies</strong>
                                    <span><?php echo htmlspecialchars($hobbies_text); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="follow-stats">
                            <a href="<?php echo project_url('followers.php?id=' . $profile_id); ?>" class="follow-stat-link"><strong><?php echo $follower_count; ?></strong><span>followers</span></a>
                            <a href="<?php echo project_url('following.php?id=' . $profile_id); ?>" class="follow-stat-link"><strong><?php echo $following_count; ?></strong><span>following</span></a>
                            <span><strong><?php echo count($posts); ?></strong><span>posts</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tweets-container">
            <?php if (empty($posts)): ?>
                <div class="empty-state">No posts yet.</div>
            <?php else: ?>
                <?php foreach ($posts as $row): ?>
                    <?php $can_manage_post = ($profile_id === $my_id); ?>
                    <div class="tweet">
                        <div class="tweet-header">
                            <div class="tweet-author">
                                <img src="uploads/<?php echo !empty($profile_user['profile_pic']) ? htmlspecialchars($profile_user['profile_pic']) : 'default.png'; ?>" class="tweet-avatar" alt="DP">
                                <div>
                                    <div class="tweet-name">@<?php echo htmlspecialchars($profile_user['username']); ?></div>
                                    <div class="tweet-timestamp"><?php echo htmlspecialchars($row['created_at']); ?></div>
                                </div>
                            </div>
                            <?php if ($can_manage_post): ?>
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
