<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\utils\Router;
use app\core\utils\Config;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando AJAX com jQuery & MIME Types ===\n";

$router = new Router();

// Teste 1: Entrega de arquivo estático JS com MIME type correto
ob_start();
$router->dispatch('/public/assets/js/jquery.min.js', 'GET');
$outJs = ob_get_clean();
assertCondition(str_contains($outJs, 'jQuery'), 'GET /public/assets/js/jquery.min.js entrega arquivo JS');

// Teste 2: Entrega de arquivo estático CSS com MIME type correto
ob_start();
$router->dispatch('/public/assets/css/style.css', 'GET');
$outCss = ob_get_clean();
assertCondition(str_contains($outCss, 'ZTFramework Stylesheet'), 'GET /public/assets/css/style.css entrega arquivo CSS');

// Teste 3: Requisição HTML tradicional sem AJAX (navegador)
unset($_SERVER['HTTP_X_REQUESTED_WITH']);
unset($_SERVER['HTTP_ACCEPT']);
$_SESSION = [];
ob_start();
$router->dispatch('/dashboard', 'GET');
$outHtml = ob_get_clean();
assertCondition(str_contains($outHtml, '401 - Autenticação Necessária'), 'Requisição direta no browser renderiza View HTML de erro 401');

// Teste 4: Requisição via AJAX (com header X-Requested-With do jQuery)
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SESSION = [];

ob_start();
$router->dispatch('/dashboard', 'GET');
$outAjax = ob_get_clean();
$jsonAjax = json_decode($outAjax, true);

assertCondition(
    is_array($jsonAjax) && isset($jsonAjax['code']) && $jsonAjax['code'] === 401 && $jsonAjax['error'] === 'ERR_UNAUTHORIZED',
    'Requisição AJAX em rota protegida responde SEMPRE JSON (401), sem quebrar com HTML'
);

// Teste 5: Requisição AJAX para rota inexistente (404)
ob_start();
$router->dispatch('/api/inexistente', 'GET');
$outAjax404 = ob_get_clean();
$jsonAjax404 = json_decode($outAjax404, true);

assertCondition(
    is_array($jsonAjax404) && isset($jsonAjax404['code']) && $jsonAjax404['code'] === 404,
    'Requisição AJAX 404 responde estritamente em JSON'
);

// Limpeza de variáveis de servidor
unset($_SERVER['HTTP_X_REQUESTED_WITH']);
unset($_SERVER['HTTP_ACCEPT']);

echo "Todos os testes de AJAX e MIME Types passaram com sucesso!\n\n";
