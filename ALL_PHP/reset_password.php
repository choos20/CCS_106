<?php
session_start();
require __DIR__ . '/../includes/db.php';

if(!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])){
    header("Location: forgot.php");
    exit;
}

$message = '';
$user_id = $_SESSION['reset_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $message = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $user_id);
        $stmt->execute();

        // Clear session
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['otp_verified']);

        $message = "Password updated successfully. <a href='index copy.php'>Login here</a>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindTwister - Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Press Start 2P', cursive;
            margin: 0;
            scroll-behavior: smooth;
            background: radial-gradient(circle at top, #1b2735, #090a0f);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #ffd166;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        h2 {
            margin-bottom: 1rem;
            font-weight: 600;
            color: #fff;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        label {
            text-align: left;
            font-size: 0.9rem;
            color: #fff;
        }

        input[type="password"] {
            padding: 0.8rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            outline: none;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        button {
            padding: 0.8rem;
            border: none;
            border-radius: 6px;
            background-color: #0056a6;
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #003e7d;
        }

        p {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #ffb3b3;
        }

        a {
            color: #ffd166;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-link" href="verify_otp.php">&larr; Back</a>
        <h2>Reset Password</h2>
        <?php if($message) echo "<p>$message</p>"; ?>
        <form method="post">
            <label>New Password:</label>
            <input type="password" name="password" required>
            <label>Confirm Password:</label>
            <input type="password" name="confirm" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>
