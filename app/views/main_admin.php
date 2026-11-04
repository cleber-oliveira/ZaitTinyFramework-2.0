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

// Métricas do sistema via DBQuery
$totalUsuarios = count((new DBQuery('usuarios', 'idUsuario'))->select());
$totalGrupos   = count((new DBQuery('grupos', 'idGrupo'))->select());
$totalArquivos = count((new DBQuery('arquivosUpload', 'idArquivo'))->select());
$totalChamados = count((new DBQuery('chamados', 'idChamado'))->select());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Administrador — Zait Tiny Framework 2.0</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
        .hero-admin { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 35px 0; border-bottom: 4px solid #ef4444; }
        .stat-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06); padding: 22px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .switcher-pill { background: #ffffff; border-radius: 50px; padding: 6px 16px; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top py-2 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-danger" href="dashboard">
            🛡️ ZTFramework <span class="badge bg-danger text-white">ADMIN MASTER</span>
        </a>
        <div class="collapse navbar-collapse" id="navAdmin">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="dashboard">Visão Geral</a></li>
                <li class="nav-item"><a class="nav-link text-warning fw-semibold" href="admin">Gestão de Usuários & Grupos</a></li>
                <li class="nav-item"><a class="nav-link" href="chamados">Central de Chamados</a></li>
                <li class="nav-item"><a class="nav-link" href="public/manual.html" target="_blank">Manual (HTML)</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white small">
                    Admin: <strong><?= htmlspecialchars((string)($user['nome'] ?? 'Admin')) ?></strong>
                </span>
                <a href="logout" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </div>
</nav>

<!-- Header Banner -->
<header class="hero-admin">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-danger mb-2 px-3 py-1 text-uppercase fw-bold">Nível 1 &bull; Controle Total</span>
                <h2 class="fw-bold mb-1">Painel de Controle do Administrador</h2>
                <p class="text-light opacity-75 mb-0">Gestão global de segurança, usuários, matriz de permissões e infraestrutura multi-banco.</p>
            </div>
            <div>
                <a href="admin" class="btn btn-warning fw-bold px-4 py-2 me-2 shadow-sm">Gerenciar Usuários e Grupos</a>
                <a href="dashboard?perfil=usuario" class="btn btn-outline-light px-3 py-2">Meu Perfil Pessoal</a>
            </div>
        </div>

        <!-- Seletor Rápido de Visão (Exclusivo Admin) -->
        <div class="switcher-pill d-inline-flex align-items-center gap-2 mt-4 text-dark small">
            <strong class="text-muted text-uppercase" style="font-size: 0.75rem;">Simular Visão de Outro Perfil:</strong>
            <a href="dashboard" class="badge bg-danger text-decoration-none">Admin</a>
            <a href="dashboard?perfil=gerente" class="badge bg-primary text-decoration-none">Gerente</a>
            <a href="dashboard?perfil=financeiro" class="badge bg-success text-decoration-none">Financeiro</a>
            <a href="dashboard?perfil=suporte" class="badge bg-info text-decoration-none text-dark">Suporte</a>
            <a href="dashboard?perfil=usuario" class="badge bg-secondary text-decoration-none">Usuário Padrão</a>
        </div>
    </div>
</header>

<!-- Métricas do Sistema -->
<div class="container my-5">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card border-start border-primary border-4">
                <div class="text-muted small text-uppercase fw-bold">Total de Usuários</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalUsuarios ?></div>
                <small class="text-success">&bull; Ativos no Banco</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-warning border-4">
                <div class="text-muted small text-uppercase fw-bold">Níveis de Acesso</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalGrupos ?></div>
                <small class="text-muted">admin, gerente, financeiro...</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-success border-4">
                <div class="text-muted small text-uppercase fw-bold">Arquivos Base64</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalArquivos ?></div>
                <small class="text-success">&bull; Storage com ACL</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-danger border-4">
                <div class="text-muted small text-uppercase fw-bold">Total de Chamados</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalChamados ?></div>
                <small class="text-muted">Tickets de Helpdesk</small>
            </div>
        </div>
    </div>

    <!-- Ações e Atalhos Administrativos -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">👥 Usuários Recentes no Sistema</h5>
                    <a href="admin" class="btn btn-sm btn-outline-primary">Abrir Gerenciamento Completo &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $usuariosRecentes = (new DBQuery('usuarios', 'idUsuario, nome, email, status'))
                                ->orderBy('idUsuario', 'DESC')
                                ->limit(5)
                                ->select();
                            foreach ($usuariosRecentes as $usr):
                            ?>
                                <tr>
                                    <td><strong>#<?= $usr['idUsuario'] ?></strong></td>
                                    <td><?= htmlspecialchars((string)$usr['nome']) ?></td>
                                    <td><code><?= htmlspecialchars((string)$usr['email']) ?></code></td>
                                    <td>
                                        <span class="badge <?= (int)$usr['status'] === 1 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= (int)$usr['status'] === 1 ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                <h5 class="fw-bold mb-3">⚡ Ações Administrativas</h5>
                <div class="d-grid gap-2">
                    <a href="admin" class="btn btn-primary py-2 text-start">
                        🛡️ <strong>Matriz de Permissões</strong><br>
                        <small class="opacity-75">Configurar grupos e direitos de acesso</small>
                    </a>
                    <a href="chamados" class="btn btn-outline-dark py-2 text-start">
                        🎫 <strong>Central de Chamados</strong><br>
                        <small class="text-muted">Visualizar e gerenciar solicitações</small>
                    </a>
                    <a href="dashboard?perfil=usuario" class="btn btn-outline-secondary py-2 text-start">
                        👤 <strong>Meu Perfil & Arquivos</strong><br>
                        <small class="text-muted">Uploads em Base64 e dados pessoais</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
