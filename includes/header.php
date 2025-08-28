<?php
require_once __DIR__ . '/../includes/session.php';
require_login();
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Mobile Todo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
<script defer src="<?= $base_url ?>/assets/js/app.js"></script>
</head>
<body>
<div class="smartphone-container">
  <div class="smartphone">
    <div class="smartphone-notch"></div>
    <div class="smartphone-content">
      <?php if ($m = flash('success')): ?>
        <div class="toast success" id="toast-success"><?= htmlspecialchars($m) ?></div>
      <?php endif; ?>
      <?php if ($m = flash('error')): ?>
        <div class="toast error" id="toast-error"><?= htmlspecialchars($m) ?></div>
      <?php endif; ?>