<?php
require_once __DIR__ . '/../includes/session.php';
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

// kosongkan data session
$_SESSION = [];
session_unset();
session_destroy();

// redirect ke login
header("Location: {$base_url}/login.php");
exit;
