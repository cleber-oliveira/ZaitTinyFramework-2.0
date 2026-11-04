<?php
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseHref = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '') ? '/' : rtrim($scriptDir, '/') . '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zait Tiny Framework 2.0 - Alta Performance & Segurança</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a369d 100%);
            color: white;
            padding: 90px 0 80px;
        }
        .feature-box {
            padding: 30px;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transition: transform 0.2s;
            height: 100%;
        }
        .feature-box:hover {
            transform: translateY(-4px);
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
        }
    </style>
</head>
<body class="bg-light">

<!-- Barra Superior (Navbar Responsiva) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="./">
            🚀 Zait Tiny Framework <span class="badge bg-primary text-white fs-6">2.0</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="#recursos">Recursos</a></li>
                <li class="nav-item"><a class="nav-link" href="#seguranca">Segurança Tripla</a></li>
                <li class="nav-item"><a class="nav-link" href="#base64">Uploads Base64</a></li>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-bold" href="public/manual.html" target="_blank">
                        📘 Manual de Uso (HTML)
                    </a>
                </li>
            </ul>
            <div class="d-flex gap-2">
                <a href="public/manual.html" target="_blank" class="btn btn-outline-warning px-3 fw-semibold shadow-sm d-none d-md-inline-block">
                    📘 Manual Didático
                </a>
                <button type="button" class="btn btn-primary px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#authModal">
                    Minha Conta / Acessar
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-section text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <span class="badge bg-light text-primary px-3 py-2 fw-bold text-uppercase mb-3 shadow-sm">Alta Performance &bull; Segurança Nativa &bull; Multi-SGBD</span>
                <h1 class="display-4 fw-bold mb-3">Zait Tiny Framework 2.0</h1>
                <p class="lead mb-4 opacity-90">
                    Construído com PHP 8.2+, controle granular de acesso (ACL), proteção criptográfica com <strong>BCrypt + Salt + Pepper</strong>, uploads em <strong>Base64</strong> e suporte a <strong>MySQL, PostgreSQL, Oracle e DB2</strong>.
                </p>
                <div class="d-flex justify-content-center flex-wrap gap-3">
                    <button class="btn btn-light btn-lg px-4 fw-bold text-primary shadow" data-bs-toggle="modal" data-bs-target="#authModal">
                        Começar Agora / Login
                    </button>
                    <a href="public/manual.html" target="_blank" class="btn btn-warning btn-lg px-4 fw-bold shadow">
                        📘 Manual de Uso (HTML)
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Recursos em Destaque -->
<section id="recursos" class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Projetado para Produtividade e Escalabilidade</h2>
            <p class="text-muted">Conheça os diferenciais técnicos do Zait Tiny Framework 2.0</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="fs-1 text-primary mb-3">🛡️</div>
                    <h4 class="fw-bold">Segurança com Pepper</h4>
                    <p class="text-muted">Senhas protegidas por HMAC-SHA256 Pepper e BCrypt. Mesmo em vazamento de dump do banco, os dados permanecem invioláveis.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="fs-1 text-primary mb-3">📦</div>
                    <h4 class="fw-bold">Uploads em Base64</h4>
                    <p class="text-muted">Fotos e documentos são convertidos em Base64 com controle refinado de proprietário (idDono) e permissões por grupos.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="fs-1 text-primary mb-3">⚡</div>
                    <h4 class="fw-bold">Roteador com ACL</h4>
                    <p class="text-muted">Verificação de permissões em memória sem consultas redundantes ao banco de dados durante as requisições.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="fs-1 text-primary mb-3">🗄️</div>
                    <h4 class="fw-bold">Suporte Multi-Banco</h4>
                    <p class="text-muted">Pronto para MySQL/MariaDB, com drivers configurados para PostgreSQL, Oracle (OCI) e IBM DB2 via PDO unificado.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="fs-1 text-primary mb-3">📬</div>
                    <h4 class="fw-bold">E-mails (Mailtrap e outros)</h4>
                    <p class="text-muted">Compatível com qualquer SMTP. Sugestão prática com Mailtrap para testes em sandbox, além de SendGrid, SES, Gmail ou simulação local em logs.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="fs-1 text-primary mb-3">📲</div>
                    <h4 class="fw-bold">SMS e OTP (Twilio e outros)</h4>
                    <p class="text-muted">Arquitetura aberta para mensageria. Sugestão com Twilio para envio de SMS e tokens 2FA/OTP, adaptável a Z-API, WhatsApp ou AWS SNS.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">
    <div class="container">
        <p class="mb-1 fw-bold">Zait Tiny Framework 2.0 (ZTFramework)</p>
        <small class="opacity-75">Desenvolvido pelos Professores Drº Cleber S. Oliveira e Drª Marcia Aparecida Silva Bissaco &bull; PHP 8.2+</small>
    </div>
