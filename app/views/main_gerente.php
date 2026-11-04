<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Zait Tiny Framework 2.0 (ZTFramework)
 * ============================================================================
 * 
 * @package   ZTFramework
 * @version   2.0.0
 * @author    Professor Drº Cleber S. Oliveira
 * @author    Professora Drª Marcia Aparecida Silva Bissaco
 * @copyright (c) 2026 ZTFramework
 * ============================================================================
 */

use app\core\database\DBQuery;

$user = $_SESSION['user'] ?? [];
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseHref = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '') ? '/' : rtrim($scriptDir, '/') . '/';

// Métricas para o Gerente
$totalMembros = count((new DBQuery('usuarios', 'idUsuario'))->select());
$chamadosPendentes = count((new DBQuery('chamados', 'idChamado'))->addCondition('AND', 'status', '!=', 'fechado')->select());
$totalArquivos = count((new DBQuery('arquivosUpload', 'idArquivo'))->select());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Gerente — Zait Tiny Framework 2.0</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .hero-gerente { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: #fff; padding: 35px 0; border-bottom: 4px solid #3b82f6; }
        .stat-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06); padding: 22px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top py-2 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="dashboard">
            👔 ZTFramework <span class="badge bg-primary text-white">GERÊNCIA</span>
        </a>
        <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
            <span class="text-white small">Gerente: <strong><?= htmlspecialchars((string)($user['nome'] ?? 'Gerente')) ?></strong></span>
            <a href="dashboard?perfil=usuario" class="btn btn-outline-light btn-sm">Meu Perfil</a>
            <a href="logout" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<header class="hero-gerente">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-primary mb-2 px-3 py-1 text-uppercase fw-bold">Nível 3 &bull; Gestão Operacional</span>
                <h2 class="fw-bold mb-1">Painel do Gerente de Operações</h2>
                <p class="text-light opacity-75 mb-0">Supervisão de equipe, acompanhamento de demandas e gestão de fluxos de trabalho.</p>
            </div>
            <div>
                <a href="chamados" class="btn btn-warning fw-bold px-4 py-2 me-2">Ver Chamados da Equipe</a>
            </div>
        </div>

        <?php if (in_array('admin', (array)($user['grupos'] ?? []), true)): ?>
            <div class="d-inline-flex align-items-center gap-2 mt-4 bg-white text-dark small px-3 py-1 rounded-pill">
                <strong class="text-muted text-uppercase" style="font-size: 0.75rem;">Visão do Perfil:</strong>
                <a href="dashboard" class="badge bg-danger text-decoration-none">Admin</a>
                <a href="dashboard?perfil=gerente" class="badge bg-primary text-decoration-none">Gerente (Ativo)</a>
                <a href="dashboard?perfil=financeiro" class="badge bg-success text-decoration-none">Financeiro</a>
                <a href="dashboard?perfil=suporte" class="badge bg-info text-dark text-decoration-none">Suporte</a>
                <a href="dashboard?perfil=usuario" class="badge bg-secondary text-decoration-none">Usuário Padrão</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="container my-5">
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card border-start border-primary border-4">
                <div class="text-muted small text-uppercase fw-bold">Equipe Cadastrada</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalMembros ?> Usuários</div>
                <small class="text-success">&bull; Integrantes Registrados</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card border-start border-warning border-4">
                <div class="text-muted small text-uppercase fw-bold">Chamados Pendentes</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $chamadosPendentes ?></div>
                <small class="text-danger">&bull; Necessitam de atenção</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card border-start border-info border-4">
                <div class="text-muted small text-uppercase fw-bold">Arquivos Compartilhados</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalArquivos ?> Documentos</div>
                <small class="text-muted">&bull; Base64 com ACL</small>
            </div>
        </div>
    </div>

    <!-- Tabela de Membros da Equipe -->
    <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">👥 Integrantes da Equipe</h5>
            <a href="chamados" class="btn btn-sm btn-outline-primary">Central de Chamados &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $membros = (new DBQuery('usuarios', 'idUsuario, nome, email, status'))
                        ->orderBy('nome', 'ASC')
                        ->limit(6)
                        ->select();
                    foreach ($membros as $m):
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)$m['nome']) ?></strong></td>
                            <td><code><?= htmlspecialchars((string)$m['email']) ?></code></td>
                            <td>
                                <span class="badge <?= (int)$m['status'] === 1 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= (int)$m['status'] === 1 ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
