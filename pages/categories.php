<?php
require_once __DIR__ . '/../includes/session.php';
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/../config/db.php';

$user_id = current_user_id();

// tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")
            ->execute([$name]);
        header("Location: categories.php");
        exit;
    }
}

// hapus kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        header("Location: categories.php");
        exit;
    }
}

// ambil semua kategori
$categoriesStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Categories • Mobile Todo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
<style>
/* Categories page specific styles */
.categories-section {
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
  width: 100%;
  background: var(--bg-primary);
  padding: var(--space-xl);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
}

.form-label {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--space-sm);
}

.form-input {
  width: 100%;
  padding: 14px var(--space-lg);
  border: 2px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: var(--text-base);
  background-color: var(--bg-input);
  font-family: inherit;
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-blue);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
  background-color: var(--bg-primary);
}

.form-input::placeholder {
  color: var(--text-muted);
}

.btn.primary {
  background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
  color: white;
  padding: var(--space-lg) var(--space-xl);
  font-size: var(--text-base);
  font-weight: 600;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn.primary:hover {
  background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
}

.btn.primary:focus {
  outline: 2px solid var(--primary-blue);
  outline-offset: 2px;
}

.divider {
  border: 0;
  height: 1px;
  background: var(--border-light);
  margin: var(--space-xl) 0;
}

.categories-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  background: var(--bg-primary);
  padding: var(--space-xl);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
}

.categories-list .list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--bg-secondary);
  padding: var(--space-lg);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  transition: all 0.2s ease;
  margin-bottom: 0;
}

.categories-list .list-item:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  background: var(--bg-primary);
}

.categories-list .item-title {
  font-size: var(--text-base);
  font-weight: 500;
  color: var(--text-primary);
  margin: 0;
  flex: 1;
  min-width: 0;
  word-break: break-word;
}

.categories-list .btn.danger {
  background-color: var(--danger-red);
  color: white;
  border: none;
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-sm);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 36px;
  flex-shrink: 0;
}

.categories-list .btn.danger:hover {
  background-color: var(--danger-red-dark);
  transform: translateY(-1px);
}

.empty-categories {
  text-align: center;
  padding: var(--space-2xl);
  color: var(--text-muted);
  font-style: italic;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border: 2px dashed var(--border-light);
}

/* Success state for form */
.form-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: var(--space-md);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-lg);
  text-align: center;
  font-weight: 500;
}

/* Loading state for buttons */
.btn.is-loading {
  opacity: 0.8;
  cursor: not-allowed;
  pointer-events: none;
}

.btn.is-loading::after {
  content: '';
  width: 16px;
  height: 16px;
  margin-left: var(--space-sm);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Category counter */
.category-counter {
  background: var(--bg-primary);
  padding: var(--space-lg);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  text-align: center;
  margin-bottom: var(--space-xl);
}

.category-counter .count {
  font-size: var(--text-2xl);
  font-weight: 700;
  color: var(--primary-blue);
  display: block;
}

.category-counter .label {
  font-size: var(--text-sm);
  color: var(--text-secondary);
  margin-top: var(--space-sm);
}

/* Mobile responsive adjustments */
@media (max-width: 480px) {
  .form-container {
    padding: var(--space-lg);
  }
  
  .categories-list {
    padding: var(--space-lg);
  }
  
  .categories-list .list-item {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-md);
  }
  
  .categories-list .btn.danger {
    width: 100%;
    justify-content: center;
  }
  
  .form-input {
    padding: var(--space-md);
    font-size: var(--text-sm);
  }
  
  .btn.primary {
    padding: var(--space-md);
    font-size: var(--text-sm);
    min-height: 44px;
  }
}

@media (max-width: 360px) {
  .form-container,
  .categories-list {
    padding: var(--space-md);
  }
  
  .categories-list .list-item {
    padding: var(--space-md);
  }
  
  .category-counter {
    padding: var(--space-md);
  }
}

