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

// Ensure game_sessions table exists (run this once, or keep for safety)
$conn->query("CREATE TABLE IF NOT EXISTS game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    score INT DEFAULT 0,
    moves INT DEFAULT 0,
    time_taken INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index copy.php");
    exit;
}

$message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $_SESSION['admin_sound'] = isset($_POST['sound']) ? 1 : 0;
    $_SESSION['admin_music'] = isset($_POST['music']) ? 1 : 0;
    $message = '<div class="alert alert-success">Settings saved.</div>';
}

// Load PHPMailer (adjust path if needed)
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle email certificate sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_certificate'])) {
    $period = $_POST['period'];
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $fullname = $_POST['fullname'];

    $mail = new PHPMailer(true);
    try {
        // Server settings – replace with your SMTP credentials
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'napinasritchiebob@gmail.com';   // <-- Replace
        $mail->Password   = 'ihxcyprwymolfuqt';      // <-- Replace
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('napinasritchiebob@gmail.com', 'MindTwister Admin');
        $mail->addAddress($email, $fullname);

        $mail->isHTML(true);
        $mail->Subject = '🏆 MindTwister Player Certificate';
        $mail->Body    = "
               <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Certificate of Achievement</title>
            <style>
                /* Inline styles are used for email compatibility */
            </style>
        </head>
        <body style='margin:0; padding:20px; background-color:#f2eee5; font-family: \"Palatino Linotype\", \"Book Antiqua\", Palatino, Georgia, serif;'>
            <div style='max-width:600px; margin:0 auto; background:#fffcf0; border:8px double #c5a028; padding:30px; box-shadow:0 10px 20px rgba(0,0,0,0.1); text-align:center;'>
                <div style='font-size:48px; margin-bottom:10px;'>🏆</div>
                <h1 style='color:#2c3e50; font-family: \"Great Vibes\", \"Brush Script MT\", cursive; font-size:48px; margin:10px 0; font-weight:normal; border-bottom:2px dashed #c5a028; padding-bottom:10px;'>Certificate of Achievement</h1>
                <p style='font-size:18px; color:#4a4a4a; margin:20px 0;'>This certificate is proudly presented to</p>
                <h2 style='color:#b8860b; font-size:36px; margin:10px 0; text-transform:uppercase; letter-spacing:2px;'>" . htmlspecialchars($fullname) . "</h2>
                <p style='font-size:18px; color:#4a4a4a;'>for being the <strong>top player</strong> of MindTwister</p>
                <p style='font-size:20px; color:#2c3e50; margin:20px 0;'><span style='background:#c5a028; color:#fff; padding:5px 15px; border-radius:30px;'>" . ucfirst($period) . "</span></p>
                <p style='font-size:16px; color:#666; margin:30px 0 10px;'>with outstanding performance and dedication.</p>
                <div style='margin-top:40px; display:flex; justify-content:space-between; align-items:center;'>
                    <div style='text-align:left;'>
                        <div style='font-family: cursive; border-top:2px solid #2c3e50; padding-top:5px; width:200px;'>Administrator</div>
                    </div>
                    <div style='font-size:40px;'>✨</div>
                    <div style='text-align:right;'>
                        <div style='font-family: cursive; border-top:2px solid #2c3e50; padding-top:5px; width:200px;'>Date</div>
                    </div>
                </div>
                <p style='margin-top:30px; font-size:14px; color:#999;'>MindTwister · Where minds play</p>
            </div>
        </body>
        </html>
        ";

        // Plain text alternative for non-HTML email clients
        $mail->AltBody = "Congratulations $fullname!\n\nYou are the top player for this $period.\n\nKeep up the great work!\n\n– MindTwister Team";

        $mail->send();
        $message = '<div class="alert alert-success">Certificate sent to ' . htmlspecialchars($email) . '.</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Email could not be sent. Error: ' . $mail->ErrorInfo . '</div>';
    }
}

