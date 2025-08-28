<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$pass = $_POST['password'] ?? '';

if (!$name || !$email || strlen($pass) < 6) {
  header("Location: {$base_url}/register.php?err=Invalid+input");
  exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) { 
  header("Location: {$base_url}/register.php?err=Email+already+used");
  exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hash]);

header("Location: {$base_url}/login.php?registered=1");
exit;
