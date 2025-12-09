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
    <link rel="stylesheet" href="https://static.hountybunter.click/styles.css?v=3">
    <style>
        body {
            background: radial-gradient(circle at 20% -10%, rgba(246, 183, 0, 0.08), transparent 60%),
                        radial-gradient(circle at 80% 0%, rgba(246, 183, 0, 0.12), transparent 55%),
                        #0a0a0a;
            color: #f7f7f7;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding: 48px 16px 64px;
          }
        .users-wrapper {
          max-width: 960px;
          margin: 0 auto;
          background: #0f0f0f;
          border: 1px solid #1f1f1f;
          border-radius: 24px;
          padding: 32px;
          box-shadow: 0 20px 50px rgba(0,0,0,0.55);
        }
        .users-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 18px;
        }
        .brand-mark {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          font-size: 1.4rem;
          font-weight: 800;
        }
        .brand-main { color: #f7f7f7; }
        .brand-tag {
          background: #f6b700;
          color: #0a0a0a;
          padding: 4px 8px;
          border-radius: 6px;
          font-weight: 800;
          letter-spacing: 0.01em;
        }
        .back-link {
          color: #f6b700;
          text-decoration: none;
          font-weight: 700;
          border: 1px solid rgba(246,183,0,0.45);
          padding: 8px 12px;
          border-radius: 10px;
          background: rgba(246,183,0,0.1);
          transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
          box-shadow: 0 8px 18px rgba(246,183,0,0.18);
        }
        .back-link:hover {
          transform: translateY(-1px);
          background: #f6b700;
          color: #0a0a0a;
          box-shadow: 0 12px 22px rgba(246,183,0,0.28);
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 10px;
          background: #0b0b0d;
          border: 1px solid #1f1f23;
          border-radius: 16px;
          overflow: hidden;
        }
        th, td {
          padding: 14px 16px;
          text-align: left;
        }
        th {
          text-transform: uppercase;
          letter-spacing: 0.06em;
          font-size: 0.78rem;
          color: #c7c7c7;
          border-bottom: 1px solid #1f1f23;
        }
        tr + tr td {
          border-top: 1px solid #1f1f23;
        }
        td a {
          color: #f6b700;
          font-weight: 700;
          text-decoration: none;
        }
        td a:hover {
          color: #ffd75c;
        }
    </style>
</head>
<body>
    <div class="users-wrapper">
        <div class="users-header">
            <div class="brand-mark"><span class="brand-main">Hounty</span><span class="brand-tag">Bunter</span></div>
            <a class="back-link" href="index.php">Back to tweets</a>
        </div>
        <h1 style="margin-bottom: 12px;">All Users</h1>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
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
