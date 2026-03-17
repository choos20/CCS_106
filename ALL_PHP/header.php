<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get username from session
$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MindTwister - SK Connect</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../ALL_CSS/style.css">

<style>
.sk-header{
    background: linear-gradient(90deg,#8e2de2,#0f9bff);
    color:white;
    padding:12px 20px;
}

.sk-title{
    font-family:'Press Start 2P';
    font-size:18px;
}

.sk-sub{
    font-size:14px;
}

.logout-btn{
    background:white;
    border:none;
    padding:6px 15px;
    border-radius:8px;
    font-weight:bold;
}
</style>

<script>
window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});
</script>

</head>

<body>

<div class="sk-header d-flex justify-content-between align-items-center">

    <div class="d-flex align-items-center">

        <img src="../IMAGES/sk_logo.png" width="45" class="me-3">

        <div>
            <div class="sk-title">SK CONNECT</div>
            <div class="sk-sub">Welcome, <?php echo htmlspecialchars($username); ?>!</div>
        </div>

    </div>

    <a href="../logout.php">
        <button class="logout-btn">Logout</button>
    </a>

</div>