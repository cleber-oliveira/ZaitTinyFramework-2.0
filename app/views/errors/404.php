<?php
use app\core\utils\Url;
$homeUrl      = Url::to('');
$dashboardUrl = Url::to('dashboard');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>404 - Página Não Encontrada</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 60px; background-color: #f8fafc; color: #1e293b;">
    <h1 style="font-size: 3.5rem; margin-bottom: 0.2rem; color: #f59e0b; font-weight: 800;">404</h1>
    <h2 style="margin-top: 0; font-weight: 700;">Página Não Encontrada</h2>
    <p style="color: #64748b; margin-bottom: 30px;">O recurso que você tentou acessar não existe ou foi movido.</p>
    <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 10px 22px; background: #0284c7; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">Página Inicial</a>
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 10px 22px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 600;">Meu Painel</a>
    </div>
</body>
</html>