</footer>

<!-- MODAL BOOTSTRAP 5: AUTENTICAÇÃO (ENTRAR, CADASTRAR, RECUPERAR SENHA) -->
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="authModalLabel">Área de Acesso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-tabs nav-fill mb-3" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-login-btn" data-bs-toggle="tab" data-bs-target="#tab-login" type="button" role="tab">Entrar</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-cadastro-btn" data-bs-toggle="tab" data-bs-target="#tab-cadastro" type="button" role="tab">Cadastrar</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-recuperar-btn" data-bs-toggle="tab" data-bs-target="#tab-recuperar" type="button" role="tab">Lembrar Senha</button>
                    </li>
                </ul>

                <div id="modalAlert" class="alert d-none" role="alert"></div>

                <div class="tab-content" id="authTabsContent">
                    <!-- ABA 1: ENTRAR (LOGIN) -->
                    <div class="tab-pane fade show active" id="tab-login" role="tabpanel">
                        <form id="formLogin" action="login" method="POST">
                            <div class="mb-3">
                                <label for="login-email" class="form-label fw-semibold">E-mail</label>
                                <input type="email" class="form-control" id="login-email" name="email" required placeholder="admin@ztframework.local">
                            </div>
                            <div class="mb-3">
                                <label for="login-senha" class="form-label fw-semibold">Senha</label>
                                <input type="password" class="form-control" id="login-senha" name="senha" required placeholder="••••••••">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btnLogin">Acessar Painel</button>
                        </form>
                    </div>

                    <!-- ABA 2: CADASTRAR (SENHA ENVIADA AUTOMATICAMENTE POR E-MAIL) -->
                    <div class="tab-pane fade" id="tab-cadastro" role="tabpanel">
                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <strong>Nota:</strong> Sua senha inicial será gerada automaticamente com alta entropia e enviada para seu e-mail. Você poderá alterá-la no painel após entrar.
                        </div>
                        <form id="formCadastro" action="cadastro" method="POST">
                            <div class="mb-3">
                                <label for="cad-nome" class="form-label fw-semibold">Nome Completo</label>
                                <input type="text" class="form-control" id="cad-nome" name="nome" required placeholder="Seu nome">
                            </div>
                            <div class="mb-3">
                                <label for="cad-email" class="form-label fw-semibold">E-mail</label>
                                <input type="email" class="form-control" id="cad-email" name="email" required placeholder="seuemail@empresa.com">
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" id="btnCadastro">Gerar Acesso e Enviar Senha</button>
                        </form>
                    </div>

                    <!-- ABA 3: LEMBRAR SENHA (RECUPERAR) -->
                    <div class="tab-pane fade" id="tab-recuperar" role="tabpanel">
                        <p class="text-muted small">Informe seu e-mail cadastrado para enviarmos uma nova senha de acesso.</p>
                        <form id="formRecuperar" action="recuperar-senha" method="POST">
                            <div class="mb-3">
                                <label for="rec-email" class="form-label fw-semibold">E-mail Cadastrado</label>
                                <input type="email" class="form-control" id="rec-email" name="email" required placeholder="seuemail@empresa.com">
                            </div>
                            <button type="submit" class="btn btn-warning w-100 py-2 fw-semibold" id="btnRecuperar">Enviar Nova Senha</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts jQuery, Bootstrap 5 Bundle e ZTF.ajax -->
