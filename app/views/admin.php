<?php
use app\core\utils\Url;
$user = $_SESSION['user'] ?? [];
$baseHref = Url::base();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZTFramework - Painel de Administração</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        .user-avatar-sm {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="admin">
            🛡️ ZTFramework <span class="badge bg-danger">Administração</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard" class="btn btn-outline-light btn-sm">&larr; Voltar ao Painel</a>
            <a href="logout" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Painel de Controle Administrativo</h3>
            <p class="text-muted small mb-0">Gerencie usuários, níveis de acesso (grupos) e matriz de permissões granulares.</p>
        </div>
    </div>

    <div id="adminAlert" class="alert d-none"></div>

    <ul class="nav nav-pills mb-4" id="adminTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" id="tab-usuarios-btn" data-bs-toggle="pill" data-bs-target="#tab-usuarios" type="button">
                👥 Gestão de Usuários
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="tab-permissoes-btn" data-bs-toggle="pill" data-bs-target="#tab-permissoes" type="button">
                🔐 Grupos & Permissões
            </button>
        </li>
    </ul>

    <div class="tab-content" id="adminTabsContent">
        <!-- ABA 1: GESTÃO DE USUÁRIOS -->
        <div class="tab-pane fade show active" id="tab-usuarios">
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Usuários Cadastrados no Sistema</h5>
                    <button class="btn btn-sm btn-outline-secondary" id="btnRecarregarUsuarios">Recarregar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Usuário</th>
                                <th>E-mail</th>
                                <th>Níveis / Grupos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaUsuariosCorpo">
                            <tr><td colspan="5" class="text-center py-3 text-muted">Carregando usuários...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 2: GRUPOS & PERMISSÕES -->
        <div class="tab-pane fade" id="tab-permissoes">
            <div class="card card-custom p-4">
                <h5 class="fw-bold mb-2">Matriz de Permissões por Grupo</h5>
                <p class="text-muted small mb-4">Selecione quais ações cada nível de usuário está autorizado a executar no framework.</p>

                <div id="containerGruposPermissoes" class="row g-4">
                    <div class="col-12 text-center text-muted py-4">Carregando matriz de permissões...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA ATRIBUIR NÍVEIS / GRUPOS AO USUÁRIO -->
<div class="modal fade" id="modalEditarGrupos" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Alterar Grupos do Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editUserId">
                <p class="mb-3">Selecione os níveis atribuídos a: <strong id="editUserNome"></strong></p>
                <div id="listaGruposCheckboxes" class="d-flex flex-column gap-2 mb-4"></div>
                <button type="button" class="btn btn-primary w-100 fw-semibold" id="btnSalvarGruposUser">Salvar Grupos</button>
            </div>
        </div>
    </div>
</div>

<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/js/zait.ajax.js"></script>

<script>
$(document).ready(function() {

    let gruposDisponiveis = [];
    let permissoesDisponiveis = [];
    let mapaGrupoPermissoes = {};

    function showAlert(msg, isSuccess) {
        $('#adminAlert').removeClass('d-none alert-danger alert-success')
            .addClass(isSuccess ? 'alert-success' : 'alert-danger')
            .html(msg);
        setTimeout(() => { $('#adminAlert').addClass('d-none'); }, 4000);
    }

    function carregarUsuarios() {
        ZTF.ajax.get('api/admin/usuarios')
            .then(function(res) {
                const $tbody = $('#tabelaUsuariosCorpo').empty();
                if (!res.data || res.data.length === 0) {
                    $tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">Nenhum usuário cadastrado.</td></tr>');
                    return;
                }

                res.data.forEach(function(u) {
                    const avatar = u.foto ? u.foto : 'https://via.placeholder.com/38?text=U';
                    const isAtivo = parseInt(u.status) === 1;
                    const statusBadge = isAtivo 
                        ? '<span class="badge bg-success">Ativo</span>' 
                        : '<span class="badge bg-secondary">Inativo</span>';

                    const btnStatusText = isAtivo ? 'Inativar' : 'Ativar';
                    const btnStatusClass = isAtivo ? 'btn-outline-danger' : 'btn-outline-success';

                    const row = `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="${avatar}" class="user-avatar-sm" alt="Avatar">
                                    <strong>${u.nome}</strong>
                                </div>
                            </td>
                            <td>${u.email}</td>
                            <td><span class="badge bg-primary">${u.grupos || 'Sem grupo'}</span></td>
                            <td>${statusBadge}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-editar-grupos" 
                                            data-id="${u.idUsuario}" 
                                            data-nome="${u.nome}" 
                                            data-grupos="${u.idsGrupos || ''}">
                                        Gerenciar Grupos
                                    </button>
                                    <button class="btn ${btnStatusClass} btn-toggle-status" 
                                            data-id="${u.idUsuario}" 
                                            data-status="${isAtivo ? 0 : 1}">
                                        ${btnStatusText}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    $tbody.append(row);
                });
            })
            .catch(function() {
                $('#tabelaUsuariosCorpo').html('<tr><td colspan="5" class="text-center text-danger py-3">Erro ao carregar usuários.</td></tr>');
            });
    }

    $(document).on('click', '.btn-toggle-status', function() {
        const idUsuario = $(this).data('id');
        const novoStatus = $(this).data('status');

        ZTF.ajax.post('api/admin/usuario/status', { idUsuario: idUsuario, status: novoStatus })
            .then(function(res) {
                showAlert(res.message, true);
                carregarUsuarios();
            })
            .catch(function(err) {
                showAlert(err.message, false);
            });
    });

    $(document).on('click', '.btn-editar-grupos', function() {
        const idUsuario = $(this).data('id');
        const nomeUsuario = $(this).data('nome');
        const idsAtuais = String($(this).data('grupos')).split(',').map(Number);

        $('#editUserId').val(idUsuario);
        $('#editUserNome').text(nomeUsuario);

        const $container = $('#listaGruposCheckboxes').empty();
        gruposDisponiveis.forEach(function(g) {
            const isChecked = idsAtuais.includes(parseInt(g.idGrupo)) ? 'checked' : '';
            const item = `
                <div class="form-check">
                    <input class="form-check-input check-modal-grupo" type="checkbox" value="${g.idGrupo}" id="grp_${g.idGrupo}" ${isChecked}>
                    <label class="form-check-label fw-semibold" for="grp_${g.idGrupo}">
                        ${g.grupo} <small class="text-muted fw-normal">(${g.descricao || ''})</small>
                    </label>
                </div>
            `;
            $container.append(item);
        });

        const modal = new bootstrap.Modal('#modalEditarGrupos');
        modal.show();
    });

    $('#btnSalvarGruposUser').on('click', function() {
        const idUsuario = $('#editUserId').val();
        const gruposSelecionados = [];
        $('.check-modal-grupo:checked').each(function() {
            gruposSelecionados.push(parseInt($(this).val()));
        });

        ZTF.ajax.post('api/admin/usuario/grupos', { idUsuario: idUsuario, grupos: JSON.stringify(gruposSelecionados) })
            .then(function(res) {
                showAlert(res.message, true);
                bootstrap.Modal.getInstance('#modalEditarGrupos').hide();
                carregarUsuarios();
            })
            .catch(function(err) {
                showAlert(err.message, false);
            });
    });

    function carregarMatriz() {
        ZTF.ajax.get('api/admin/grupos')
            .then(function(res) {
                gruposDisponiveis = res.data.grupos || [];
                permissoesDisponiveis = res.data.permissoes || [];
                mapaGrupoPermissoes = res.data.grupoPermissoes || {};

                const $container = $('#containerGruposPermissoes').empty();

                gruposDisponiveis.forEach(function(g) {
                    const idG = parseInt(g.idGrupo);
                    const permsGrupo = mapaGrupoPermissoes[idG] || [];

                    let permsHtml = '';
                    permissoesDisponiveis.forEach(function(p) {
                        const idP = parseInt(p.idPermissao);
                        const isChecked = permsGrupo.includes(idP) ? 'checked' : '';
                        permsHtml += `
                            <div class="form-check mb-2">
                                <input class="form-check-input perm-check" type="checkbox" data-grupo="${idG}" value="${idP}" id="gp_${idG}_${idP}" ${isChecked}>
                                <label class="form-check-label small" for="gp_${idG}_${idP}">
                                    <code>${p.permissao}</code> - ${p.descricao || ''}
                                </label>
                            </div>
                        `;
                    });

                    const card = `
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border rounded-3 p-3 shadow-sm bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h6 class="fw-bold mb-0 text-primary">${g.grupo.toUpperCase()}</h6>
                                    ${parseInt(g.isAdmin) === 1 ? '<span class="badge bg-danger">SuperAdmin</span>' : '<span class="badge bg-secondary">Nível Padrão</span>'}
                                </div>
                                <div class="mb-3">${permsHtml}</div>
                                <button class="btn btn-sm btn-outline-primary w-100 btn-salvar-perm-grupo" data-grupo="${idG}">
                                    Salvar Permissões
                                </button>
                            </div>
                        </div>
                    `;
                    $container.append(card);
                });
            });
    }

    $(document).on('click', '.btn-salvar-perm-grupo', function() {
        const idGrupo = $(this).data('grupo');
        const permsSelecionadas = [];
        $(`.perm-check[data-grupo="${idGrupo}"]:checked`).each(function() {
            permsSelecionadas.push(parseInt($(this).val()));
        });

        ZTF.ajax.post('api/admin/grupo/permissoes', { idGrupo: idGrupo, permissoes: JSON.stringify(permsSelecionadas) })
            .then(function(res) {
                showAlert(res.message, true);
            })
            .catch(function(err) {
                showAlert(err.message, false);
            });
    });

    $('#btnRecarregarUsuarios').on('click', carregarUsuarios);

    carregarUsuarios();
    carregarMatriz();
});
</script>

</body>
</html>
