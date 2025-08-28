<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

$email = strtolower(trim($_POST['email'] ?? ''));
$pass = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password'])) {
  header("Location: {$base_url}/login.php?err=Invalid+credentials");
  exit;
}

$_SESSION['user'] = [
  'id'    => (int)$user['id'],
  'name'  => $user['name'],
  'email' => $user['email']
];
header("Location: {$base_url}/index.php");
exit;
