<?php
declare(strict_types=1);

/**
 * ============================================================================
 * ZTFramework 2.0 - Teste de RootPath, Endpoints, ACL e Consulta Direta
 * ============================================================================
 */

require_once __DIR__ . '/../autoload.php';

use app\core\utils\Url;
use app\core\utils\Router;
use app\models\Usuario;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando RootPath, Endpoints dos Botões, ACL Estrita e Consultas Diretas ===\n";

// ----------------------------------------------------------------------------
// 1. Testes de Detecção Dinâmica de RootPath (Url.php)
// ----------------------------------------------------------------------------
$_SERVER['SCRIPT_NAME'] = '/ZTFramework/index.php';
assertCondition(Url::base() === '/ZTFramework/', '1.1 Detecção correta do basePath em subpasta (/ZTFramework/)');
assertCondition(Url::rootPath() === '/ZTFramework', '1.2 Detecção do rootPath sem barra (/ZTFramework)');
assertCondition(Url::to('login') === '/ZTFramework/login', '1.3 Geração de URL para login (/ZTFramework/login)');
assertCondition(Url::to('dashboard/admin') === '/ZTFramework/dashboard/admin', '1.4 Geração de URL para dashboard/admin');
assertCondition(Url::asset('css/style.css') === '/ZTFramework/public/css/style.css', '1.5 Geração de URL para assets');
assertCondition(url('logout') === '/ZTFramework/logout', '1.6 Função helper global url()');
assertCondition(rootPath() === '/ZTFramework', '1.7 Função helper global rootPath()');

// Simulação de aplicação na raiz do domínio (ex: http://meusite.com/)
$_SERVER['SCRIPT_NAME'] = '/index.php';
assertCondition(Url::base() === '/', '1.8 Base na raiz do domínio é /');
assertCondition(Url::rootPath() === '', '1.9 RootPath na raiz é string vazia');
assertCondition(Url::to('login') === '/login', '1.10 Geração de URL na raiz (/login)');
assertCondition(url('logout') === '/logout', '1.11 Helper url() na raiz (/logout)');

// Restaura SCRIPT_NAME para os próximos testes
$_SERVER['SCRIPT_NAME'] = '/ZTFramework/index.php';

// ----------------------------------------------------------------------------
// 2. Testes de Endpoints Públicos e Rota de Logout
// ----------------------------------------------------------------------------
$router = new Router();

// Teste 2.1: GET /login entrega página com formulário
ob_start();
$router->dispatch('/login', 'GET');
$outLogin = ob_get_clean();
assertCondition(str_contains($outLogin, 'Entrar no Sistema') && str_contains($outLogin, 'action="login"'), '2.1 GET /login entrega página com action="login" relativa');

// Teste 2.2: GET /logout destrói sessão e dispara redirect
$_SESSION['user'] = ['idUsuario' => 1, 'nome' => 'Admin'];
ob_start();
$router->dispatch('/logout', 'GET');
$outLogout = ob_get_clean();
assertCondition(empty($_SESSION), '2.2 GET /logout limpa a sessão com sucesso');

// ----------------------------------------------------------------------------
// 3. Testes das Rotas de Consulta Direta do Banco (JSON)
// ----------------------------------------------------------------------------
// Teste 3.1: GET /api/consulta-direta (SQL Declarativo no routes.json)
ob_start();
$router->dispatch('/api/consulta-direta', 'GET');
$rawConsulta1 = ob_get_clean();
$jsonConsulta1 = json_decode($rawConsulta1, true);
assertCondition(
    is_array($jsonConsulta1) && ($jsonConsulta1['status'] ?? false) === true && count($jsonConsulta1['data'] ?? []) > 0,
    '3.1 GET /api/consulta-direta executa SQL declarativo e retorna JSON do banco'
);

// Teste 3.2: GET /api/consulta-sql (Controller via PDO direto)
ob_start();
$router->dispatch('/api/consulta-sql', 'GET');
$rawConsulta2 = ob_get_clean();
$jsonConsulta2 = json_decode($rawConsulta2, true);
assertCondition(
    is_array($jsonConsulta2) && ($jsonConsulta2['status'] ?? false) === true && count($jsonConsulta2['data'] ?? []) > 0,
    '3.2 GET /api/consulta-sql executa consulta via PDO no Controller e retorna JSON'
);

// Teste 3.3: GET /api/consulta-dbquery (Controller via Fluent DBQuery com JOIN)
ob_start();
$router->dispatch('/api/consulta-dbquery', 'GET');
$rawConsulta3 = ob_get_clean();
$jsonConsulta3 = json_decode($rawConsulta3, true);
assertCondition(
    is_array($jsonConsulta3) && ($jsonConsulta3['status'] ?? false) === true && isset($jsonConsulta3['data'][0]['grupo']),
    '3.3 GET /api/consulta-dbquery executa DBQuery com JOIN e retorna JSON com grupos'
);

