<?php
require_once __DIR__ . '/includes/session.php';
$config = require __DIR__ . '/config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

if (isset($_SESSION['user'])) { 
    header("Location: {$base_url}/index.php"); 
    exit; 
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Register • Mobile Todo</title>
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
        <div class="app-header">
          <h1>Create Account 🚀</h1>
          <p>Get started with your free account</p>
        </div>

        <?php if (!empty($_GET['err'])): ?>
          <div class="toast error"><?= htmlspecialchars($_GET['err']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $base_url ?>/actions/register.php" class="auth-form">
          <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" placeholder="Enter your name" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required minlength="6">
          </div>
          <button class="btn primary w-full">Register</button>
        </form>

        <p class="auth-footer">
          Already have an account? <a href="<?= $base_url ?>/login.php">Login</a>
        </p>
      </div>
      <div class="smartphone-home-button"></div>
    </div>
  </div>

<script src="<?= $base_url ?>/assets/js/app.js"></script>
</body>
</html>