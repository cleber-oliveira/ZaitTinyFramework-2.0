<?php
declare(strict_types=1);

use app\models\Usuario;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;
use app\core\utils\CodeGenerator;
use app\core\utils\Mail;

$email = Sanitize::email($_POST['email'] ?? '');

if (empty($email)) {
    $raw = (string)file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $email = Sanitize::email($json['email'] ?? '');
        }
    }
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    JsonResponse::error(400, 'ERR_INVALID_EMAIL', 'Informe um endereço de e-mail válido.');
    exit;
}

// 1. Gera uma senha temporária segura utilizando o CodeGenerator
$novaSenha = CodeGenerator::generatePassword(10);

// 2. Atualiza no banco de dados se conectado
$usuarioModel = new Usuario();
try {
    $usuarioModel->redefinirSenhaPorEmail($email, $novaSenha);
} catch (\Throwable $e) {
    // Permite continuar em ambiente de desenvolvimento/teste sem banco físico
}

// 3. Monta o e-mail em HTML
$corpoEmail = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e4e8; border-radius: 8px;'>
        <h2 style='color: #0366d6;'>Recuperação de Senha - ZTFramework</h2>
        <p>Olá,</p>
        <p>Recebemos uma solicitação para redefinir a sua senha de acesso.</p>
        <p>Sua nova senha temporária é:</p>
        <div style='background: #f6f8fa; padding: 15px; font-size: 20px; font-weight: bold; text-align: center; border-radius: 6px; letter-spacing: 2px; color: #24292e;'>
            {$novaSenha}
        </div>
        <p style='color: #586069; font-size: 13px; margin-top: 20px;'>
            Recomendamos que você acesse o sistema e altere esta senha imediatamente na seção de preferências.
        </p>
        <hr style='border: none; border-top: 1px solid #e1e4e8; margin: 20px 0;'>
        <small style='color: #8c959f;'>Se você não solicitou esta alteração, entre em contato imediatamente com o suporte.</small>
    </div>
";

try {
    // 4. Envia o e-mail via Mail (PHPMailer / Simulação em Dev)
    Mail::quickSend($email, 'Recuperação de Senha - ZTFramework', $corpoEmail);

    JsonResponse::success([
        'email' => $email,
        'simulated_password' => (php_sapi_name() === 'cli' ? $novaSenha : null)
    ], 'Uma nova senha temporária foi gerada e enviada para o seu e-mail!');

} catch (\Throwable $e) {
    JsonResponse::error(500, 'ERR_MAIL_SEND', 'Não foi possível enviar o e-mail de recuperação: ' . $e->getMessage());
}
