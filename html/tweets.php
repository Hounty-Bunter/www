<?php
session_start();
require_once 'mysql.php';

function buildProfilePicUrl(?string $profilePic): string
{
    if (!empty($profilePic) && preg_match('#^https?://#i', $profilePic)) {
        return $profilePic;
    }
    $filename = $profilePic ?: 'default.png';
    return 'https://static.hountybunter.click/user_profile/' . rawurlencode($filename);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    $isLoggedIn = $_SESSION['logged_in'] ?? false;

    if (!$userId || !$isLoggedIn) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Please log in to post a tweet.']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    $tweetContent = isset($payload['tweet_content']) ? trim($payload['tweet_content']) : '';

    if ($tweetContent === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tweet content cannot be empty.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $tweetContent);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Tweet posted.']);
    exit;
}

$tweets = [];
$stmt = $conn->prepare(
    "SELECT t.content, t.created_at, u.username, u.id AS user_id, u.profile_picture
     FROM tweets t
     JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC"
);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($tweetContent, $createdAt, $username, $userId, $profilePic);

while ($stmt->fetch()) {
    // Human readable date
    if (!empty($createdAt)) {
        $dt = new DateTime($createdAt);
        $createdHuman = $dt->format('M j, Y \a\t H:i');
    } else {
        $createdHuman = '';
    }

    $tweets[] = [
        'user_id' => $userId,
        'username' => $username,
        'content' => $tweetContent,
        'created_at' => $createdAt,
        'created_human' => $createdHuman,
        'profile_picture' => $profilePic,
        'profile_picture_url' => buildProfilePicUrl($profilePic),
    ];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($tweets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
