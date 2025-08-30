<?php
require_once __DIR__ . '/../includes/session.php';
require_login();

$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$user_id = current_user_id();
$team_id = $_GET['team_id'] ?? null;

// ambil semua tim user
$stmt = $pdo->prepare("SELECT t.id, t.name
                       FROM teams t
                       JOIN team_members tm ON tm.team_id=t.id
                       WHERE tm.user_id=?");
$stmt->execute([$user_id]);
$teams = $stmt->fetchAll();

if (!$teams) {
    echo '<div class="flash-message error">Tidak ada tim yang ditemukan.</div>';
    exit;
}

if (!$team_id) {
    $team_id = $teams[0]['id'];
}

// pastikan user anggota tim
$stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id=? AND user_id=?");
$stmt->execute([$team_id, $user_id]);
if (!$stmt->fetchColumn()) {
    echo '<div class="flash-message error">Anda tidak memiliki akses ke tim ini.</div>';
    exit;
}

// ambil nama tim
$stmt = $pdo->prepare("SELECT name FROM teams WHERE id=?");
$stmt->execute([$team_id]);
$team_name = $stmt->fetchColumn();

// ambil tugas selesai
$stmt = $pdo->prepare("
    SELECT t.*, u.name AS creator_name, c.name AS category_name
    FROM tasks t
    LEFT JOIN users u ON t.created_by=u.id
    LEFT JOIN categories c ON t.category_id=c.id
    WHERE t.team_id=? AND t.completed=1
    ORDER BY t.completed_at DESC
");

$stmt->execute([$team_id]);
$completed_tasks = $stmt->fetchAll();
?>

<div class="screen-header">
    <h1 class="page-title">Tugas Selesai</h1>
</div>

<!-- Dropdown pilih tim -->
<form method="get" action="" class="team-selector" style="margin-bottom: 20px;">
    <label for="team_id">Pilih Tim:</label>
    <select name="team_id" id="team_id" onchange="this.form.submit()">
        <?php foreach($teams as $team): ?>
            <option value="<?= $team['id'] ?>" <?= $team['id']==$team_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($team['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="screen-content">
    <div class="content-scrollable">
        <?php if ($team_name): ?>
            <div class="section-title">Tim: <?= htmlspecialchars($team_name) ?></div>
        <?php endif; ?>

        <?php if ($completed_tasks): ?>
            <div class="drafts-list">
                <?php foreach ($completed_tasks as $task): ?>
                    <div class="draft-card completed">
                        <div class="draft-content">
                            <div class="form-group">
                                <strong><?= htmlspecialchars($task['title']) ?></strong>
                            </div>
                            <div class="form-group">
                                <?= htmlspecialchars($task['description'] ?? '') ?>
                            </div>
                            <div class="draft-meta">
                                <small class="text-muted">
                                    Selesai: <?= date('d M Y H:i', strtotime($task['completed_at'])) ?>
                                    <?php if ($task['creator_name']): ?> | Dibuat oleh <?= htmlspecialchars($task['creator_name']) ?><?php endif; ?>
                                    <?php if ($task['priority']): ?> | Prioritas: <span class="priority-badge <?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span><?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Tidak ada tugas selesai.</p>
                <p class="muted">Tugas yang selesai akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom navigation -->
<nav class="bottom-nav">
    <a href="<?= $base_url ?>/index.php" class="nav-item" aria-label="Home"><span class="icon">🏠</span><span>Home</span></a>
    <a href="<?= $base_url ?>/pages/teams.php" class="nav-item" aria-label="Team"><span class="icon">👥</span><span>Team</span></a>
    <a href="<?= $base_url ?>/pages/tasks.php" class="nav-item" aria-label="Tasks"><span class="icon">✅</span><span>Tasks</span></a>
    <a href="<?= $base_url ?>/pages/draft.php" class="nav-item" aria-label="Draft"><span class="icon">📝</span><span>Draft</span></a>
    <a href="<?= $base_url ?>/pages/categories.php" class="nav-item" aria-label="Categories"><span class="icon">📂</span><span>Categories</span></a>
    <a href="<?= $base_url ?>/pages/profile.php" class="nav-item" aria-label="Profile"><span class="icon">👤</span><span>Profile</span></a>
</nav>

<style>
/* reuse styling dari draft */
.drafts-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-xl);
}

.draft-card {
    background-color: var(--bg-primary);
    border-radius: var(--radius-lg);
    padding: var(--space-xl);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-light);
    border-left: 4px solid var(--primary-blue);
    transition: all 0.2s ease;
}

.draft-card.completed {
    border-left-color: var(--primary-blue-dark);
    opacity: 0.9;
}

.draft-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.draft-content {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.draft-meta {
    padding-top: var(--space-md);
    border-top: 1px solid var(--border-light);
}

.text-muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

/* Priority badge */
.priority-badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-badge.high { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.priority-badge.medium { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.priority-badge.low { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

/* responsive */
@media (max-width: 480px) {
    .draft-card { padding: var(--space-lg); }
}

</style>
