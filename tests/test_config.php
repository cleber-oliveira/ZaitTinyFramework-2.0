<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\utils\Config;

function assertEqual($actual, $expected, string $testName): void {
    if ($actual === $expected) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> Esperado: " . json_encode($expected) . ", Obtido: " . json_encode($actual) . "\n";
        exit(1);
    }
}

echo "=== Testando Config.php ===\n";

Config::reset();

// Teste 1: Leitura de chave simples
assertEqual(Config::get('app.name'), 'Zait Tiny Framework', 'Config::get(app.name)');

// Teste 2: Leitura aninhada
assertEqual(Config::get('database.connections.mysql.port'), 3306, 'Config::get(database.connections.mysql.port)');

// Teste 3: Valor padrão para chave inexistente
assertEqual(Config::get('chave.inexistente', 'padrao'), 'padrao', 'Config::get com fallback default');

// Teste 4: Verificação de existência com has()
assertEqual(Config::has('publicZone.allowedMimes.css'), true, 'Config::has(publicZone.allowedMimes.css)');
assertEqual(Config::has('chave.que.nao.existe'), false, 'Config::has(chave.que.nao.existe)');

// Teste 5: Alteração em runtime
Config::set('app.env', 'testing');
assertEqual(Config::get('app.env'), 'testing', 'Config::set em runtime');

echo "Todos os testes de Config passaram com sucesso!\n\n";
