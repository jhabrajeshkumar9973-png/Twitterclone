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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $hobbies = trim($_POST['hobbies'] ?? '');
    $profile_pic = '';

    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/';
        $fileName = basename($_FILES['profile_pic']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExt, $allowed, true)) {
            $error = 'Only JPG, PNG, and GIF files are allowed for profile images.';
        } elseif ($_FILES['profile_pic']['size'] > 5 * 1024 * 1024) {
            $error = 'Profile image must be smaller than 5MB.';
        } else {
            $newFileName = 'profile_' . $my_id . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $profile_pic = $newFileName;
            } else {
                $error = 'Unable to upload the profile image. Please try again.';
            }
        }
    }

    if ($error === '') {
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = '$my_id' LIMIT 1"));
        $currentPic = $user['profile_pic'] ?? 'default.png';
        $newPic = $profile_pic !== '' ? $profile_pic : $currentPic;

        $stmt = mysqli_prepare($conn, 'UPDATE users SET bio = ?, location = ?, gender = ?, hobbies = ?, profile_pic = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'sssssi', $bio, $location, $gender, $hobbies, $newPic, $my_id);
        mysqli_stmt_execute($stmt);

        $_SESSION['profile_pic'] = $newPic;
        header('Location: ' . project_url('profile.php'));
        exit();
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = '$my_id' LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Edit Profile / Twitter Clone</title>
</head>
<body>
    <div class="main-layout edit-profile-layout">
        <aside class="sidebar">
            <div class="logo">TW</div>
            <div class="user-tag">@<?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <nav>
                <a href="index.php" class="nav-item">Home</a>
                <a href="explore.php" class="nav-item">Explore</a>
                <a href="profile.php" class="nav-item active">Profile</a>
                <a href="chat.php" class="nav-item">Messages</a>
                <a href="bookmarks.php" class="nav-item">Bookmarks</a>
                <a href="notifications.php" class="nav-item">Notifications</a>
            </nav>
            <a href="logout.php" class="logout-btn">Log out</a>
        </aside>

        <main class="timeline">
            <section class="edit-profile-card">
                <div class="edit-profile-header">
                    <h2>Edit profile</h2>
                    <p>Update your bio, location, gender, and hobbies separately.</p>
                </div>
                <form method="POST" class="profile-form" enctype="multipart/form-data">
                    <div class="profile-form-grid">
                        <div class="profile-form-field full">
                            <label>
                                Profile picture
                                <div class="profile-picture-preview">
                                    <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>" alt="Current profile picture">
                                </div>
                                <input type="file" name="profile_pic" accept="image/png, image/jpeg, image/gif">
                            </label>
                        </div>
                        <div class="profile-form-field full">
                            <label>
                                Bio
                                <textarea name="bio" placeholder="Tell people about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </label>
                        </div>
                        <div class="profile-form-field">
                            <label>
                                Location
                                <input type="text" name="location" placeholder="Where are you based?" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                            </label>
                        </div>
                        <div class="profile-form-field">
                            <label>
                                Gender
                                <input type="text" name="gender" placeholder="Gender" value="<?php echo htmlspecialchars($user['gender'] ?? ''); ?>">
                            </label>
                        </div>
                        <div class="profile-form-field full">
                            <label>
                                Hobbies
                                <textarea name="hobbies" placeholder="What do you enjoy?"><?php echo htmlspecialchars($user['hobbies'] ?? ''); ?></textarea>
                            </label>
                        </div>
                    </div>
                    <div class="profile-form-actions">
                        <button type="submit" class="tweet-btn">Save changes</button>
                        <a href="profile.php" class="cancel-link">Cancel</a>
                    </div>
                </form>
            </section>
        </main>

        <aside class="right-sidebar">
            <div class="notification-card">
                <h3>Profile tips</h3>
                <p>Keep your bio short, use a clear location, and add hobbies to help people discover you.</p>
            </div>
        </aside>
    </div>
</body>
</html>
