<?php
session_start();
require_once 'mysql.php';

// Ensure output buffering to prevent header errors
if (!ob_get_level()) {
    ob_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: msg.php?msg=Please+log+in+to+access+the+dashboard.&type=error&goto=login.php");
    exit;
}

// Handle logout via GET request
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy(); 
    if (isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        
        // Remove session id cookie from browser
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000, // Expire the cookie by setting a past time
                $params["path"], 
                $params["domain"], 
                isset($params["secure"]) ? $params["secure"] : false,
                isset($params["httponly"]) ? $params["httpOnly"] : false
            );
        }
    }
    
    header('Location: msg.php?msg=Logged+out+successfully&type=success&goto=login.php');
    exit();
}

// Get the current username from the session
$current_username = $_SESSION['username'] ?? null;
if (!$current_username) {
    header("Location: msg.php?msg=Session+expired,+please+log+in+again.&type=error&goto=login.php");
    exit;
}

// Fetch user data from the database
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User record not found.");
}

// Google login info if applicable
$isGoogle     = isset($_SESSION['oauth_provider']) && $_SESSION['oauth_provider'] === 'google';
$displayName  = $isGoogle ? ($_SESSION['google_name'] ?? $_SESSION['username']) : $_SESSION['username'];
$displayEmail = $isGoogle ? ($_SESSION['google_email'] ?? '') : ($user['email'] ?? '');

// Profile picture URL (supports stored URLs or local filenames)
function buildProfilePicUrl(?string $profilePic): string
{
    if (!empty($profilePic) && preg_match('#^https?://#i', $profilePic)) {
        return $profilePic;
    }
    $filename = $profilePic ?: 'default.png';
    return 'http://static.hountybunter.click/user_profile/' . rawurlencode($filename);
}
$profilePicUrl = buildProfilePicUrl($user['profile_picture'] ?? null);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hounty Bunter - Dashboard</title>
    <!-- Bust cache on the static CDN so latest dashboard styles load -->
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css?v=3">
    <!-- Inline fallback to ensure brand mark styles apply even if CDN cache lags -->
    <style>
      .brand-mark {
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }
      .brand-main {
        color: #f7f7f7;
      }
      .brand-tag {
        background: #f6b700;
        color: #0a0a0a;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 800;
        letter-spacing: 0.01em;
      }
    </style>
</head>

<body class="dashboard-page">

<div class="dashboard-container">

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1 class="brand-mark"><span class="brand-main">Hounty</span><span class="brand-tag">Bunter</span></h1>
    </div>
    <div class="dashboard-actions">
        <a class="btn ghost" href="index.php">Back to tweets</a>
    </div>

    <!-- Profile Card -->
    <div class="profile">
        <img class="avatar" src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Profile">
        <div class="profile-info">
            <h2 class="profile-name">Welcome, <?php echo htmlspecialchars($displayName); ?>!</h2>
            <?php if ($displayEmail): ?>
                <p class="profile-email"><?php echo htmlspecialchars($displayEmail); ?></p>
        <?php endif; ?>
        <div class="profile-card__actions">
            <!-- Add public profile link that opens in a new tab -->
            <a href="profile.php?user_id=<?php echo urlencode($user['id']); ?>" target="_blank">
                View Public Profile
            </a>
        </div>
            <?php if (!empty($user['bio'])): ?>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Info Form -->
    <div class="user-info">
        <form method="POST" action="update_user.php" enctype="multipart/form-data" class="profile-form" autocomplete="off">

            <!-- Hidden field to send current profile picture -->
            <input type="hidden" name="current_profile_picture" value="<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>">

            <!-- File upload -->
            <div class="form-group full-width">
                <label for="profile_picture">Profile picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <small class="form-hint">PNG, JPG, or GIF up to 2MB.</small>
            </div>

            <!-- Username -->
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>

            <!-- Email (readonly) -->
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
            </div>

            <!-- Bio -->
            <div class="form-group full-width">
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                <small class="form-hint">Write a short bio about yourself.</small>
            </div>

            <!-- Password Fields -->
            <div class="form-group full-width">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
            </div>

            <div class="form-group full-width">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
            </div>

            <!-- Submit Button -->
            <button type="submit" name="submit" class="submit-btn full-width">Update Profile</button>

            <!-- Logout Link -->
            <a href="?logout=1" class="logout-link full-width" aria-label="Logout" title="Logout">
                <svg class="logout-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="logout-text">Logout</span>
            </a>

        </form>
    </div>
</div>

</body>
</html>
