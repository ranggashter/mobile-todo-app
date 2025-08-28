<?php
require_once __DIR__ . '/../includes/session.php';
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/../config/db.php';

$user_id = current_user_id();

// handle create team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_team'])) {
  $name = trim($_POST['team_name'] ?? '');
  if ($name) {
    $pdo->prepare("INSERT INTO teams (name, owner_id) VALUES (?, ?)")->execute([$name, $user_id]);
    $team_id = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, 'owner')")->execute([$team_id, $user_id]);
    // flash('success', 'Team created');
    header("Location: {$base_url}/pages/teams.php");
    exit;
  } else {
    // flash('error', 'Team name required');
  }
}

// handle add member (pakai user_id langsung)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
  $team_id = (int)($_POST['team_id'] ?? 0);
  $uid = (int)($_POST['user_id'] ?? 0);
  if ($team_id && $uid) {
    $pdo->prepare("INSERT IGNORE INTO team_members (team_id, user_id, role) VALUES (?, ?, 'member')")->execute([$team_id, $uid]);
    // flash('success', 'Member added');
    header("Location: {$base_url}/pages/teams.php");
    exit;
  } else {
    // flash('error', 'Invalid data');
  }
}

// list my teams
$stmt = $pdo->prepare("SELECT t.*, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id=t.id) as members 
  FROM teams t
  JOIN team_members tm ON tm.team_id=t.id 
  WHERE tm.user_id=? ORDER BY t.created_at DESC");
$stmt->execute([$user_id]);
$teams = $stmt->fetchAll();

// list all users
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Teams • Mobile Todo</title>
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
          <h2 class="page-title">Team</h2>
          <a href="<?= $base_url ?>/actions/logout.php" class="btn danger small">Logout</a>
        </header>

        <section class="teams-section">
          <div class="form-create-team card">
            <form method="post">
              <input type="hidden" name="create_team" value="1">
              <div class="form-group-inline">
                <input type="text" name="team_name" placeholder="Nama team baru" required class="form-input">
                <button class="btn primary">Buat Team</button>
              </div>
            </form>
          </div>

          <div class="card">
            <div class="content-scrollable">
              <section class="teams-list">
                <?php if (empty($teams)): ?>
                  <div class="empty-state">
                    <p>Anda belum memiliki tim.</p>
                    <p class="muted">Buat tim pertama Anda untuk memulai!</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($teams as $t): ?>
                    <div class="team-card">
                      <div class="team-main">
                        <div class="team-info">
                          <h3 class="team-name"><?= htmlspecialchars($t['name']) ?></h3>
                          <span class="team-members"><?= (int)$t['members'] ?> anggota</span>
                        </div>
                        <details class="team-details">
                          <summary class="details-summary">Tambah anggota</summary>
                          <div class="details-content">
                            <form method="post" class="add-member-form">
                              <input type="hidden" name="add_member" value="1">
                              <input type="hidden" name="team_id" value="<?= (int)$t['id'] ?>">
                              <div class="form-group">
                                <select name="user_id" required class="form-select">
                                  <option value="">Pilih untuk menambahkan</option>
                                  <?php foreach ($users as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>">
                                      <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <button class="btn primary small">Tambah anggota</button>
                            </form>
                          </div>
                        </details>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </section>
            </div>
          </div>
        </section>
        
        <!-- Bottom Navigation inside smartphone -->
        <nav class="bottom-nav">
          <a href="<?= $base_url ?>/index.php" class="nav-item" aria-label="Home">
            <span class="icon">🏠</span>
            <span>Home</span>
          </a>
          <a href="<?= $base_url ?>/pages/teams.php" class="nav-item active" aria-label="Team">
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