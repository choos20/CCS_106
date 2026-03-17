<?php
session_start();

// Autoload PHPMailer (if installed via Composer)
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CCS_106_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $admin_code = trim($_POST['admin_code'] ?? '');

    // Default role is user
    $role = ($admin_code === 'Basic') ? 'admin' : 'user';

    // Validation
    if (!$fullname) {
        $error = "Full name is required.";
    } elseif (!$username || strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!$password || strlen($password) != 8) {
        $error = "Password must be exactly 8 characters.";
    } elseif (!preg_match('/(?=.*[a-zA-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?])/', $password)) {
        $error = "Password must contain at least one letter and one special character.";
    } else {
        // Check if username/email exists (email is case-sensitive)
        $stmt = $conn->prepare("SELECT id FROM users WHERE BINARY username = ? OR BINARY email = ? LIMIT 1");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $stmt->close();

            // Hash password & insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            // Note: email is stored with original case due to case-sensitive validation
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role, brain, stars, best_time) VALUES (?, ?, ?, ?, ?, 0, 0, NULL)");
            $stmt->bind_param('sssss', $fullname, $username, $email, $password_hash, $role);

            if ($stmt->execute()) {
                $success = "✅ Account created successfully! You can <a href='index copy.php'>log in here</a>.";


                // Send welcome email
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'napinasritchiebob@gmail.com'; // change to your email
                    $mail->Password   = 'ihxcyprwymolfuqt'; // use App Password if using Gmail
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('napinasritchiebob@gmail.com', 'MIND TWISTER');
                    $mail->addAddress($email, $fullname);

                    $mail->isHTML(true);
$mail->Subject = '🎮 Welcome to Mind Twister - Your Gaming Adventure Awaits!';
$mail->Body    = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; font-family: \"Poppins\", \"Helvetica Neue\", Helvetica, Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'>
    <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);'>
        
        <!-- Header with Game Logo/Banner -->
        <div style='background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%); padding: 40px 20px; text-align: center;'>
            <div style='font-size: 48px; margin-bottom: 10px;'>🧠</div>
            <h1 style='color: white; margin: 0; font-size: 36px; font-weight: 700; letter-spacing: 2px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);'>MIND TWISTER</h1>
            <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px; font-weight: 300;'>Where Puzzles Meet Adventure</p>
        </div>
        
        <!-- Main Content -->
        <div style='padding: 40px 30px; background: white;'>
            
            <!-- Welcome Message with Gamer Style -->
            <div style='text-align: center; margin-bottom: 30px;'>
                <h2 style='color: #4A5568; font-size: 28px; font-weight: 700; margin: 0 0 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;'>
                    ⚡ Welcome, $fullname! ⚡
                </h2>
                <div style='height: 4px; width: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 15px auto; border-radius: 2px;'></div>
            </div>
            
            <!-- Player Card -->
            <div style='background: #F7FAFC; border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 2px solid #E2E8F0;'>
                
                <!-- Game-Themed Welcome -->
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='font-size: 32px; margin-bottom: 15px;'>🎮 🎲 🧩</div>
                    <p style='color: #2D3748; font-size: 18px; line-height: 1.6; margin: 0; font-weight: 400;'>
                        Ready to challenge your mind? Your journey into the world of puzzles, 
                        riddles, and brain teasers begins now!
                    </p>
                </div>
                
                " . ($role === 'admin' ? "
                <!-- Admin Badge - Special for Admin Users -->
                <div style='background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%); border-radius: 50px; padding: 15px 20px; margin: 20px 0; text-align: center;'>
                    <span style='color: white; font-weight: 700; font-size: 16px; letter-spacing: 1px;'>
                        👑 ADMIN ACCESS GRANTED 👑
                    </span>
                    <p style='color: white; margin: 10px 0 0; font-size: 14px; opacity: 0.9;'>
                        You have special administrative privileges
                    </p>
                </div>
                " : "
                <!-- Gamer Badge - for Regular Users -->
                <div style='background: linear-gradient(135deg, #48BB78 0%, #38A169 100%); border-radius: 50px; padding: 8px 20px; margin: 20px 0; text-align: center; display: inline-block;'>
                    <span style='color: white; font-weight: 600; font-size: 14px;'>
                        ⭐ PLAYER LEVEL: BEGINNER ⭐
                    </span>
                </div>
                " ) . "
                
                <!-- Game Features Preview -->
                <div style='display: flex; justify-content: space-around; margin: 30px 0; text-align: center;'>
                    <div style='flex: 1;'>
                        <div style='font-size: 24px; margin-bottom: 5px;'>🧩</div>
                        <div style='color: #4A5568; font-size: 14px; font-weight: 600;'>Brain Puzzles</div>
                    </div>
                    <div style='flex: 1;'>
                        <div style='font-size: 24px; margin-bottom: 5px;'>🏆</div>
                        <div style='color: #4A5568; font-size: 14px; font-weight: 600;'>Leaderboards</div>
                    </div>
                    <div style='flex: 1;'>
                        <div style='font-size: 24px; margin-bottom: 5px;'>🎯</div>
                        <div style='color: #4A5568; font-size: 14px; font-weight: 600;'>Challenges</div>
                    </div>
                </div>
                
                <!-- Call to Action Button -->
                <div style='text-align: center; margin: 30px 0 20px;'>
                    <a href='http://yourdomain.com/index.php' 
                       style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                              color: white; text-decoration: none; padding: 16px 40px; border-radius: 50px; 
                              font-weight: 700; font-size: 18px; letter-spacing: 1px; box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
                              transition: transform 0.3s ease;'>
                        🚀 START YOUR ADVENTURE 🚀
                    </a>
                </div>
                
                <!-- Quick Tips -->
                <div style='background: #EDF2F7; border-radius: 10px; padding: 15px; margin-top: 25px;'>
                    <p style='color: #4A5568; margin: 0 0 10px; font-weight: 600; font-size: 14px;'>
                        💡 PRO TIPS:
                    </p>
                    <ul style='color: #718096; margin: 0; padding-left: 20px; font-size: 14px;'>
                        <li style='margin-bottom: 5px;'>Complete daily puzzles to earn bonus points</li>
                        <li style='margin-bottom: 5px;'>Challenge friends to beat your high score</li>
                        <li>Check leaderboards to see top players worldwide</li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer with Team Signature -->
            <div style='text-align: center; border-top: 2px solid #E2E8F0; padding-top: 25px;'>
                <div style='font-size: 24px; margin-bottom: 15px;'>🎪</div>
                <p style='color: #718096; font-size: 16px; line-height: 1.5; margin: 0 0 10px; font-style: italic;'>
                    \"Train your brain, twist your mind!\"
                </p>
                <p style='color: #4A5568; font-size: 18px; font-weight: 600; margin: 20px 0 5px;'>
                    The Mind Twister Team
                </p>
                <p style='color: #A0AEC0; font-size: 14px; margin: 0;'>
                    Ready to play? <a href='http://yourdomain.com/index.php' style='color: #667eea; text-decoration: none; font-weight: 600;'>Login here</a>
                </p>
            </div>
            
        </div>
        
        <!-- Footer Decoration -->
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; text-align: center;'>
            <p style='color: rgba(255,255,255,0.8); margin: 0; font-size: 12px;'>
                © 2024 Mind Twister. All rights reserved. | Level up your mind!
            </p>
        </div>
        
    </div>
