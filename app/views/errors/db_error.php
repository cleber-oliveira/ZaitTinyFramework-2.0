<?php
declare(strict_types=1);

/**
 * ZTFramework - Visualização de Erro de Banco de Dados / Diagnóstico
 * 
 * Exibido quando o Router detecta falha na conexão ou auto-provisionamento do banco.
 */

$errorInfo = $errorDetails ?? [
    'message' => 'Falha inesperada na conexão com o banco de dados.',
    'details' => []
];

$details = $errorInfo['details'] ?? [];
$host = htmlspecialchars((string)($details['host'] ?? 'localhost'), ENT_QUOTES, 'UTF-8');
$port = htmlspecialchars((string)($details['port'] ?? '3306'), ENT_QUOTES, 'UTF-8');
$user = htmlspecialchars((string)($details['user'] ?? 'root'), ENT_QUOTES, 'UTF-8');
$dbname = htmlspecialchars((string)($details['dbname'] ?? 'ztframework'), ENT_QUOTES, 'UTF-8');
$pdoError = htmlspecialchars((string)($details['pdo_error'] ?? $errorInfo['message'] ?? 'Erro desconhecido'), ENT_QUOTES, 'UTF-8');
$baseHref = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') . '/';
if ($baseHref === '//') $baseHref = '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZTFramework - Erro no Banco de Dados</title>
    <base href="<?= $baseHref ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            max-width: 720px;
            width: 100%;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        .error-header {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            color: #ffffff;
            padding: 24px 30px;
        }
        .code-box {
            background-color: #212529;
            color: #f8f9fa;
            border-radius: 6px;
            padding: 12px 16px;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-header">
        <h4 class="mb-1"><i class="bi bi-database-slash"></i> Falha de Conexão com o Banco de Dados</h4>
        <small class="opacity-75">ZTFramework 2.0 &bull; Auto-Provisioning & Diagnostic Shield</small>
    </div>
    
    <div class="p-4">
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <div>
                <strong>Atenção:</strong> <?= htmlspecialchars((string)($errorInfo['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">Parâmetros Verificados</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Host</th>
                        <th>Porta</th>
                        <th>Usuário</th>
                        <th>Banco de Dados</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code><?= $host ?></code></td>
                        <td><code><?= $port ?></code></td>
                        <td><code><?= $user ?></code></td>
                        <td><code><?= $dbname ?></code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">Mensagem Técnica do Servidor</h6>
        <div class="code-box mb-4">
            <?= $pdoError ?>
        </div>

        <div class="card bg-light border-0 p-3 mb-4">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-lightbulb"></i> Como solucionar:</h6>
            <ol class="mb-0 text-muted small ps-3">
                <li>Certifique-se de que o serviço do MySQL/MariaDB está rodando no servidor (ex: <code>sudo systemctl start mysql</code>).</li>
                <li>Verifique se as credenciais no arquivo <code>app/etc/config.json</code> (chave <code>database.connections.mysql</code>) estão corretas.</li>
                <li>Garanta que o usuário <code><?= $user ?></code> tenha privilégios de conexão e de criação (<code>CREATE DATABASE</code> / <code>CREATE TABLE</code>).</li>
                <li>Assim que a conexão estiver acessível, o ZTFramework criará automaticamente o banco <code><?= $dbname ?></code> e provisionará toda a estrutura de tabelas e dados iniciais!</li>
            </ol>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="https://github.com/google/antigravity" target="_blank" class="text-muted small text-decoration-none">Documentação do Framework</a>
            <button onclick="window.location.reload();" class="btn btn-primary px-4">
                Tentar Reconectar Agora
            </button>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
