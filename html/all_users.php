<?php
session_start();
require_once 'mysql.php'; // Include the database connection file

$stmt = $conn->prepare("SELECT id, username, email, created_at, updated_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

?>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Users</title>
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css">
</head>
<body>
    <div class="container">
        <h1>All Users</h1>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <a href="profile.php?user_id=<?php echo urlencode($row['id']); ?>">
                                <?php echo htmlspecialchars($row['username']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$stmt->close();
?>
