<?php
declare(strict_types=1);

/**
 * ZTFramework 2.0 - Suíte de Testes de Segurança (Defensive Security & Hardening)
 * 
 * Cobre:
 * 1. Broken Access Control (OWASP A01): Escalação vertical, horizontal (ACL de arquivos), proteção de e-mail.
 * 2. Cryptographic Failures & Senhas (OWASP A02): Mitigação do limite de 72 bytes do BCrypt, proteção por Pepper.
 * 3. Injection Defense (OWASP A03): Proteção contra SQL Injection no login e no DBQuery, bloqueio de UPDATE/DELETE sem WHERE.
 * 4. Path Traversal & Upload Security (OWASP A01/A08): Bloqueio de escape de diretórios, validação estrita de Base64 e headers defensivos.
 * 5. XSS Defense & Output Sanitization (OWASP A03): Sanitização de strings e neutralização de tags maliciosas.
 */

require_once __DIR__ . '/../autoload.php';

use app\core\utils\Router;
use app\core\utils\PasswordSecurity;
use app\core\utils\Sanitize;
use app\core\utils\Config;
use app\core\database\DBConnection;
use app\core\database\DBQuery;
use app\models\Usuario;
use app\controllers\FileController;
use app\controllers\PerfilController;

function assertSecurity(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=====================================================\n";
echo "   ZTFramework 2.0 - SUÍTE DE TESTES DE SEGURANÇA    \n";
echo "=====================================================\n\n";

$router = new Router();
$db = DBConnection::getConnection();

// =============================================================================
// SEÇÃO 1: BROKEN ACCESS CONTROL & PRIVILEGE ESCALATION (OWASP A01)
// =============================================================================
echo "--- 1. Testes de Controle de Acesso e Escalação de Privilégios ---\n";

// 1.1 Rota Administrativa sem Sessão (Vertical Escalation Block)
$_SESSION = [];
ob_start();
$router->dispatch('/admin', 'GET');
$out = ob_get_clean();
assertSecurity(str_contains($out, '401 - Autenticação Necessária'), '1.1 Acesso deslogado a /admin é bloqueado com 401');

// 1.2 Endpoint API Admin sem Sessão via AJAX (deve retornar JSON 401)
$_SESSION = [];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
ob_start();
$router->dispatch('/api/admin/usuarios', 'GET');
$outAjax = ob_get_clean();
unset($_SERVER['HTTP_X_REQUESTED_WITH']);
$jsonAjax = json_decode($outAjax, true);
assertSecurity(
    isset($jsonAjax['code']) && $jsonAjax['code'] === 401 && ($jsonAjax['status'] ?? true) === false,
    '1.2 Chamada AJAX deslogada em /api/admin/usuarios retorna JSON 401 sem vazar dados'
);

// 1.3 Usuário padrão (nível usuario) tentando acessar /admin (Vertical Escalation Block)
$_SESSION['user'] = [
    'idUsuario'  => 3,
    'nome'       => 'Usuário Padrão',
    'email'      => 'usuario@ztframework.local',
    'grupos'     => ['usuario'],
    'permissoes' => ['arquivos.upload']
];
ob_start();
$router->dispatch('/admin', 'GET');
$out403 = ob_get_clean();
assertSecurity(str_contains($out403, '403 - Acesso Proibido'), '1.3 Usuário comum tentando acessar /admin é bloqueado com 403');

// 1.4 Usuário padrão tentando invocar API de alterar status de usuários (Vertical Escalation Block)
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
ob_start();
$router->dispatch('/api/admin/usuario/status', 'POST');
$outStatus = ob_get_clean();
unset($_SERVER['HTTP_X_REQUESTED_WITH']);
$jsonStatus = json_decode($outStatus, true);
assertSecurity(
    isset($jsonStatus['code']) && $jsonStatus['code'] === 403 && $jsonStatus['error'] === 'ERR_FORBIDDEN_GROUP',
    '1.4 Usuário comum invocando /api/admin/usuario/status é rejeitado com 403 ERR_FORBIDDEN_GROUP'
);

// 1.5 Imutabilidade de E-mail: Usuário tenta alterar e-mail via endpoint de perfil
$idUsuarioTeste = 3;
$emailOriginal = (string)$db->query("SELECT email FROM usuarios WHERE idUsuario = {$idUsuarioTeste}")->fetchColumn();

$_SESSION['user'] = [
    'idUsuario' => $idUsuarioTeste,
    'nome'      => 'Usuário Teste Perfil',
    'email'     => $emailOriginal,
    'grupos'    => ['usuario']
];
$_POST = [
    'nome'  => 'Nome Atualizado com Sucesso',
    'email' => 'hacker_tenta_mudar_email@invasor.com' // Tentativa de injeção de troca de e-mail
];
ob_start();
$perfilController = new PerfilController();
$perfilController->atualizar();
ob_get_clean();

$emailAposAtualizacao = (string)$db->query("SELECT email FROM usuarios WHERE idUsuario = {$idUsuarioTeste}")->fetchColumn();
assertSecurity(
    $emailOriginal === $emailAposAtualizacao && $emailAposAtualizacao !== 'hacker_tenta_mudar_email@invasor.com',
    '1.5 E-mail é estritamente imutável (tentativa de adulteração em /api/perfil/atualizar é ignorada)'
);

// 1.6 Isolamento Horizontal: Dono A vs Usuário B em arquivo Privado (File ACL)
$hashPrivado = 'sec_acl_test_' . bin2hex(random_bytes(8));
$conteudoPrivado = base64_encode("Segredo Confidencial do Usuário 2");
$db->exec("
    INSERT INTO arquivosUpload 
    (hash, nomeOriginal, mimeType, tamanhoBytes, idDono, publico, gruposPermitidos, usuariosPermitidos, caminhoFisico)
    VALUES ('{$hashPrivado}', 'confidencial.txt', 'text/plain', 33, 2, 0, NULL, NULL, 'arquivo_nao_existente.base64')
");

// Usuário 3 (não é dono nem admin) tenta acessar
$_SESSION['user'] = [
    'idUsuario' => 3,
    'nome'      => 'Usuário Comum',
    'grupos'    => ['usuario']
];
$_GET['hash'] = $hashPrivado;
ob_start();
$fileController = new FileController();
$fileController->download();
$outAclNegado = ob_get_clean();
$jsonAclNegado = json_decode($outAclNegado, true);
assertSecurity(
    isset($jsonAclNegado['code']) && $jsonAclNegado['code'] === 403,
    '1.6 Isolamento Horizontal: Usuário 3 é bloqueado com 403 ao tentar acessar arquivo privado do Usuário 2'
);

// Limpa registro de teste
$db->exec("DELETE FROM arquivosUpload WHERE hash = '{$hashPrivado}'");


// =============================================================================
// SEÇÃO 2: DEFESA CONTRA INJECTION (OWASP A03)
// =============================================================================
echo "\n--- 2. Testes de Defesa Contra Injection (SQL Injection & Blind Injection) ---\n";

// 2.1 Tentativa de SQL Injection no Login clássico (' OR '1'='1)
$usuarioModel = new Usuario();
$tentativaSqlInj = $usuarioModel->autenticar("' OR '1'='1", "' OR '1'='1");
assertSecurity($tentativaSqlInj === null, '2.1 SQL Injection clássico no Login é neutralizado sem autenticar');

// 2.2 Tentativa de Blind SQL Injection / Union em parâmetros de busca do DBQuery
$dbQuery = new DBQuery('usuarios');
$payloadInj = "admin' UNION SELECT 1, 'hacked', '3', '4', 5, 6, 7, 8, 9 --";
$dbQuery->addCondition('AND', 'email', '=', $payloadInj);
$compilado = $dbQuery->getSelectSql();
$bindings = $dbQuery->getBindings();
assertSecurity(
    str_contains($compilado, 'WHERE email = :p0') && !str_contains($compilado, 'UNION SELECT') && in_array($payloadInj, $bindings, true),
    '2.2 DBQuery utiliza Prepared Statements parametrizados impedindo SQL Injection'
);

// 2.3 Proteção contra exclusão em massa sem WHERE
$deleteSemWhereBloqueado = false;
try {
    $dbQueryDelete = new DBQuery('usuarios');
    $dbQueryDelete->delete();
} catch (RuntimeException $e) {
    $deleteSemWhereBloqueado = true;
}
assertSecurity($deleteSemWhereBloqueado, '2.3 DBQuery bloqueia DELETE sem cláusula WHERE');

// 2.4 Proteção contra atualização em massa sem WHERE
$updateSemWhereBloqueado = false;
try {
    $dbQueryUpdate = new DBQuery('usuarios');
    $dbQueryUpdate->update(['status' => 0]);
} catch (RuntimeException $e) {
    $updateSemWhereBloqueado = true;
}
assertSecurity($updateSemWhereBloqueado, '2.4 DBQuery bloqueia UPDATE sem cláusula WHERE');


// =============================================================================
// SEÇÃO 3: CRIPTOGRAFIA & SEGURANÇA DE SENHAS (OWASP A02 / A07)
// =============================================================================
echo "\n--- 3. Testes Criptográficos e Resiliência do BCrypt + Salt + Pepper ---\n";

// 3.1 Mitigação do limite clássico de 72 bytes do BCrypt via HMAC-SHA256 Pepper
// No BCrypt padrão, senhas com mais de 72 bytes são truncadas. Com pré-hash HMAC,
// senhas longas que diferem apenas após o byte 72 geram hashes completamente distintos!
$senhaBaseLonga = str_repeat('A', 75);
$senhaLonga1 = $senhaBaseLonga . 'SUFIXO_ALPHA';
$senhaLonga2 = $senhaBaseLonga . 'SUFIXO_OMEGA';

$hashLonga1 = PasswordSecurity::hash($senhaLonga1);
assertSecurity(
    PasswordSecurity::verify($senhaLonga1, $hashLonga1) === true,
    '3.1 Senha longa (> 80 caracteres) é autenticada com sucesso pelo BCrypt+Pepper'
);
assertSecurity(
    PasswordSecurity::verify($senhaLonga2, $hashLonga1) === false,
    '3.2 Truncamento de 72 bytes neutralizado: senhas que diferem após o caractere 72 NÃO colidem'
);

// 3.3 Proteção contra vazamento de Dump do Banco de Dados (Pepper Secret)
$senhaUsuario = 'SenhaForte#2026@ZT';
$hashComPepperOficial = PasswordSecurity::hash($senhaUsuario);

// Se um invasor obtiver o dump do banco mas NÃO tiver o Pepper do servidor:
$pepperOriginal = Config::get('security.password_pepper');
Config::set('security.password_pepper', 'Pepper_Invalido_Ou_Ausente_No_Dump');
$autenticouSemPepper = PasswordSecurity::verify($senhaUsuario, $hashComPepperOficial);
Config::set('security.password_pepper', $pepperOriginal); // Restaura

assertSecurity(
    $autenticouSemPepper === false,
    '3.3 Hashes protegidos por Pepper não podem ser verificados sem a chave secreta do servidor'
);


// =============================================================================
// SEÇÃO 4: PATH TRAVERSAL & SEGURANÇA DE ARQUIVOS (OWASP A01 / A08)
// =============================================================================
echo "\n--- 4. Testes de Path Traversal e Uploads em Base64 ---\n";

// 4.1 Bloqueio de Path Traversal na entrega estática
ob_start();
$router->dispatch('/public/../../app/etc/config.json', 'GET');
$outTraversal1 = ob_get_clean();
$jsonTraversal1 = json_decode($outTraversal1, true);
assertSecurity(
    isset($jsonTraversal1['code']) && $jsonTraversal1['code'] === 404,
    '4.1 Tentativa de Path Traversal (/public/../../app/etc/config.json) é bloqueada com 404'
);

// 4.2 Bloqueio de Path Traversal codificado
ob_start();
$router->dispatch('/public/..%2f..%2fapp/etc/routes.json', 'GET');
$outTraversal2 = ob_get_clean();
$jsonTraversal2 = json_decode($outTraversal2, true);
assertSecurity(
    isset($jsonTraversal2['code']) && $jsonTraversal2['code'] === 404,
    '4.2 Path Traversal codificado em URL é bloqueado com 404'
);

// 4.3 Rejeição de Upload com Base64 corrompido / malformado
$_SESSION['user'] = ['idUsuario' => 1, 'nome' => 'Admin', 'grupos' => ['admin']];
$_POST = [
    'arquivo_base64' => 'Conteudo_Que_Nao_Eh_Base64_Valido!@#$%^&*()',
    'nome_original'  => 'malicioso.txt'
];
ob_start();
$fileController = new FileController();
$fileController->upload();
$outInvalidB64 = ob_get_clean();
$jsonInvalidB64 = json_decode($outInvalidB64, true);
assertSecurity(
    isset($jsonInvalidB64['code']) && $jsonInvalidB64['code'] === 400 && $jsonInvalidB64['error'] === 'ERR_INVALID_BASE64',
    '4.3 Upload com payload Base64 corrompido é rejeitado com 400 ERR_INVALID_BASE64'
);

// 4.4 Rejeição de Upload com payload vazio
$_POST = [
    'arquivo_base64' => '',
    'nome_original'  => 'vazio.txt'
];
ob_start();
$fileController->upload();
$outEmpty = ob_get_clean();
$jsonEmpty = json_decode($outEmpty, true);
assertSecurity(
    isset($jsonEmpty['code']) && $jsonEmpty['code'] === 400 && $jsonEmpty['error'] === 'ERR_EMPTY_FILE',
    '4.4 Upload com conteúdo vazio é rejeitado com 400 ERR_EMPTY_FILE'
);


// =============================================================================
// SEÇÃO 5: SANITIZAÇÃO DE ENTRADAS & PREVENÇÃO DE XSS (OWASP A03)
// =============================================================================
echo "\n--- 5. Testes de Sanitização de Dados e Prevenção de XSS ---\n";

// 5.1 Sanitize::string remove tags de script ativas
$inputXss = '<script>alert("XSS Attack!");</script>Nome Limpo';
$sanitizado = Sanitize::string($inputXss);
assertSecurity(
    $sanitizado === 'Nome Limpo' && !str_contains($sanitizado, '<script>'),
    '5.1 Sanitize::string neutraliza tags de script executáveis'
);

// 5.2 Sanitize::string remove eventos inline perigosos
$inputImgXss = '<img src=x onerror=alert(1)>Imagem';
$sanitizadoImg = Sanitize::string($inputImgXss);
assertSecurity(
    $sanitizadoImg === 'Imagem' && !str_contains($sanitizadoImg, 'onerror'),
    '5.2 Sanitize::string elimina elementos HTML com manipuladores de eventos inline'
);

// 5.3 Sanitize::email valida formato e elimina caracteres impróprios
$emailInvalido = 'teste<script>@dominio.com';
$emailSanitizado = Sanitize::email($emailInvalido);
assertSecurity(
    !str_contains($emailSanitizado, '<script>'),
    '5.3 Sanitize::email remove caracteres e tags perigosas em e-mails'
);

// 5.4 Sanitize::array recursivo para payloads complexos
$payloadArray = [
    'nome'   => '<b>Administrador</b>',
    'filtro' => ['chave' => '<script>steal()</script>ok']
];
$arrayLimpo = Sanitize::array($payloadArray);
assertSecurity(
    $arrayLimpo['nome'] === 'Administrador' && $arrayLimpo['filtro']['chave'] === 'ok',
    '5.4 Sanitize::array limpa recursivamente estruturas aninhadas de formulários'
);


// =============================================================================
// SEÇÃO 6: AUTO-PROVISIONAMENTO SEGURO DO BANCO DE DADOS
// =============================================================================
echo "\n--- 6. Testes de Segurança no Auto-Provisionamento de Banco de Dados ---\n";

// 6.1 Tratamento gracioso de credencial inválida sem crash fatal e sem vazar senha
Config::set('database.connections.mysql.password', 'senha_errada_security_test');
\app\core\database\DatabaseProvisioner::clearCache();

$statusDbErro = \app\core\database\DatabaseProvisioner::checkAndProvision(null, true);
Config::set('database.connections.mysql.password', 'senha'); // Restaura credencial correta
\app\core\database\DatabaseProvisioner::clearCache();

assertSecurity(
    $statusDbErro['ok'] === false && $statusDbErro['error'] === 'ERR_DATABASE_SERVER_CONNECTION',
    '6.1 Credencial incorreta no banco é interceptada com diagnóstico seguro sem derrubar o PHP'
);
assertSecurity(
    !str_contains(json_encode($statusDbErro), 'senha_errada_security_test'),
    '6.2 Senhas do banco de dados NUNCA são expostas na resposta de erro de diagnóstico'
);

echo "\n=====================================================\n";
echo "   TODOS OS TESTES DE SEGURANÇA PASSARAM COM SUCESSO!\n";
echo "=====================================================\n";
