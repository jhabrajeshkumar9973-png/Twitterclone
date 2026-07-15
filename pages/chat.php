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
$other_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$other_user = $other_id > 0 ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, username, profile_pic FROM users WHERE id = '$other_id' LIMIT 1")) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $project_url_base; ?>/">
    <link rel="stylesheet" href="css/style.css">
    <title>Chat / Twitter Clone</title>
</head>
<body>
    <div class="chat-layout">
        <aside class="chat-sidebar">
            <div class="chat-sidebar-header">
                <div class="chat-logo">💬</div>
                <div>
                    <h3>Messages</h3>
                    <p>Start a conversation or pick a contact.</p>
                </div>
            </div>
            <div class="chat-conversations">
                <div class="chat-contact active">
                    <img src="uploads/<?php echo !empty($other_user['profile_pic']) ? htmlspecialchars($other_user['profile_pic']) : 'default.png'; ?>" alt="Contact avatar">
                    <div>
                        <strong><?php echo $other_user ? htmlspecialchars($other_user['username']) : 'No chat selected'; ?></strong>
                        <span>Tap to continue</span>
                    </div>
                </div>
                <a href="<?php echo project_url('profile.php?user_id=' . $other_id); ?>" class="chat-contact-link">View profile</a>
            </div>
        </aside>

        <main class="chat-panel">
            <div class="chat-panel-header">
                <div class="chat-user-info">
                    <img src="uploads/<?php echo !empty($other_user['profile_pic']) ? htmlspecialchars($other_user['profile_pic']) : 'default.png'; ?>" alt="Contact avatar">
                    <div>
                        <h2><?php echo $other_user ? htmlspecialchars($other_user['username']) : 'Chat'; ?></h2>
                        <span><?php echo $other_user ? 'Direct messages' : 'Select a user to start chatting'; ?></span>
                    </div>
                </div>
                <a href="<?php echo project_url('profile.php?user_id=' . $other_id); ?>" class="chat-profile-link">View profile</a>
            </div>

            <div class="chat-box-container">
                <div id="chatBox" class="chat-box">
                    <?php if ($other_id > 0): ?>
                        <?php include __DIR__ . '/../includes/get_messages.php'; ?>
                    <?php else: ?>
                        <div class="empty-state chat-empty">Select a conversation to see messages.</div>
                    <?php endif; ?>
                </div>

                <?php if ($other_id > 0): ?>
                    <form method="POST" action="<?php echo project_url('send_message.php'); ?>" class="chat-input-form" onsubmit="return false;">
                        <input type="hidden" name="receiver_id" value="<?php echo $other_id; ?>">
                        <input id="messageInput" type="text" name="message" placeholder="Type a message..." autocomplete="off">
                        <button id="sendBtn" type="button" class="tweet-btn">Send</button>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
        window.receiverId = <?php echo (int) $other_id; ?>;
    </script>
    <script src="js/main.js"></script>
</body>
</html>
