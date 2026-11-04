<?php
declare(strict_types=1);

use app\models\Usuario;
use app\core\utils\JsonResponse;

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

// 1. Captura dados enviados (POST form ou JSON body)
$email = trim($_POST['email'] ?? '');
$senha = (string)($_POST['senha'] ?? '');

if (empty($email) || empty($senha)) {
    // Tenta ler do php://input se for JSON payload
    $raw = (string)file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $email = trim($json['email'] ?? $email);
            $senha = (string)($json['senha'] ?? $senha);
        }
    }
}

if (empty($email) || empty($senha)) {
    JsonResponse::error(400, 'ERR_EMPTY_FIELDS', 'E-mail e senha são obrigatórios.');
    exit;
}

// 2. Chama Model Usuario para autenticar
$usuarioModel = new Usuario();
$dadosUsuario = $usuarioModel->autenticar($email, $senha);

if (!$dadosUsuario) {
    JsonResponse::error(401, 'ERR_INVALID_CREDENTIALS', 'Credenciais inválidas. Verifique seu e-mail e senha.');
    exit;
}

// 3. Popula a estrutura rica de sessão
$_SESSION['user'] = [
    'idUsuario'  => $dadosUsuario['idUsuario'],
    'nome'       => $dadosUsuario['nome'],
    'email'      => $dadosUsuario['email'],
    'grupos'     => (array)$dadosUsuario['grupos'],
    'permissoes' => (array)$dadosUsuario['permissoes']
];

// 4. Retorna resposta JSON direta
JsonResponse::success([
    'usuario' => [
        'id'    => $dadosUsuario['idUsuario'],
        'nome'  => $dadosUsuario['nome'],
        'email' => $dadosUsuario['email']
    ],
    'redirect' => '/dashboard'
], 'Autenticado com sucesso!');
