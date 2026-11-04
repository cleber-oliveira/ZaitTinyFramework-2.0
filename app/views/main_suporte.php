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
$isAdmin = in_array('admin', (array)($user['grupos'] ?? []), true);

$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseHref = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '') ? '/' : rtrim($scriptDir, '/') . '/';

// Métricas de Helpdesk / Suporte via DBQuery
$totalChamados   = count((new DBQuery('chamados', 'idChamado'))->select());
$abertosChamados = count((new DBQuery('chamados', 'idChamado'))->addCondition('AND', 'status', '=', 'aberto')->select());
$emAtendimento   = count((new DBQuery('chamados', 'idChamado'))->addCondition('AND', 'status', '=', 'em_andamento')->select());
$fechadosChamados = count((new DBQuery('chamados', 'idChamado'))->addCondition('AND', 'status', '=', 'fechado')->select());

// Chamados recentes com INNER JOIN no usuário solicitante
$chamadosRecentes = (new DBQuery('chamados c', 'c.*, u.nome as solicitante, u.email as solicitanteEmail'))
    ->joinRaw('INNER JOIN usuarios u ON u.idUsuario = c.idUsuario')
    ->orderBy('c.idChamado', 'DESC')
    ->limit(10)
    ->select();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Suporte & Helpdesk — Zait Tiny Framework 2.0</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f0fdfa; font-family: system-ui, -apple-system, sans-serif; }
        .hero-suporte { background: linear-gradient(135deg, #0e7490 0%, #0891b2 100%); color: #fff; padding: 35px 0; border-bottom: 4px solid #06b6d4; }
        .stat-card { border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06); padding: 22px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .table-custom th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-aberto { background-color: #f59e0b; color: #fff; }
        .badge-em_andamento { background-color: #0284c7; color: #fff; }
        .badge-fechado { background-color: #10b981; color: #fff; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top py-2 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-info" href="dashboard">
            🎧 ZTFramework <span class="badge bg-info text-dark">SUPORTE & HELPDESK</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navSuporte">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navSuporte">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="dashboard">Fila de Atendimento</a></li>
                <li class="nav-item"><a class="nav-link" href="chamados">Central de Chamados</a></li>
                <li class="nav-item"><a class="nav-link" href="public/manual.html" target="_blank">Manual do Framework</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white small">
                    Atendente: <strong><?= htmlspecialchars((string)($user['nome'] ?? 'Técnico de Suporte')) ?></strong>
                    <span class="badge bg-secondary ms-1">suporte</span>
                </span>
                <a href="dashboard?perfil=usuario" class="btn btn-outline-light btn-sm">Meu Perfil</a>
                <a href="logout" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-suporte">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-light text-dark mb-2 px-3 py-1 text-uppercase fw-bold">Nível Suporte Técnico</span>
                <h2 class="fw-bold mb-1">Central de Atendimento e Triagem</h2>
                <p class="text-light opacity-75 mb-0">Monitoramento em tempo real de chamados, suporte aos usuários e SLA de atendimento.</p>
            </div>
            <div>
                <button type="button" class="btn btn-light fw-bold px-4 py-2 shadow-sm text-dark me-2" data-bs-toggle="modal" data-bs-target="#modalNovoChamado">
                    ➕ Novo Chamado
                </button>
                <a href="chamados" class="btn btn-outline-light px-3 py-2">Gerenciar Chamados</a>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <!-- Seletor Rápido se for Admin simulando -->
            <div class="d-inline-flex align-items-center gap-2 mt-4 bg-white text-dark small px-3 py-1 rounded-pill">
                <strong class="text-muted text-uppercase" style="font-size: 0.75rem;">Visão do Perfil:</strong>
                <a href="dashboard" class="badge bg-danger text-decoration-none">Admin</a>
                <a href="dashboard?perfil=gerente" class="badge bg-primary text-decoration-none">Gerente</a>
                <a href="dashboard?perfil=financeiro" class="badge bg-success text-decoration-none">Financeiro</a>
                <a href="dashboard?perfil=suporte" class="badge bg-info text-dark text-decoration-none">Suporte (Ativo)</a>
                <a href="dashboard?perfil=usuario" class="badge bg-secondary text-decoration-none">Usuário Padrão</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="container my-5">
    <!-- Stat Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card border-start border-warning border-4">
                <div class="text-muted small text-uppercase fw-bold">Aguardando Triagem</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $abertosChamados ?></div>
                <small class="text-warning">&bull; Chamados abertos</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-info border-4">
                <div class="text-muted small text-uppercase fw-bold">Em Atendimento</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $emAtendimento ?></div>
                <small class="text-info">&bull; Em andamento com equipe</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-success border-4">
                <div class="text-muted small text-uppercase fw-bold">Resolvidos / Concluídos</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $fechadosChamados ?></div>
                <small class="text-success">&bull; Finalizados com sucesso</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-dark border-4">
                <div class="text-muted small text-uppercase fw-bold">Total Registrado</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= $totalChamados ?></div>
                <small class="text-muted">&bull; Histórico acumulado</small>
            </div>
        </div>
    </div>

    <!-- Tabela de Chamados Recentes -->
    <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0">📋 Fila Recente de Chamados</h5>
                <small class="text-muted">Chamados submetidos pelos usuários via API REST declarativa</small>
            </div>
            <a href="chamados" class="btn btn-outline-info btn-sm text-dark fw-semibold">Abrir Painel Dedicado &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;"># ID</th>
                        <th>Título do Chamado</th>
                        <th>Solicitante</th>
                        <th>Status Atual</th>
                        <th>Data Abertura</th>
                        <th class="text-end">Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($chamadosRecentes)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum chamado registrado no momento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($chamadosRecentes as $c): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= (int)$c['idChamado'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string)$c['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($c['descricao'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 320px;"><?= htmlspecialchars((string)$c['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars((string)$c['solicitante'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars((string)$c['solicitanteEmail'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $st = (string)$c['status'];
                                    $stClass = match($st) {
                                        'aberto' => 'badge-aberto',
                                        'em_andamento' => 'badge-em_andamento',
                                        'fechado' => 'badge-fechado',
                                        default => 'bg-secondary'
                                    };
                                    $stLabel = match($st) {
                                        'aberto' => 'Aberto',
                                        'em_andamento' => 'Em Atendimento',
                                        'fechado' => 'Concluído',
                                        default => $st
                                    };
                                    ?>
                                    <span class="badge <?= $stClass ?> px-3 py-1"><?= $stLabel ?></span>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y H:i', strtotime((string)$c['dataAbertura'])) ?>
                                </td>
                                <td class="text-end">
                                    <a href="chamados" class="btn btn-sm btn-outline-secondary">Gerenciar &rarr;</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dicas & Ferramentas Rápidas -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-white h-100">
                <h5 class="fw-bold mb-3">🛠️ Base de Conhecimento e Dicas</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3 d-flex align-items-start gap-2">
                        <span class="badge bg-primary rounded-circle p-1">✓</span>
                        <div>
                            <strong>Simulação de E-mails com Mailtrap:</strong>
                            <p class="text-muted small mb-0">Para testar respostas automatizadas de abertura e fechamento de tickets sem disparar e-mails para caixas reais, utilize a sandbox SMTP do Mailtrap ou consulte o log <code>app/storage/logs/mail.log</code>.</p>
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <span class="badge bg-info text-dark rounded-circle p-1">✓</span>
                        <div>
                            <strong>Alertas de SLA via SMS:</strong>
                            <p class="text-muted small mb-0">Utilize o helper <code>app\core\utils\Sms::send()</code> para enviar avisos de tickets críticos via Twilio ou simulação em <code>app/storage/logs/sms.log</code>.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 border-0 shadow-sm rounded-4 bg-white h-100">
                <h5 class="fw-bold mb-3">⚡ Acesso Rápido a Outros Recursos</h5>
                <p class="text-muted small">Como operador de suporte, você pode navegar entre a fila de tickets e os módulos do Zait Tiny Framework:</p>
                <div class="d-grid gap-2">
                    <a href="chamados" class="btn btn-outline-primary fw-semibold text-start">🎫 Central de Chamados Completa</a>
                    <a href="dashboard?perfil=usuario" class="btn btn-outline-secondary fw-semibold text-start">👤 Meu Perfil de Usuário & Uploads de Arquivo</a>
                    <a href="public/manual.html" target="_blank" class="btn btn-outline-dark fw-semibold text-start">📖 Manual Didático da Aplicação (HTML)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Chamado Rápido -->
<div class="modal fade" id="modalNovoChamado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Registrar Novo Chamado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <div id="chamadoAlert" class="alert d-none"></div>
                <form id="formNovoChamado" action="api/chamados/novo" method="POST">
                    <div class="mb-3">
                        <label for="chamado-titulo" class="form-label fw-semibold">Título do Chamado</label>
                        <input type="text" class="form-control" id="chamado-titulo" name="titulo" required placeholder="Ex: Ajuste em permissão de acesso">
                    </div>
                    <div class="mb-3">
                        <label for="chamado-descricao" class="form-label fw-semibold">Descrição / Detalhes</label>
                        <textarea class="form-control" id="chamado-descricao" name="descricao" rows="4" placeholder="Descreva os detalhes da solicitação..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-info text-dark fw-bold w-100 py-2" id="btnSalvarChamado">Registrar Chamado</button>
                </form>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white text-center py-4 mt-5">
    <div class="container">
        <p class="mb-1 fw-bold">Zait Tiny Framework 2.0 (ZTFramework)</p>
        <small class="opacity-75">Prof. Drº Cleber S. Oliveira & Profª Drª Marcia Aparecida Silva Bissaco &bull; Suporte Técnico</small>
    </div>
</footer>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/js/zait.ajax.js"></script>
<script>
$(document).ready(function() {
    $('#formNovoChamado').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSalvarChamado');
        $btn.prop('disabled', true).text('Gravando...');
        
        ZTF.ajax.submitForm(this)
            .then(function(res) {
                if (res.status) {
                    $('#chamadoAlert').removeClass('d-none alert-danger').addClass('alert alert-success').text(res.message);
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    $('#chamadoAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(res.message);
                    $btn.prop('disabled', false).text('Registrar Chamado');
                }
            })
            .catch(function(err) {
                $('#chamadoAlert').removeClass('d-none alert-success').addClass('alert alert-danger').text(err.message || 'Erro ao registrar chamado.');
                $btn.prop('disabled', false).text('Registrar Chamado');
            });
    });
});
</script>
</body>
</html>
