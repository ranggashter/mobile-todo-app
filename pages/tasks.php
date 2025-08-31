<?php
require_once __DIR__ . '/../includes/session.php';
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');
require_once __DIR__ . '/../config/db.php';

$user_id = current_user_id();

// ambil teams user
$teamsStmt = $pdo->prepare("SELECT t.id, t.name 
  FROM teams t 
  JOIN team_members tm ON tm.team_id=t.id 
  WHERE tm.user_id=? 
  ORDER BY t.name");
$teamsStmt->execute([$user_id]);
$teams = $teamsStmt->fetchAll();

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : (isset($teams[0]['id']) ? $teams[0]['id'] : 0);

// ambil members & categories
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

  $cats = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
  $cats->execute();
  $categories = $cats->fetchAll();
}

// CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['create_task'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $due = $_POST['due_date'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $t_team = (int)($_POST['team_id'] ?? 0);
    $status = $_POST['status'] ?? 'published';
    $assignees = $_POST['assignee_ids'] ?? [];

    if ($t_team && $title) {
      $pdo->prepare("INSERT INTO tasks 
                (team_id, category_id, title, description, priority, due_date, created_by, status, completed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)")
        ->execute([$t_team, $category_id ?: null, $title, $desc, $priority, $due ?: null, $user_id, $status]);

      $task_id = $pdo->lastInsertId();

      // assign ke semua anggota tim jika checkbox kosong
      $target_members = !empty($assignees) ? $assignees : array_column($members, 'id');
      foreach ($target_members as $uid) {
        $pdo->prepare("INSERT INTO task_completions (task_id, user_id, completed) VALUES (?, ?, 0)")
          ->execute([$task_id, $uid]);
      }

      header("Location: tasks.php?team_id=$t_team");
      exit;
    }
  }

  if (isset($_POST['update_task'])) {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $due = $_POST['due_date'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $assignees = $_POST['assignee_ids'] ?? [];

    $pdo->prepare("UPDATE tasks 
            SET title=?, description=?, priority=?, due_date=?, category_id=? 
            WHERE id=? AND team_id IN (SELECT team_id FROM team_members WHERE user_id=?)")
      ->execute([$title, $desc, $priority, $due ?: null, $category_id ?: null, $id, $user_id]);

    // reset completions & assign ke semua anggota tim jika checkbox kosong
    $pdo->prepare("DELETE FROM task_completions WHERE task_id=?")->execute([$id]);
    $target_members = !empty($assignees) ? $assignees : array_column($members, 'id');
    foreach ($target_members as $uid) {
      $pdo->prepare("INSERT INTO task_completions (task_id, user_id, completed) VALUES (?, ?, 0)")
        ->execute([$id, $uid]);
    }

    header("Location: tasks.php?team_id=" . (int)$_POST['team_id']);
    exit;
  }

  if (isset($_POST['delete_task'])) {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM task_completions WHERE task_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM tasks WHERE id=? AND team_id IN 
            (SELECT team_id FROM team_members WHERE user_id=?)")->execute([$id, $user_id]);
    header("Location: tasks.php?team_id=" . (int)$_POST['team_id']);
    exit;
  }

  if (isset($_POST['toggle_complete'])) {
    $id = (int)($_POST['id'] ?? 0);
    $target_uid = (int)($_POST['user_id'] ?? $user_id);

    // ambil status lama
    $stmt = $pdo->prepare("SELECT completed FROM task_completions WHERE task_id=? AND user_id=?");
    $stmt->execute([$id, $target_uid]);
    $row = $stmt->fetch();

    if ($row) {
      $newStatus = $row['completed'] ? 0 : 1;
      $pdo->prepare("UPDATE task_completions SET completed=? WHERE task_id=? AND user_id=?")
        ->execute([$newStatus, $id, $target_uid]);
    }

    // cek semua assignee
    $all = $pdo->prepare("SELECT COUNT(*) FROM task_completions WHERE task_id=?");
    $all->execute([$id]);
    $total = $all->fetchColumn();

    $done = $pdo->prepare("SELECT COUNT(*) FROM task_completions WHERE task_id=? AND completed=1");
    $done->execute([$id]);
    $completed = $done->fetchColumn();

    // update tasks.completed + completed_at
    if ($total > 0 && $completed == $total) {
      $pdo->prepare("UPDATE tasks SET completed=1, completed_at=NOW() WHERE id=?")->execute([$id]);
    } else {
      $pdo->prepare("UPDATE tasks SET completed=0, completed_at=NULL WHERE id=?")->execute([$id]);
    }

    header("Location: tasks.php?team_id=" . (int)$_POST['team_id']);
    exit;
  }
}

// ambil tasks
if ($team_id) {
  $tasks = $pdo->prepare("SELECT t.*, c.name AS category_name
        FROM tasks t
        LEFT JOIN categories c ON c.id=t.category_id
        WHERE t.team_id=? 
          AND t.status='published'
          AND (t.completed = 0 OR (t.completed_at IS NOT NULL AND t.completed_at >= NOW() - INTERVAL 1 MINUTE))
        ORDER BY t.completed ASC, t.priority='high' DESC, t.priority='medium' DESC, t.due_date IS NULL, t.due_date ASC");
  $tasks->execute([$team_id]);
  $taskRows = $tasks->fetchAll();
  foreach ($taskRows as &$row) {
    $stmt = $pdo->prepare("SELECT tc.*, u.name 
                               FROM task_completions tc 
                               JOIN users u ON u.id=tc.user_id
                               WHERE tc.task_id=?");
    $stmt->execute([$row['id']]);
    $row['checklist'] = $stmt->fetchAll();
  }
  unset($row);
}
?>



<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Tasks • Mobile Todo</title>
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
                      <label>Tugaskan ke (bisa pilih lebih dari satu)</label>
                      <div class="checkbox-group">
                        <?php foreach ($members as $m): ?>
                          <label class="checkbox-label">
                            <input type="checkbox" name="assignee_ids[]" value="<?= (int)$m['id'] ?>"
                              <?= isset($selected_ids) && in_array($m['id'], $selected_ids) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                          </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div class="form-group">
                      <button type="submit" name="status" value="draft" class="btn secondary w-full">Simpan dulu</button>
                      <button type="submit" name="status" value="published" class="btn primary w-full">Tambah & Publish</button>
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
                    <?php
                    $assignee_names = [];
                    if ($row['assignee_id']) {
                      $ids = explode(',', $row['assignee_id']);
                      foreach ($members as $m) {
                        if (in_array($m['id'], $ids)) $assignee_names[] = $m['name'];
                      }
                    }
                    ?>
                    <div class="list-item 
     <?= $row['priority'] ?> 
     <?= $row['completed'] ? 'completed-task' : '' ?>">
                      <div class="task-main">
                        <div class="item-info">
                          <div class="item-title"><?= htmlspecialchars($row['title']) ?></div>
                          <div class="item-sub">
                            <?= $row['category_name'] ? 'Category: ' . htmlspecialchars($row['category_name']) . ' • ' : '' ?>
                            Prioritas: <?= htmlspecialchars(ucfirst($row['priority'])) ?>
                            <?= $row['due_date'] ? ' • Due: ' . htmlspecialchars($row['due_date']) : '' ?>
                          </div>

                        </div>
                        <div class="checklist-users scrollable">
                          <?php foreach ($row['checklist'] as $cl): ?>
                            <?php if ($cl['user_id'] == $user_id): // hanya user sendiri bisa toggle 
                            ?>
                              <form method="post" class="checklist-form">
                                <input type="hidden" name="toggle_complete" value="1">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="team_id" value="<?= (int)$team_id ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$cl['user_id'] ?>">
                                <button class="check-btn <?= $cl['completed'] ? 'done' : '' ?>">
                                  <span class="check-icon"><?= $cl['completed'] ? '✔' : '◯' ?></span>
                                  <span class="check-name"><?= htmlspecialchars($cl['name']) ?></span>
                                </button>
                              </form>
                            <?php else: // user lain tampilkan sebagai readonly 
                            ?>
                              <div class="check-btn readonly <?= $cl['completed'] ? 'done' : '' ?>">
                                <span class="check-icon"><?= $cl['completed'] ? '✔' : '◯' ?></span>
                                <span class="check-name"><?= htmlspecialchars($cl['name']) ?></span>
                              </div>

                            <?php endif; ?>
                          <?php endforeach; ?>
                        </div>

                      </div>

                      <div class="task-actions">
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
                                  <option value="high" <?= $row['priority'] == 'high' ? 'selected' : '' ?>>High</option>
                                  <option value="medium" <?= $row['priority'] == 'medium' ? 'selected' : '' ?>>Medium</option>
                                  <option value="low" <?= $row['priority'] == 'low' ? 'selected' : '' ?>>Low</option>
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
                                    <option value="<?= (int)$c['id'] ?>" <?= $row['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Tugaskan ke (bisa pilih lebih dari satu)</label>
                                <div class="checkbox-group">
                                  <?php
                                  $selected_ids = $row['assignee_id'] ? explode(',', $row['assignee_id']) : [];
                                  foreach ($members as $m):
                                  ?>
                                    <label class="checkbox-label">
                                      <input type="checkbox" name="assignee_ids[]" value="<?= (int)$m['id'] ?>" <?= in_array($m['id'], $selected_ids) ? 'checked' : '' ?>>
                                      <?= htmlspecialchars($m['name']) ?>
                                    </label>
                                  <?php endforeach; ?>
                                </div>
                              </div>
                              <button class="btn primary w-full">Simpan</button>
                            </form>
                          </div>
                        </details>

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
            <?php endif; ?>
          <?php else: ?>
            <div class="empty-state">
              <p>Bergabunglah atau buat tim terlebih dahulu.</p>
            </div>
          <?php endif; ?>
        </div>

        <nav class="bottom-nav">
          <a href="<?= $base_url ?>/index.php" class="nav-item" aria-label="Home"><span class="icon">🏠</span><span>Home</span></a>
          <a href="<?= $base_url ?>/pages/teams.php" class="nav-item" aria-label="Team"><span class="icon">👥</span><span>Team</span></a>
          <a href="<?= $base_url ?>/pages/tasks.php" class="nav-item" aria-label="Tasks"><span class="icon">✅</span><span>Tasks</span></a>
          <a href="<?= $base_url ?>/pages/draft.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'draft.php' ? 'active' : '' ?>" aria-label="Draft"><span class="icon">📝</span><span>Draft</span></a>
          <a href="<?= $base_url ?>/pages/categories.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" aria-label="Categories"><span class="icon">📂</span><span>Categories</span></a>
          <a href="<?= $base_url ?>/pages/profile.php" class="nav-item" aria-label="Profile"><span class="icon">👤</span><span>Profile</span></a>
        </nav>
      </div>
      <div class="smartphone-home-button"></div>
    </div>
  </div>
  <script src="<?= $base_url ?>/assets/js/app.js"></script>
  <style>
    .checklist-users.scrollable {
      display: flex;
      overflow-x: auto;
      gap: 6px;
      padding-bottom: 4px;
      margin-top: 6px;
    }

    .checklist-form {
      flex: 0 0 auto;
    }

    .check-btn {
      display: flex;
      align-items: center;
      gap: 4px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background: #f7f7f7;
      padding: 4px 8px;
      font-size: 12px;
      cursor: pointer;
      white-space: nowrap;
    }

    .check-btn.done {
      background: #d4edda;
      color: #155724;
      border-color: #c3e6cb;
    }

    .check-icon {
      font-weight: bold;
    }

    .check-name {
      white-space: nowrap;
    }

    .completed-task {
      background-color: #e0e0e0;
      /* background lebih pucat */
      color: #666;
      /* teks lebih pudar */
      text-decoration: line-through;
      /* coret teks */
    }

    .completed-task .checklist-users button {
      opacity: 0.6;
      /* tombol checklist sedikit transparan */
    }

    .completed-task .item-title {
      font-style: italic;
    }

    .list-item {
      display: flex;
      flex-direction: column;
      background: #fff;
      padding: 12px;
      margin-bottom: 10px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s;
    }

    .list-item:hover {
      transform: translateY(-2px);
    }

    .task-main {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .item-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .item-title {
      font-weight: 600;
      font-size: 16px;
    }

    .item-sub {
      font-size: 12px;
      color: #555;
    }

    .checklist-users {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 6px;
    }

    .check-btn {
      border: 1px solid #ccc;
      border-radius: 5px;
      background: #f7f7f7;
      padding: 4px 6px;
      font-size: 12px;
      cursor: pointer;
    }

    .check-btn.done {
      background: #d4edda;
      color: #155724;
      border-color: #c3e6cb;
    }

    .task-actions {
      display: flex;
      justify-content: flex-end;
      gap: 6px;
      margin-top: 8px;
    }

    .completed-task {
      background-color: #f0f0f0;
      color: #777;
      text-decoration: line-through;
    }

    @media screen and (min-width: 600px) {
      .list-item {
        flex-direction: row;
        justify-content: space-between;
        align-items: flex-start;
      }

      .task-main {
        flex: 1;
      }

      .task-actions {
        flex-shrink: 0;
        align-self: center;
      }
    }
  </style>


  </styl>
</body>

</html>