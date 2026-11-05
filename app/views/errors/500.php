<?php
use app\core\utils\Url;
$homeUrl = Url::to('');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>500 - Erro Interno</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 60px; background-color: #f8fafc; color: #1e293b;">
    <h1 style="font-size: 3.5rem; margin-bottom: 0.2rem; color: #ef4444; font-weight: 800;">500</h1>
    <h2 style="margin-top: 0; font-weight: 700;">Erro Interno do Servidor</h2>
    <p style="color: #64748b; margin-bottom: 30px;">Ocorreu uma falha inesperada durante o processamento da sua solicitação.</p>
    <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block; padding: 10px 22px; background: #0284c7; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">Página Inicial</a>
</body>
</html>
