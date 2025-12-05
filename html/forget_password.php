<?php
session_start();
require_once 'mysql.php';

// Make MySQL throw exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $message = 'Please enter your username.';
        $message_type = 'error';
    } else {

        // Lookup user by username
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {

            $stmt->bind_result($user_id, $email);
            $stmt->fetch();
            $stmt->close();

            // Generate secure token
            $raw_token  = bin2hex(random_bytes(32));  // 64 chars
            $token_hash = hash('sha256', $raw_token);
            $expires_at = date('Y-m-d H:i:s', time() + 1800); // +30 minutes

            // Delete any older tokens for this user
            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();
            $del->close();

            // Insert new reset record
            $ins = $conn->prepare(
                "INSERT INTO password_resets (user_id, token_hash, expires_at) 
                 VALUES (?, ?, ?)"
            );
            $ins->bind_param("iss", $user_id, $token_hash, $expires_at);
            $ins->execute();
            $ins->close();

            // Build reset link
            $reset_link = "https://hountybunter.click/reset_password.php?token=" . urlencode($raw_token);

            // Send mail — still commented, same as your original
            /*
            $subject = "Password Reset";
            $messageBody = "Hello $username,\n\nClick to reset your password:\n$reset_link\nThis link expires in 30 minutes.";
            $headers = "From: no-reply@hountybunter.click\r\n";
            mail($email, $subject, $messageBody, $headers);
            */

            // Redirect with success indicator
            header("Location: forget_password.php?reset=1");
            exit;

        } else {
            $stmt->close();
            $message = 'We could not find an account with that username.';
            $message_type = 'error';
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Forget Password</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Forget Password</h1>
        </div>

        <?php if (isset($_GET['reset']) && $_GET['reset'] == '1'): ?>
            <div class="alert success">
                If the account exists, a password reset link has been sent.
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="forget_password.php" method="post" accept-charset="UTF-8" autocomplete="on">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="hajmamad" 
                        required 
                        minlength="3"
                    >
                </div>
            </div>

            <button type="submit" name="submit" class="submit-btn">
                Forget Password
            </button>
        </form>

        <div class="register-link">
            <p>Don't have an account?</p>
            <a href="register.php">Create Account</a>
        </div>
    </div>
</body>
</html>