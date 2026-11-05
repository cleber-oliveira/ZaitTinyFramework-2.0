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

use app\core\utils\Url;

$user = $_SESSION['user'] ?? [];
$baseHref = Url::base();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Financeiro — Zait Tiny Framework 2.0</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .hero-fin { background: linear-gradient(135deg, #065f46 0%, #047857 100%); color: #fff; padding: 35px 0; border-bottom: 4px solid #10b981; }
        .stat-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06); padding: 22px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top py-2 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="dashboard">
            💰 ZTFramework <span class="badge bg-success text-white">FINANCEIRO</span>
        </a>
        <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
            <span class="text-white small">Analista: <strong><?= htmlspecialchars((string)($user['nome'] ?? 'Financeiro')) ?></strong></span>
            <a href="dashboard?perfil=usuario" class="btn btn-outline-light btn-sm">Meu Perfil</a>
            <a href="logout" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<header class="hero-fin">
    <div class="container">
        <span class="badge bg-success mb-2 px-3 py-1 text-uppercase fw-bold">Nível 4 &bull; Controladoria & Finanças</span>
        <h2 class="fw-bold mb-1">Painel do Departamento Financeiro</h2>
        <p class="text-light opacity-75 mb-0">Gestão de fluxo de caixa, conciliação de lançamentos e custódia de documentos fiscais em Base64.</p>

        <?php if (in_array('admin', (array)($user['grupos'] ?? []), true)): ?>
            <div class="d-inline-flex align-items-center gap-2 mt-4 bg-white text-dark small px-3 py-1 rounded-pill">
                <strong class="text-muted text-uppercase" style="font-size: 0.75rem;">Visão do Perfil:</strong>
                <a href="dashboard" class="badge bg-danger text-decoration-none">Admin</a>
                <a href="dashboard?perfil=gerente" class="badge bg-primary text-decoration-none">Gerente</a>
                <a href="dashboard?perfil=financeiro" class="badge bg-success text-decoration-none">Financeiro (Ativo)</a>
                <a href="dashboard?perfil=suporte" class="badge bg-info text-dark text-decoration-none">Suporte</a>
                <a href="dashboard?perfil=usuario" class="badge bg-secondary text-decoration-none">Usuário Padrão</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="container my-5">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card border-start border-success border-4">
                <div class="text-muted small text-uppercase fw-bold">Receitas do Mês</div>
                <div class="fs-2 fw-bold text-success mt-1">R$ 84.500,00</div>
                <small class="text-muted">&bull; Faturamento consolidado</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-danger border-4">
                <div class="text-muted small text-uppercase fw-bold">Despesas Operacionais</div>
                <div class="fs-2 fw-bold text-danger mt-1">R$ 32.120,00</div>
                <small class="text-muted">&bull; Contas liquidadas</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-primary border-4">
                <div class="text-muted small text-uppercase fw-bold">Saldo Disponível</div>
                <div class="fs-2 fw-bold text-primary mt-1">R$ 52.380,00</div>
                <small class="text-success">&bull; Fluxo superavitário</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-warning border-4">
                <div class="text-muted small text-uppercase fw-bold">Notas Fiscais em Base64</div>
                <div class="fs-2 fw-bold text-dark mt-1">142 Docs</div>
                <small class="text-muted">&bull; Storage com ACL Financeiro</small>
            </div>
        </div>
    </div>

    <!-- Tabela de Lançamentos Recentes -->
    <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
        <h5 class="fw-bold mb-3">📑 Demonstrativo de Lançamentos Recentes</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th># ID</th>
                        <th>Descrição da Operação</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>#101</strong></td>
                        <td>Licenciamento ZTFramework Enterprise</td>
                        <td>Receita de Software</td>
                        <td class="text-success fw-bold">+ R$ 12.500,00</td>
                        <td><span class="badge bg-success">Liquidado</span></td>
                    </tr>
                    <tr>
                        <td><strong>#102</strong></td>
                        <td>Servidores Cloud & Hospedagem</td>
                        <td>Infraestrutura</td>
                        <td class="text-danger fw-bold">- R$ 1.850,00</td>
                        <td><span class="badge bg-success">Pago</span></td>
                    </tr>
                    <tr>
                        <td><strong>#103</strong></td>
                        <td>Serviços de Consultoria e Treinamento</td>
                        <td>Serviços</td>
                        <td class="text-success fw-bold">+ R$ 6.200,00</td>
                        <td><span class="badge bg-warning text-dark">A Conciliar</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
