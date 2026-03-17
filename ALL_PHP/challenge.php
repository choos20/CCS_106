<?php
session_start();

// Prevent browser caching of authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Database configuration
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CCS_106_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../ALL_HTML/index.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// Create challenges table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
    grid_size INT,
    target_matches INT,
    reward_brain INT DEFAULT 0,
    reward_stars INT DEFAULT 0,
    is_daily BOOLEAN DEFAULT FALSE,
    is_weekly BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create user_challenges table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS user_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id INT NOT NULL,
    progress INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    claimed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
)");

// Insert sample challenges if table is empty
$check = $conn->query("SELECT COUNT(*) as cnt FROM challenges");
$row = $check->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO challenges (title, description, difficulty, grid_size, target_matches, reward_brain, reward_stars, is_daily, is_weekly) VALUES
        ('Daily Sprint', 'Match 20 tiles as fast as you can!', 'easy', 4, 20, 50, 1, TRUE, FALSE),
        ('Weekly Gauntlet', 'Match 50 tiles in one game', 'medium', 5, 50, 200, 3, FALSE, TRUE),
        ('Brain Teaser', '6x6 grid – 40 matches required', 'hard', 6, 40, 500, 5, FALSE, FALSE),
        ('Quick Reflexes', 'Match 15 tiles on a 4x4 board', 'easy', 4, 15, 30, 0, FALSE, FALSE),
        ('Master Mind', 'Complete 60 matches on a 6x6 grid', 'hard', 6, 60, 600, 7, FALSE, FALSE)
    ");
}

