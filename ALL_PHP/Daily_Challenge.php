<?php
session_start();

// Database configuration
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CCS_106_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: index copy.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get challenge ID from URL
$challenge_id = isset($_GET['challenge']) ? (int)$_GET['challenge'] : 0;
if (!$challenge_id) {
    header("Location: challenge.php");
    exit;
}

// Fetch challenge details
$stmt = $conn->prepare("SELECT * FROM challenges WHERE id = ? AND active = 1");
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$challenge = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$challenge) {
    die("Challenge not found.");
}

// Check user progress
$stmt = $conn->prepare("SELECT * FROM user_challenges WHERE user_id = ? AND challenge_id = ?");
$stmt->bind_param("ii", $user_id, $challenge_id);
$stmt->execute();
$user_challenge = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_challenge) {
    $stmt = $conn->prepare("INSERT INTO user_challenges (user_id, challenge_id, progress) VALUES (?, ?, 0)");
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $stmt->close();
    $progress = 0;
    $completed = false;
    $claimed = false;
} else {
    $progress = $user_challenge['progress'];
    $completed = $user_challenge['completed'];
    $claimed = $user_challenge['claimed'];
}

$target = $challenge['target_matches']; // should be 50 for Daily Challenge
$grid_size = $challenge['grid_size'];   // likely 4 or 5? We'll keep it generic.

// AJAX progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_progress') {
    header('Content-Type: application/json');

    if ($completed || $claimed) {
        echo json_encode(['success' => false, 'message' => 'Challenge already completed']);
        exit;
    }

    $new_progress = $progress + 2;
    if ($new_progress > $target) $new_progress = $target;

    $completed_now = ($new_progress >= $target) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE user_challenges SET progress = ?, completed = ? WHERE user_id = ? AND challenge_id = ?");
    $stmt->bind_param("iiii", $new_progress, $completed_now, $user_id, $challenge_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'new_progress' => $new_progress,
        'completed' => $completed_now
    ]);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindTwister · <?= htmlspecialchars($challenge['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <!-- Canvas Confetti -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
    <style>
        /* (Include all your existing CSS – same as in Daily_Sprint.php) */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Press Start 2P', cursive;
            background: radial-gradient(circle at top, #0f172a, #030712);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 80px 10px 20px;
            position: relative;
            overflow-x: hidden;
            color: #e0f2fe;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00ffff;
            box-shadow: 0 0 10px #00ffff;
            border-radius: 50%;
            opacity: 0.3;
            animation: float 15s infinite linear;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes float {
            0% { transform: translateY(100vh) translateX(0); }
            100% { transform: translateY(-100vh) translateX(20px); }
        }

        .container {
            position: relative;
            z-index: 10;
            max-width: 800px;
            width: 100%;
        }

        /* Top actions bar */
        .top-actions {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            display: flex;
            gap: 8px;
        }

        .top-actions button,
        .top-actions a {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.55rem;
            padding: 8px 12px;
            backdrop-filter: blur(10px);
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00ffff;
            color: white;
            border-radius: 8px;
            transition: 0.2s;
            text-decoration: none;
            cursor: pointer;
        }

        .top-actions button:hover,
        .top-actions a:hover {
            background: rgba(0, 255, 255, 0.2);
            box-shadow: 0 0 15px #00ffff;
            border-color: #ff00ff;
        }

        .game-panel {
            background: rgba(0, 255, 255, 0.1);
            border: 2px solid cyan;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            font-size: 0.8rem;
            margin-bottom: 20px;
        }

        .timer {
            color: #ffcc00;
            font-size: 1.2rem;
        }

        .progress-bar {
            height: 10px;
            background: #333;
            border-radius: 5px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, cyan, magenta);
            width: 0%;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(<?= $grid_size ?>, 1fr);
            gap: 15px;
            max-width: 500px;
            margin: 20px auto;
        }

        .card {
            aspect-ratio: 1 / 1;
            background: rgba(0, 0, 0, 0.6);
            border: 3px solid cyan;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
            color: white;
        }

        .card.flipped {
            background: rgba(255, 255, 255, 0.2);
            border-color: magenta;
            box-shadow: 0 0 20px magenta;
        }

        .card.matched {
            background: rgba(0, 255, 0, 0.2);
            border-color: #00ff00;
            box-shadow: 0 0 20px #00ff00;
            cursor: default;
            opacity: 0.6;
        }

        .card:active:not(.flipped):not(.matched) {
            transform: scale(0.95);
        }

        .message {
            margin-top: 20px;
            font-size: 0.8rem;
            color: #ffcc00;
            text-align: center;
        }

        .btn-complete {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.7rem;
            border: 2px solid gold;
            border-radius: 30px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-complete:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px gold;
        }

        /* Sparkle effect for matches */
        .sparkle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: gold;
            border-radius: 50%;
            pointer-events: none;
            animation: sparkleAnim 0.6s ease-out forwards;
            z-index: 20;
        }

        @keyframes sparkleAnim {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(2); }
        }
    </style>
