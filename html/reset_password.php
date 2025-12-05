<?php
session_start();
require_once 'mysql.php';

// Make mysqli throw exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = '';
$show_form = false;

// Raw token (GET for first visit, POST for submit)
$raw_token = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($raw_token === '') {
    $error = "Invalid or expired link.";
} else {

    $token_hash = hash('sha256', $raw_token);

    // First: validate token on GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("
            SELECT user_id, expires_at
            FROM password_resets
            WHERE token_hash = ?
              AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $show_form = true;
        } else {
            $error = "Invalid or expired link.";
        }

        $stmt->close();
    }

    // Handle POST (password update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($password === '' || $password_confirm === '') {
            $error = "Please fill in both password fields.";
        } elseif ($password !== $password_confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        }

        if ($error === '') {

            // Validate token again (still valid)
            $stmt = $conn->prepare("
                SELECT user_id
                FROM password_resets
                WHERE token_hash = ?
                  AND expires_at > NOW()
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->bind_param("s", $token_hash);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows !== 1) {
                $error = "Invalid or expired link.";
            } else {

                $row = $result->fetch_assoc();
                $user_id = (int)$row['user_id'];

                // Hash password
                $new_pass_hash = password_hash($password, PASSWORD_DEFAULT);

                // Update user password
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $new_pass_hash, $user_id);
                $upd->execute();
                $upd->close();

                // Delete all reset tokens for this user
                $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $del->bind_param("i", $user_id);
                $del->execute();
                $del->close();

                // Redirect success
                header("Location: login.php?reset_done=1");
                exit;
            }

            $stmt->close();
        }

        // On error → show form again
        $show_form = ($error !== '');
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Reset Password</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert error">
                <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form action="reset_password.php" method="post" accept-charset="UTF-8" autocomplete="off">
                <!-- Keep the raw token -->
                <input type="hidden" name="token"
                       value="<?= htmlspecialchars($raw_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password"
                               required minlength="8" placeholder="New password">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password_confirm">Confirm New Password</label>
                        <input type="password" id="password_confirm" name="password_confirm"
                               required minlength="8" placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="submit-btn">Set New Password</button>
            </form>
        <?php else: ?>
            <div class="alert error">Invalid or expired link.</div>
        <?php endif; ?>

        <div class="register-link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
