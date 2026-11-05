<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

$_SESSION = [];

if (ini_get("session.use_cookies") && !headers_sent()) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

use app\core\utils\Url;

Url::redirect('login');
