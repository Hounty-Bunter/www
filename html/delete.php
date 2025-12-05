<?php
session_start();
require_once 'mysql.php';

// Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: msg.php?msg=' . rawurlencode('Please log in first.') . '&type=error&goto=login.php');
    exit;
}

// Get current user info from session
$currentUsername = $_SESSION['username'] ?? null;
if (!$currentUsername) {
    header('Location: msg.php?msg=' . rawurlencode('Session expired, please log in again.') . '&type=error&goto=login.php');
    exit;
}

// Resolve current user ID from DB
$userStmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$userStmt->bind_param("s", $currentUsername);
$userStmt->execute();
$userResult = $userStmt->get_result();
$currentUser = $userResult->fetch_assoc();
$userStmt->close();

if (!$currentUser) {
    header('Location: msg.php?msg=' . rawurlencode('User not found.') . '&type=error&goto=login.php');
    exit;
}

$currentUserId = (int)$currentUser['id'];

// Validate post_id
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if ($postId <= 0) {
    header('Location: msg.php?msg=' . rawurlencode('Invalid tweet id.') . '&type=error&goto=profile.php?user_id=' . $currentUserId);
    exit;
}

// Verify ownership
$tweetStmt = $conn->prepare("SELECT user_id FROM tweets WHERE id = ? LIMIT 1");
$tweetStmt->bind_param("i", $postId);
$tweetStmt->execute();
$tweetResult = $tweetStmt->get_result();
$tweet = $tweetResult->fetch_assoc();
$tweetStmt->close();

if (!$tweet) {
    header('Location: msg.php?msg=' . rawurlencode('Tweet not found.') . '&type=error&goto=profile.php?user_id=' . $currentUserId);
    exit;
}

if ((int)$tweet['user_id'] !== $currentUserId) {
    header('Location: msg.php?msg=' . rawurlencode('You can only delete your own tweets.') . '&type=error&goto=profile.php?user_id=' . $currentUserId);
    exit;
}

// Delete the tweet
//$deleteStmt = $conn->prepare("DELETE FROM tweets WHERE id = ?");
$deleteStmt = $conn->prepare("DELETE FROM tweets WHERE id = ? AND user_id = ?");
$deleteStmt->bind_param("ii", $postId, $currentUserId);
$deleteStmt->execute();
$deleteStmt->close();

// Redirect back to profile (same tab)
header('Location: profile.php?user_id=' . $currentUserId);
exit;