<script src="public/assets/js/jquery.min.js"></script>
<script src="public/assets/js/bootstrap.bundle.min.js"></script>
<script src="public/assets/js/zait.ajax.js"></script>

<script>
$(document).ready(function() {

    function showAlert(msg, isSuccess) {
        const $alert = $('#modalAlert');
        $alert.removeClass('d-none alert-danger alert-success')
              .addClass(isSuccess ? 'alert-success' : 'alert-danger')
              .html(msg);
    }

    function hideAlert() {
        $('#modalAlert').addClass('d-none');
    }

    $('.nav-link').on('shown.bs.tab', function() {
        hideAlert();
    });

    // 1. Submit Login via jQuery AJAX
    $('#formLogin').on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        const $btn = $('#btnLogin');
        $btn.prop('disabled', true).text('Autenticando...');

        ZTF.ajax.submitForm(this)
            .then(function(res) {
                if (res.status) {
                    showAlert('Autenticado com sucesso! Redirecionando para o painel...', true);
                    setTimeout(function() {
                        window.location.href = res.data?.redirect || 'dashboard';
                    }, 700);
                } else {
                    showAlert(res.message || 'Credenciais inválidas.', false);
                    $btn.prop('disabled', false).text('Acessar Painel');
                }
            })
            .catch(function(err) {
                showAlert(err.message || 'Erro na comunicação com o servidor.', false);
                $btn.prop('disabled', false).text('Acessar Painel');
            });
    });

    // 2. Submit Cadastro via jQuery AJAX
    $('#formCadastro').on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        const $btn = $('#btnCadastro');
        $btn.prop('disabled', true).text('Registrando...');
        const $form = $(this);

        ZTF.ajax.submitForm(this)
            .then(function(res) {
                if (res.status) {
                    let msg = res.message;
                    if (res.data?.simulated_password) {
                        msg += '<br><strong>Senha inicial gerada:</strong> <code>' + res.data.simulated_password + '</code>';
                    }
                    showAlert(msg, true);
                    $form[0].reset();
                    setTimeout(function() {
                        bootstrap.Tab.getOrCreateInstance('#tab-login-btn').show();
                    }, 3500);
                } else {
                    showAlert(res.message || 'Erro ao realizar cadastro.', false);
                }
            })
            .catch(function(err) {
                showAlert(err.message || 'Falha ao cadastrar.', false);
            })
            .finally(function() {
                $btn.prop('disabled', false).text('Gerar Acesso e Enviar Senha');
            });
    });

    // 3. Submit Recuperação de Senha via jQuery AJAX
    $('#formRecuperar').on('submit', function(e) {
        e.preventDefault();
        hideAlert();

        const $btn = $('#btnRecuperar');
        $btn.prop('disabled', true).text('Enviando...');
        const $form = $(this);

        ZTF.ajax.submitForm(this)
            .then(function(res) {
                if (res.status) {
                    let msg = res.message;
                    if (res.data?.simulated_password) {
                        msg += '<br><strong>Nova senha gerada:</strong> <code>' + res.data.simulated_password + '</code>';
                    }
                    showAlert(msg, true);
                    $form[0].reset();
                } else {
                    showAlert(res.message || 'Falha ao recuperar senha.', false);
                }
            })
            .catch(function(err) {
                showAlert(err.message || 'Erro ao solicitar recuperação.', false);
            })
            .finally(function() {
                $btn.prop('disabled', false).text('Enviar Nova Senha');
            });
    });

});
</script>

</body>
</html>