/* Enhanced visual feedback */
.delete-confirm {
  background: #fef2f2;
  border: 2px solid #fecaca;
  animation: shake 0.5s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Form validation styles */
.form-group.error .form-input {
  border-color: var(--danger-red);
  background-color: #fef2f2;
}

.form-error {
  color: var(--danger-red);
  font-size: var(--text-xs);
  margin-top: var(--space-xs);
}

/* Search functionality (future enhancement) */
.search-container {
  position: relative;
  margin-bottom: var(--space-lg);
}

.search-input {
  padding-right: 40px;
}

.search-icon {
  position: absolute;
  right: var(--space-lg);
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  pointer-events: none;
}
</style>
</head>
<body>
  <div class="smartphone-container">
    <div class="smartphone">
      <div class="smartphone-notch"></div>
      <div class="smartphone-content">
        <header class="screen-header">
          <h2 class="page-title">Kategori</h2>
          <a href="<?= $base_url ?>/actions/logout.php" class="btn danger small">Logout</a>
        </header>

        <div class="content-scrollable">
          <div class="categories-section">
            <!-- Category Counter -->
            <div class="category-counter">
              <span class="count"><?= count($categories) ?></span>
              <div class="label">Total Kategori</div>
            </div>

            <!-- Form Tambah Kategori -->
            <div class="form-container">
              <form method="post" id="categoryForm">
                <input type="hidden" name="create_category" value="1">
                
                <label for="category_name" class="form-label">Nama Kategori</label>
                <input 
                  type="text" 
                  id="category_name" 
                  name="name" 
                  required 
                  class="form-input" 
                  placeholder="Masukkan nama kategori..."
                  maxlength="50">
                
                <button type="submit" class="btn primary">
                  <span class="button-text">+ Tambah Kategori</span>
                </button>
              </form>
            </div>

            <!-- List Kategori -->
            <div class="categories-list">
              <h3 style="margin-bottom: var(--space-lg); color: var(--text-primary); font-size: var(--text-lg);">
                Daftar Kategori
              </h3>
              
              <?php if ($categories): ?>
                <?php foreach ($categories as $c): ?>
                  <div class="list-item">
                    <div class="item-title"><?= htmlspecialchars($c['name']) ?></div>
                    <form method="post" style="display: inline;">
                      <input type="hidden" name="delete_category" value="1">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button 
                        type="submit" 
                        class="btn danger small"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus kategori \'<?= htmlspecialchars($c['name']) ?>\'? Tindakan ini tidak dapat dibatalkan.')">
                        Hapus
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="empty-categories">
                  <p>Belum ada kategori</p>
                  <small>Tambahkan kategori pertama Anda menggunakan form di atas</small>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Bottom Navigation inside smartphone -->
        <nav class="bottom-nav">
          <a href="<?= $base_url ?>/index.php" class="nav-item" aria-label="Home"><span class="icon">🏠</span><span>Home</span></a>
          <a href="<?= $base_url ?>/pages/teams.php" class="nav-item" aria-label="Team"><span class="icon">👥</span><span>Team</span></a>
          <a href="<?= $base_url ?>/pages/tasks.php" class="nav-item" aria-label="Tasks"><span class="icon">✅</span><span>Tasks</span></a>
          <a href="<?= $base_url ?>/pages/draft.php" class="nav-item" aria-label="Draft"><span class="icon">📝</span><span>Draft</span></a>
          <a href="<?= $base_url ?>/pages/categories.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" aria-label="Categories"><span class="icon">📂</span><span>Categories</span></a>
          <a href="<?= $base_url ?>/pages/profile.php" class="nav-item" aria-label="Profile"><span class="icon">👤</span><span>Profile</span></a>
        </nav>
      </div>
      <div class="smartphone-home-button"></div>
    </div>
  </div>

<script src="<?= $base_url ?>/assets/js/app.js"></script>
<script>
// Enhanced form handling
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('categoryForm');
  const input = document.getElementById('category_name');
  const button = form.querySelector('.btn.primary');
  const buttonText = button.querySelector('.button-text');

  // Form submission with loading state
  form.addEventListener('submit', function(e) {
    const value = input.value.trim();
    
    if (!value) {
      e.preventDefault();
      input.classList.add('error');
      input.focus();
      return;
    }
    
    // Add loading state
    button.classList.add('is-loading');
    buttonText.textContent = 'Menambahkan...';
    button.disabled = true;
  });

  // Remove error state on input
  input.addEventListener('input', function() {
    this.classList.remove('error');
  });

  // Character counter (optional)
  input.addEventListener('input', function() {
    const maxLength = this.getAttribute('maxlength');
    const currentLength = this.value.length;
    
    if (currentLength >= maxLength - 10) {
      this.style.borderColor = 'var(--warning-orange)';
    } else {
      this.style.borderColor = '';
    }
  });

  // Auto-focus on input when page loads
  input.focus();
});

// Enhanced delete confirmation
function confirmDelete(categoryName) {
  return confirm(`Apakah Anda yakin ingin menghapus kategori "${categoryName}"?\n\nTindakan ini tidak dapat dibatalkan dan mungkin mempengaruhi tugas yang menggunakan kategori ini.`);
}
</script>
</body>
</html>