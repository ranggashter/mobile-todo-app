</div> <!-- app-container -->
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
</div> <!-- smartphone-content -->
<div class="smartphone-home-button"></div>
</div> <!-- smartphone -->
</div> <!-- smartphone-container -->
</body>
</html>
