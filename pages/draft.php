<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db.php';

$user_id = current_user_id();
$team_id = $_GET['team_id'] ?? null;

// Handle missing team_id
if (!$team_id) {
    // Try to get user's first team
    $stmt = $pdo->prepare("
        SELECT tm.team_id, t.name as team_name 
        FROM team_members tm 
        JOIN teams t ON tm.team_id = t.id 
        WHERE tm.user_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $team = $stmt->fetch();
    
    if ($team) {
        $team_id = $team['team_id'];
    } else {
        echo '<div class="flash-message error">Tidak ada tim yang ditemukan. Silakan bergabung dengan tim terlebih dahulu.</div>';
        echo '<div class="auth-footer"><a href="' . $base_url . '/pages/teams.php">Kembali ke Teams</a></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// Verify user is member of the team
$stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ? AND user_id = ?");
$stmt->execute([$team_id, $user_id]);
if (!$stmt->fetchColumn()) {
    echo '<div class="flash-message error">Anda tidak memiliki akses ke tim ini.</div>';
    echo '<div class="auth-footer"><a href="' . $base_url . '/pages/teams.php">Kembali ke Teams</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Get team name
$stmt = $pdo->prepare("SELECT name FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team_name = $stmt->fetchColumn();

// === ACTIONS ===

// Update draft (edit title/description)
if (isset($_POST['update_draft'])) {
    $task_id = (int)$_POST['task_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    
    if (empty($title)) {
        $error_message = "Judul tidak boleh kosong";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=? WHERE id=? AND status='draft' AND team_id=?");
            $stmt->execute([$title, $desc, $task_id, $team_id]);
            $success_message = "Draft berhasil diperbarui";
        } catch (PDOException $e) {
            $error_message = "Gagal memperbarui draft";
        }
    }
}

// Publish draft
if (isset($_POST['publish_task'])) {
    $task_id = (int)$_POST['task_id'];
    try {
        $stmt = $pdo->prepare("UPDATE tasks SET status='published' WHERE id=? AND status='draft' AND team_id=?");
        $stmt->execute([$task_id, $team_id]);
        $success_message = "Draft berhasil dipublish";
    } catch (PDOException $e) {
        $error_message = "Gagal mempublish draft";
    }
}

// Delete draft
if (isset($_POST['delete_draft'])) {
    $task_id = (int)$_POST['task_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id=? AND status='draft' AND team_id=?");
        $stmt->execute([$task_id, $team_id]);
        $success_message = "Draft berhasil dihapus";
    } catch (PDOException $e) {
        $error_message = "Gagal menghapus draft";
    }
}

// === GET DRAFT DATA ===
$stmt = $pdo->prepare("
    SELECT t.*, u.name as creator_name 
    FROM tasks t 
    LEFT JOIN users u ON t.created_by = u.id 
    WHERE t.team_id=? AND t.status='draft' 
    ORDER BY t.created_at DESC
");
$stmt->execute([$team_id]);
$drafts = $stmt->fetchAll();
?>

<div class="screen-header">
    <h1 class="page-title">Draft Tugas</h1>
</div>

<?php if (isset($success_message)): ?>
    <div class="toast success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="toast error"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<div class="screen-content">
    <div class="content-scrollable">
        <?php if ($team_name): ?>
            <div class="section-title">Tim: <?= htmlspecialchars($team_name) ?></div>
        <?php endif; ?>

        <?php if ($drafts): ?>
            <div class="drafts-list">
                <?php foreach ($drafts as $draft): ?>
                    <div class="draft-card">
                        <form method="post" class="draft-form">
                            <input type="hidden" name="task_id" value="<?= $draft['id'] ?>">
                            
                            <div class="draft-content">
                                <div class="form-group">
                                    <label for="title_<?= $draft['id'] ?>">Judul Tugas</label>
                                    <input type="text" 
                                           id="title_<?= $draft['id'] ?>" 
                                           name="title" 
                                           class="form-input" 
                                           value="<?= htmlspecialchars($draft['title']) ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="desc_<?= $draft['id'] ?>">Deskripsi</label>
                                    <textarea id="desc_<?= $draft['id'] ?>" 
                                              name="description" 
                                              class="form-input" 
                                              rows="3"><?= htmlspecialchars($draft['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="draft-meta">
                                    <small class="text-muted">
                                        Dibuat: <?= date('d M Y H:i', strtotime($draft['created_at'])) ?>
                                        <?php if ($draft['creator_name']): ?>
                                            oleh <?= htmlspecialchars($draft['creator_name']) ?>
                                        <?php endif; ?>
                                        <?php if ($draft['priority']): ?>
                                            | Prioritas: <span class="priority-badge <?= $draft['priority'] ?>"><?= ucfirst($draft['priority']) ?></span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="draft-actions">
                                <button type="submit" name="update_draft" class="btn primary small">
                                    Simpan Perubahan
                                </button>
                                <button type="submit" name="publish_task" class="btn primary small">
                                    Publish
                                </button>
                                <button type="submit" 
                                        name="delete_draft" 
                                        class="btn danger small"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus draft ini?')">
                                    Hapus
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Tidak ada draft tugas yang tersedia</p>
                <p class="muted">Draft tugas yang Anda buat akan muncul di sini sebelum dipublish</p>
                <div style="margin-top: 20px;">
                    <a href="<?= $base_url ?>/pages/tasks.php?team_id=<?= $team_id ?>" class="btn primary">
                        Buat Tugas Baru
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Draft-specific styles */
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
    border-left: 4px solid var(--warning-orange);
    transition: all 0.2s ease;
}

.draft-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.draft-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
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

.draft-actions {
    display: flex;
    gap: var(--space-sm);
    flex-wrap: wrap;
    padding-top: var(--space-lg);
    border-top: 1px solid var(--border-light);
}

.draft-actions .btn {
    flex: 1;
    min-width: 120px;
}

.text-muted {
    color: var(--text-muted);
    font-size: var(--text-sm);
}

/* Priority badge styling */
.priority-badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-badge.high {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.priority-badge.medium {
    background-color: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}

.priority-badge.low {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

/* Mobile responsive adjustments */
@media (max-width: 480px) {
    .draft-actions {
        flex-direction: column;
    }
    
    .draft-actions .btn {
        width: 100%;
        flex: none;
    }
    
    .draft-card {
        padding: var(--space-lg);
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>