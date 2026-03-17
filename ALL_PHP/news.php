<?php
// Prevent browser caching of authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/auth.php';

requireLogin(); // only logged-in users can view

// Fetch all news
$result = $conn->query("SELECT * FROM news ORDER BY date_posted DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>News & Announcements - MindTwister</title>
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../ALL_CSS/news.css">
  <style>
    body {
      font-family: 'Press Start 2P', cursive;
      background: radial-gradient(circle at top, #1b2735, #090a0f);
    }
    .header-left img {
      width: 60px;
      height: 60px;
      filter: drop-shadow(0 0 15px rgba(0,204,255,0.6));
    }
  </style>
</head>
<body>

<header>
  <div class="header-left">
    <img src="../images/1.png" alt="MindTwister Logo">
    <div class="logo-text">
      <span class="sk">SK</span> <span class="connect">CONNECT</span>
    </div>
  </div>
</header>

<!-- Navigation -->
<nav>
  <ul>
    <li><a href="../ALL_PHP/dashboard.php">Home</a></li>
    <li><a href="../ALL_HTML/about.html">About Us</a></li>
    <li><a href="../ALL_PHP/members.php">SK Members</a></li>
    <li><a href="../ALL_PHP/events.php">Events</a></li>
    <li><a href="../ALL_PHP/budget.php">Budget Reports</a></li>
    <li><a href="../ALL_HTML/contact.html">Contact Us</a></li>
    <li><a href="../ALL_PHP/news.php" class="active">News & Announcements</a></li>
  </ul>
</nav>

<main class="news-main">

  <section class="news-hero">
    <h1>News & Announcements</h1>
    <p>Stay updated with the latest community news from SK CONNECT.</p>
  </section>

  <section class="news-section">
    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <article class="news-card">
          <h2><?= htmlspecialchars($row['title']) ?></h2>
          <p class="date">Posted on <?= date("F j, Y", strtotime($row['date_posted'])) ?></p>
          <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>

          <!-- Admin-only actions -->
          <?php if (isAdmin()): ?>
          <div class="actions">
            <a href="edit_news.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
            <a href="delete_news.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Delete this announcement?')">Delete</a>
          </div>
          <?php endif; ?>
        </article>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;">No announcements yet.</p>
    <?php endif; ?>
  </section>

  <!-- Admin-only: Add News Form -->
  <?php if (isAdmin()): ?>
  <section class="add-news">
    <h2>Add New Announcement</h2>
    <form action="add_news.php" method="POST">
      <input type="text" name="title" placeholder="Title" required>
      <textarea name="content" placeholder="Write your announcement..." required></textarea>
      <button type="submit" name="add">Add Announcement</button>
    </form>
  </section>
  <?php endif; ?>

  <div class="back-container">
    <a href="dashboard.php" class="back-btn">← Back</a>
  </div>
</main>

<footer>
  &copy; <?= date("Y") ?> SK CONNECT. All rights reserved.
</footer>

</body>
</html>