// ----------------------------------------------------------------------------
// 4. Testes de Acesso Estrito por Perfil (Somente o Login Equivalente Entra)
// ----------------------------------------------------------------------------
$usuarioModel = new Usuario();
$adminUser    = $usuarioModel->autenticar('admin@ztframework.local', 'admin123');
$gerenteUser  = $usuarioModel->autenticar('gerente@ztframework.local', 'gerente123');
$finUser      = $usuarioModel->autenticar('financeiro@ztframework.local', 'financeiro123');
$supUser      = $usuarioModel->autenticar('suporte@ztframework.local', 'suporte123');
$padraoUser   = $usuarioModel->autenticar('usuario@ztframework.local', 'usuario123');

// 4.1 Acessos Autorizados para cada perfil equivalente
$_SESSION['user'] = $adminUser;
ob_start();
$router->dispatch('/dashboard/admin', 'GET');
$outAdmin = ob_get_clean();
assertCondition(str_contains($outAdmin, 'ADMIN MASTER'), '4.1 Admin acessa /dashboard/admin com sucesso');

$_SESSION['user'] = $gerenteUser;
ob_start();
$router->dispatch('/dashboard/gerente', 'GET');
$outGer = ob_get_clean();
assertCondition(str_contains($outGer, 'GERÊNCIA'), '4.2 Gerente acessa /dashboard/gerente com sucesso');

$_SESSION['user'] = $finUser;
ob_start();
$router->dispatch('/dashboard/financeiro', 'GET');
$outFin = ob_get_clean();
assertCondition(str_contains($outFin, 'FINANCEIRO'), '4.3 Financeiro acessa /dashboard/financeiro com sucesso');

$_SESSION['user'] = $supUser;
ob_start();
$router->dispatch('/dashboard/suporte', 'GET');
$outSup = ob_get_clean();
assertCondition(str_contains($outSup, 'SUPORTE & HELPDESK'), '4.4 Suporte acessa /dashboard/suporte com sucesso');

$_SESSION['user'] = $padraoUser;
ob_start();
$router->dispatch('/dashboard/usuario', 'GET');
$outPad = ob_get_clean();
assertCondition(str_contains($outPad, 'PAINEL DO USUÁRIO'), '4.5 Usuário comum acessa /dashboard/usuario com sucesso');

// 4.2 Acessos Bloqueados (Perfis não equivalentes são barrados com 403)
$_SESSION['user'] = $padraoUser;
ob_start();
$router->dispatch('/dashboard/admin', 'GET');
$outBlock1 = ob_get_clean();
assertCondition(str_contains($outBlock1, '403') || str_contains($outBlock1, 'ERR_FORBIDDEN_GROUP'), '4.6 Usuário comum bloqueado com 403 ao tentar acessar /dashboard/admin');

$_SESSION['user'] = $gerenteUser;
ob_start();
$router->dispatch('/dashboard/financeiro', 'GET');
$outBlock2 = ob_get_clean();
assertCondition(str_contains($outBlock2, '403') || str_contains($outBlock2, 'ERR_FORBIDDEN_GROUP'), '4.7 Gerente bloqueado com 403 ao tentar acessar /dashboard/financeiro');

$_SESSION['user'] = $finUser;
ob_start();
$router->dispatch('/dashboard/suporte', 'GET');
$outBlock3 = ob_get_clean();
assertCondition(str_contains($outBlock3, '403') || str_contains($outBlock3, 'ERR_FORBIDDEN_GROUP'), '4.8 Financeiro bloqueado com 403 ao tentar acessar /dashboard/suporte');

$_SESSION['user'] = $supUser;
ob_start();
$router->dispatch('/dashboard/gerente', 'GET');
$outBlock4 = ob_get_clean();
assertCondition(str_contains($outBlock4, '403') || str_contains($outBlock4, 'ERR_FORBIDDEN_GROUP'), '4.9 Suporte bloqueado com 403 ao tentar acessar /dashboard/gerente');

// ----------------------------------------------------------------------------
// 5. Testes de Redirecionamento após Login com RootPath Dinâmico
// ----------------------------------------------------------------------------
$redirecionamentos = [
    'admin@ztframework.local'      => 'dashboard/admin',
    'gerente@ztframework.local'    => 'dashboard/gerente',
    'financeiro@ztframework.local' => 'dashboard/financeiro',
    'suporte@ztframework.local'    => 'dashboard/suporte',
    'usuario@ztframework.local'    => 'dashboard/usuario',
];

foreach ($redirecionamentos as $em => $expectedRedirect) {
    $auth = $usuarioModel->autenticar($em, str_replace('@ztframework.local', '123', $em));
    $_POST = ['email' => $em, 'senha' => str_replace('@ztframework.local', '123', $em)];
    ob_start();
    $router->dispatch('/login', 'POST');
    $rawRes = ob_get_clean();
    $jsonRes = json_decode($rawRes, true);
    
    assertCondition(
        isset($jsonRes['data']['redirect']) && $jsonRes['data']['redirect'] === $expectedRedirect,
        "5.x Login de {$em} redireciona para {$expectedRedirect}"
    );
}

echo "Todos os testes de RootPath, Endpoints, ACL e Consultas Diretas passaram com sucesso!\n\n";
