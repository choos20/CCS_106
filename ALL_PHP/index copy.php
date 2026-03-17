<?php
session_start();
require __DIR__ . '/../includes/db.php';

$error = '';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $password = $_POST['password'] ?? '';

    // Enforce exactly 8-character passwords (min/max)
    if (strlen($password) !== 8) {
        $error = "Password must be exactly 8 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR BINARY email = ?");
        $stmt->bind_param("ss", $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
        // Redirect based on role
        if ($row['role'] === 'admin') {
            header("Location: DB_admin.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }
        }
        $error = "Invalid username/email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MindTwister | Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

<style>
* {
  box-sizing: border-box;
}

body {
  font-family: 'Press Start 2P', cursive;
  background: radial-gradient(circle at top, #1b2735, #090a0f);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0;
  padding: 20px;
  color: #fff;
}

.login-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 500px;
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

.logo-text {
    font-size: 30px;
    font-weight: bold;
    margin-bottom: 30px;
}

.login-box {
  background: #121820;
  border: 4px solid #00ccff;
  box-shadow: 0 0 25px rgba(0,204,255,0.4);
  padding: 50px;
  width: 100%;
  max-width: 500px;
  text-align: center;
  border-radius: 12px;
  box-sizing: border-box;
}

.login-title {
  font-size: 1.3rem;
  margin: 6px 0;
  color: #ffcc00;
  text-shadow: 2px 2px 6px #000;
}

.form-title {
  font-size: 1.1rem;
  margin: 6px 0;
  color: #00ccff;
  text-shadow: 2px 2px 6px #000;
  letter-spacing: 2px;
}

label {
  display: block;
  text-align: left;
  font-size: 0.65rem;
  margin: 16px 0 6px;
  color: #aaa;
}

input {
  width: 100%;
  padding: 12px;
  background: #212529;
  border: 2px solid #555;
  border-radius: 8px;
  color: #fff;
  font-family: 'Press Start 2P';
  font-size: 0.7rem;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #ffcc00;
  box-shadow: 0 0 8px rgba(255,204,0,0.5);
}

button {
  width: 100%;
  margin-top: 25px;
  padding: 14px;
  background: linear-gradient(45deg, #00ccff, #0066ff);
  border: none;
  border-radius: 10px;
  font-family: 'Press Start 2P';
  font-size: 0.7rem;
  color: #fff;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

button:hover {
  transform: scale(1.05);
  box-shadow: 0 0 18px rgba(0,204,255,0.9);
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

.links {
  margin-top: 30px;
  font-size: 0.6rem;
  color: #aaa;
}

.links a {
  color: #ffcc00;
  text-decoration: none;
}

.links a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .login-box {
    padding: 35px 25px;
  }
  .logo img {
    width: 120px;
    height: 120px;
  }
}

@media (max-width: 480px) {
  body {
    padding: 15px;
  }
  .login-box {
    padding: 30px 20px;
  }
  .logo img {
    width: 100px;
    height: 100px;
  }
  .login-title {
    font-size: 1.1rem;
  }
  label {
    font-size: 0.6rem;
  }
  input {
    font-size: 0.65rem;
    padding: 10px;
  }
  button {
    font-size: 0.7rem;
    padding: 12px;
  }
}
</style>
</head>

<body>
<div class="login-container">
  <div class="login-box">
  <div class="logo">
    <img src="../images/1.png" alt="MindTwister Logo">
  </div>
  <div class="login-title"> ------------------</div>
  <div class="form-title"> [BE ONE OF US]</div>
  <div class="login-title"> ------------------</div>

  <form method="post" novalidate>
    <label for="user">USERNAME OR EMAIL</label>
    <input type="text" id="user" name="user" required
      value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">

    <label for="password">PASSWORD</label>
    <input type="password" id="password" name="password" minlength="8" maxlength="8" required placeholder="Exactly 8 characters">

    <button type="submit">Login</button>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </form>

  <div class="links">
    <p><a href="forgot.php">Forgot Password?</a></p>
    <p><a href="signup.php">Sign Up </a></p>
  </div>
  </div>
</div>

</body>
</html>
