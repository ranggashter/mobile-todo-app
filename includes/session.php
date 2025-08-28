        <?php
        if (session_status() === PHP_SESSION_NONE) session_start();
        function require_login() {
          if (!isset($_SESSION['user'])) {
            header('Location: /login.php');
            exit;
          }
        }
        function current_user_id() { return $_SESSION['user']['id'] ?? null; }
        function flash($key, $msg = null) {
          if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return; }
          if (isset($_SESSION['flash'][$key])) { $m = $_SESSION['flash'][$key]; unset($_SESSION['flash'][$key]); return $m; }
          return null;
        } 
