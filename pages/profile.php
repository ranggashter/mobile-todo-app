<?php
require_once __DIR__ . '/../includes/session.php';
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/../config/db.php';

$user_id = current_user_id();
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    if ($name && $email) {
      $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?")->execute([$name, $email, $user_id]);
      $_SESSION['user']['name'] = $name;
      $_SESSION['user']['email'] = $email;
      header("Location: {$base_url}/pages/profile.php");
      exit;
    }
  }
  if (isset($_POST['change_password'])) {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';
    if ($p1 && $p1 === $p2 && strlen($p1) >= 6) {
      $hash = password_hash($p1, PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user_id]);
      header("Location: {$base_url}/pages/profile.php");
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Profile • Mobile Todo</title>
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
          <h2 class="page-title">Profile</h2>
          <a href="<?= $base_url ?>/actions/logout.php" class="btn danger small">Logout</a>
        </header>

        <div class="content-scrollable">
          <section class="card">
            <h3 class="section-title">Update Profile</h3>
            <form method="post" class="form">
              <input type="hidden" name="update_profile" value="1">
              <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-input">
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-input">
              </div>
              <button class="btn primary w-full">Simpan Profile</button>
            </form>
          </section>

          <section class="card">
            <h3 class="section-title">Ganti Password</h3>
            <form method="post" class="form">
              <input type="hidden" name="change_password" value="1">
              <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password" minlength="6" required class="form-input">
              </div>
              <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password2" minlength="6" required class="form-input">
              </div>
              <button class="btn primary w-full">Update Password</button>
            </form>
          </section>

          <section class="card">
            <h3 class="section-title">Account Info</h3>
            <div class="list-item">
              <div class="grow">
                <div class="item-title">User ID</div>
                <div class="item-sub">#<?= (int)$user['id'] ?></div>
              </div>
            </div>
            <div class="list-item">
              <div class="grow">
                <div class="item-title">Account Status</div>
                <div class="item-sub">Active</div>
              </div>
            </div>
          </section>
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
          <a href="<?= $base_url ?>/pages/tasks.php" class="nav-item" aria-label="Tasks">
            <span class="icon">✅</span>
            <span>Tasks</span>
          </a>
          <a href="<?= $base_url ?>/pages/profile.php" class="nav-item active" aria-label="Profile">
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