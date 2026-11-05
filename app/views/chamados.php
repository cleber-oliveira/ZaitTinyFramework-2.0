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

$baseHref = Url::base();
$chamados = $chamados ?? [];
$user = $_SESSION['user'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamados de Suporte — Zait Tiny Framework 2.0</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table th { background-color: #f1f5f9; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="./">
            🚀 Zait Tiny Framework <span class="badge bg-primary text-white">2.0</span>
        </a>
        <div class="navbar-nav ms-auto gap-2">
            <a href="dashboard" class="btn btn-outline-light btn-sm px-3">Voltar ao Painel</a>
            <a href="public/manual.html" target="_blank" class="btn btn-outline-warning btn-sm px-3">Manual Didático</a>
        </div>
    </div>
</nav>

<!-- Conteúdo Principal -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">🎫 Central de Chamados de Suporte</h2>
            <p class="text-muted mb-0">Módulo didático de exemplo construído sobre o ZTFramework 2.0 com DBQuery e AJAX.</p>
        </div>
        <button class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovoChamado">
            + Abrir Novo Chamado
        </button>
    </div>

    <!-- Tabela de Chamados -->
    <div class="card card-custom bg-white p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaChamados">
                <thead>
                    <tr>
                        <th style="width: 80px;"># ID</th>
                        <th>Título do Chamado</th>
                        <th>Solicitante</th>
                        <th>Data</th>
                        <th style="width: 130px;">Status</th>
                    </tr>
                </thead>
                <tbody id="listaChamadosBody">
                    <?php if (empty($chamados)): ?>
                        <tr id="linhaVazia">
                            <td colspan="5" class="text-center text-muted py-4">Nenhum chamado registrado até o momento. Clique em "+ Abrir Novo Chamado" para iniciar!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($chamados as $c): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars((string)$c['idChamado']) ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars((string)$c['titulo']) ?></div>
                                    <?php if (!empty($c['descricao'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars((string)$c['descricao']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)($c['solicitante'] ?? 'Usuário')) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime((string)$c['dataAbertura'])) ?></td>
                                <td>
                                    <?php if ($c['status'] === 'aberto'): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1">Aberto</span>
                                    <?php elseif ($c['status'] === 'em_andamento'): ?>
                                        <span class="badge bg-info text-dark px-2 py-1">Em Andamento</span>
                                    <?php else: ?>
                                        <span class="badge bg-success px-2 py-1">Fechado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Abertura de Novo Chamado -->
<div class="modal fade" id="modalNovoChamado" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalLabel">Registrar Novo Chamado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalAlert" class="alert d-none" role="alert"></div>

                <form id="formNovoChamado">
                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-semibold">Título do Chamado *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required placeholder="Ex: Erro ao gerar relatório financeiro">
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label fw-semibold">Descrição do Problema</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Descreva os detalhes da solicitação..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSalvarChamado">Registrar Chamado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/js/zait.ajax.js"></script>
<script>
$(document).ready(function() {
    $('#formNovoChamado').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#btnSalvarChamado');
        const $alert = $('#modalAlert');
        $btn.prop('disabled', true).text('Registrando...');
        $alert.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: 'api/chamados/novo',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        })
        .done(function(res) {
            if (res.status) {
                $alert.removeClass('d-none').addClass('alert-success').html('<strong>Sucesso!</strong> ' + res.message);
                
                // Insere dinamicamente a nova linha no topo da tabela
                $('#linhaVazia').remove();
                const d = res.data;
                const novaLinha = `
                    <tr class="table-success">
                        <td><strong>#${d.idChamado}</strong></td>
                        <td>
                            <div class="fw-bold">${d.titulo}</div>
                        </td>
                        <td>${d.solicitante}</td>
                        <td>${d.dataAbertura}</td>
                        <td><span class="badge bg-warning text-dark px-2 py-1">Aberto</span></td>
                    </tr>
                `;
                $('#listaChamadosBody').prepend(novaLinha);

                setTimeout(function() {
                    $('#formNovoChamado')[0].reset();
                    $alert.addClass('d-none');
                    bootstrap.Modal.getInstance($('#modalNovoChamado')[0]).hide();
                }, 1200);
            } else {
                $alert.removeClass('d-none').addClass('alert-danger').text(res.message || 'Erro ao registrar chamado.');
            }
        })
        .fail(function(xhr) {
            const err = xhr.responseJSON || {};
            $alert.removeClass('d-none').addClass('alert-danger').text(err.message || 'Erro de comunicação com o servidor.');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Registrar Chamado');
        });
    });
});
</script>
</body>
</html>
