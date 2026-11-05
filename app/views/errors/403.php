<?php
use app\core\utils\Url;
$dashboardUrl = Url::to('dashboard');
$homeUrl      = Url::to('');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>403 - Acesso Proibido</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 60px; background-color: #f8fafc; color: #1e293b;">
    <h1 style="font-size: 3.5rem; margin-bottom: 0.2rem; color: #dc2626; font-weight: 800;">403</h1>
    <h2 style="margin-top: 0; font-weight: 700;">Acesso Proibido</h2>
    <p style="color: #64748b; margin-bottom: 30px;">Seu perfil de usuário ou grupo não tem permissão para visualizar este recurso.</p>
    <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 10px 22px; background: #0284c7; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">Voltar ao Meu Painel</a>
        <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 10px 22px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 600;">Página Inicial</a>
    </div>
</body>
</html>
