<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\utils\PasswordSecurity;
use app\core\utils\Config;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando PasswordSecurity (BCrypt + Salt + Pepper) ===\n";

$senhaOriginal = 'MinhaSenhaSecreta@2026';

// Teste 1: Geração de Hash
$hash1 = PasswordSecurity::hash($senhaOriginal);
$hash2 = PasswordSecurity::hash($senhaOriginal);

assertCondition(strlen($hash1) === 60, 'Hash gerado tem 60 caracteres (BCrypt padrão)');
assertCondition($hash1 !== $hash2, 'Hashes diferentes para mesma senha (Salt único gerado a cada hash)');

// Teste 2: Verificação de senha correta
$valida = PasswordSecurity::verify($senhaOriginal, $hash1);
assertCondition($valida === true, 'Senha correta valida com sucesso');

// Teste 3: Verificação de senha incorreta
$invalida = PasswordSecurity::verify('SenhaErrada123', $hash1);
assertCondition($invalida === false, 'Senha incorreta é rejeitada');

// Teste 4: Eficácia do Pepper
// Se o Pepper mudar, o hash não deve mais validar (prova que o Pepper é obrigatório)
$pepperOriginal = Config::get('security.password_pepper');
Config::set('security.password_pepper', 'OutroPepperDiferente999');

$validaSemPepper = PasswordSecurity::verify($senhaOriginal, $hash1);
assertCondition($validaSemPepper === false, 'Pepper diferente bloqueia autenticação (Segurança contra vazamento de BD)');

// Restaura pepper
Config::set('security.password_pepper', $pepperOriginal);
$validaRestaurado = PasswordSecurity::verify($senhaOriginal, $hash1);
assertCondition($validaRestaurado === true, 'Autenticação restabelecida com o Pepper correto');

echo "Todos os testes de PasswordSecurity passaram com sucesso!\n\n";
