<?php
session_start();
require_once 'config.php';
require_once 'mysql.php';

function get_ip_address() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

function hmac($key, $data) {
    return hash_hmac('sha256', $data, $key);
}

// Auto-login via remember-me cookie
if (isset($_COOKIE['remember_me'])) {
    $remember_me_cookie = $_COOKIE['remember_me'];
    $remember_me_cookie_parts = explode('|', $remember_me_cookie);
    if (count($remember_me_cookie_parts) !== 2) {
        header('Location: msg.php?msg=Invalid+remember+me+cookie&type=error&goto=login.php');
        exit();
    }

    $user_id = $remember_me_cookie_parts[0];
    $hmac = $remember_me_cookie_parts[1];

    if (hmac($KEY_FOR_REMEMBER_ME, $user_id) !== $hmac) {
        header('Location: msg.php?msg=Invalid+remember+me+cookie&type=error&goto=login.php');
        exit();
    }

    $user_id_int = (int) $user_id;
    if ($user_id_int > 0) {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id_int);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && ($user = $result->fetch_assoc())) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['remember_me'] = true;
            $stmt->close();
            $msg_redirect = rawurlencode('Login successful! Welcome back, ' . $user['username'] . '.');
            header('Location: msg.php?msg=' . $msg_redirect . '&type=success&goto=dashboard.php');
            exit();
        }
        $stmt->close();
    }
}

// Default values
$login_status = null; // null = not submitted, 0 = failure, 1 = success
$msg = '';

if (isset($_POST['submit'])) {
    // Handle form submission but do not render a separate page on failure.
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $login_status = 0;
        $msg = 'Please fill all requirements.';
    } else {
        // Sanitize and fetch user input
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $remember_me = !empty($_POST['remember_me']);

        // Prepare the database query to get the user by username
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hashed_password);
            $stmt->fetch();

            // Check the password
            if (password_verify($password, $hashed_password)) {
                // Set session variables
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $user_id;
                $remember_me_cookie = $user_id . '|' . hmac($KEY_FOR_REMEMBER_ME, $user_id);
                
                // Persist remember-me preference via cookie
                if ($remember_me) {
                    setcookie('remember_me', $remember_me_cookie, time() + (86400 * 30), "/");
                } else {
                    setcookie('remember_me', '', time() - 3600, "/");
                }
            

                // On success redirect to msg.php so it shows a friendly message and handles the redirect
                $login_status = 1; // SUCCESS
                $stmt->close();
                $msg_redirect = rawurlencode('Login successful! Welcome, ' . $username . '.');
                header("Location: msg.php?msg={$msg_redirect}&type=success&goto=dashboard.php");
                exit;
            } else {
                $login_status = 0;
                $msg = 'The credentials are incorrect';
            }

        } else {
            $login_status = 0;
            $msg = 'The username entered does not exist';
        }

        $stmt->close();
    }
}
?>
<html>
<head>
    <title>Login - Hounty Bunter</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css?v=3">
    <style>
        .login-container {
            background: #0f0f0f;
            border: 1px solid #1f1f1f;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.55);
        }
        .logo h1 {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .logo .brand-main {
            color: #f7f7f7;
        }
        .logo .brand-tag {
            background: #f6b700;
            color: #0a0a0a;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 800;
            letter-spacing: 0.01em;
        }
        .login-container .form-group input {
            background: #161616;
            border: 1px solid #2a2a2a;
            color: #f7f7f7;
        }
        .login-container .form-group input:focus {
            border-color: #f6b700;
            box-shadow: 0 0 0 3px rgba(246, 183, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1><span class="brand-main">Hounty</span><span class="brand-tag">Bunter</span></h1>
            <p>Welcome back! Please sign in</p>
        </div>
    <?php if ($login_status === 0 && !empty($msg)) { echo '<div class="form-message error">'.htmlspecialchars($msg).'</div>'; } ?>

    <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="form-group checkbox-row">
                <label for="remember_me">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1">
                    Remember me
                </label>
            </div>
            
            <div>
                <a href="forget_password.php">Forgot password?</a>
            </div>

            <button type="submit" name="submit" class="submit-btn">Sign In</button>
    </form>
        
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="google-login.php">
                <button class="submit-btn" style="background: #DB4437;">Login with Google</button>
            </a>
        </div>
        
        <div class="register-link">
            <p>Don't have an account?</p>
            <a href="register.php">Create Account</a>
        </div>
        </div>
    </div>
</body>
</html>
