<?php
session_start(); //Used to store data between pages (like user ID, OTP status).
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer; //important para magamit ag $mail = new PHPMailer();
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila'); // Set your timezone

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

// Check attempt count
$attemptQuery = $conn->prepare("SELECT attempts FROM otp_attempts WHERE BINARY email = ?");
$attemptQuery->bind_param("s", $email);
$attemptQuery->execute();
$attemptResult = $attemptQuery->get_result();

if ($attempt = $attemptResult->fetch_assoc()) {
    if ($attempt['attempts'] >= 5) {
        $message = "You have reached the maximum OTP requests. Try again later.";
        return;
    }
}
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE BINARY email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];

        // Generate 6-digit OTP
        $otp = strval(rand(100000, 999999));
        $expires = date("Y-m-d H:i:s", strtotime("+3 minutes"));

        // Store OTP
        $stmt = $conn->prepare("INSERT INTO password_otp (user_id, otp, expires_at) VALUES (?, ?, ?)"); //important para ma store ang otp sa database
        $stmt->bind_param("iss", $user_id, $otp, $expires);
        $stmt->execute();

        // Send OTP via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'napinasritchiebob@gmail.com'; // your email
            $mail->Password   = 'ihxcyprwymolfuqt';    // app password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('napinasritchiebob@gmail.com', 'Hi ka Mind Twister');
            $mail->addAddress($row['email']);

            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset OTP';
            $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. It expires in 3 minutes.";

            $mail->send();
            $mail->send();

// ----------------------------------------
// 7. UPDATE OTP ATTEMPT COUNT AFTER SENDING
// ----------------------------------------
$updateAttempt = $conn->prepare("
    INSERT INTO otp_attempts (email, attempts)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()
");
$updateAttempt->bind_param("s", $email);
$updateAttempt->execute();


            $_SESSION['reset_user_id'] = $user_id;
            header("Location: verify_otp.php");   // Redirect to OTP verification
            exit;

        } catch (Exception $e) {
            $message = "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        $message = "Email not found."; // If dili makitan ang email maoni ma send
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MindTwister - Forgot Password</title>
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

    input[type="email"] {
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
  </style>
</head>
<body>
  <div class="container">
    <a class="back-link" href="index copy.php">&larr; Back to Login</a>
    <h2>Forgot Password</h2>
    <?php if($message) echo "<p>$message</p>"; ?>
    <form method="post">
      <label>Email:</label>
      <input type="email" name="email" required>
      <button type="submit">Send OTP</button>
    </form>
  </div>
</body>
</html>
