<?php
declare(strict_types=1);

use app\models\Usuario;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;
use app\core\utils\Mail;

// Captura nome e e-mail
$nome  = Sanitize::string($_POST['nome'] ?? '');
$email = Sanitize::email($_POST['email'] ?? '');

// Suporte a JSON body
if (empty($nome) && empty($email)) {
    $raw = (string)file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $nome  = Sanitize::string($json['nome'] ?? '');
            $email = Sanitize::email($json['email'] ?? '');
        }
    }
}

if (empty($nome) || empty($email)) {
    JsonResponse::error(400, 'ERR_EMPTY_FIELDS', 'Nome e e-mail são obrigatórios.');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    JsonResponse::error(400, 'ERR_INVALID_EMAIL', 'Formato de e-mail inválido.');
    exit;
}

$usuarioModel = new Usuario();
$resultado = $usuarioModel->cadastrarComSenhaAutomatica($nome, $email);

if (!$resultado['status']) {
    JsonResponse::error(400, 'ERR_REGISTER_FAILED', $resultado['message']);
    exit;
}

$senhaInicial = $resultado['senhaInicial'];

// Envia a senha inicial por e-mail via PHPMailer
$corpoEmail = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e1e4e8; border-radius: 8px;'>
        <h2 style='color: #0d6efd;'>Bem-vindo ao ZTFramework!</h2>
        <p>Olá, <strong>{$nome}</strong>!</p>
        <p>Sua conta foi criada com sucesso no nível inicial <strong>Usuário</strong>.</p>
        <p>Sua senha inicial de acesso gerada automaticamente pelo sistema é:</p>
        <div style='background: #f8f9fa; padding: 15px; font-size: 20px; font-weight: bold; text-align: center; border-radius: 6px; letter-spacing: 2px; color: #212529; border: 1px solid #dee2e6;'>
            {$senhaInicial}
        </div>
        <p style='color: #6c757d; font-size: 13px; margin-top: 20px;'>
            Após efetuar o primeiro login, você poderá alterar esta senha e personalizar sua foto no seu perfil.
        </p>
    </div>
";

try {
    Mail::quickSend($email, 'Sua Senha Inicial de Acesso - ZTFramework', $corpoEmail);
} catch (\Throwable $e) {
    // Continua para não interromper caso SMTP falhe localmente
}

JsonResponse::success([
    'email'              => $email,
    'nome'               => $nome,
    'simulated_password' => (php_sapi_name() === 'cli' || (bool)\app\core\utils\Config::get('mail.simulate_in_dev') ? $senhaInicial : null)
], 'Cadastro concluído! Sua senha inicial foi gerada e enviada para o seu e-mail.');
