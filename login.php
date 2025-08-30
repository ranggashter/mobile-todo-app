<?php
$config = require __DIR__ . '/config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

require_once __DIR__ . '/includes/session.php';
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
<title>Login • Mobile Todo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
<script src="<?= $base_url ?>/assets/js/app.js" defer></script>
<style>
/* Auth page specific responsive styles */
@media (max-width: 768px) {
  html, body {
    height: 100vh;
    height: -webkit-fill-available;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
  }
  
  body {
    background: var(--bg-secondary) !important;
    padding: 0 !important;
    display: block !important;
    align-items: stretch !important;
    min-height: 100vh !important;
    min-height: -webkit-fill-available !important;
  }
  
  .smartphone-container {
    width: 100% !important;
    height: 100vh !important;
    height: -webkit-fill-available !important;
    display: block !important;
  }
  
  .smartphone {
    all: unset !important;
    display: block !important;
    width: 100% !important;
    height: 100vh !important;
    height: -webkit-fill-available !important;
    background: transparent !important;
  }
  
  .smartphone-notch {
    display: none !important;
  }
  
  .smartphone-home-button {
    display: none !important;
  }
  
  .smartphone-content {
    all: unset !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    height: 100vh !important;
    height: -webkit-fill-available !important;
    min-height: 100vh !important;
    min-height: -webkit-fill-available !important;
    background: var(--bg-secondary) !important;
    padding: var(--space-lg) !important;
    padding-top: calc(var(--space-lg) + env(safe-area-inset-top, 0)) !important;
    padding-bottom: calc(var(--space-lg) + env(safe-area-inset-bottom, 0)) !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    justify-content: center !important;
  }
}

/* Auth form container */
.auth-form {
  background: var(--bg-primary);
  padding: var(--space-xl);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border-light);
  width: 100%;
  max-width: 400px;
  margin: 0 auto var(--space-xl);
}

.app-header {
  text-align: center;
  margin-bottom: var(--space-2xl);
  padding: var(--space-xl) 0;
}

.app-header h1 {
  font-size: var(--text-3xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--space-sm);
  line-height: 1.2;
}

.app-header p {
  color: var(--text-secondary);
  font-size: var(--text-base);
  line-height: 1.5;
}

.auth-footer {
  text-align: center;
  color: var(--text-secondary);
  padding: var(--space-lg);
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light);
  font-size: var(--text-sm);
  margin-top: var(--space-xl);
}

.auth-footer a { 
  color: var(--primary-blue); 
  text-decoration: none; 
  font-weight: 600; 
}

.auth-footer a:hover { 
  text-decoration: underline; 
}

/* Mobile adjustments for auth pages */
@media (max-width: 480px) {
  .smartphone-content {
    padding: var(--space-md) !important;
    padding-top: calc(var(--space-md) + env(safe-area-inset-top, 0)) !important;
    padding-bottom: calc(var(--space-md) + env(safe-area-inset-bottom, 0)) !important;
  }
  
  .auth-form {
    padding: var(--space-lg);
    margin-bottom: var(--space-lg);
  }
  
  .app-header {
    padding: var(--space-lg) 0;
    margin-bottom: var(--space-xl);
  }
  
  .app-header h1 {
    font-size: var(--text-2xl);
  }
  
  .app-header p {
    font-size: var(--text-sm);
  }
  
  .auth-footer {
    padding: var(--space-md);
    margin-top: var(--space-lg);
  }
}

/* Very small devices */
@media (max-width: 360px) {
  .smartphone-content {
    padding: var(--space-sm) !important;
    padding-top: calc(var(--space-sm) + env(safe-area-inset-top, 0)) !important;
    padding-bottom: calc(var(--space-sm) + env(safe-area-inset-bottom, 0)) !important;
  }
  
  .auth-form {
    padding: var(--space-md);
  }
  
  .app-header {
    padding: var(--space-md) 0;
  }
}

/* Toast/flash messages for auth pages */
.toast {
  width: 100%;
  max-width: 400px;
  margin: 0 auto var(--space-xl);
}

/* Ensure proper form styling */
.form-group {
  margin-bottom: var(--space-lg);
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--space-sm);
  color: var(--text-primary);
  font-size: var(--text-sm);
}

.form-group input {
  width: 100%;
  padding: 14px var(--space-lg);
  border: 2px solid var(--border-default);
  border-radius: var(--radius-md);
  font-size: var(--text-base);
  background-color: var(--bg-input);
  font-family: inherit;
  transition: all 0.2s ease;
}

.form-group input:focus {
  outline: none;
  border-color: var(--primary-blue);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
  background-color: var(--bg-primary);
}

.form-group input::placeholder {
  color: var(--text-muted);
}

/* Button styling */
.btn {
  padding: var(--space-lg) var(--space-xl);
  border: none;
  border-radius: var(--radius-md);
  font-size: var(--text-base);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-family: inherit;
  min-height: 48px;
}

.btn.primary {
  background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
  color: white;
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

.btn.w-full {
  width: 100%;
}

/* Loading state */
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

/* iOS Safari specific fixes */
@supports (-webkit-touch-callout: none) {
  @media (max-width: 768px) {
    html, body {
      height: -webkit-fill-available !important;
    }
    
    .smartphone-content {
      height: -webkit-fill-available !important;
      min-height: -webkit-fill-available !important;
    }
  }
}

/* Landscape mode adjustments */
@media (max-width: 768px) and (orientation: landscape) {
  .smartphone-content {
    padding: var(--space-md) var(--space-lg) !important;
    justify-content: flex-start !important;
  }
  
  .app-header {
    padding: var(--space-md) 0;
    margin-bottom: var(--space-lg);
  }
  
  .app-header h1 {
    font-size: var(--text-xl);
  }
  
  .auth-form {
    margin-bottom: var(--space-lg);
  }
}
</style>
</head>
<body>
  <div class="smartphone-container">
    <div class="smartphone">
      <div class="smartphone-notch"></div>
      <div class="smartphone-content">
        <div class="app-header">
          <h1>Welcome Back 👋</h1>
          <p>Sign in to continue to your account</p>
        </div>

        <?php if (!empty($_GET['registered'])): ?>
          <div class="toast success">Registration successful. Please login.</div>
        <?php endif; ?>
        <?php if (!empty($_GET['err'])): ?>
          <div class="toast error"><?= htmlspecialchars($_GET['err']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $base_url ?>/actions/login.php" class="auth-form">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
          </div>
          <button class="btn primary w-full">Login</button>
        </form>

        <p class="auth-footer">No account? <a href="<?= $base_url ?>/register.php">Register</a></p>
      </div>
      <div class="smartphone-home-button"></div>
    </div>
  </div>
</body>
</html>