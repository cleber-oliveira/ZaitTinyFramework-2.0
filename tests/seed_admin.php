<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use app\core\database\DBConnection;
use app\core\utils\PasswordSecurity;

$db = DBConnection::getConnection('mysql');

$email = 'admin@ztframework.local';
$stmt = $db->prepare("SELECT idUsuario FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $hash = PasswordSecurity::hash('admin123');
    $stmtIns = $db->prepare("INSERT INTO usuarios (nome, email, senha, status, primeiroAcesso) VALUES (?, ?, ?, 1, 0)");
    $stmtIns->execute(['Administrador Master', $email, $hash]);
    $idUsuario = (int)$db->lastInsertId();

    $stmtUg = $db->prepare("INSERT INTO usuariosGrupos (idUsuario, idGrupo) VALUES (?, 1)");
    $stmtUg->execute([$idUsuario]);

    echo "Usuário Administrador criado com sucesso! (Email: {$email} / Senha: admin123)\n";
} else {
    echo "Usuário Administrador já existe.\n";
}