// Handle reward claiming
if (isset($_POST['claim_reward'])) {
    $challenge_id = $_POST['challenge_id'];
    // Get reward values
    $stmt = $conn->prepare("SELECT reward_brain, reward_stars FROM challenges WHERE id = ?");
    $stmt->bind_param("i", $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $challenge = $result->fetch_assoc();
    $stmt->close();

    // Update user stats (assuming you have brain and stars columns in users table)
    $conn->query("UPDATE users SET brain = brain + {$challenge['reward_brain']}, stars = stars + {$challenge['reward_stars']} WHERE id = $user_id");

    // Mark as claimed
    $conn->query("UPDATE user_challenges SET claimed = TRUE WHERE user_id = $user_id AND challenge_id = $challenge_id");

    // Refresh page to show updated stats
    header("Location: challenge.php");
    exit;
}

// Fetch all active challenges
$challenges = $conn->query("SELECT * FROM challenges WHERE active = TRUE ORDER BY is_daily DESC, is_weekly DESC, difficulty");

// Fetch user progress for these challenges
$userProgress = [];
$progressResult = $conn->query("SELECT challenge_id, progress, completed, claimed FROM user_challenges WHERE user_id = $user_id");
while ($row = $progressResult->fetch_assoc()) {
    $userProgress[$row['challenge_id']] = $row;
}

// For simplicity, we'll also update user's brain/stars display
$userData = $conn->query("SELECT brain, stars FROM users WHERE id = $user_id")->fetch_assoc();
$brain = $userData['brain'] ?? 0;
$stars = $userData['stars'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindTwister · Challenges</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Press Start 2P', cursive;
            background: radial-gradient(circle at top, #0f172a, #030712);
            color: #e0f2fe;
            padding: 20px;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(0,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,255,255,0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            animation: gridMove 20s linear infinite;
            z-index: 0;
        }
        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }
        .container { position: relative; z-index: 10; max-width: 1200px; }
        h1 {
            text-shadow: 0 0 10px cyan, 0 0 20px magenta;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(0,0,0,0.5);
            padding: 15px 25px;
            border-radius: 50px;
            border: 2px solid cyan;
        }
        .user-stats span {
            color: #ffcc00;
            margin-left: 20px;
        }
        .back-btn {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.6rem;
            border: 2px solid magenta;
            border-radius: 30px;
            padding: 8px 16px;
            background: rgba(0,0,0,0.7);
            color: white;
            text-decoration: none;
            transition: 0.2s;
        }
        .back-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px magenta;
        }
        .challenge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .challenge-card {
            background: rgba(0,255,255,0.1);
            backdrop-filter: blur(8px);
            border: 2px solid cyan;
            border-radius: 20px;
            padding: 25px 20px;
            transition: 0.2s;
            box-shadow: 0 0 20px rgba(0,255,255,0.3);
            position: relative;
            overflow: hidden;
        }
        .challenge-card:hover {
            transform: translateY(-5px);
            border-color: #ff00ff;
            box-shadow: 0 0 30px #ff00ff;
        }
        .difficulty {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.5rem;
            padding: 4px 8px;
            border-radius: 20px;
            background: rgba(0,0,0,0.5);
            border: 1px solid;
        }
        .difficulty.easy { border-color: #00ff00; color: #aaffaa; }
        .difficulty.medium { border-color: #ffff00; color: #ffffaa; }
        .difficulty.hard { border-color: #ff0000; color: #ffaaaa; }
        .challenge-title {
            font-size: 1rem;
            color: #ffcc00;
            margin-bottom: 10px;
        }
        .challenge-desc {
            font-size: 0.6rem;
            margin: 15px 0;
            color: #ccc;
        }
        .reward {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            font-size: 0.6rem;
        }
        .reward span { color: #ffcc00; }
        .progress-section {
            margin: 15px 0;
        }
        .progress-bar {
            height: 8px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, cyan, magenta);
            width: 0%;
        }
        .progress-text {
            font-size: 0.5rem;
            display: flex;
            justify-content: space-between;
        }
        .btn-action {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.6rem;
            border: 2px solid cyan;
            border-radius: 30px;
            padding: 8px 16px;
            background: rgba(0,0,0,0.7);
            color: white;
            width: 100%;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
        }
        .btn-action:hover:not(:disabled) {
            transform: scale(1.02);
            box-shadow: 0 0 20px cyan;
        }
        .btn-action:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .badge-daily, .badge-weekly {
            font-size: 0.4rem;
            padding: 4px 8px;
            border-radius: 20px;
            background: #ffcc00;
            color: black;
            display: inline-block;
            margin-bottom: 10px;
        }
        .badge-weekly { background: magenta; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <div class="user-stats">
            🧠 Brain: <span><?= $brain ?></span> &nbsp; ⭐ Stars: <span><?= $stars ?></span>
        </div>
        <a href="dashboard.php" class="back-btn">⬅ Back to Lobby</a>
    </div>
    
    <h1>🏆 Challenges</h1>

    <div class="challenge-grid">
        <?php while ($challenge = $challenges->fetch_assoc()): 
            $cid = $challenge['id'];
            $progress = $userProgress[$cid]['progress'] ?? 0;
            $completed = $userProgress[$cid]['completed'] ?? false;
            $claimed = $userProgress[$cid]['claimed'] ?? false;
            $target = $challenge['target_matches'];
            $percent = $target > 0 ? min(100, ($progress / $target) * 100) : 0;
        ?>
        <div class="challenge-card">
            <?php if ($challenge['is_daily']): ?>
                <span class="badge-daily">DAILY</span>
            <?php elseif ($challenge['is_weekly']): ?>
                <span class="badge-weekly">WEEKLY</span>
            <?php endif; ?>
            <span class="difficulty <?= $challenge['difficulty'] ?>"><?= strtoupper($challenge['difficulty']) ?></span>
            <div class="challenge-title"><?= htmlspecialchars($challenge['title']) ?></div>
            <div class="challenge-desc"><?= htmlspecialchars($challenge['description']) ?></div>
            <div class="reward">
                <span>🏆 <?= $challenge['reward_brain'] ?> Brain</span>
                <span>⭐ <?= $challenge['reward_stars'] ?> Stars</span>
            </div>
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $percent ?>%"></div>
                </div>
                <div class="progress-text">
                    <span>Progress: <?= $progress ?>/<?= $target ?></span>
                    <?php if ($completed && !$claimed): ?>
                        <span style="color: #00ff00;">✔ Completed</span>
                    <?php elseif ($completed && $claimed): ?>
                        <span style="color: #aaa;">✔ Claimed</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($challenge['title'] == 'Brain Teaser' || $challenge['title'] == 'Master Mind' || $challenge['title'] == 'Weekly Gauntlet'): ?>
                <!-- Coming Soon Button -->
                <button class="btn-action" disabled style="background: rgba(100, 100, 100, 0.5); border-color: #888; color: #aaa; cursor: not-allowed;">
                    🔜 Coming Soon - Stay Tuned
                </button>
            <?php elseif (!$completed): ?>
                <?php if ($challenge['title'] == 'Daily Sprint'): ?>
                    <form action="Daily_Sprint.php" method="GET">
                <?php elseif ($challenge['title'] == 'Quick Reflexes'): ?>
                    <form action="Quick Reflexes.php" method="GET">
                <?php else: ?>
                    <form action="game.php" method="GET">
                <?php endif; ?>
                    <input type="hidden" name="challenge" value="<?= $cid ?>">
                    <button type="submit" class="btn-action">▶ Start Challenge</button>
                </form>
            <?php endif; ?>

            <?php if ($completed && !$claimed): ?>
                <!-- Claim reward -->
                <form method="POST">
                    <input type="hidden" name="challenge_id" value="<?= $cid ?>">
                    <button type="submit" name="claim_reward" class="btn-action" style="border-color: gold;">🎁 Claim Reward</button>
                </form>
            <?php else: ?>
                <button class="btn-action" disabled>✔ Completed</button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Optional: auto-refresh or AJAX could be added here -->
<script>
  // Prevent back button navigation after logout
  window.history.pushState(null, null, window.location.href);
  window.addEventListener('popstate', function() {
    // Check if user is still logged in by making AJAX call
    fetch('check_session.php')
      .then(response => response.json())
      .then(data => {
        if (!data.logged_in) {
          // Session expired, redirect to login
          window.location.href = '../ALL_HTML/index.html';
        } else {
          // Still logged in, allow back
          window.history.back();
        }
      })
      .catch(() => {
        // If check fails, redirect to login for safety
        window.location.href = '../ALL_HTML/index.html';
      });
  });
</script>
</body>
</html>