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
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../ALL_HTML/index.html");
    exit;
}
$user_id = $_SESSION['user_id'];

// Helper function for JSON responses
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ---------- CREATE GAME ----------
    if ($action === 'create') {
        do {
            $game_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
            $stmt = $conn->prepare("SELECT id FROM multiplayer_games WHERE game_code = ?");
            if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
            $stmt->bind_param("s", $game_code);
            $stmt->execute();
            $check = $stmt->get_result();
            $exists = $check->num_rows > 0;
            $stmt->close();
        } while ($exists);

        $board_seed = rand(1, 1000000);
        $stmt = $conn->prepare("INSERT INTO multiplayer_games (game_code, board_seed) VALUES (?, ?)");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("si", $game_code, $board_seed);
        if (!$stmt->execute()) jsonResponse(['success' => false, 'message' => 'Failed to create game: ' . $stmt->error]);
        $game_id = $stmt->insert_id;
        $stmt->close();

        // Add creator as first player
        $stmt = $conn->prepare("INSERT INTO multiplayer_players (game_id, user_id) VALUES (?, ?)");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("ii", $game_id, $user_id);
        if (!$stmt->execute()) jsonResponse(['success' => false, 'message' => 'Failed to add player: ' . $stmt->error]);
        $player_id = $stmt->insert_id;
        $stmt->close();

        $_SESSION['multiplayer_game'] = $game_id;
        $_SESSION['multiplayer_player'] = $player_id;

        jsonResponse(['success' => true, 'game_code' => $game_code, 'game_id' => $game_id]);
    }

    // ---------- JOIN GAME ----------
    if ($action === 'join') {
        if (empty($_POST['game_code'])) jsonResponse(['success' => false, 'message' => 'Game code required.']);
        $game_code = strtoupper($_POST['game_code']);

        $stmt = $conn->prepare("SELECT * FROM multiplayer_games WHERE game_code = ? AND status = 'waiting'");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("s", $game_code);
        $stmt->execute();
        $game = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$game) {
            jsonResponse(['success' => false, 'message' => 'Invalid game code or game already started.']);
        }

        // Check if already in this game
        $stmt = $conn->prepare("SELECT id FROM multiplayer_players WHERE game_id = ? AND user_id = ?");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("ii", $game['id'], $user_id);
        $stmt->execute();
        $check = $stmt->get_result();
        $alreadyIn = $check->num_rows > 0;
        $stmt->close();

        if ($alreadyIn) {
            // User already registered for this game: restore session so they can view/play the game.
            $row = $check->fetch_assoc();
            $player_id = $row['id'];
            $_SESSION['multiplayer_game'] = $game['id'];
            $_SESSION['multiplayer_player'] = $player_id;

            jsonResponse(['success' => true, 'game_id' => $game['id'], 'message' => 'Restored game session.']);
        }

        // Add player
        $stmt = $conn->prepare("INSERT INTO multiplayer_players (game_id, user_id) VALUES (?, ?)");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("ii", $game['id'], $user_id);
        if (!$stmt->execute()) jsonResponse(['success' => false, 'message' => 'Failed to join game: ' . $stmt->error]);
        $player_id = $stmt->insert_id;
        $stmt->close();

        // Update game status to active
        $stmt = $conn->prepare("UPDATE multiplayer_games SET status = 'active' WHERE id = ?");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("i", $game['id']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['multiplayer_game'] = $game['id'];
        $_SESSION['multiplayer_player'] = $player_id;

        jsonResponse(['success' => true, 'game_id' => $game['id']]);
    }

    // ---------- LEAVE GAME ----------
    if ($action === 'leave') {
        unset($_SESSION['multiplayer_game'], $_SESSION['multiplayer_player']);
        jsonResponse(['success' => true]);
    }

    // ---------- POLL ----------
    if ($action === 'poll') {
        $game_id = $_SESSION['multiplayer_game'] ?? 0;
        if (!$game_id) jsonResponse(['success' => false, 'message' => 'No active game']);

        $stmt = $conn->prepare("SELECT * FROM multiplayer_games WHERE id = ?");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $game = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$game) jsonResponse(['success' => false, 'message' => 'Game not found']);

        $stmt = $conn->prepare("SELECT p.*, u.username FROM multiplayer_players p JOIN users u ON p.user_id = u.id WHERE p.game_id = ?");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $players_result = $stmt->get_result();
        $player_list = [];
        while ($row = $players_result->fetch_assoc()) {
            $player_list[] = $row;
        }
        $stmt->close();

        $current_player_id = $_SESSION['multiplayer_player'] ?? 0;
        $opponent = null;
        foreach ($player_list as $p) {
            if ($p['id'] != $current_player_id) {
                $opponent = $p;
                break;
            }
        }

        jsonResponse([
            'success' => true,
            'game_status' => $game['status'],
            'winner_id' => $game['winner_id'],
            'players' => $player_list,
            'opponent' => $opponent,
            'current_player_id' => $current_player_id
        ]);
    }

    // ---------- UPDATE PROGRESS ----------
    if ($action === 'update_progress') {
        $game_id = $_SESSION['multiplayer_game'] ?? 0;
        $player_id = $_SESSION['multiplayer_player'] ?? 0;
        if (!$game_id || !$player_id) jsonResponse(['success' => false, 'message' => 'No active game']);

        // Get current progress
        $stmt = $conn->prepare("SELECT progress, completed FROM multiplayer_players WHERE id = ?");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $player = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$player) jsonResponse(['success' => false, 'message' => 'Player not found']);
        if ($player['completed']) jsonResponse(['success' => false, 'message' => 'Already completed']);

        $new_progress = $player['progress'] + 1;
        $total_pairs = 8;
        $completed = ($new_progress >= $total_pairs) ? 1 : 0;
        $completed_at = $completed ? date('Y-m-d H:i:s') : null;

        $stmt = $conn->prepare("UPDATE multiplayer_players SET progress = ?, completed = ?, completed_at = ? WHERE id = ?");
        if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        $stmt->bind_param("iisi", $new_progress, $completed, $completed_at, $player_id);
        if (!$stmt->execute()) jsonResponse(['success' => false, 'message' => 'Failed to update progress: ' . $stmt->error]);
        $stmt->close();

        // If player completed, check if they are the first winner
        if ($completed) {
            $stmt = $conn->prepare("SELECT winner_id FROM multiplayer_games WHERE id = ?");
            if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $game = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$game['winner_id']) {
                $stmt = $conn->prepare("UPDATE multiplayer_games SET winner_id = ?, status = 'completed' WHERE id = ?");
                if (!$stmt) jsonResponse(['success' => false, 'message' => 'DB error: ' . $conn->error]);
                $stmt->bind_param("ii", $user_id, $game_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        jsonResponse(['success' => true, 'new_progress' => $new_progress, 'completed' => $completed]);
    }

    // Unknown action
    jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

// Handle direct join via URL parameter
if (isset($_GET['join']) && !isset($_SESSION['multiplayer_game'])) {
    // will be handled by JavaScript
}

// If not AJAX, check if user is in a game
$game_id = $_SESSION['multiplayer_game'] ?? 0;
$player_id = $_SESSION['multiplayer_player'] ?? 0;
$game = null;
$opponent = null;
$board_seed = null;
$current_player = null;

if ($game_id) {
    $stmt = $conn->prepare("SELECT * FROM multiplayer_games WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $game = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if ($game) {
        $board_seed = $game['board_seed'];
        $stmt = $conn->prepare("SELECT p.*, u.username FROM multiplayer_players p JOIN users u ON p.user_id = u.id WHERE p.game_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $players_result = $stmt->get_result();
            while ($row = $players_result->fetch_assoc()) {
                if ($row['id'] == $player_id) {
                    $current_player = $row;
                } else {
                    $opponent = $row;
                }
            }
            $stmt->close();
        }
    } else {
        // Invalid game, clear session
        unset($_SESSION['multiplayer_game'], $_SESSION['multiplayer_player']);
        $game_id = 0;
    }
}

$conn->close();

// Base URL for sharing (fix REQUEST_SCHEME issue)
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindTwister · Multiplayer Battle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <!-- Canvas Confetti for celebration -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
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
        .container {
            position: relative;
            z-index: 10;
            max-width: 1000px;
        }
        h1 {
            text-shadow: 0 0 10px cyan, 0 0 20px magenta;
            margin-bottom: 20px;
            font-size: 1.5rem;
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
        .game-panel {
            background: rgba(0,255,255,0.1);
            border: 2px solid cyan;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .join-area {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-neon {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.7rem;
            border: 2px solid cyan;
            border-radius: 30px;
            padding: 12px 24px;
            background: rgba(0,0,0,0.7);
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-neon:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px cyan;
            border-color: magenta;
        }
        .btn-leave {
            border-color: #ff4444;
        }
        .btn-leave:hover {
            box-shadow: 0 0 20px #ff4444;
        }
        .game-code-input {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.7rem;
            padding: 12px;
            border: 2px solid cyan;
            border-radius: 30px;
            background: rgba(0,0,0,0.5);
            color: white;
            text-align: center;
            width: 200px;
        }
        .game-code-display {
            font-size: 2.5rem;
            color: #ffcc00;
            letter-spacing: 5px;
            background: rgba(0,0,0,0.3);
            padding: 10px 20px;
            border-radius: 15px;
            display: inline-block;
            border: 2px solid gold;
        }
        .copy-btn {
            font-size: 0.6rem;
            margin-left: 10px;
            padding: 8px 12px;
        }
        .players-status {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
        }
        .player-card {
            background: rgba(0,0,0,0.6);
            border: 2px solid cyan;
            border-radius: 15px;
            padding: 15px;
            width: 200px;
            text-align: center;
        }
        .player-card.you {
            border-color: #00ff00;
            box-shadow: 0 0 15px #00ff00;
        }
        .player-card.opponent {
            border-color: magenta;
            box-shadow: 0 0 15px magenta;
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
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            max-width: 500px;
            margin: 20px auto;
        }
        .card {
            aspect-ratio: 1 / 1;
            background: rgba(0,0,0,0.6);
            border: 3px solid cyan;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 0 15px rgba(0,255,255,0.3);
            color: white;
        }
        .card.flipped {
            background: rgba(255,255,255,0.2);
            border-color: magenta;
            box-shadow: 0 0 20px magenta;
        }
        .card.matched {
            background: rgba(0,255,0,0.2);
            border-color: #00ff00;
            box-shadow: 0 0 20px #00ff00;
            cursor: default;
            opacity: 0.6;
        }
        .card:active:not(.flipped):not(.matched) {
            transform: scale(0.95);
        }
        .winner-message {
            font-size: 1.2rem;
            color: gold;
            text-shadow: 0 0 10px orange;
            margin: 20px 0;
        }
        .waiting-message {
            color: #ffcc00;
            font-size: 0.8rem;
            margin: 20px 0;
        }
        .instructions {
            font-size: 0.6rem;
            color: #aaa;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <div>🌐 MULTIPLAYER BATTLE</div>
        <a href="dashboard.php" class="back-btn">⬅ Back to Lobby</a>
    </div>

    <?php if (!$game_id): ?>
        <!-- No active game: show create/join -->
        <div class="game-panel text-center">
            <h2>Battle a Friend</h2>
            <p style="font-size:0.6rem; margin:20px 0;">Create a room and share the code, or enter a code to join.</p>
            <div class="join-area">
                <button class="btn-neon" onclick="createGame()">✨ CREATE GAME</button>
                <div style="display: flex; gap:10px;">
                    <input type="text" id="gameCodeInput" class="game-code-input" placeholder="CODE" maxlength="6" autocomplete="off" value="<?= isset($_GET['join']) ? htmlspecialchars($_GET['join']) : '' ?>">
                    <button class="btn-neon" onclick="joinGame()">🔑 JOIN</button>
                </div>
            </div>
            <div id="message" style="margin-top:20px; color:#ffcc00;"></div>
            <div class="instructions">Share the 6-character code with a friend.</div>
        </div>

        <!-- Auto-join if join parameter is present -->
        <script>
            <?php if (isset($_GET['join'])): ?>
            window.onload = function() {
                setTimeout(function() {
                    joinGame();
                }, 500);
            };
            <?php endif; ?>
        </script>
    <?php else: ?>
        <!-- Active game -->
        <div class="game-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    Game Code: 
                    <span class="game-code-display" id="gameCode"><?= $game['game_code'] ?></span>
                    <button class="btn-neon copy-btn" onclick="copyGameCode()">📋 COPY</button>
                </div>
                <div>
                    Status: <span id="gameStatus"><?= $game['status'] ?></span>
                    <button class="btn-neon btn-leave" onclick="leaveGame()" style="margin-left:10px;">🚪 LEAVE</button>
                </div>
            </div>

            <div class="players-status">
                <div class="player-card you">
                    <div>YOU</div>
                    <div id="yourProgress"><?= $current_player ? $current_player['progress'] : '0' ?>/8 pairs</div>
                    <div class="progress-bar"><div id="yourProgressFill" class="progress-fill" style="width:<?= ($current_player ? $current_player['progress'] : 0)/8*100 ?>%"></div></div>
                </div>
                <div class="player-card opponent">
                    <div id="opponentName"><?= $opponent ? $opponent['username'] : 'Waiting for opponent...' ?></div>
                    <div id="opponentProgress"><?= $opponent ? $opponent['progress'].'/8 pairs' : '0/8 pairs' ?></div>
                    <div class="progress-bar"><div id="opponentProgressFill" class="progress-fill" style="width:<?= $opponent ? ($opponent['progress']/8*100) : 0 ?>%"></div></div>
                </div>
            </div>

            <?php if ($game['status'] == 'waiting'): ?>
                <div class="waiting-message text-center">
                    ⏳ Waiting for another player to join with this code...<br>
                    <small>Share the code above with your friend.</small>
                </div>
            <?php endif; ?>

            <?php if ($game['status'] == 'active'): ?>
                <div class="grid-container" id="game-grid"></div>
                <div class="text-center" id="gameMessage" style="margin-top:15px;">Find pairs! Race to 8 pairs.</div>
            <?php elseif ($game['status'] == 'completed'): ?>
                <div class="winner-message text-center" id="winnerMessage">
                    <?php if ($game['winner_id'] == $user_id): ?>
                        🏆 YOU WIN! 🏆
                    <?php else: ?>
                        😢 Opponent wins. Better luck next time!
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <button class="btn-neon" onclick="leaveGame()">Play Again</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden audio for celebration -->
<audio id="winSound" preload="auto">
    <source src="data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAtAAADY29tbWVudABCaWdTb3VuZEJhbmsuY29tIC8gTG9uZXBhbm5pbmcub3JnAFJlbGVhc2VkIHVuZGVyIENyZWF0aXZlIENvbW1vbnMgQXR0cmlidXRpb24gTGljZW5zZQBDcmVhdGl2ZSBDb21tb25zIEF0dHJpYnV0aW9uIE5vbi1Db21tZXJjaWFsIExpY2Vuc2UAMjAyNC0wMy0yMgoAAAAYVVJMAAAAAABodHRwOi8vd3d3LkJpZ1NvdW5kQmFuay5jb20vAC0tLQAAAAgARXhwbGljaXQgYmluYXJ5IG9iamVjdCBmaWxlLgo=" type="audio/mpeg">
    Your browser does not support the audio element.
</audio>

<script>
    // Game state
    let gameId = <?= $game_id ?: 0 ?>;
    let playerId = <?= $player_id ?: 0 ?>;
    let boardSeed = <?= $board_seed ?: 0 ?>;
    let currentProgress = <?= $current_player ? $current_player['progress'] : 0 ?>;
    let totalPairs = 8;
    let gameActive = <?= ($game && $game['status'] == 'active') ? 'true' : 'false' ?>;
    let gameCompleted = <?= ($game && $game['status'] == 'completed') ? 'true' : 'false' ?>;
    let gameWaiting = <?= ($game && $game['status'] == 'waiting') ? 'true' : 'false' ?>;
    let winnerId = <?= ($game && $game['winner_id']) ? $game['winner_id'] : 0 ?>;

    // Memory game variables
    let cards = [];
    let flippedCards = [];
    let lockBoard = false;
    let matchedPairs = 0;

    // Symbols
    const symbols = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼'];

    // If winner is current user, trigger celebration
    if (gameCompleted && winnerId == <?= $user_id ?>) {
        celebrateWin();
    }

    // If in active game, initialize board
    if (gameId && gameActive && !gameCompleted) {
        initGame();
        startPolling();
    } else if (gameId && gameWaiting) {
        // If waiting, still poll to detect when opponent joins
        startPolling();
    }

    // Create game
    function createGame() {
        fetch('multiplayer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=create'
        })
        .then(res => res.json())
        .then(data => {
            console.log('Create game response:', data);
            if (data.success) {
                location.reload();
            } else {
                document.getElementById('message').innerText = data.message || 'Error creating game.';
            }
        })
        .catch(err => {
            console.error('Create game request error:', err);
            document.getElementById('message').innerText = 'Network error: ' + err;
        });
    }

    // Join game
    function joinGame() {
        const code = document.getElementById('gameCodeInput').value.trim().toUpperCase();
        if (code.length !== 6) {
            document.getElementById('message').innerText = 'Enter a 6-character code.';
            return;
        }
        fetch('multiplayer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=join&game_code=' + code
        })
        .then(res => res.json())
        .then(data => {
            console.log('Join game response:', data);
            if (data.success) {
                location.reload();
            } else {
                document.getElementById('message').innerText = data.message || 'Join failed.';
            }
        })
        .catch(err => {
            console.error('Join game request error:', err);
            document.getElementById('message').innerText = 'Network error: ' + err;
        });
    }

    // Leave game
    function leaveGame() {
        fetch('multiplayer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=leave'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    // Copy game code to clipboard
    function copyGameCode() {
        const code = document.getElementById('gameCode').innerText;
        navigator.clipboard.writeText(code).then(() => {
            alert('Code copied!');
        }).catch(() => {
            alert('Could not copy code.');
        });
    }

    // Poll for opponent progress and game status
    function startPolling() {
        setInterval(() => {
            fetch('multiplayer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=poll'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateOpponentInfo(data);
                    // If game becomes active, reload to show board
                    if (data.game_status === 'active' && gameWaiting) {
                        location.reload();
                    }
                    // If game becomes completed and we are winner, trigger celebration
                    if (data.game_status === 'completed' && !gameCompleted) {
                        if (data.winner_id == <?= $user_id ?>) {
                            celebrateWin();
                        }
                        location.reload(); // reload to show final message
                    }
                } else {
                    console.warn('Poll response not successful:', data);
                }
            })
            .catch(err => {
                console.error('Poll request failed:', err);
            });
        }, 800);
    }

    function updateOpponentInfo(data) {
        const opponent = data.opponent;
        if (opponent) {
            document.getElementById('opponentName').innerText = opponent.username;
            const oppProgress = opponent.progress || 0;
            document.getElementById('opponentProgress').innerText = oppProgress + '/8 pairs';
            document.getElementById('opponentProgressFill').style.width = (oppProgress / 8 * 100) + '%';
        }
        // Update own progress from server (in case of multiple tabs)
        const currentPlayer = data.players.find(p => p.id == data.current_player_id);
        if (currentPlayer) {
            currentProgress = currentPlayer.progress;
            document.getElementById('yourProgress').innerText = currentProgress + '/8 pairs';
            document.getElementById('yourProgressFill').style.width = (currentProgress / 8 * 100) + '%';
        }
    }

    // Celebration function: confetti + sound
    function celebrateWin() {
        // Play sound
        const audio = document.getElementById('winSound');
        audio.play().catch(e => console.log('Audio play failed:', e));

        // Fire confetti
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
        // Additional bursts
        setTimeout(() => {
            confetti({
                particleCount: 50,
                spread: 100,
                origin: { y: 0.5, x: 0.2 }
            });
            confetti({
                particleCount: 50,
                spread: 100,
                origin: { y: 0.5, x: 0.8 }
            });
        }, 200);
    }

    // ---------- Memory Game Functions ----------
    function initGame() {
        // Seeded random for consistent shuffle
        const rng = mulberry32(boardSeed);
        let deck = [];
        for (let i = 0; i < 8; i++) {
            deck.push(symbols[i]);
            deck.push(symbols[i]);
        }
        // Fisher-Yates shuffle with seeded RNG
        for (let i = deck.length - 1; i > 0; i--) {
            const j = Math.floor(rng() * (i + 1));
            [deck[i], deck[j]] = [deck[j], deck[i]];
        }
        cards = deck.map((symbol, index) => ({
            id: index,
            symbol: symbol,
            flipped: false,
            matched: false
        }));
        renderGrid();
    }

    function mulberry32(seed) {
        return function() {
            seed |= 0;
            seed = (seed + 0x6D2B79F5) | 0;
            let t = Math.imul(seed ^ seed >>> 15, 1 | seed);
            t = (t + Math.imul(t ^ t >>> 7, 61 | t)) ^ t;
            return ((t ^ t >>> 14) >>> 0) / 4294967296;
        };
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
        if (lockBoard || gameCompleted || !gameActive) return;
        const card = cards.find(c => c.id === id);
        if (card.matched || card.flipped) return;

        card.flipped = true;
        renderGrid();
        flippedCards.push(card);

        if (flippedCards.length === 2) {
            lockBoard = true;
            const [card1, card2] = flippedCards;

            if (card1.symbol === card2.symbol) {
                // Match
                card1.matched = true;
                card2.matched = true;
                matchedPairs++;

                updateProgress();

                flippedCards = [];
                lockBoard = false;
                renderGrid();
            } else {
                // No match
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

    function updateProgress() {
        fetch('multiplayer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=update_progress'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentProgress = data.new_progress;
                document.getElementById('yourProgress').innerText = currentProgress + '/8 pairs';
                document.getElementById('yourProgressFill').style.width = (currentProgress / 8 * 100) + '%';
                if (data.completed) {
                    gameCompleted = true;
                    document.getElementById('gameMessage').innerHTML = '🎉 You completed all pairs! Waiting for opponent...';
                }
            }
        });
    }
</script>
</body>
</html>