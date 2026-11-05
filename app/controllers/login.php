<?php
use app\core\utils\Url;
$baseHref = Url::base();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>ZTFramework - Login</title>
    <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') ?>">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 360px; }
        h2 { margin-top: 0; color: #1c1e21; font-size: 24px; text-align: center; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-size: 14px; color: #606770; font-weight: 600; }
        input { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #ccd0d5; border-radius: 6px; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #1877f2; border: none; border-radius: 6px; color: white; font-weight: bold; font-size: 16px; cursor: pointer; }
        button:hover { background: #166fe5; }
        .alert { display: none; padding: 10px; margin-bottom: 16px; border-radius: 6px; font-size: 14px; }
        .alert-error { background: #ffebe8; color: #c00; border: 1px solid #dd3c10; }
        .alert-success { background: #e7f3ff; color: #1877f2; border: 1px solid #1877f2; }
        .link-home { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #606770; text-decoration: none; }
        .link-home:hover { text-decoration: underline; color: #1877f2; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Entrar no Sistema</h2>
        <div id="alertBox" class="alert"></div>
        <form id="loginForm" method="POST" action="login">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="admin@empresa.com">
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="••••••••">
            </div>
            <button type="submit" id="btnSubmit">Entrar</button>
            <a href="./" class="link-home">&larr; Voltar à Página Inicial</a>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('alertBox');
            const btnSubmit = document.getElementById('btnSubmit');
            alertBox.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.innerText = 'Aguarde...';

            const formData = new FormData(this);

            try {
                const response = await fetch('login', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status) {
                    alertBox.className = 'alert alert-success';
                    alertBox.innerText = result.message || 'Login efetuado com sucesso!';
                    alertBox.style.display = 'block';
                    setTimeout(() => {
                        let redirectUrl = result.data?.redirect || 'dashboard';
                        if (redirectUrl.startsWith('/')) {
                            redirectUrl = redirectUrl.substring(1);
                        }
                        window.location.href = redirectUrl;
                    }, 800);
                } else {
                    alertBox.className = 'alert alert-error';
                    alertBox.innerText = result.message || 'Falha ao autenticar.';
                    alertBox.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.innerText = 'Entrar';
                }
            } catch (err) {
                alertBox.className = 'alert alert-error';
                alertBox.innerText = 'Erro na comunicação com o servidor.';
                alertBox.style.display = 'block';
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Entrar';
            }
        });
    </script>
                    }, 800);
                } else {
                    alertBox.className = 'alert alert-error';
                    alertBox.innerText = result.message || 'Falha ao autenticar.';
                    alertBox.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.innerText = 'Entrar';
                }
            } catch (err) {
                alertBox.className = 'alert alert-error';
                alertBox.innerText = 'Erro na comunicação com o servidor.';
                alertBox.style.display = 'block';
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Entrar';
            }
        });
    </script>
</body>
</html>
