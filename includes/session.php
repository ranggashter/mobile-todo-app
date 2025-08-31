<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// load config untuk ambil $base_url
$config = require __DIR__ . '/../config/config.php';
$base_url = rtrim($config['app']['base_url'], '/');

/**
 * Pastikan user sudah login
 */
function require_login() {
    global $base_url;
    if (!isset($_SESSION['user'])) {
        header("Location: {$base_url}/login.php");
        exit;
    }
}

/**
 * Ambil ID user yang sedang login
 */
function current_user_id() {
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Flash message (sekali tampil, lalu hilang)
 */
function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return;
    }
    if (isset($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}
