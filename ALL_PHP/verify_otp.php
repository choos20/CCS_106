<?php
session_start();
require __DIR__ . '/../includes/db.php';

if(!isset($_SESSION['reset_user_id'])){
    header("Location: forgot.php");
    exit;
}

$message = '';
$user_id = $_SESSION['reset_user_id'];
$seconds_left = 0;

// Fetch latest unused OTP to determine remaining time
$stmt = $conn->prepare("SELECT expires_at FROM password_otp WHERE user_id = ? AND used = 0 ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$otpMeta = $stmt->get_result();
if ($meta = $otpMeta->fetch_assoc()) {
    $seconds_left = max(0, strtotime($meta['expires_at']) - time());
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $otp = strval($otp); // ensure string

    // Get latest unused OTP
    $stmt = $conn->prepare("SELECT * FROM password_otp WHERE user_id = ? AND used = 0 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['otp'] === $otp && strtotime($row['expires_at']) >= time()) {
            // Mark OTP as used
            $stmt = $conn->prepare("UPDATE password_otp SET used = 1 WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();

            $_SESSION['otp_verified'] = true;
            header("Location: reset_password.php");
            exit;
        } else {
            $message = "Invalid or expired OTP.";
        }
    } else {
        $message = "No OTP found. Please request again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindTwister - Verify OTP</title>
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

        input[type="text"] {
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

                .timer {
                        margin: 0.5rem 0 1rem;
                        font-size: 0.8rem;
                        color: #ffcc00;
                        letter-spacing: 1px;
                }

                .expired {
                        color: #ff8080;
                }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-link" href="forgot.php">&larr; Back</a>
        <h2>Enter OTP</h2>
                <div class="timer">OTP expires in <span id="timer" data-remaining="<?= $seconds_left ?>">03:00</span></div>
        <?php if($message) echo "<p>$message</p>"; ?>
        <form method="post">
            <input type="text" name="otp" placeholder="6-digit OTP" required>
            <button type="submit">Verify OTP</button>
        </form>
    </div>
        <script>
            (function() {
                const timerEl = document.getElementById('timer');
                if (!timerEl) return;
                let remaining = parseInt(timerEl.dataset.remaining || '0', 10);
                const form = document.querySelector('form');
                const button = form ? form.querySelector('button[type="submit"]') : null;

                function format(seconds) {
                    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                    const s = (seconds % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                }

                function tick() {
                    timerEl.textContent = format(remaining);
                    if (remaining <= 0) {
                        timerEl.classList.add('expired');
                        timerEl.textContent = '00:00 (expired)';
                        if (button) {
                            button.disabled = true;
                            button.textContent = 'OTP expired';
                        }
                        if (form) {
                            const input = form.querySelector('input[name="otp"]');
                            if (input) input.disabled = true;
                        }
                        return;
                    }
                    remaining -= 1;
                    setTimeout(tick, 1000);
                }

                // Clamp to 3 minutes max for safety
                remaining = Math.min(remaining, 180);
                tick();
            })();
        </script>
</body>
</html>
