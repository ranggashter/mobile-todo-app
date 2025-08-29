<?php
require_once __DIR__ . '/../includes/session.php';
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/../config/db.php';

$user_id = current_user_id();

// fetch teams user is in
$teamsStmt = $pdo->prepare("SELECT t.id, t.name 
  FROM teams t 
  JOIN team_members tm ON tm.team_id=t.id 
  WHERE tm.user_id=? 
  ORDER BY t.name");
$teamsStmt->execute([$user_id]);
$teams = $teamsStmt->fetchAll();

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : (isset($teams[0]['id']) ? $teams[0]['id'] : 0);

// CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['create_task'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $due = $_POST['due_date'] ?? null;
    $assignee = $_POST['assignee_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $t_team = (int)($_POST['team_id'] ?? 0);

    // ambil status dari tombol (draft/published)
    $status = $_POST['status'] ?? 'published';

    if ($t_team && $title) {
      $pdo->prepare("INSERT INTO tasks 
        (team_id, category_id, title, description, assignee_id, priority, due_date, created_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$t_team, $category_id ?: null, $title, $desc, $assignee ?: null, $priority, $due ?: null, $user_id, $status]);

      header("Location: tasks.php?team_id=$t_team"); exit;
    }
  }

  if (isset($_POST['update_task'])) {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $due = $_POST['due_date'] ?? null;
    $assignee = $_POST['assignee_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;

    $pdo->prepare("UPDATE tasks 
      SET title=?, description=?, priority=?, due_date=?, assignee_id=?, category_id=? 
      WHERE id=? AND team_id IN (SELECT team_id FROM team_members WHERE user_id=?)")
      ->execute([$title, $desc, $priority, $due ?: null, $assignee ?: null, $category_id ?: null, $id, $user_id]);

    header("Location: tasks.php?team_id=".(int)$_POST['team_id']); exit;
  }

  if (isset($_POST['delete_task'])) {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM tasks WHERE id=? AND team_id IN 
      (SELECT team_id FROM team_members WHERE user_id=?)")->execute([$id, $user_id]);
    header("Location: tasks.php?team_id=".(int)$_POST['team_id']); exit;
  }

  if (isset($_POST['toggle_complete'])) {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE tasks SET completed = 1 - completed 
      WHERE id=? AND team_id IN (SELECT team_id FROM team_members WHERE user_id=?)")
      ->execute([$id, $user_id]);
    header("Location: tasks.php?team_id=".(int)$_POST['team_id']); exit;
  }

  if (isset($_POST['create_category'])) {
    $name = trim($_POST['name'] ?? '');
    $t = (int)($_POST['team_id'] ?? 0);
    if ($name && $t) {
      $pdo->prepare("INSERT INTO categories (team_id, name) VALUES (?, ?)")->execute([$t, $name]);
      header("Location: tasks.php?team_id=$t"); exit;
    }
  }
}

// get members for selected team
$members = [];
$categories = [];
$taskRows = [];
if ($team_id) {
  $m = $pdo->prepare("SELECT u.id, u.name 
    FROM users u 
    JOIN team_members tm ON tm.user_id=u.id 
    WHERE tm.team_id=? ORDER BY u.name");
  $m->execute([$team_id]);
  $members = $m->fetchAll();

  $cats = $pdo->prepare("SELECT id, name FROM categories WHERE team_id=? ORDER BY name");
  $cats->execute([$team_id]);
  $categories = $cats->fetchAll();

  // hanya tampilkan yang published
  $tasks = $pdo->prepare("SELECT t.*, c.name AS category_name, u.name AS assignee_name 
    FROM tasks t
    LEFT JOIN categories c ON c.id=t.category_id
    LEFT JOIN users u ON u.id=t.assignee_id
    WHERE t.team_id=? AND t.status='published'
    ORDER BY t.completed ASC, t.priority='high' DESC, t.priority='medium' DESC, t.due_date IS NULL, t.due_date ASC");
  $tasks->execute([$team_id]);
  $taskRows = $tasks->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Tasks • Mobile Todo</title>
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
          <h2 class="page-title">Tasks</h2>
          <a href="<?= $base_url ?>/actions/logout.php" class="btn danger small">Logout</a>
        </header>

        <div class="content-scrollable">
          <?php if (!empty($teams)): ?>
            <form method="get" class="card">
              <div class="form-group">
                <label>Pilih team</label>
                <select name="team_id" onchange="this.form.submit()" class="form-select">
                  <?php foreach ($teams as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $team_id == $t['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($t['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>

            <?php if ($team_id): ?>
              <details class="card">
                <summary class="details-summary">Tugas baru</summary>
                <div class="details-content">
                  <form method="post" class="form">
                    <input type="hidden" name="create_task" value="1">
                    <input type="hidden" name="team_id" value="<?= (int)$team_id ?>">
                    <div class="form-group">
                      <label>Judul</label>
                      <input type="text" name="title" required class="form-input">
                    </div>
                    <div class="form-group">
                      <label>Description</label>
                      <textarea name="description" rows="3" class="form-input"></textarea>
                    </div>
                    <div class="form-group">
                      <label>Prioritas</label>
                      <select name="priority" class="form-select">
                        <option value="high">Tinggi</option>
                        <option value="medium" selected>Sedang</option>
                        <option value="low">Low</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Date line</label>
                      <input type="date" name="due_date" class="form-input">
                    </div>
                    <div class="form-group">
                      <label>Kategori</label>
                      <select name="category_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($categories as $c): ?>
                          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Tugas ke</label>
                      <select name="assignee_id" class="form-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($members as $m): ?>
                          <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <button type="submit" name="status" value="draft" class="btn secondary w-full">Simpan dulu</button>
                      <button type="submit" name="status" value="published" class="btn primary w-full">Tambah & Publish</button>
                    </div>
                  </form>
                </div>
              </details>

              <details class="card">
                <summary class="details-summary">Kategori baru</summary>
                <div class="details-content">
                  <form method="post" class="form-inline">
                    <input type="hidden" name="create_category" value="1">
                    <input type="hidden" name="team_id" value="<?= (int)$team_id ?>">
                    <div class="form-group-inline">
                      <input type="text" name="name" placeholder="Category name" required class="form-input">
                      <button class="btn primary">Buat</button>
                    </div>
                  </form>
                </div>
              </details>

              <section class="list">
                <?php if (empty($taskRows)): ?>
                  <div class="empty-state">
                    <p>Belum ada tugas di tim ini</p>
                    <p class="muted">Buat tugas pertama Anda untuk memulai!</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($taskRows as $row): ?>
                    <div class="list-item <?= $row['priority'] ?> <?= (!$row['completed'] && $row['due_date'] && $row['due_date'] <= date('Y-m-d', strtotime('+2 days'))) ? 'due' : '' ?>">
                      
                      <!-- Toggle Complete -->
                      <div class="checkbox">
                        <form method="post">
                          <input type="hidden" name="toggle_complete" value="1">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                          <input type="hidden" name="team_id" value="<?= (int)$team_id ?>">
                          <button class="check-btn" title="Toggle complete"><?= $row['completed'] ? '✓' : '○' ?></button>
                        </form>
                      </div>
                      
                      <!-- Info -->
                      <div class="grow">
                        <div class="item-title"><?= htmlspecialchars($row['title']) ?></div>
                        <div class="item-sub">
                          <?= $row['category_name'] ? 'Category: ' . htmlspecialchars($row['category_name']) . ' • ' : '' ?>
                          <?= $row['assignee_name'] ? 'Assignee: ' . htmlspecialchars($row['assignee_name']) . ' • ' : '' ?>
                          Prioritas: <?= htmlspecialchars(ucfirst($row['priority'])) ?>
                          <?= $row['due_date'] ? ' • Due: ' . htmlspecialchars($row['due_date']) : '' ?>
                        </div>
                      </div>
                      
                      <!-- Actions -->
                      <div class="task-actions">
                        <!-- Edit button -->
                        <details class="task-details">
                          <summary class="details-summary small">Edit</summary>
                          <div class="details-content">
                            <form method="post" class="form">
                              <input type="hidden" name="update_task" value="1">
                              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                              <input type="hidden" name="team_id" value="<?= (int)$team_id ?>">
                              <div class="form-group">
                                <label>Judul</label>
                                <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" required class="form-input">
                              </div>
                              <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="description" rows="2" class="form-input"><?= htmlspecialchars($row['description']) ?></textarea>
                              </div>
                              <div class="form-group">
                                <label>Prioritas</label>
                                <select name="priority" class="form-select">
                                  <option value="high" <?= $row['priority']=='high'?'selected':'' ?>>High</option>
                                  <option value="medium" <?= $row['priority']=='medium'?'selected':'' ?>>Medium</option>
                                  <option value="low" <?= $row['priority']=='low'?'selected':'' ?>>Low</option>
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Date line</label>
                                <input type="date" name="due_date" value="<?= htmlspecialchars($row['due_date']) ?>" class="form-input">
                              </div>
                              <div class="form-group">
                                <label>Kategori</label>
                                <select name="category_id" class="form-select">
                                  <option value="">None</option>
                                  <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= $row['category_id']==$c['id']?'selected':'' ?>>
                                      <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Tugaskan ke</label>
                                <select name="assignee_id" class="form-select">
                                  <option value="">Unassigned</option>
                                  <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>" <?= $row['assignee_id']==$m['id']?'selected':'' ?>>
                                      <?= htmlspecialchars($m['name']) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <button class="btn primary w-full">Simpan</button>
                            </form>
                          </div>
                        </details>

                        <!-- Delete button -->
                        <form method="post" onsubmit="return confirm('Delete this task?');">
                          <input type="hidden" name="delete_task" value="1">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                          <input type="hidden" name="team_id" value="<?= (int)$team_id ?>">
                          <button class="btn danger small">Hapus</button>
                        </form>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </section>
            <?php else: ?>
              <div class="empty-state">
                <p>Pilih tim untuk melihat tugas.</p>
                <p class="muted">Pilih tim dari dropdown di atas.</p>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="empty-state">
              <p>Bergabunglah atau buat tim terlebih dahulu.</p>
              <p class="muted">Pergi ke halaman Tim untuk memulai!</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Bottom Navigation inside smartphone -->
        <nav class="bottom-nav">
          <a href="<?= $base_url ?>/index.php" class="nav-item" aria-label="Home">
            <span class="icon">🏠</span>
            <span>Home</span>
          </a>
          <a href="<?= $base_url ?>/pages/teams.php" class="nav-item" aria-label="Team">
            <span class="icon">👥</span>
            <span>Team</span>
          </a>
          <a href="<?= $base_url ?>/pages/tasks.php" class="nav-item active" aria-label="Tasks">
            <span class="icon">✅</span>
            <span>Tasks</span>
          </a>
          <a href="<?= $base_url ?>/pages/profile.php" class="nav-item" aria-label="Profile">
            <span class="icon">👤</span>
            <span>Profile</span>
          </a>
                    <a href="<?= $base_url ?>/pages/draft.php" 
     class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'draft.php' ? 'active' : '' ?>" 
     aria-label="Draft">
    <span class="icon">📝</span>
    <span>Draft</span>
  </a>
        </nav>
      </div>
      <div class="smartphone-home-button"></div>
    </div>
  </div>
<script src="<?= $base_url ?>/assets/js/app.js"></script>
</body>
</html>