// Handle CRUD actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add user
    if (isset($_POST['add_user'])) {
        $fullname = trim($_POST['fullname']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR BINARY email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = '<div class="alert alert-danger">Username or email already exists.</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $username, $email, $password, $role);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">User added successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error adding user.</div>';
            }
            $stmt->close();
        }
        $check->close();
    }

    // Edit user
    elseif (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $fullname = trim($_POST['fullname']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssssi", $fullname, $username, $email, $password, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $fullname, $username, $email, $role, $id);
        }

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">User updated.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating user.</div>';
        }
        $stmt->close();
    }

    // Delete user
    elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">User deleted.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting user.</div>';
        }
        $stmt->close();
    }
}

// Fetch all users with games played
$result = $conn->query("
    SELECT u.id, u.fullname, u.username, u.email, u.role, u.created_at, COUNT(g.id) as games_played
    FROM users u
    LEFT JOIN game_sessions g ON u.id = g.user_id
    GROUP BY u.id
    ORDER BY u.id DESC
");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Real stats from game_sessions
$totalGames = $conn->query("SELECT COUNT(*) FROM game_sessions")->fetch_row()[0] ?? 0;
// Use ABS() so negative stored scores don't make the average appear negative.
$avgScore = $conn->query("SELECT AVG(ABS(score)) FROM game_sessions")->fetch_row()[0] ?? 0;

// Top players per period
$period = $_GET['period'] ?? 'week';
$dateCondition = '';
if ($period === 'week') {
    $dateCondition = "AND g.game_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($period === 'month') {
    $dateCondition = "AND g.game_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($period === 'year') {
    $dateCondition = "AND g.game_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

$topPlayers = $conn->query("
    SELECT u.id, u.fullname, u.email, COUNT(*) as games_played
    FROM game_sessions g
    JOIN users u ON g.user_id = u.id
    WHERE 1 $dateCondition
    GROUP BY u.id
    ORDER BY games_played DESC
    LIMIT 5
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindTwister · Admin Dashboard</title>
    <!-- Bootstrap + Retro Font -->
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
        /* Animated grid background */
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
        .container { position: relative; z-index: 10; }
        h1, h2 {
            text-shadow: 0 0 10px cyan, 0 0 20px magenta;
            margin-bottom: 30px;
        }
        .card {
            background: rgba(0,255,255,0.1);
            backdrop-filter: blur(8px);
            border: 2px solid cyan;
            border-radius: 15px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0,255,255,0.3);
        }
        .card-header {
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid cyan;
            font-weight: bold;
            color: #ffcc00;
        }
        .btn-glow {
            font-family: 'Press Start 2P', cursive;
            font-size: 0.6rem;
            border: 2px solid;
            border-radius: 30px;
            padding: 8px 16px;
            background: rgba(0,0,0,0.7);
            color: white;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-glow:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px currentColor;
        }
        .btn-cyan { border-color: cyan; }
        .btn-magenta { border-color: magenta; }
        .btn-gold { border-color: #ffcc00; }
        .table {
            color: white;
            font-size: 0.7rem;
        }
        .table thead { color: #ffcc00; }
        .table td, .table th { border-color: rgba(0,255,255,0.3); vertical-align: middle; }
        .modal-content {
            background: rgba(10,20,30,0.95);
            backdrop-filter: blur(12px);
            border: 2px solid cyan;
            color: white;
        }
        .form-control, .form-select {
            background: rgba(0,0,0,0.6);
            border: 1px solid cyan;
            color: white;
            font-family: 'Press Start 2P', cursive;
            font-size: 0.6rem;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(0,0,0,0.8);
            border-color: magenta;
            box-shadow: 0 0 10px magenta;
            color: white;
        }
        .stat-card {
            background: rgba(255,0,255,0.1);
            border: 2px solid magenta;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 0 20px magenta;
        }
        .stat-card .number {
            font-size: 2rem;
            color: #ffcc00;
            text-shadow: 0 0 10px gold;
        }
        .alert {
            font-size: 0.7rem;
            border-radius: 30px;
            padding: 10px 20px;
        }
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .period-btn {
            font-size: 0.6rem;
            padding: 8px 12px;
        }
        .top-player-item {
            background: rgba(255,255,255,0.05);
            border: 1px solid cyan;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .certificate-form {
            display: inline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>👑 Admin Panel</h1>
        <div>
            <button class="btn-glow btn-cyan me-2" data-bs-toggle="modal" data-bs-target="#settingsModal">⚙ Settings</button>
            <a href="logout.php" class="btn-glow btn-magenta">🚪 Logout</a>
        </div>
    </div>

    <?= $message ?>

    <!-- Performance Stats Row -->
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <div>👥 Total Users</div>
                <div class="number"><?= count($users) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div>🎮 Games Played</div>
                <div class="number"><?= $totalGames ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div>📊 Avg Score</div>
                <div class="number"><?= round($avgScore, 1) ?></div>
            </div>
        </div>
    </div>

    <!-- Top Players Section -->
    <div class="card">
        <div class="card-header">🏆 Top Players (by games played)</div>
        <div class="card-body">
            <div class="period-selector">
                <a href="?period=week" class="btn-glow btn-cyan period-btn <?= $period == 'week' ? 'active' : '' ?>">Week</a>
                <a href="?period=month" class="btn-glow btn-cyan period-btn <?= $period == 'month' ? 'active' : '' ?>">Month</a>
                <a href="?period=year" class="btn-glow btn-cyan period-btn <?= $period == 'year' ? 'active' : '' ?>">Year</a>
            </div>

            <?php if ($topPlayers && $topPlayers->num_rows > 0): ?>
                <?php while ($player = $topPlayers->fetch_assoc()): ?>
                    <div class="top-player-item">
                        <div>
                            <strong><?= htmlspecialchars($player['fullname']) ?></strong> (<?= $player['games_played'] ?> games)
                        </div>
                        <form method="POST" class="certificate-form">
                            <input type="hidden" name="user_id" value="<?= $player['id'] ?>">
                            <input type="hidden" name="email" value="<?= $player['email'] ?>">
                            <input type="hidden" name="fullname" value="<?= htmlspecialchars($player['fullname']) ?>">
                            <input type="hidden" name="period" value="<?= $period ?>">
                            <button type="submit" name="send_certificate" class="btn-glow btn-gold btn-sm">📧 Send Certificate</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No game data for this period.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tools Section -->
    <div class="card mt-4">
        <div class="card-header">🔧 Admin Tools</div>
        <div class="card-body">
            <button class="btn-glow btn-cyan me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">➕ Add User</button>
            <button class="btn-glow btn-gold" onclick="exportData()">📥 Export Users CSV</button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card mt-4">
        <div class="card-header">📋 User Management</div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Games Played</th>
                        <th></th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['fullname']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['role'] ?></td>
                        <td><?= $user['games_played'] ?></td>
                        <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                        <td>
                            <button class="btn-glow btn-cyan btn-sm" onclick="editUser(<?= $user['id'] ?>)">✏️ Edit</button>
                            <button class="btn-glow btn-magenta btn-sm" onclick="deleteUser(<?= $user['id'] ?>)">🗑️ Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">⚙ Admin Settings</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="sound" id="sound" <?= isset($_SESSION['admin_sound']) && $_SESSION['admin_sound'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="sound">🔊 Sound Effects</label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="music" id="music" <?= isset($_SESSION['admin_music']) && $_SESSION['admin_music'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="music">🎵 Background Music</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-glow btn-magenta" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="save_settings" class="btn-glow btn-cyan">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">➕ Add New User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Role</label>
                <select name="role" class="form-select">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-glow btn-magenta" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_user" class="btn-glow btn-cyan">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✏️ Edit User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="modal-body">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="mb-3">
                <label>Role</label>
                <select name="role" id="edit_role" class="form-select">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-glow btn-magenta" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_user" class="btn-glow btn-cyan">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">⚠️ Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this user?
      </div>
      <div class="modal-footer">
        <form method="POST">
            <input type="hidden" name="user_id" id="delete_user_id">
            <button type="button" class="btn-glow btn-cyan" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_user" class="btn-glow btn-magenta">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const users = <?= json_encode($users) ?>;

function editUser(id) {
    const user = users.find(u => u.id == id);
    if (user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_fullname').value = user.fullname;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
}

function deleteUser(id) {
    document.getElementById('delete_user_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

function exportData() {
    let csv = "ID,Full Name,Username,Email,Role,Created At\n";
    users.forEach(u => {
        csv += `${u.id},"${u.fullname}","${u.username}","${u.email}",${u.role},${u.created_at}\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'users.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
</body>
</html>