</body>
</html>
";

                    $mail->send(); 
                } catch (Exception $e) {
                    error_log("Welcome email failed: " . $mail->ErrorInfo);
                }

                $fullname = $username = $email = $admin_code = '';
            } else {
                $error = "Error during signup: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MindTwister - Sign Up</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Press Start 2P', cursive;
    background: radial-gradient(circle at top, #1b2735, #090a0f);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
    color: #fff;
    padding: 20px;
}
.container {
    background: #121820;
    border: 4px solid #00ccff;
    box-shadow: 0 0 25px rgba(0,204,255,0.4);
    padding: 50px;
    width: 100%;
    max-width: 500px;
    text-align: center;
    border-radius: 12px;
}
.logo {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}
.logo img {
    width: 140px;
    height: 140px;
    object-fit: contain;
    filter: drop-shadow(0 0 20px rgba(0,204,255,0.6));
    transition: transform 0.3s ease;
}
.logo img:hover {
    transform: scale(1.05) rotate(3deg);
}
.form-title {
    font-size: 1.3rem;
    margin-bottom: 18px;
    color: #ffcc00;
    text-shadow: 2px 2px 6px #000;
}
.subtitle {
    font-size: 0.7rem;
    color: #aaa;
    margin-bottom: 18px;
}
label {
    display: block;
    text-align: left;
    font-size: 0.65rem;
    margin: 16px 0 6px;
    color: #aaa;
}
input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 12px;
    background: #212529;
    border: 2px solid #555;
    border-radius: 8px;
    color: #fff;
    font-family: 'Press Start 2P';
    font-size: 0.7rem;
}
input:focus {
    outline: none;
    border-color: #ffcc00;
    box-shadow: 0 0 8px rgba(255,204,0,0.5);
}
.password-container {
    position: relative;
    display: flex;
    align-items: center;
}
.toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 1rem;
    padding: 5px 8px;
    transition: color 0.2s ease;
    font-family: 'Press Start 2P';
    display: none; /* Hidden by default */
}
.toggle-password.visible {
    display: block;
}
.toggle-password:hover {
    color: #ffcc00;
}
button {
    width: 100%;
    margin-top: 25px;
    padding: 14px;
    background: linear-gradient(45deg, #00ccff, #0066ff);
    border: none;
    border-radius: 10px;
    font-family: 'Press Start 2P';
    font-size: 0.8rem;
    color: #fff;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
button:hover {
    transform: scale(1.05);
    box-shadow: 0 0 18px rgba(0,204,255,0.9);
}
.back-button {
    margin-top: 14px;
    background: transparent;
    color: #00ccff;
    border: 2px solid #00ccff;
    padding: 12px 0;
    font-family: 'Press Start 2P';
    font-size: 0.7rem;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}
.back-button:hover {
    background: #00ccff;
    color: #121820;
}
.error {
    margin-top: 20px;
    background: #4d1f1f;
    border: 2px solid #ff4444;
    padding: 12px;
    border-radius: 8px;
    font-size: 0.65rem;
    color: #ffaaaa;
}
.success {
    margin-top: 20px;
    background: #1f3f2c;
    border: 2px solid #44ff88;
    padding: 12px;
    border-radius: 8px;
    font-size: 0.65rem;
    color: #a8ffcd;
}

@media (max-width: 480px) {
    .container {
        padding: 30px 20px;
    }
    .logo img {
        width: 100px;
        height: 100px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="../images/1.png" alt="MindTwister Logo">
    </div>
    <div class="form-title">🧠 MindTwister</div>
    <div class="subtitle">Create your account</div>

    <form method="post" novalidate>
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" required value="<?= htmlspecialchars($fullname ?? '') ?>" />

        <label for="username">Username</label>
        <input type="text" id="username" name="username" minlength="3" required value="<?= htmlspecialchars($username ?? '') ?>" />

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>" />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="8" maxlength="8" required placeholder="Exactly 8 characters" title="Password must be exactly 8 characters with at least one letter and one special character" />
        <small style="color: #888; font-size: 0.7rem; display: block; margin-top: 5px;">
            ⚠ Must be exactly 8 characters, contain 1 letter and 1 special character (!@#$%^&* etc.)
        </small>

        <label for="admin_code">Admin Code (optional)</label>
        <div class="password-container">
            <input type="password" id="admin_code" name="admin_code" placeholder="Enter admin code" />

        </div>

        <button type="submit">SIGN UP</button>
    </form>

    <button class="back-button" onclick="window.location.href='index copy.php'">Back to Login</button>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}

function updateToggleVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.value.length > 0) {
        button.classList.add('visible');
    } else {
        button.classList.remove('visible');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const adminCodeInput = document.getElementById('admin_code');
    if (adminCodeInput) {
        adminCodeInput.addEventListener('input', function() {
            updateToggleVisibility('admin_code');
        });
    }
});
</script>

</body>
</html>
