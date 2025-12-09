<?php
session_start();
require_once 'mysql.php';

// Get the user_id from the URL (query parameter)
$id = $_GET['user_id'] ?? null;

if (!$id) {
    die("User ID is required.");
}

// Fetch user data from the database based on user_id
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

// Profile picture URL
$profilePicFilename = $user['profile_picture'] ?? 'default.png'; // Default to 'default.png' if not set
$profilePicUrl = 'http://static.hountybunter.click/user_profile/' . rawurlencode($profilePicFilename);

// Optionally, you can print the profilePicUrl to check if it's correct
// echo $profilePicUrl; // Uncomment to debug and verify the URL
//calling internal api to fetch user information
// Fetch the internal API
$apiUrl = 'http://127.0.0.1:8000/api/user/' . urlencode($id);
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);
$apiResponse = @file_get_contents($apiUrl, false, $context);

if ($apiResponse !== false) {
    $apiJson = json_decode($apiResponse, true);
    if ($apiJson !== null) {
        // Use API data for display
        $user_info = $apiJson;
    } else {
        die("Failed to decode internal API JSON.");
    }
} else {
    echo "<p>Failed to fetch internal API data.</p>";
    die(); 
}

// Fetch user tweets from the database
$tweets_stmt = $conn->prepare("SELECT * FROM tweets WHERE user_id = ? ORDER BY created_at DESC");
$tweets_stmt->bind_param("i", $id);
$tweets_stmt->execute();
$tweets_result = $tweets_stmt->get_result();
$tweets = [];
while ($tweet = $tweets_result->fetch_assoc()) {
    $tweets[] = $tweet;
}
$tweets_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_info['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css?v=5">
</head>
<body class="profile-page">

<div class="profile-container">
    <div class="profile-actions">
        <a class="btn ghost" href="index.php">Back to tweets</a>
    </div>
    <div class="profile-layout">
        <!-- Profile card -->
        <div class="profile-card">
            <!-- Profile Picture -->
            <?php
                $apiProfilePicFilename = $user_info['profile_picture'] ?? 'default.png';
                $apiProfilePicUrl = 'http://static.hountybunter.click/user_profile/' . rawurlencode($apiProfilePicFilename);
            ?>
            <img class="avatar" src="<?php echo htmlspecialchars($apiProfilePicUrl); ?>" alt="Profile Picture">
            <div class="profile-card-info">
                <!-- Display User's Username -->
                <h2><?php echo htmlspecialchars($user_info['username']); ?></h2>

                <p><strong>@<?php echo htmlspecialchars($user_info['username']); ?></strong></p>
                
                <!-- Display Bio -->
                <?php if (!empty($user_info['bio'])): ?>
                    <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user_info['bio'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tweets Section -->
        <div class="tweets-section">
            <h3>Tweets</h3>
            <?php if (empty($tweets)): ?>
                <p class="no-tweets">No tweets yet.</p>
            <?php else: ?>
                <div class="tweets-list">
                    <?php foreach ($tweets as $tweet): ?>
                        <div class="tweet-card">
                            <a class="tweet-delete" href="delete.php?post_id=<?php echo urlencode($tweet['id']); ?>" aria-label="Delete tweet">&#10005;</a>
                            <div class="tweet-content">
                                <?php echo nl2br(htmlspecialchars($tweet['content'])); ?>
                            </div>
                            <div class="tweet-date">
                                <?php 
                                    $date = new DateTime($tweet['created_at']);
                                    echo $date->format('M d, Y \a\t g:i A');
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
