<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\utils\Router;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando Router.php & ACL ===\n";

$router = new Router();

// Teste 1: Rota pública de Home
ob_start();
$resHome = $router->dispatch('/', 'GET');
$outHome = ob_get_clean();
assertCondition(str_contains($outHome, 'Zait Tiny Framework') || str_contains($outHome, 'ZTFramework'), 'GET / (Home pública renderiza View)');

// Teste 2: Rota inexistente (404)
ob_start();
$res404 = $router->dispatch('/rota/que/nao/existe', 'GET');
$out404 = ob_get_clean();
$json404 = json_decode($out404, true);
assertCondition(isset($json404['code']) && $json404['code'] === 404, 'GET /rota/inexistente retorna 404');

// Teste 3: Método HTTP não permitido (405)
ob_start();
$res405 = $router->dispatch('/', 'DELETE');
$out405 = ob_get_clean();
$json405 = json_decode($out405, true);
assertCondition(isset($json405['code']) && $json405['code'] === 405, 'DELETE / retorna 405 Method Not Allowed');

// Teste 4: Rota protegida sem sessão (401)
$_SESSION = [];
ob_start();
$res401 = $router->dispatch('/dashboard', 'GET');
$out401 = ob_get_clean();
assertCondition(str_contains($out401, '401 - Autenticação Necessária'), 'GET /dashboard sem login exibe View 401');

// Teste 5: Rota com restrição de grupo (403)
$_SESSION['user'] = [
    'idUsuario'  => 5,
    'nome'       => 'João Sem Permissão',
    'email'      => 'joao@empresa.com',
    'grupos'     => ['estagiario'],
    'permissoes' => ['basico']
];
ob_start();
$res403 = $router->dispatch('/admin', 'GET');
$out403 = ob_get_clean();
assertCondition(str_contains($out403, '403 - Acesso Proibido'), 'GET /admin com perfil insuficiente exibe View 403');

// Teste 6: Rota com perfil autorizado de admin (200)
$_SESSION['user'] = [
    'idUsuario'  => 1,
    'nome'       => 'Administrador Master',
    'email'      => 'admin@ztframework.local',
    'grupos'     => ['admin'],
    'permissoes' => ['sistema.config']
];
ob_start();
$resAdmin = $router->dispatch('/admin', 'GET');
$outAdmin = ob_get_clean();
assertCondition(str_contains($outAdmin, 'Painel de Controle Administrativo'), 'GET /admin com perfil admin concede acesso 200');

// Teste 7: Entrega de arquivo estático da publicZone
ob_start();
$router->dispatch('/public/assets/css/style.css', 'GET');
$outCss = ob_get_clean();
assertCondition(str_contains($outCss, 'ZTFramework Stylesheet'), 'GET /public/assets/css/style.css entrega conteúdo estático');

// Teste 8: Bloqueio de Path Traversal na publicZone
ob_start();
$router->dispatch('/public/../../app/etc/config.json', 'GET');
$outHack = ob_get_clean();
$jsonHack = json_decode($outHack, true);
assertCondition(isset($jsonHack['code']) && $jsonHack['code'] === 404, 'Path Traversal /public/../../ é bloqueado com 404');

echo "Todos os testes de Router & ACL passaram com sucesso!\n\n";