</head>
<body>

<!-- Fixed top actions bar with mute button -->
<div class="top-actions">
    <a href="challenge.php" class="back-btn">🚪 BACK</a>
    <button id="muteButton" onclick="toggleMute()">🔊 MUTE</button>
</div>

<div class="container">
    <div class="game-panel">
        <?php if ($completed && !$claimed): ?>
            <div style="color: #00ff00; text-align: center;">✔ Challenge completed! Go claim your reward.</div>
            <div class="text-center"><a href="challenge.php" class="btn-complete">🎁 Claim Reward</a></div>
        <?php elseif ($completed && $claimed): ?>
            <div style="color: #aaa; text-align: center;">✔ Reward already claimed.</div>
        <?php else: ?>
            <div class="stats">
                <div>Progress: <span id="progress"><?= $progress ?></span>/<?= $target ?> tiles</div>
                <div class="timer" id="timer">00:00</div>
            </div>
            <div class="progress-bar">
                <div id="progressFill" class="progress-fill" style="width: <?= ($progress/$target)*100 ?>%"></div>
            </div>
            <div class="grid-container" id="game-grid"></div>
            <div class="message" id="gameMessage">Click on two cards to find a match!</div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden audio elements -->
<audio id="bgMusic" loop>
    <!-- You can replace this with your own music file -->
    <source src="data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAtAAADY29tbWVudABCaWdTb3VuZEJhbmsuY29tIC8gTG9uZXBhbm5pbmcub3JnAFJlbGVhc2VkIHVuZGVyIENyZWF0aXZlIENvbW1vbnMgQXR0cmlidXRpb24gTGljZW5zZQBDcmVhdGl2ZSBDb21tb25zIEF0dHJpYnV0aW9uIE5vbi1Db21tZXJjaWFsIExpY2Vuc2UAMjAyNC0wMy0yMgoAAAAYVVJMAAAAAABodHRwOi8vd3d3LkJpZ1NvdW5kQmFuay5jb20vAC0tLQAAAAgARXhwbGljaXQgYmluYXJ5IG9iamVjdCBmaWxlLgo=" type="audio/mpeg">
    Your browser does not support audio.
</audio>
<audio id="matchSound" preload="auto">
    <source src="data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAtAAADY29tbWVudABCaWdTb3VuZEJhbmsuY29tIC8gTG9uZXBhbm5pbmcub3JnAFJlbGVhc2VkIHVuZGVyIENyZWF0aXZlIENvbW1vbnMgQXR0cmlidXRpb24gTGljZW5zZQBDcmVhdGl2ZSBDb21tb25zIEF0dHJpYnV0aW9uIE5vbi1Db21tZXJjaWFsIExpY2Vuc2UAMjAyNC0wMy0yMgoAAAAYVVJMAAAAAABodHRwOi8vd3d3LkJpZ1NvdW5kQmFuay5jb20vAC0tLQAAAAgARXhwbGljaXQgYmluYXJ5IG9iamVjdCBmaWxlLgo=" type="audio/mpeg">
