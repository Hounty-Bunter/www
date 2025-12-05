<?php
session_start();
require_once 'mysql.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: msg.php?msg=Please+log+in.&type=error&goto=login.php");
    exit;
}

if (!isset($_POST['submit'])) {
    header("Location: dashboard.php");
    exit;
}

/* --- 1) گرفتن کاربر: اول با user_id اگر هست، وگرنه با username --- */
$user_id = $_SESSION['user_id'] ?? null;
$username_session = $_SESSION['username'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username_session);
}

$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: msg.php?msg=User+not+found.&type=error&goto=login.php");
    exit;
}

/* اگر user_id تو سشن نبود، از DB بگیر */
$user_id = $user['id'];

/* --- 2) ورودی‌ها --- */
$newUsername = trim($_POST['username'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$newBio = trim($_POST['bio'] ?? '');

$current_profile_picture =
    $_POST['current_profile_picture']
    ?? ($user['profile_picture'] ?: 'default.png');

/* --- 3) پیش‌فرض عکس، تا undef نخوریم --- */
$profile_picture = $current_profile_picture ?: 'default.png';

if ($newUsername === '') {
    header("Location: msg.php?msg=Username+is+required.&type=error&goto=dashboard.php");
    exit;
}

/* --- 4) پسورد فقط اگر پر شده بود --- */
$hashedPassword = null;
if ($newPassword !== '') {
    if ($newPassword !== $confirmPassword) {
        header("Location: msg.php?msg=Passwords+do+not+match.&type=error&goto=dashboard.php");
        exit;
    }
    if (strlen($newPassword) < 6) {
        header("Location: msg.php?msg=Password+must+be+at+least+6+characters.&type=error&goto=dashboard.php");
        exit;
    }
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
}

/* --- 5) آپلود عکس --- */
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        header("Location: msg.php?msg=Error+uploading+file.&type=error&goto=dashboard.php");
        exit;
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));

    // FIX: اینجا extension رو چک کن
    if (!in_array($extension, $allowed_extensions, true)) {
        header("Location: msg.php?msg=Invalid+file+type.+Allowed:+jpg,+png,+gif.&type=error&goto=dashboard.php");
        exit;
    }

    $max_size = 300 * 1024; // 300 KB
    if ($_FILES['profile_picture']['size'] > $max_size) {
        header("Location: msg.php?msg=Profile+picture+exceeds+300kb+limit.&type=error&goto=dashboard.php");
        exit;
    }

    $upload_dir = '/var/www/static/user_profile/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            header("Location: msg.php?msg=Unable+to+prepare+upload+directory.&type=error&goto=dashboard.php");
            exit();
        }
    }

    $new_filename = 'pf_' . $user_id . '_' . time() . '.' . $extension;
    $destination = $upload_dir . $new_filename;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
        header("Location: msg.php?msg=Failed+to+save+uploaded+file.&type=error&goto=dashboard.php");
        exit;
    }

    @chmod($destination, 0644);
    $profile_picture = $new_filename;

    // حذف عکس قبلی
    if (!empty($current_profile_picture) && $current_profile_picture !== 'default.png') {
        $old_path = $upload_dir . $current_profile_picture;
        if (is_file($old_path)) {
            @unlink($old_path);
        }
    }
}

/* --- 6) آپدیت دیتابیس --- */
if ($hashedPassword !== null) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET username = ?, password = ?, profile_picture = ?, bio = ?, updated_at = NOW()
        WHERE id = ? LIMIT 1
    ");
    $stmt->bind_param("ssssi", $newUsername, $hashedPassword, $profile_picture, $newBio, $user_id);
} else {
    $stmt = $conn->prepare(" UPDATE users SET username = ?, profile_picture = ?, bio = ?, updated_at = NOW() WHERE id = ? LIMIT 1 ");
    $stmt->bind_param("sssi", $newUsername, $profile_picture, $newBio, $user_id);
}

$stmt->execute();
$stmt->close();

/* --- 7) آپدیت سشن --- */
$_SESSION['username'] = $newUsername;
$_SESSION['user_id']  = $user_id;

header("Location: msg.php?msg=Profile+updated+successfully.&type=success&goto=dashboard.php");
exit;
