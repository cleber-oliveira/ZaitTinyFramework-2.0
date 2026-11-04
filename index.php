<?php
declare(strict_types=1);

/**
 * ZTFramework - Front Controller
 * 
 * Ponto único de entrada da aplicação. Carrega o autoloader e despacha
 * a requisição através do Router.
 */

require_once __DIR__ . '/autoload.php';

use app\core\utils\Router;

try {
    $router = new Router();
    $router->dispatch();
} catch (\Throwable $e) {
    http_response_code(500);
    echo "Erro Fatal no Framework: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
