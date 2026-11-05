<?php
declare(strict_types=1);

echo "=====================================================\n";
echo "       ZTFramework 2.0 - SUÍTE COMPLETA DE TESTES    \n";
echo "=====================================================\n\n";

$tests = [
    'test_config.php',
    'test_router.php',
    'test_dbquery.php',
    'test_password_security.php',
    'test_mail_and_auth.php',
    'test_ajax_and_mimes.php',
    'test_full_workflow.php',
    'test_security.php',
    'test_url_and_endpoints.php'
];

$allPassed = true;

foreach ($tests as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    echo ">>> Executando: {$testFile}\n";
    passthru("php " . escapeshellarg($fullPath), $returnCode);
    if ($returnCode !== 0) {
        $allPassed = false;
        echo "\n[ERRO] Falha ao executar {$testFile}\n";
        break;
    }
}

if ($allPassed) {
    echo "=====================================================\n";
    echo "       TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! \n";
    echo "=====================================================\n";
    exit(0);
} else {
    echo "=====================================================\n";
    echo "       ALGUNS TESTES FALHARAM.                       \n";
    echo "=====================================================\n";
    exit(1);
}
