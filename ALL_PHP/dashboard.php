<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../ALL_HTML/index.html");
    exit;
}


// Database connection
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CCS_106_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Safely fetch user data – if columns don't exist, use defaults
$username = $_SESSION['username']; // fallback
$brain = 720;
$stars = 12;
$bestTime = '45s';

// Check if the columns exist (optional but safe)
$columns = $conn->query("SHOW COLUMNS FROM users LIKE 'brain'");
if ($columns->num_rows > 0) {
    $stmt = $conn->prepare("SELECT username, brain, stars, best_time FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $username = $row['username'] ?? $username;
        $brain = $row['brain'] ?? $brain;
        $stars = $row['stars'] ?? $stars;
        $bestTime = $row['best_time'] ?? $bestTime;
    }
    $stmt->close();
} else {
    // Columns don't exist – just fetch username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $username = $row['username'];
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>MindTwister | Game Lobby</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <style>
    /* (Keep all your existing CSS – it's unchanged) */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Press Start 2P', cursive;
      background: #0a0f1e;
       overflow: auto;        /* was hidden – now allows scrolling */
  min-height: 100vh;     /* ensure full height */

      display: flex;
      justify-content: center;
      align-items: center;
      color: #e0f2fe;
      overflow: hidden;
      position: relative;
    }

    /* Animated grid background */
    body::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        linear-gradient(rgba(0, 255, 255, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 255, 255, 0.05) 1px, transparent 1px);
      background-size: 50px 50px;
      pointer-events: none;
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
    }

    @keyframes float {
      0% { transform: translateY(100vh) translateX(0); }
      100% { transform: translateY(-100vh) translateX(20px); }
    }

    /* INTRO SCREEN */
    .intro {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #090a0f;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 999;
      overflow: hidden;
      transition: opacity 0.8s ease;
    }

    .puzzle-container {
      display: grid;
      grid-template-columns: repeat(3, 90px);
      gap: 10px;
      margin-bottom: 30px;
    }

    .piece {
      width: 90px;
      height: 90px;
      background: linear-gradient(145deg, #00ccff, #00ffcc);
      border-radius: 8px;
      box-shadow: 0 0 15px #00ccff;
      opacity: 0;
      transition: box-shadow 0.3s;
      position: relative;
    }

    .piece::before,
    .piece::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      background: inherit;
      border-radius: 50%;
      box-shadow: 0 0 10px currentColor;
    }
    .piece::before {
      top: -10px;
      left: 50%;
      transform: translateX(-50%);
    }
    .piece::after {
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
    }

    @keyframes flyIn {
      0% {
        opacity: 0;
        transform: translate(var(--startX), var(--startY)) rotate(var(--startRotate)) scale(0.3);
      }
      70% {
        opacity: 1;
        transform: translate(0, 0) rotate(0deg) scale(1.1);
      }
      100% {
        opacity: 1;
        transform: translate(0, 0) rotate(0deg) scale(1);
      }
    }

    .piece.fly-in {
      animation: flyIn 0.6s cubic-bezier(0.25, 0.1, 0.15, 1.2) forwards;
    }

    .piece.glow {
      animation: glow 1s infinite alternate;
    }

    @keyframes glow {
      from { box-shadow: 0 0 10px #00ccff, 0 0 20px #00ccff; }
      to { box-shadow: 0 0 30px #00ffff, 0 0 50px #00ffff; }
    }

    .logo {
      font-size: 3rem;
      font-weight: bold;
      background: linear-gradient(45deg, #ffcc00, #ffaa00);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 0 20px #ffaa00;
      opacity: 0;
      transform: scale(0.5);
      transition: opacity 0.5s, transform 0.5s;
      margin-bottom: 20px;
    }
    .logo.reveal {
      opacity: 1;
      transform: scale(1);
    }

    .countdown {
      font-size: 2rem;
      color: #ffcc00;
      text-shadow: 0 0 20px #ffcc00;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .countdown.reveal {
      opacity: 1;
    }

    /* ---------- DASHBOARD ---------- */
    .dashboard {
      width: 1000px;
      max-height: 90vh;      /* limit height to 90% of viewport */
    overflow-y: auto;
      background: rgba(10, 20, 30, 0.8);
      backdrop-filter: blur(8px);
      border: 2px solid #00ffff;
      box-shadow: 0 0 40px rgba(0, 255, 255, 0.3), inset 0 0 20px rgba(0, 255, 255, 0.2);
      border-radius: 30px;
      padding: 40px;
      position: relative;
      display: none;
      animation: slideUp 0.8s ease;
      z-index: 10;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(50px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .game-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 20px;
    }

    .player-info {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .avatar-frame {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #00ffff, #ff00ff);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      box-shadow: 0 0 20px #00ffff;
      border: 2px solid white;
    }

    .player-details h2 {
      font-size: 16px;
      color: #ffcc00;
      margin-bottom: 5px;
    }

    .player-details p {
      font-size: 10px;
      color: #aaa;
    }

    .level-progress {
      margin-top: 8px;
      width: 200px;
      height: 8px;
      background: #333;
      border-radius: 4px;
      overflow: hidden;
      border: 1px solid #00ffff;
    }

    .level-progress-fill {
      width: 65%;
      height: 100%;
      background: linear-gradient(90deg, #00ffff, #ff00ff);
      box-shadow: 0 0 10px #00ffff;
      border-radius: 4px;
    }

    .stats-grid {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .stat-item {
      background: rgba(0, 255, 255, 0.1);
      border: 1px solid #00ffff;
      border-radius: 15px;
      padding: 15px 20px;
      text-align: center;
      min-width: 110px;
      transition: 0.2s;
      backdrop-filter: blur(4px);
    }

    .stat-item:hover {
      transform: scale(1.05);
      box-shadow: 0 0 20px #00ffff;
      border-color: #ff00ff;
    }

    .stat-icon {
      font-size: 24px;
      margin-bottom: 8px;
    }

    .stat-label {
      font-size: 8px;
      color: #ffcc00;
      margin-bottom: 5px;
    }

    .stat-value {
      font-size: 16px;
      font-weight: bold;
      color: white;
      text-shadow: 0 0 8px #00ffff;
    }

    .menu-section {
      margin: 40px 0;
    }

    .section-title {
      font-size: 12px;
      color: #ffcc00;
      margin-bottom: 20px;
      text-transform: uppercase;
      letter-spacing: 2px;
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
    }

    .game-card {
      background: rgba(0, 255, 255, 0.05);
      border: 2px solid #00ffff;
      border-radius: 20px;
      padding: 35px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s;
      backdrop-filter: blur(4px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
      position: relative;
      overflow: hidden;
    }

    .game-card::before {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(45deg, #00ffff, #ff00ff, #00ffff);
      z-index: -1;
      border-radius: 22px;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .game-card:hover::before {
      opacity: 1;
    }

    .game-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 0 30px #00ffff;
      border-color: transparent;
    }

    .card-icon {
      font-size: 48px;
      margin-bottom: 15px;
      filter: drop-shadow(0 0 10px #00ffff);
    }

    .card-title {
      font-size: 14px;
      color: #ffcc00;
      margin-bottom: 8px;
    }

    .card-desc {
      font-size: 8px;
      color: #ccc;
    }

    .activity-row {
      display: flex;
      gap: 25px;
      margin-top: 30px;
    }

    .daily-challenge {
      flex: 2;
      background: rgba(255, 0, 255, 0.1);
      border: 2px solid #ff00ff;
      border-radius: 20px;
      padding: 20px;
      backdrop-filter: blur(4px);
      box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
      cursor: pointer;
      transition: 0.2s;
    }

    .daily-challenge:hover {
      transform: scale(1.02);
      border-color: #00ffff;
      box-shadow: 0 0 30px cyan;
    }

    .challenge-title {
      font-size: 12px;
      color: #ffcc00;
      margin-bottom: 15px;
    }

    .challenge-progress {
      display: flex;
      align-items: center;
      gap: 15px;
      margin: 15px 0;
    }

    .progress-bar {
      flex: 1;
      height: 10px;
      background: #333;
      border-radius: 5px;
      overflow: hidden;
    }

    .progress-fill {
      width: 40%;
      height: 100%;
      background: linear-gradient(90deg, #ff00ff, #00ffff);
      border-radius: 5px;
    }

    .challenge-reward {
      font-size: 10px;
      color: #ffcc00;
    }

    .activity-feed {
      flex: 1;
      background: rgba(0, 255, 255, 0.1);
      border: 2px solid #00ffff;
      border-radius: 20px;
      padding: 20px;
      backdrop-filter: blur(4px);
    }

    .feed-item {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 15px;
      font-size: 8px;
    }

    .feed-icon {
      width: 24px;
      height: 24px;
      background: #00ffff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: black;
    }

    .settings-panel {
      display: none;
      margin-top: 30px;
      border-top: 2px solid #00ffff;
      padding-top: 25px;
    }

    .settings-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-top: 20px;
    }

    .setting-box {
      background: rgba(0, 255, 255, 0.1);
      padding: 20px;
      border: 2px solid #00ffff;
      border-radius: 15px;
      text-align: center;
      font-size: 24px;
      backdrop-filter: blur(4px);
    }

    .setting-box p {
      font-size: 10px;
      margin: 10px 0;
      color: #ffcc00;
    }

    input[type="range"] {
      width: 100%;
      accent-color: #00ffff;
    }

    select {
      width: 100%;
      padding: 8px;
      margin-top: 10px;
      background: #0a0f1e;
      color: white;
      border: 1px solid #00ffff;
      border-radius: 5px;
    }

    button {
      background: #00ffff;
      border: none;
      padding: 8px 16px;
      font-family: inherit;
      cursor: pointer;
      border-radius: 5px;
      color: black;
      font-weight: bold;
      transition: 0.2s;
    }

    button:hover {
      background: #ff00ff;
      color: white;
      box-shadow: 0 0 15px #ff00ff;
    }

    .action-row {
      display: flex;
      gap: 25px;
      margin-top: 30px;
    }
  </style>
</head>
<body>

<!-- INTRO ANIMATION -->
<div class="intro" id="intro">
  <div class="puzzle-container" id="puzzleContainer"></div>
  <div class="logo" id="logo">MindTwister</div>
  <div class="countdown" id="countdown"></div>
</div>

<!-- MAIN DASHBOARD -->
<div class="dashboard" id="dashboard">
  <div class="game-header">
    <div class="player-info">
      <div class="avatar-frame">🎮</div>
      <div class="player-details">
        <h2><?= htmlspecialchars($username) ?></h2>
        <p>BRAIN SOLDIER • RANK: 5</p>
        <div class="level-progress">
          <div class="level-progress-fill" style="width:65%"></div>
        </div>
      </div>
    </div>
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-icon">🧠</div>
        <div class="stat-label">BRAIN POINTS</div>
        <div class="stat-value" id="brainPoints"><?= $brain ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-icon">⭐</div>
        <div class="stat-label">STARS</div>
        <div class="stat-value" id="starCount"><?= $stars ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-icon">⏱</div>
        <div class="stat-label">BEST TIME</div>
        <div class="stat-value"><?= $bestTime ?></div>
      </div>
    </div>
  </div>

  <div class="menu-section">
    <div class="section-title">GAME LOBBY</div>
    <div class="card-grid">
      <div class="game-card" onclick="playGame()">
        <div class="card-icon">▶</div>
        <div class="card-title">QUICK PLAY</div>
        <div class="card-desc">Jump into random puzzle</div>
      </div>
      <div class="game-card" onclick="openChallenges()">
        <div class="card-icon">🏆</div>
        <div class="card-title">CHALLENGES</div>
        <div class="card-desc">Daily & weekly quests</div>
      </div>
     <div class="game-card" onclick="openMultiplayer()">
        <div class="card-icon">🌐</div>
        <div class="card-title">MULTIPLAYER</div>
        <div class="card-desc">Battle friends</div>
      </div>
    </div>
  </div>

  <div class="activity-row">
    <div class="daily-challenge" onclick="openChallenges()">
      <div class="challenge-title">⚡ DAILY CHALLENGE</div>
      <div>Match 50 tiles</div>
      <div class="challenge-progress">
        <span>20/50</span>
        <div class="progress-bar"><div class="progress-fill" style="width:40%"></div></div>
      </div>
      <div class="challenge-reward">Reward: +100 Brain, 1 Star</div>
    </div>
    <div class="activity-feed">
      <div class="challenge-title">📡 ACTIVITY</div>
      <div class="feed-item">
        <div class="feed-icon">🏅</div>
        <div>You unlocked 'Speed Demon'</div>
      </div>
      <div class="feed-item">
        <div class="feed-icon">👤</div>
        <div>Friend 'Neo' beat your score</div>
      </div>
      <div class="feed-item">
        <div class="feed-icon">🎁</div>
        <div>Daily bonus ready!</div>
      </div>
    </div>
  </div>

  <div class="action-row">
    <div class="game-card" style="flex:1; padding:20px;" onclick="toggleSettings()">
      <div class="card-icon">⚙</div>
      <div class="card-title">SETTINGS</div>
    </div>
    <div class="game-card" style="flex:1; padding:20px;" onclick="logout()">
      <div class="card-icon">❌</div>
      <div class="card-title">QUIT</div>
    </div>
  </div>

  <div class="settings-panel" id="settingsPanel">
    <h3 style="color:#ffcc00;">GAME SETTINGS</h3>
    <div class="settings-grid">
      <div class="setting-box">
        🎵 <p>Music</p>
        <select id="musicSelect">
          <option value="music1.mp3">Theme 1</option>
          <option value="music2.mp3">Theme 2</option>
          <option value="14.mp3">14 Silent Sanctuary</option>
          <option value="multo.mp3">Multo</option>
          <option value="yayoi.mp3">Yayoi</option>
        </select>
      </div>
      <div class="setting-box">
        🔊 <p>Volume</p>
        <input type="range" id="volume" min="0" max="100">
      </div>
      <div class="setting-box">
        🔇 <p>Mute</p>
        <button onclick="muteMusic()">Toggle</button>
      </div>
      <div class="setting-box">
        🎮 <p>Controls</p>
        <button>Keyboard</button>
      </div>
    </div>
  </div>
</div>

<!-- Audio elements -->
<audio id="bgMusic" loop><source src="../Music/music1.mp3" type="audio/mpeg"></audio>
<audio id="pieceSound"><source src="../Music/puzzle_drop.mp3" type="audio/mpeg"></audio>
<audio id="completeSound"><source src="../Music/puzzle_complete.mp3" type="audio/mpeg"></audio>

<script>
(function() {
  // Web Audio setup
  let audioCtx;
  try {
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  } catch (e) {
    console.warn('Web Audio not supported');
  }

  function playSwoosh() {
    if (!audioCtx || audioCtx.state === 'suspended') return;
    const now = audioCtx.currentTime;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.type = 'sawtooth';
    osc.frequency.value = 300 + Math.random() * 400;
    gain.gain.setValueAtTime(0.2, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
    osc.start(now);
    osc.stop(now + 0.3);
  }

  function playDrum() {
    if (!audioCtx || audioCtx.state === 'suspended') return;
    const now = audioCtx.currentTime;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.frequency.value = 100;
    gain.gain.setValueAtTime(0.3, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
    osc.start(now);
    osc.stop(now + 0.2);
  }

  function playFanfare() {
    if (!audioCtx || audioCtx.state === 'suspended') return;
    const now = audioCtx.currentTime;
    [523.25, 659.25, 783.99].forEach((freq, i) => {
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.1, now + i * 0.15);
      gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.15 + 0.4);
      osc.start(now + i * 0.15);
      osc.stop(now + i * 0.15 + 0.4);
    });
  }

  function resumeAudio() {
    if (audioCtx && audioCtx.state === 'suspended') {
      audioCtx.resume();
    }
    document.removeEventListener('click', resumeAudio);
  }
  document.addEventListener('click', resumeAudio);

  // Intro animation
  const intro = document.getElementById('intro');
  const dashboard = document.getElementById('dashboard');
  const puzzleContainer = document.getElementById('puzzleContainer');
  const logoEl = document.getElementById('logo');
  const countdownEl = document.getElementById('countdown');
  const music = document.getElementById('bgMusic');

  const showDashboard = () => {
    intro.style.display = 'none';
    dashboard.style.display = 'block';
    music.volume = 0.5;
    music.play().catch(() => {});
  };

  const introPlayed = sessionStorage.getItem('introPlayed') === 'true';
  if (introPlayed) {
    showDashboard();
  } else {
    for (let i = 0; i < 9; i++) {
      const piece = document.createElement('div');
      piece.className = 'piece';
      const side = Math.floor(Math.random() * 4);
      let startX, startY;
      const offset = 600;
      switch (side) {
        case 0: startX = (Math.random() - 0.5) * 400 + 'px'; startY = -offset + 'px'; break;
        case 1: startX = offset + 'px'; startY = (Math.random() - 0.5) * 400 + 'px'; break;
        case 2: startX = (Math.random() - 0.5) * 400 + 'px'; startY = offset + 'px'; break;
        case 3: startX = -offset + 'px'; startY = (Math.random() - 0.5) * 400 + 'px'; break;
      }
      const startRotate = Math.random() * 360 - 180 + 'deg';
      piece.style.setProperty('--startX', startX);
      piece.style.setProperty('--startY', startY);
      piece.style.setProperty('--startRotate', startRotate);
      puzzleContainer.appendChild(piece);
    }

    const pieces = document.querySelectorAll('.piece');
    let delay = 0;
    pieces.forEach((piece) => {
      setTimeout(() => {
        piece.classList.add('fly-in');
        playSwoosh();
      }, delay);
      delay += 150;
    });

    setTimeout(() => {
      pieces.forEach(p => p.classList.add('glow'));
      playFanfare();
      logoEl.classList.add('reveal');

      let count = 3;
      countdownEl.classList.add('reveal');
      const interval = setInterval(() => {
        if (count > 0) {
          countdownEl.textContent = count + '...';
          playDrum();
          count--;
        } else {
          countdownEl.textContent = 'GO!';
          playFanfare();
          clearInterval(interval);
          setTimeout(() => {
            intro.style.opacity = '0';
            setTimeout(() => {
              intro.style.display = 'none';
              dashboard.style.display = 'block';
              sessionStorage.setItem('introPlayed', 'true');
              music.volume = 0.5;
              music.play().catch(() => {});
            }, 800);
          }, 800);
        }
      }, 800);
    }, delay + 500);
  }

  // Dashboard functions
  window.playGame = function() {
    window.location.href = "../ALL_HTML/index.html";
  };

  window.openChallenges = function() {
    window.location.href = "challenge.php";
  };

  window.openMultiplayer = function() {
    window.location.href = "multiplayer.php";
  };

  window.toggleSettings = function() {
    const panel = document.getElementById('settingsPanel');
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
  };

  window.muteMusic = function() {
    const music = document.getElementById('bgMusic');
    music.muted = !music.muted;
  };

  window.logout = function() {
    window.location.href = "logout.php";
  };

  document.getElementById('volume').addEventListener('input', function(e) {
    const music = document.getElementById('bgMusic');
    music.volume = e.target.value / 100;
  });

  document.getElementById('musicSelect').addEventListener('change', function(e) {
    const music = document.getElementById('bgMusic');
    music.src = "../Music/" + e.target.value;
    music.play().catch(() => {});
  });

  // Generate floating particles
  for (let i = 0; i < 30; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDelay = Math.random() * 15 + 's';
    particle.style.animationDuration = 10 + Math.random() * 20 + 's';
    document.body.appendChild(particle);
  }
})();
</script>

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