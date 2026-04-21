<?php
// Redirect to login if not authenticated. Include after config.php.
if (empty($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: /login.php' . ($redirect ? '?next=' . $redirect : ''));
    exit;
}
