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
$grupos = (array)($user['grupos'] ?? []);
$perfilReq = $_GET['perfil'] ?? null;

// Roteador de Perfil: Despacha para o dashboard específico do perfil
if ($perfilReq !== null) {
    if ($perfilReq === 'admin' && in_array('admin', $grupos, true)) {
        require __DIR__ . '/main_admin.php';
        return;
    }
    if ($perfilReq === 'gerente' && (in_array('gerente', $grupos, true) || in_array('admin', $grupos, true))) {
        require __DIR__ . '/main_gerente.php';
        return;
    }
    if ($perfilReq === 'financeiro' && (in_array('financeiro', $grupos, true) || in_array('admin', $grupos, true))) {
        require __DIR__ . '/main_financeiro.php';
        return;
    }
    if ($perfilReq === 'suporte' && (in_array('suporte', $grupos, true) || in_array('admin', $grupos, true))) {
        require __DIR__ . '/main_suporte.php';
        return;
    }
    // Caso perfil=usuario, continua para renderizar o painel pessoal padrão (main.php)
} else {
    // Sem parâmetro 'perfil', abre o painel específico do perfil do usuário logado
    if (in_array('admin', $grupos, true)) {
        require __DIR__ . '/main_admin.php';
        return;
    }
    if (in_array('gerente', $grupos, true)) {
        require __DIR__ . '/main_gerente.php';
        return;
    }
    if (in_array('financeiro', $grupos, true)) {
        require __DIR__ . '/main_financeiro.php';
        return;
    }
    if (in_array('suporte', $grupos, true)) {
        require __DIR__ . '/main_suporte.php';
        return;
    }
}

$isAdmin = in_array('admin', $grupos, true);
$fotoSrc = !empty($user['foto']) ? $user['foto'] : 'https://via.placeholder.com/150?text=Sem+Foto';

