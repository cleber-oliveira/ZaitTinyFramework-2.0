<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\database\DBQuery;

function assertCondition(bool $condition, string $testName, string $failMessage = ''): void {
    if ($condition) {
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName} -> {$failMessage}\n";
        exit(1);
    }
}

echo "=== Testando DBQuery (Query Builder & Compilação SQL) ===\n";

// Teste 1: SELECT simples com WHERE
$q1 = (new DBQuery('produtos', 'id, nome, preco'))
    ->addCondition('AND', 'status', '=', 1)
    ->orderBy('preco', 'DESC')
    ->limit(10, 0);

$sql1 = $q1->getSelectSql();
$bindings1 = $q1->getBindings();

assertCondition(
    $sql1 === "SELECT id, nome, preco FROM produtos WHERE status = :p0 ORDER BY preco DESC LIMIT 10",
    'Compilação SELECT simples com WHERE e LIMIT'
);
assertCondition($bindings1[':p0'] === 1, 'Bindings de parâmetros para SELECT simples');

// Teste 2: SELECT com JOIN e WHERE IN
$q2 = (new DBQuery('usuarios u', 'u.id, u.nome, g.grupo'))
    ->addJoin('INNER', 'u', 'idGrupo', '=', 'grupos g', 'idGrupo')
    ->addIn('AND', 'g.grupo', ['admin', 'financeiro'])
    ->orderBy('u.nome', 'ASC');

$sql2 = $q2->getSelectSql();
$bindings2 = $q2->getBindings();

assertCondition(
    str_contains($sql2, 'INNER JOIN grupos g ON u.idGrupo = g.idGrupo') &&
    str_contains($sql2, 'g.grupo IN (:p0, :p1)'),
    'Compilação SELECT com JOIN e WHERE IN'
);
assertCondition(
    $bindings2[':p0'] === 'admin' && $bindings2[':p1'] === 'financeiro',
    'Bindings corretos para cláusula WHERE IN'
);

// Teste 3: Proteção de Segurança - Bloqueio de UPDATE sem WHERE
$bloqueouUpdate = false;
try {
    (new DBQuery('produtos'))->update(['preco' => 999]);
} catch (\RuntimeException $e) {
    if (str_contains($e->getMessage(), 'UPDATE sem cláusula WHERE não é permitido')) {
        $bloqueouUpdate = true;
    }
}
assertCondition($bloqueouUpdate, 'UPDATE sem WHERE bloqueado por segurança');

// Teste 4: Proteção de Segurança - Bloqueio de DELETE sem WHERE
$bloqueouDelete = false;
try {
    (new DBQuery('produtos'))->delete();
} catch (\RuntimeException $e) {
    if (str_contains($e->getMessage(), 'DELETE sem cláusula WHERE não é permitido')) {
        $bloqueouDelete = true;
    }
}
assertCondition($bloqueouDelete, 'DELETE sem WHERE bloqueado por segurança');

// Teste 5: Inserção com dados vazios deve disparar erro
$bloqueouInsertVazio = false;
try {
    (new DBQuery('produtos'))->insert([]);
} catch (\RuntimeException $e) {
    $bloqueouInsertVazio = true;
}
assertCondition($bloqueouInsertVazio, 'INSERT com array vazio bloqueado');

echo "Todos os testes de DBQuery passaram com sucesso!\n\n";