</audio>
<audio id="winSound" preload="auto">
    <source src="data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAtAAADY29tbWVudABCaWdTb3VuZEJhbmsuY29tIC8gTG9uZXBhbm5pbmcub3JnAFJlbGVhc2VkIHVuZGVyIENyZWF0aXZlIENvbW1vbnMgQXR0cmlidXRpb24gTGljZW5zZQBDcmVhdGl2ZSBDb21tb25zIEF0dHJpYnV0aW9uIE5vbi1Db21tZXJjaWFsIExpY2Vuc2UAMjAyNC0wMy0yMgoAAAAYVVJMAAAAAABodHRwOi8vd3d3LkJpZ1NvdW5kQmFuay5jb20vAC0tLQAAAAgARXhwbGljaXQgYmluYXJ5IG9iamVjdCBmaWxlLgo=" type="audio/mpeg">
</audio>
<audio id="buzzSound" preload="auto">
    <source src="data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAtAAADY29tbWVudABCaWdTb3VuZEJhbmsuY29tIC8gTG9uZXBhbm5pbmcub3JnAFJlbGVhc2VkIHVuZGVyIENyZWF0aXZlIENvbW1vbnMgQXR0cmlidXRpb24gTGljZW5zZQBDcmVhdGl2ZSBDb21tb25zIEF0dHJpYnV0aW9uIE5vbi1Db21tZXJjaWFsIExpY2Vuc2UAMjAyNC0wMy0yMgoAAAAYVVJMAAAAAABodHRwOi8vd3d3LkJpZ1NvdW5kQmFuay5jb20vAC0tLQAAAAgARXhwbGljaXQgYmluYXJ5IG9iamVjdCBmaWxlLgo=" type="audio/mpeg">
</audio>