$baseHref = Url::base();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário — Zait Tiny Framework 2.0</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #0d6efd;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .card-custom {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            background-color: #fff;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar Logada -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="dashboard">
            👤 ZTFramework <span class="badge bg-secondary">PAINEL DO USUÁRIO</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navDashboard">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navDashboard">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="dashboard?perfil=usuario">Meu Perfil</a></li>
                <li class="nav-item"><a class="nav-link" href="chamados">Abrir Chamado</a></li>
                <li class="nav-item"><a class="nav-link" href="public/manual.html" target="_blank">Manual (HTML)</a></li>
                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold" href="admin">
                            🛡️ Gestão Administrativa
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white small">
                    Olá, <strong><?= htmlspecialchars((string)($user['nome'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="badge bg-primary ms-1"><?= implode(', ', (array)($user['grupos'] ?? [])) ?></span>
                </span>
                <a href="logout" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <?php if (in_array('admin', $grupos, true)): ?>
        <div class="alert alert-danger py-2 d-flex justify-content-between align-items-center mb-4 rounded-3 shadow-sm">
            <span>🛡️ Você está visualizando seu <strong>Perfil Pessoal</strong> como Administrador.</span>
            <a href="dashboard" class="btn btn-danger btn-sm fw-bold">Voltar ao Painel Admin &rarr;</a>
        </div>
    <?php elseif (in_array('gerente', $grupos, true)): ?>
        <div class="alert alert-primary py-2 d-flex justify-content-between align-items-center mb-4 rounded-3 shadow-sm">
            <span>👔 Você está visualizando seu <strong>Perfil Pessoal</strong> como Gerente.</span>
            <a href="dashboard" class="btn btn-primary btn-sm fw-bold">Voltar ao Painel da Gerência &rarr;</a>
        </div>
    <?php elseif (in_array('financeiro', $grupos, true)): ?>
        <div class="alert alert-success py-2 d-flex justify-content-between align-items-center mb-4 rounded-3 shadow-sm">
            <span>💰 Você está visualizando seu <strong>Perfil Pessoal</strong> como Analista Financeiro.</span>
            <a href="dashboard" class="btn btn-success btn-sm fw-bold">Voltar ao Painel Financeiro &rarr;</a>
        </div>
    <?php elseif (in_array('suporte', $grupos, true)): ?>
        <div class="alert alert-info py-2 d-flex justify-content-between align-items-center mb-4 rounded-3 shadow-sm">
            <span>🎧 Você está visualizando seu <strong>Perfil Pessoal</strong> como Suporte Técnico.</span>
            <a href="dashboard" class="btn btn-info text-dark btn-sm fw-bold">Voltar à Central de Suporte &rarr;</a>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <!-- Coluna 1: Edição de Perfil e Foto Base64 -->
        <div class="col-lg-5">
            <div class="card card-custom p-4">
                <div class="text-center mb-4">
                    <img id="imgPreview" src="<?= htmlspecialchars($fotoSrc, ENT_QUOTES, 'UTF-8') ?>" class="profile-img mb-3" alt="Foto de Perfil">
                    <h4 class="fw-bold mb-1" id="labelNome"><?= htmlspecialchars((string)($user['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="text-muted small mb-2"><?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div id="perfilAlert" class="alert d-none"></div>

                <form id="formPerfil" action="api/perfil/atualizar" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">E-mail (Inalterável)</label>
                        <input type="email" class="form-control bg-light" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                        <small class="text-muted" style="font-size: 11px;">O e-mail é o identificador único e não pode ser alterado.</small>
                    </div>

                    <div class="mb-3">
                        <label for="perfil-nome" class="form-label small fw-bold">Nome Completo</label>
                        <input type="text" class="form-control" id="perfil-nome" name="nome" value="<?= htmlspecialchars((string)($user['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="inputFoto" class="form-label small fw-bold">Alterar Foto de Perfil (Base64)</label>
                        <input type="file" class="form-control" id="inputFoto" accept="image/*">
                        <input type="hidden" id="foto_base64" name="foto_base64" value="">
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-bold text-secondary mb-3">Definir Nova Senha</h6>
                    <div class="mb-3">
                        <label for="perfil-nova-senha" class="form-label small fw-bold">Nova Senha (deixe em branco para manter)</label>
                        <input type="password" class="form-control" id="perfil-nova-senha" name="nova_senha" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label for="perfil-conf-senha" class="form-label small fw-bold">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="perfil-conf-senha" name="confirmar_nova_senha" placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btnSalvarPerfil">Salvar Alterações</button>
                </form>
            </div>
        </div>

        <!-- Coluna 2: Uploads em Base64 com Controle de Acesso (ACL) -->
        <div class="col-lg-7">
            <!-- Card de Envio Rápido via Componente uploadB64 -->
            <div class="card card-custom p-4 mb-4 border-start border-primary border-4">
                <h5 class="fw-bold mb-1">⚡ Upload Automático Base64 (Componente <code>.uploadB64</code>)</h5>
                <p class="text-muted small mb-3">Selecione um arquivo: ele será convertido para Base64 no navegador e enviado automaticamente via AJAX.</p>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Selecione o arquivo para envio automático:</label>
                    <input type="file" 
                           class="form-control uploadB64" 
                           data-target="#hiddenHashAuto" 
                           data-publico="0"
                           data-grupos='["admin"]'>
                    <input type="hidden" id="hiddenHashAuto" name="documento_hash">
                </div>
            </div>

            <!-- Card de Listagem de Arquivos -->
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">📁 Arquivos Disponíveis (Protegidos por ACL)</h5>
                    <button class="btn btn-sm btn-outline-primary" id="btnRecarregarArquivos">Atualizar Lista</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome do Arquivo</th>
                                <th>Dono</th>
                                <th>Tipo / Tamanho</th>
                                <th>Acesso</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaArquivosCorpo">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Carregando arquivos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/js/zait.ajax.js"></script>
<script src="public/assets/js/ztfBase64File.js"></script>

<script>
$(document).ready(function() {

    // 1. CONVERSÃO DA FOTO DE PERFIL EM BASE64 NO CLIENTE
    $('#inputFoto').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            const base64String = event.target.result;
            $('#imgPreview').attr('src', base64String);
            $('#foto_base64').val(base64String);
        };
        reader.readAsDataURL(file);
    });

    // 2. SUBMISSÃO DO PERFIL VIA JQUERY AJAX
    $('#formPerfil').on('submit', function(e) {
        e.preventDefault();
        const $alert = $('#perfilAlert').addClass('d-none');
        const $btn = $('#btnSalvarPerfil').prop('disabled', true).text('Gravando...');

        ZTF.ajax.submitForm(this)
            .then(function(res) {
                $alert.removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
                if (res.data?.nome) {
                    $('#labelNome').text(res.data.nome);
                }
                $('#perfil-nova-senha').val('');
                $('#perfil-conf-senha').val('');
            })
            .catch(function(err) {
                $alert.removeClass('d-none alert-success').addClass('alert-danger').text(err.message || 'Erro ao atualizar.');
            })
            .finally(function() {
                $btn.prop('disabled', false).text('Salvar Alterações');
            });
    });

    // 3. EVENTO DE SUCESSO DO COMPONENTE .uploadB64: RECARREGA A LISTA DE ARQUIVOS
    $(document).on('upload:success', 'input.uploadB64', function(e, data) {
        carregarArquivos();
    });

    // 4. CARREGAMENTO DA TABELA DE ARQUIVOS
    function carregarArquivos() {
        ZTF.ajax.get('api/arquivos/listar')
            .then(function(res) {
                const $tbody = $('#tabelaArquivosCorpo').empty();
                if (!res.data || res.data.length === 0) {
                    $tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">Nenhum arquivo encontrado.</td></tr>');
                    return;
                }

                res.data.forEach(function(arq) {
                    const isPub = parseInt(arq.publico) === 1;
                    const badgeAcesso = isPub 
                        ? '<span class="badge bg-success">Público</span>' 
                        : '<span class="badge bg-secondary">Privado (ACL)</span>';

                    const tamanhoKB = (parseInt(arq.tamanhoBytes) / 1024).toFixed(1) + ' KB';

                    const row = `
                        <tr>
                            <td><strong>${arq.nomeOriginal}</strong></td>
                            <td class="small">${arq.nomeDono}</td>
                            <td class="small text-muted">${arq.mimeType}<br><small>${tamanhoKB}</small></td>
                            <td>${badgeAcesso}</td>
                            <td>
                                <a href="arquivo/${arq.hash}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    Baixar / Ver
                                </a>
                            </td>
                        </tr>
                    `;
                    $tbody.append(row);
                });
            })
            .catch(function() {
                $('#tabelaArquivosCorpo').html('<tr><td colspan="5" class="text-center text-danger py-3">Erro ao carregar lista de arquivos.</td></tr>');
            });
    }

    $('#btnRecarregarArquivos').on('click', carregarArquivos);
    carregarArquivos();
});
</script>

</body>
</html>
