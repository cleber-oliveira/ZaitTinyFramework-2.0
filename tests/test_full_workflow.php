<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\models\Usuario;
use app\controllers\FileController;
use app\core\database\DBConnection;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando Fluxo Completo: MySQL Real, Autenticação, ACL & Upload Base64 ===\n";

$usuarioModel = new Usuario();

// Teste 1: Autenticação do Admin Seed no MySQL
$adminAuth = $usuarioModel->autenticar('admin@ztframework.local', 'admin123');
assertCondition($adminAuth !== null && in_array('admin', $adminAuth['grupos'], true), 'Autenticação do Administrador no MySQL com BCrypt+Pepper');

// Teste 2: Cadastro com Senha Inicial Automática
$emailCliente = 'cliente_' . time() . '@teste.com';
$cadResult = $usuarioModel->cadastrarComSenhaAutomatica('Cliente Teste', $emailCliente);
assertCondition($cadResult['status'] === true && !empty($cadResult['senhaInicial']), 'Cadastro com geração de senha temporária automática');

// Teste 3: Autenticação do novo cliente com a senha inicial gerada
$clienteAuth = $usuarioModel->autenticar($emailCliente, $cadResult['senhaInicial']);
assertCondition(
    $clienteAuth !== null && in_array('usuario', $clienteAuth['grupos'], true),
    'Novo usuário loga com a senha gerada e inicia com nível "usuario"'
);

// Teste 4: Edição de Perfil (Nome, Nova Senha e Foto em Base64)
$fotoFakeBase64 = 'data:image/png;base64,' . base64_encode('fake_image_bytes_123');
$updPerfil = $usuarioModel->atualizarPerfil($clienteAuth['idUsuario'], 'Cliente Nome Atualizado', 'NovaSenha@999', $fotoFakeBase64);
assertCondition($updPerfil['status'] === true, 'Atualização de perfil (Nome, Nova Senha e Foto Base64)');

// Teste 5: Autenticação com a nova senha
$authNovaSenha = $usuarioModel->autenticar($emailCliente, 'NovaSenha@999');
assertCondition($authNovaSenha !== null && $authNovaSenha['nome'] === 'Cliente Nome Atualizado', 'Login com a nova senha e conferência do nome atualizado');
assertCondition(!empty($authNovaSenha['foto']), 'Foto de perfil em Base64 recuperada na autenticação');

// Teste 6: Upload em Base64 com Controle de Acesso (ACL)
$_SESSION['user'] = $authNovaSenha;
$conteudoDoc = "Relatório Confidencial em Base64 - Teste ZTFramework";
$docBase64 = 'data:text/plain;base64,' . base64_encode($conteudoDoc);

$_POST = [
    'arquivo_base64'    => $docBase64,
    'nome_original'     => 'relatorio_privado.txt',
    'publico'           => 0,
    'grupos_permitidos' => ['admin']
];

$fileCtrl = new FileController();
ob_start();
$fileCtrl->upload();
$outUpload = ob_get_clean();
$jsonUpload = json_decode($outUpload, true);

assertCondition(isset($jsonUpload['status']) && $jsonUpload['status'] === true, 'Upload de documento em Base64 gravado no storage com ACL');
$hashArquivo = $jsonUpload['data']['hash'] ?? '';

// Teste 7: Acesso ao arquivo pelo Dono
$_GET['hash'] = $hashArquivo;
ob_start();
$fileCtrl->download();
$conteudoBaixado = ob_get_clean();
assertCondition($conteudoBaixado === $conteudoDoc, 'Dono do arquivo faz download e recebe conteúdo binário decodificado');

// Teste 8: Acesso de terceiro sem permissão (deve dar 403)
$_SESSION['user'] = [
    'idUsuario'  => 999,
    'nome'       => 'Invasor Sem Acesso',
    'email'      => 'outro@teste.com',
    'grupos'     => ['usuario'],
    'permissoes' => []
];

ob_start();
$fileCtrl->download();
$outNegado = ob_get_clean();
$jsonNegado = json_decode($outNegado, true);
assertCondition(isset($jsonNegado['code']) && $jsonNegado['code'] === 403, 'Terceiro sem permissão tem acesso negado com 403');

// Teste 9: Acesso pelo SuperAdmin (deve liberar pelo bypass de admin)
$_SESSION['user'] = $adminAuth;
ob_start();
$fileCtrl->download();
$outAdminDownload = ob_get_clean();
assertCondition($outAdminDownload === $conteudoDoc, 'SuperAdmin tem acesso garantido ao arquivo pela ACL');

// Teste 10: Admin gerencia grupos do usuário (atribui nível 'gerente' ao cliente)
$db = DBConnection::getConnection();
$stmtG = $db->query("SELECT idGrupo FROM grupos WHERE grupo = 'gerente' LIMIT 1");
$idGrupoGerente = (int)$stmtG->fetchColumn();

$usuarioModel->sincronizarGruposUsuario($clienteAuth['idUsuario'], [2, $idGrupoGerente]);
$clienteRecarregado = $usuarioModel->autenticar($emailCliente, 'NovaSenha@999');

assertCondition(
    in_array('usuario', $clienteRecarregado['grupos'], true) && in_array('gerente', $clienteRecarregado['grupos'], true),
    'Administrador atribuiu novo nível (gerente) ao usuário com sucesso'
);

echo "Todos os testes do Fluxo Completo passaram com 100% de sucesso!\n\n";
