<?php

require_once 'mysql.php';

// Handle form submission
if (isset($_POST['submit'])) {

    // Error array to hold validation errors
    $errors = array();

    // Check if email already exists in the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errors[] = "Email already registered";
    }
    $stmt->close();

    // Get form data
    $name = htmlspecialchars(trim($_POST['name']));  // Still collecting name, but not inserting into DB
    $surname = htmlspecialchars(trim($_POST['surname']));  // Still collecting surname, but not inserting into DB
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = htmlspecialchars(trim($_POST['password']));
    $confirm_password = htmlspecialchars(trim($_POST['confirm_password']));

    // Check if email is already used
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already registered, only one email is allowed per account.";
    }
    $stmt->close();

    // Check if username is already used
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Username already taken, please choose another one.";
    }
    $stmt->close();

    // Validate required fields
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 3) {
        $errors[] = "Password must be at least 3 characters long";
    }

    if (empty($confirm_password)) {
        $errors[] = "Confirm password is required";
    } elseif ($password != $confirm_password) {
        $errors[] = "Password and confirm password do not match";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password before storing (for security reasons)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the user data into the database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        $stmt->execute();

        // Check if the insert was successful
        if ($stmt->affected_rows > 0) {
            echo "<div style='text-align:center; color:#f6b700; font-weight:700; margin:16px 0;'>✓ Account created. Redirecting to login page…</div>";
            echo "<script>
                setTimeout(function(){
                    window.location.href = 'login.php';
                }, 1500);
            </script>";
        } else {
            echo "<div style='text-align:center; color:#f87171; font-weight:700; margin:16px 0;'>✗ Registration failed. Please try again later.</div>";
        }
        $stmt->close();
    } else {
        // If there are errors, display them
        echo "<div style='color: red; font-weight: bold;'>Registration failed. Please fix the following errors:</div><ul>";
        foreach ($errors as $error) {
            echo "<li style='color: red;'>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
}
// After successful registration, display message and redirect to login page after 3 seconds

?>

<html>
<head>
    <title>Register - Hounty Bunter</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css?v=3">
    <style>
        .register-container {
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1><span class="brand-main">Hounty</span><span class="brand-tag">Bunter</span></h1>
            <p>Create your account</p>
        </div>

<form action="register.php" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">First Name</label>
                    <input type="text" id="name" name="name" placeholder="mamad" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="surname">Last Name</label>
                    <input type="text" id="surname" name="surname" placeholder="hajmamad" value="<?= isset($_POST['surname']) ? htmlspecialchars($_POST['surname']) : '' ?>" required>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            </div>
            
            <div class="form-group full-width">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="your@email.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
            </div>
            
            <button type="submit" name="submit" class="submit-btn">Create Account</button>
</form>
        
        <div class="login-link">
            <p>Already have an account?</p>
            <a href="login.php">Sign In</a>
        </div>
    </div>
</body>
</html>
