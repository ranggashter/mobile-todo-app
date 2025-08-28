<?php
$config = require __DIR__ . '/config.php';
$dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];
try {
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
} catch (PDOException $e) {
  http_response_code(500);
  die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}
