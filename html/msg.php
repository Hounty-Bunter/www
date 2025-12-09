<?php
session_start();
require_once 'mysql.php';
require_once 'functions.php';

$msg  = $_GET['msg']  ?? 'No message provided.';
$type = $_GET['type'] ?? 'error';
$goto = $_GET['goto'] ?? 'index.php';

$isSuccess = ($type === 'success');
$title = $isSuccess ? 'Success' : 'Error';
$bodyClass = $isSuccess ? 'success-page' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hounty Bunter - <?php echo htmlspecialchars($title); ?></title>
  <link rel="stylesheet" href="static/styles.css">

  <style>
    /* Simple, neutral message page */
    body{
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:100vh;
      padding:20px;
      background:#0d0d0f;
      color:#e9e9ec;
      font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .message-container{
      background:#121318;
      padding:32px 28px;
      border-radius:14px;
      border:1px solid rgba(255,255,255,0.08);
      box-shadow: 0 16px 32px rgba(0,0,0,0.45);
      text-align:center;
      max-width:480px;
      width:100%;
    }

    .message-status{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:64px; height:64px;
      border-radius:50%;
      margin:0 auto 18px;
      font-size:32px;
      font-weight:800;
      color:#fff;
    }
    .message-status.success{ background:#f6b700; color:#0a0a0a; }
    .message-status.error{ background:#dc2626; }

    .message-container h1{
      font-size:1.6rem;
      margin-bottom:10px;
      font-weight:800;
      color:#f5f5f5;
    }

    .message-text{
      font-size:0.98rem;
      color:#cfd3dc;
      margin:10px 0 18px;
      line-height:1.6;
    }

    .countdown{
      font-size:0.9rem;
      color:#9aa0ad;
      margin-top:8px;
    }
  </style>
</head>

<body class="<?php echo $bodyClass; ?>">
  <div class="message-container">
    <div class="message-status <?php echo $isSuccess ? 'success' : 'error'; ?>">
      <?php echo $isSuccess ? '✓' : '✕'; ?>
    </div>

    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p class="message-text"><?php echo htmlspecialchars($msg); ?></p>
    <p class="countdown">Redirecting in <span id="countdown">2</span> seconds...</p>
  </div>

  <script>
    const params = new URLSearchParams(window.location.search);
    const goto = params.get("goto") || "index.php";
    let countdown = 3;

    const countdownEl = document.getElementById("countdown");
    const timer = setInterval(() => {
      countdown--;
      countdownEl.textContent = countdown;
      if (countdown <= 0) {
        clearInterval(timer);
        location.href = goto;
      }
    }, 1000);
  </script>
</body>
</html>
