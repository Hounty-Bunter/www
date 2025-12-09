<?php
require_once 'vendor/autoload.php';
session_start();
require_once 'mysql.php';  // DB

// ❌ Error check
if (isset($_GET['error'])) {
    die('<div style="color:red;font-size:24px;text-align:center;">❌ Google Error: ' . htmlspecialchars($_GET['error_description'] ?? $_GET['error']) . '<br><a href="login.php">← Back</a></div>');
}

if (!isset($_GET['code']) || empty($_GET['code'])) {
    die('<div style="color:red;font-size:24px;text-align:center;">❌ No Code!<br><a href="login.php">← Login</a></div>');
}

try {
    $client = new Google_Client();
    $client->setClientId('863391200215-fn8jatj2ivhc1ogma4dgdh0ltuoivohf.apps.googleusercontent.com');
    $client->setClientSecret('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');
    $client->setRedirectUri('https://hountybunter.click/callback.php');

    // ✅ Token + User Info
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    $email = $userInfo->email;
    $name = $userInfo->name;
    $google_id = $userInfo->id;
    $picture = $userInfo->picture; // Google profile photo URL

    // ✅ DB: Find/Create User
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? OR google_id = ?");
    $stmt->bind_param("ss", $email, $google_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $userId = null;
    if ($row = $result->fetch_assoc()) {
        // Update
        $userId = $row['id'];
        $update = $conn->prepare("UPDATE users SET google_id = ?, email = ?, profile_picture = ? WHERE id = ? LIMIT 1");
        $update->bind_param("sssi", $google_id, $email, $picture, $userId);
        $update->execute();
    } else {
        // New: Unique username
        $username_base = strtolower(preg_replace('/[^a-z0-9]/', '_', $name));
        $username = $username_base;
        $i = 1;
        while (true) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows === 0) break;
            $username = $username_base . '_' . $i++;
        }
//to here
        $dummy_pass = password_hash('google_' . $google_id, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (username, email, password, google_id, profile_picture) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("sssss", $username, $email, $dummy_pass, $google_id, $picture);
        $insert->execute();
        $userId = $insert->insert_id;
        $row['username'] = $username;
    }

    // ✅ Session match با login.php + store Google details
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $row['username'];
    $_SESSION['user_id'] = $userId;
    $_SESSION['oauth_provider'] = 'google';
    $_SESSION['google_name'] = $name;
    $_SESSION['google_email'] = $email;
    $_SESSION['google_picture'] = $picture;

    header('Location: dashboard.php');
    exit();

} catch (Exception $e) {
    die('<div style="color:red;font-size:20px;">❌ Error: ' . htmlspecialchars($e->getMessage()) . '<br><a href="login.php">← Back</a></div>');
}
?>
