<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\utils\CodeGenerator;
use app\core\utils\Mail;
use app\core\utils\Router;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando CodeGenerator & Mail (PHPMailer) ===\n";

// Teste 1: CodeGenerator - Senha temporária
$senhaTemp = CodeGenerator::generatePassword(10);
assertCondition(strlen($senhaTemp) === 10, 'Geração de senha temporária com 10 caracteres');

// Teste 2: CodeGenerator - Código numérico OTP
$codigoOtp = CodeGenerator::generateNumericCode(6);
assertCondition(strlen($codigoOtp) === 6 && ctype_digit($codigoOtp), 'Geração de código numérico OTP de 6 dígitos');

// Teste 3: CodeGenerator - Token seguro
$token = CodeGenerator::generateSecureToken(32);
assertCondition(strlen($token) === 64, 'Token hexadecimal seguro gerado');

// Teste 4: Envio de e-mail com PHPMailer / Simulação em Dev
$logMail = __DIR__ . '/../app/storage/logs/mail.log';
if (is_file($logMail)) {
    unlink($logMail);
}

$enviou = Mail::quickSend('teste@destinatario.com', 'Assunto de Teste', '<h1>Corpo do Teste</h1>');
assertCondition($enviou === true, 'Mail::quickSend executado com sucesso');
assertCondition(is_file($logMail) && str_contains(file_get_contents($logMail), 'Assunto de Teste'), 'Log de envio de e-mail registrado em storage/logs');

// Teste 5: Rota POST /recuperar-senha pelo Router
$_POST['email'] = 'usuario@empresa.com';
$router = new Router();
ob_start();
$router->dispatch('/recuperar-senha', 'POST');
$outRec = ob_get_clean();
$jsonRec = json_decode($outRec, true);
assertCondition(isset($jsonRec['status']) && $jsonRec['status'] === true, 'POST /recuperar-senha despachado com sucesso');

echo "Todos os testes de CodeGenerator e Mail passaram com sucesso!\n\n";
