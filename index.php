<?php
require_once __DIR__ . '/includes/session.php';
$config = require __DIR__ . '/config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/config/db.php';

$user_id = current_user_id();

// Ambil tim user
$myTeams = $pdo->prepare("SELECT t.* FROM teams t JOIN team_members tm ON tm.team_id=t.id WHERE tm.user_id=? ORDER BY t.created_at DESC");
$myTeams->execute([$user_id]);
$teams = $myTeams->fetchAll();

// Hitung task belum selesai
$taskCount = $pdo->prepare("SELECT COUNT(*) c FROM tasks WHERE team_id IN (SELECT team_id FROM team_members WHERE user_id=?) AND completed=0");
$taskCount->execute([$user_id]);
$openTasks = $taskCount->fetchColumn();

// Ambil task yang segera jatuh tempo
$dueSoon = $pdo->prepare("SELECT id, title, due_date, priority 
                          FROM tasks 
                          WHERE completed=0 
                            AND due_date IS NOT NULL 
                            AND due_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY) 
                            AND team_id IN (SELECT team_id FROM team_members WHERE user_id=?) 
                          ORDER BY due_date ASC LIMIT 5");
$dueSoon->execute([$user_id]);
$due = $dueSoon->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Home • Mobile Todo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
</head>
<body>
  <div class="smartphone-container">
    <div class="smartphone">
      <div class="smartphone-notch"></div>
      <div class="smartphone-content">
        <header class="screen-header">
          <h2 class="page-title">Home</h2>
          <a href="<?= $base_url ?>/actions/logout.php" class="btn danger small">Logout</a>
        </header>

        <section class="cards">
          <div class="card">
            <div class="card-title">📋 Tugas</div>
            <div class="card-value"><?= (int)$openTasks ?></div>
          </div>
          <div class="card">
            <div class="card-title">👥 Team </div>
            <div class="card-value"><?= count($teams) ?></div>
          </div>
        </section>

        <section class="list">
          <h3 class="section-title">⏰ akan jatuh tempo</h3>
          <?php if (!$due): ?>
            <p class="muted">✅ tidak ada yang jatuh tempo 2 hari kedepan</p>
          <?php else: foreach ($due as $t): ?>
            <div class="list-item <?= $t['priority'] ?> due">
              <div class="item-title"><?= htmlspecialchars($t['title']) ?></div>
              <div class="item-sub">
                📅 <?= htmlspecialchars($t['due_date']) ?> • 
                ⚡ <?= htmlspecialchars(ucfirst($t['priority'])) ?>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </section>
        
        <!-- Bottom Navigation inside smartphone -->
        <nav class="bottom-nav">
          <a href="<?= $base_url ?>/index.php" class="nav-item active" aria-label="Home">
            <span class="icon">🏠</span>
            <span>Home</span>
          </a>
          <a href="<?= $base_url ?>/pages/teams.php" class="nav-item" aria-label="Team">
            <span class="icon">👥</span>
            <span>Team</span>
          </a>
          <a href="<?= $base_url ?>/pages/tasks.php" class="nav-item" aria-label="Tasks">
            <span class="icon">✅</span>
            <span>Tasks</span>
          </a>
          <a href="<?= $base_url ?>/pages/profile.php" class="nav-item" aria-label="Profile">
            <span class="icon">👤</span>
            <span>Profile</span>
          </a>
        </nav>
      </div>
      <div class="smartphone-home-button"></div>
    </div>
  </div>
<script src="<?= $base_url ?>/assets/js/app.js"></script>
</body>
</html>