<script>
    // Game configuration
    const target = <?= $target ?>;
    let currentProgress = <?= $progress ?>;
    let completed = <?= $completed ? 'true' : 'false' ?>;
    let claimed = <?= $claimed ? 'true' : 'false' ?>;

    // Memory game variables
    let cards = [];
    let flippedCards = [];
    let matchedPairs = 0;
    let lockBoard = false;

    // Timer
    let timerInterval = null;
    let seconds = 0;
    let timerStarted = false;

    // Audio elements
    const bgMusic = document.getElementById('bgMusic');
    const matchSound = document.getElementById('matchSound');
    const winSound = document.getElementById('winSound');
    const buzzSound = document.getElementById('buzzSound');
    let isMuted = false;

    // Symbols (8 unique pairs – you can expand if grid > 4)
    const symbols = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🦁', '🐸', '🐧']; // up to 12 pairs

    // Start background music (if not completed)
    if (!completed && !claimed) {
        bgMusic.volume = 0.3;
        bgMusic.play().catch(e => console.log('Autoplay blocked, user interaction needed'));
    }

    // Mute toggle
    window.toggleMute = function() {
        isMuted = !isMuted;
        bgMusic.muted = isMuted;
        matchSound.muted = isMuted;
        winSound.muted = isMuted;
        buzzSound.muted = isMuted;
        document.getElementById('muteButton').textContent = isMuted ? '🔇 UNMUTE' : '🔊 MUTE';
    };

    // Initialize game if not completed
    if (!completed && !claimed) {
        initGame();
    }

    function initGame() {
        createDeck();
        renderGrid();
    }

    function createDeck() {
        // Ensure enough symbols for the grid size (grid_size^2 / 2 pairs)
        const numPairs = (<?= $grid_size * $grid_size ?>) / 2;
        let deck = [];
        for (let i = 0; i < numPairs; i++) {
            const sym = symbols[i % symbols.length];
            deck.push(sym);
            deck.push(sym);
        }
        deck = shuffle(deck);
        cards = deck.map((symbol, index) => ({
            id: index,
            symbol: symbol,
            flipped: false,
            matched: false
        }));
    }

    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }

    function renderGrid() {
        const grid = document.getElementById('game-grid');
        if (!grid) return;
        grid.innerHTML = '';
        cards.forEach(card => {
            const cardDiv = document.createElement('div');
            cardDiv.className = 'card';
            if (card.flipped) cardDiv.classList.add('flipped');
            if (card.matched) cardDiv.classList.add('matched');
            cardDiv.textContent = card.flipped || card.matched ? card.symbol : '?';
            cardDiv.dataset.id = card.id;
            cardDiv.addEventListener('click', () => onCardClick(card.id));
            grid.appendChild(cardDiv);
        });
    }

    function onCardClick(id) {
        if (lockBoard || completed) return;

        const card = cards.find(c => c.id === id);
        if (card.matched || card.flipped) return;

        if (!timerStarted) {
            startTimer();
            timerStarted = true;
        }

        card.flipped = true;
        renderGrid();
        flippedCards.push(card);

        if (flippedCards.length === 2) {
            lockBoard = true;
            const [card1, card2] = flippedCards;

            if (card1.symbol === card2.symbol) {
                // Match found
                card1.matched = true;
                card2.matched = true;
                matchedPairs++;

                // Play match sound
                matchSound.play().catch(e => {});

                // Sparkle effect
                createSparkle(card1.id);
                createSparkle(card2.id);

                // Update progress
                updateProgress();

                flippedCards = [];
                lockBoard = false;
                renderGrid();

                // If all cards matched, reshuffle for another round
                if (cards.every(c => c.matched)) {
                    setTimeout(() => {
                        if (!completed && currentProgress < target) {
                            createDeck();
                            renderGrid();
                        }
                    }, 500);
                }
            } else {
                // No match
                buzzSound.play().catch(e => {});

                setTimeout(() => {
                    card1.flipped = false;
                    card2.flipped = false;
                    flippedCards = [];
                    lockBoard = false;
                    renderGrid();
                }, 800);
            }
        }
    }

    // Sparkle effect on matched cards
    function createSparkle(cardId) {
        const cardElement = document.querySelector(`.card[data-id="${cardId}"]`);
        if (!cardElement) return;
        const rect = cardElement.getBoundingClientRect();
        const sparkle = document.createElement('div');
        sparkle.className = 'sparkle';
        sparkle.style.left = rect.left + rect.width / 2 + 'px';
        sparkle.style.top = rect.top + rect.height / 2 + 'px';
        document.body.appendChild(sparkle);
        setTimeout(() => sparkle.remove(), 600);
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            seconds++;
            const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');
            document.getElementById('timer').textContent = `${mins}:${secs}`;
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function updateProgress() {
        if (completed) return;

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=update_progress'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentProgress = data.new_progress;
                document.getElementById('progress').textContent = currentProgress;
                document.getElementById('progressFill').style.width = (currentProgress / target * 100) + '%';

                if (data.completed) {
                    completed = true;
                    stopTimer();
                    winSound.play().catch(e => {});
                    confetti({
                        particleCount: 150,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                    setTimeout(() => {
                        confetti({
                            particleCount: 100,
                            spread: 100,
                            origin: { y: 0.5, x: 0.2 }
                        });
                        confetti({
                            particleCount: 100,
                            spread: 100,
                            origin: { y: 0.5, x: 0.8 }
                        });
                    }, 200);

                    document.getElementById('gameMessage').innerHTML = '🎉 CHALLENGE COMPLETE! Redirecting to claim...';
                    setTimeout(() => {
                        window.location.href = 'challenge.php';
                    }, 3000);
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Floating particles (optional, can be added)
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = 10 + Math.random() * 20 + 's';
        document.body.appendChild(particle);
    }
</script>
</body>
</html>