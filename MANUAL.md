# Zait Tiny Framework 2.0 — Manual Oficial & Apostila do Desenvolvedor

```
================================================================================
                    Zait Tiny Framework 2.0 (PHP 8.2+)
================================================================================
Autores Oficiais:
  - Professor Drº Cleber S. Oliveira
  - Professora Drª Marcia Aparecida Silva Bissaco

Padrão de Qualidade:
  - Strict Types (declare(strict_types=1);)
  - UTF-8 (utf8mb4) de ponta a ponta
  - Multi-SGBD: MySQL, PostgreSQL, Oracle e IBM DB2
================================================================================
```

---

## Sumário
1. [Visão Geral & Filosofia](#1-visão-geral--filosofia)
2. [Estrutura de Diretórios](#2-estrutura-de-diretórios)
3. [Autoloader Recursivo Inteligente](#3-autoloader-recursivo-inteligente)
4. [Roteamento Declarativo Completo (Router 2.0)](#4-roteamento-declarativo-completo-router-20)
5. [Suporte Multi-Banco de Dados (MySQL, PostgreSQL, Oracle, DB2)](#5-suporte-multi-banco-de-dados)
6. [Auto-Provisionamento no Início do Router](#6-auto-provisionamento-no-início-do-router)
7. [Query Builder Fluent (`DBQuery`)](#7-query-builder-fluent-dbquery)
8. [Segurança Criptográfica & Autenticação (BCrypt + Salt + Pepper)](#8-segurança-criptográfica--autenticação)
9. [Uploads em Base64 & Lista de Controle de Acesso (ACL)](#9-uploads-em-base64--acl-de-arquivos)
10. [Sugestões de Integração: E-mails (Mailtrap e outros)](#10-sugestões-de-integração-e-mails-mailtrap-e-outros)
11. [Sugestões de Integração: SMS & OTP (Twilio e outros)](#11-sugestões-de-integração-sms--otp-twilio-e-outros)
12. [Apostila Prática: Construindo um Módulo Completo Passo a Passo](#12-apostila-prática-construindo-um-módulo-completo)
13. [Suíte de Testes Automatizados (8 Suítes / 100% Cobertura)](#13-suíte-de-testes-automatizados)

---

## 1. Visão Geral & Filosofia

O **Zait Tiny Framework 2.0** foi concebido pelos professores **Drº Cleber S. Oliveira** e **Drª Marcia Aparecida Silva Bissaco** com foco em **altíssimo desempenho, clareza didática, segurança nativa e zero dependências pesadas de terceiros**.

### Pilares Fundamentais:
- **Tipagem Forte Estrita**: `declare(strict_types=1);` em todos os scripts do núcleo.
- **Auto-Provisionamento no Boot**: Se o banco de dados configurado não existir, o framework o cria e popula sua estrutura (DDL + DML) automaticamente no primeiro acesso.
- **Isolamento Criptográfico**: Senhas protegidas com BCrypt + Salt + Pepper HMAC-SHA256 (eliminando o limite de 72 bytes e protegendo contra vazamentos de dump de banco).
- **Arquitetura 100% Base64**: Uploads de fotos e documentos tratados em Base64 com ACL individual e por grupos.
- **AJAX Transparente**: Requisições assíncronas do jQuery recebem estritamente JSON padronizado com HTTP Status Codes adequados (200, 400, 401, 403, 404, 500).

---

## 2. Estrutura de Diretórios

```text
ZTFramework/
├── app/
│   ├── controllers/         # Controladores da aplicação (ex: PerfilController, AdminController, ChamadoController)
│   ├── core/
│   │   ├── database/        # DBConnection, DBQuery, DatabaseProvisioner, script.sql
│   │   └── utils/           # Router, Config, PasswordSecurity, CodeGenerator, Mail, Sms, Sanitize, JsonResponse
│   ├── documentation/       # Diagramas de arquitetura (PlantUML) e manuais técnicos
│   ├── etc/
│   │   ├── config.json      # Configurações globais (bancos, e-mail, sms, pepper, mimes da publicZone)
│   │   └── routes.json      # Catálogo centralizado de rotas, verbos e regras de ACL
│   ├── models/              # Modelos de domínio integrados ao DBQuery (ex: Usuario.php)
│   ├── storage/             # Armazenamento privado (uploads base64, logs de e-mail/sms, sessões)
│   └── views/               # Telas do sistema (home.php, main.php, admin.php, chamados.php e errors/)
├── public/
│   ├── assets/              # Arquivos públicos (css, js, imagens)
│   └── manual.html          # Manual interativo e didático do desenvolvedor em HTML
├── tests/                   # 8 suítes integradas de testes automatizados
├── autoload.php             # Autoloader recursivo com suporte a Composer
└── index.php                # Front Controller (Ponto de Entrada Único)
```

---

## 3. Autoloader Recursivo Inteligente

O arquivo `autoload.php` resolve automaticamente qualquer classe do sistema:
1. **Resolução Direta PSR-4**: Traduz `app\controllers\MeuController` para `app/controllers/MeuController.php`.
2. **Fallback Recursivo Case-Insensitive**: Percorre a árvore de `app/` no Linux localizando classes dinamicamente.

---

## 4. Roteamento Declarativo Completo (Router 2.0)

Todas as rotas são declaradas no arquivo `app/etc/routes.json`. O Router 2.0 oferece um conjunto exaustivo de possibilidades:

### 4.1 Anatomia Completa de uma Rota
```json
{
  "/exemplo/completo/{id}": {
    "get": {
      "responseType": "json",
      "controller": "MeuController",
      "action": "detalhes",
      "session": {
        "required": true,
        "keys": ["idUsuario", "email"]
      },
      "users": [1, 2, 5],
      "groups": ["admin", "gerente"],
      "permissions": ["usuarios.view", "relatorios.export"],
      "cors": {
        "enable": true,
        "origins": ["https://meusite.com"]
      }
    }
  }
}
```

### 4.2 Padrões de URI (Matching Exato e Dinâmico)
- **Rotas Fixas**: `"/dashboard"`, `"/api/upload"`, `"/login"`.
- **Rotas com Parâmetros Dinâmicos `{param}`**:
  - `"/arquivo/{hash}"` &rarr; Injeta o valor em `$_GET['hash']`.
  - `"/usuario/{idUsuario}/perfil"` &rarr; Injeta em `$_GET['idUsuario']`.
  - `"/pedidos/{ano}/{mes}"` &rarr; Injeta em `$_GET['ano']` e `$_GET['mes']`.

### 4.3 Verbos HTTP
- `get`: Visualização, entrega de views HTML, arquivos e JSON.
- `post`: Submissões de formulário, autenticação e uploads.
- `put`: Atualizações completas ou modificações idempotentes.
- `delete`: Exclusões lógicas ou físicas.
- `head`: Mapeado internamente para `GET`, retornando apenas os cabeçalhos HTTP sem corpo.
- `options`: Interceptado automaticamente para responder ao preflight CORS com HTTP 204.
- *Verbo não declarado*: Se a rota existir mas o verbo não, responde automaticamente com **HTTP 405 Method Not Allowed**.

### 4.4 Os 4 Alvos de Execução (Dispatch Handlers)
1. **Script / View Direta (`"path"`)**:
   ```json
   "path": "app/views/home.php"
   ```
   Inclui o arquivo diretamente, disponibilizando as variáveis `$request`, `$session` e `$route`.
2. **Invocação de Controller & Action (`"controller"` + `"action"`)**:
   ```json
   "controller": "AdminController",
   "action": "listarUsuarios"
   ```
   Instancia `app\controllers\AdminController` e executa o método informado.
3. **Consulta Declarativa SQL (`"query"`)**:
   ```json
   "query": {
     "sql": "SELECT idUsuario, nome FROM usuarios WHERE status = ?",
     "params": ["status"]
   }
   ```
   Executa o SQL parametrizado diretamente no banco **sem escrever código PHP**, devolvendo JSON estruturado!
4. **Consulta Declarativa DBQuery (`"dbquery"`)**:
   ```json
   "dbquery": {
     "tableName": "grupos",
     "fieldsName": ["idGrupo", "grupo"],
     "where": { "isAdmin": 0 }
   }
   ```

### 4.5 Controle de Acesso e ACL (Camada de Sessão)
- **`session.required: true`**: Bloqueia visitantes deslogados com **HTTP 401** (`ERR_UNAUTHORIZED`).
- **`session.keys: ["idUsuario"]`**: Exige chaves mandatórias em `$_SESSION['user']` (`ERR_INVALID_SESSION`).
- **`users: [1, 2, 7]`**: Whitelist de IDs autorizados (`ERR_FORBIDDEN_USER`).
- **`groups: ["admin", "gerente"]`**: Exige que o usuário pertença ao grupo indicado (`ERR_FORBIDDEN_GROUP`). *SuperAdmins (`admin`) possuem bypass garantido*.
- **`permissions: ["usuarios.edit"]`**: Exige permissão específica na matriz do usuário (`ERR_FORBIDDEN_PERMISSION`).

### 4.6 Respostas Estritas em JSON para AJAX
Se o cabeçalho `X-Requested-With: XMLHttpRequest` ou `Accept: application/json` estiver presente, **qualquer erro ou bloqueio (401, 403, 404, 500) responde obrigatoriamente em JSON**, impedindo telas de erro em HTML truncadas no JavaScript.

---

## 5. Suporte Multi-Banco de Dados

O componente `DBConnection` conecta a 4 grandes bancos corporativos através de PDO:
- **MySQL / MariaDB**: `driver: "mysql"` (Porta 3306)
- **PostgreSQL**: `driver: "pgsql"` (Porta 5432)
- **Oracle Database**: `driver: "oci"` (Porta 1521, Easy Connect `//host:port/service`)
- **IBM DB2**: `driver: "ibm"` (Porta 50000, ODBC/IBM CLI)

Alterne entre eles simplesmente alterando `database.default` no `config.json`.

---

## 6. Auto-Provisionamento no Início do Router

No boot de cada requisição, o `DatabaseProvisioner`:
1. Valida se a base de dados configurada existe; caso não exista, executa `CREATE DATABASE IF NOT EXISTS ...` em `utf8mb4`.
2. Confere as tabelas essenciais; se ausentes, executa o script oficial `app/core/database/script.sql` (DDL + DML com dados semente).
3. Se o banco estiver inacessível por credenciais incorretas, exibe tela de diagnóstico sem vazar senhas.

---

## 7. Query Builder Fluent (`DBQuery`)

```php
use app\core\database\DBQuery;

// SELECT com filtro e primeiro registro:
$user = (new DBQuery('usuarios'))
    ->addCondition('AND', 'email', '=', $email)
    ->first();

// JOINs múltiplos:
$dados = (new DBQuery('chamados c', 'c.*, u.nome AS solicitante'))
    ->joinRaw('INNER JOIN usuarios u ON u.idUsuario = c.idUsuario')
    ->orderBy('c.idChamado', 'DESC')
    ->select();

// INSERT:
$id = (new DBQuery('chamados'))->insert([
    'idUsuario' => $idUser,
    'titulo'    => $titulo,
    'status'    => 'aberto'
]);

// UPDATE e DELETE exigem WHERE obrigatoriamente (trava de segurança):
(new DBQuery('chamados'))
    ->addCondition('AND', 'idChamado', '=', 10)
    ->update(['status' => 'fechado']);
```

---

## 8. Segurança Criptográfica & Autenticação

$$\text{Hash} = \text{BCrypt}(\text{HMAC-SHA256}(\text{senha}, \text{Pepper}), \text{Salt})$$

- **Proteção contra Dump**: Se a base for vazada, a ausência do Pepper impede ataques offline com GPUs.
- **Fim do Limite de 72 Bytes**: Senhas com mais de 80 caracteres são normalizadas em 64 caracteres hexadecimais únicos antes do BCrypt.

---

## 9. Uploads em Base64 & ACL de Arquivos

Campos com a classe `.uploadB64` convertem automaticamente arquivos em Base64 no cliente:
```html
<input type="file" class="form-control uploadB64" data-target-base64="#base64Data">
<input type="hidden" id="base64Data" name="arquivo_base64">
```
No backend, o arquivo é gravado no storage privado com ACL: `idDono`, `publico`, `gruposPermitidos` e `usuariosPermitidos`. Acesso não autorizado recebe **HTTP 403**.

---

## 10. Sugestões de Integração: E-mails (Mailtrap e outros)

O ZTFramework é agnóstico a provedores SMTP.
- **Sugestão para Testes**: **Mailtrap** (`sandbox.smtp.mailtrap.io`, porta 2525) para capturar e inspecionar e-mails com segurança em ambiente de desenvolvimento.
- **Outros Provedores Suportados**: SendGrid, Amazon SES, Brevo, Gmail ou simulação local em `app/storage/logs/mail.log`.

---

## 11. Sugestões de Integração: SMS & OTP (Twilio e outros)

A classe `Sms` permite envio de mensagens e tokens de 2 fatores:
- **Sugestão Prática**: **Twilio REST API** (`Sms::send($to, $message)`).
- **Outros Gateways**: Adaptável a Z-API, WhatsApp Business API, AWS SNS ou modo de simulação em `app/storage/logs/sms.log`.

---

## 12. Apostila Prática: Construindo um Módulo Completo

### Passo 1: Criar a Tabela no Banco (DDL)
```sql
CREATE TABLE IF NOT EXISTS `chamados` (
    `idChamado` INT AUTO_INCREMENT PRIMARY KEY,
    `idUsuario` INT NOT NULL,
    `titulo` VARCHAR(200) NOT NULL,
    `descricao` TEXT DEFAULT NULL,
    `status` ENUM('aberto', 'em_andamento', 'fechado') NOT NULL DEFAULT 'aberto',
    `dataAbertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_chamados_user` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Passo 2: Declarar as Rotas em `app/etc/routes.json`
```json
{
  "/chamados": {
    "get": {
      "responseType": "html",
      "controller": "ChamadoController",
      "action": "index",
      "session": { "required": true, "keys": ["idUsuario"] }
    }
  },
  "/api/chamados/novo": {
    "post": {
      "responseType": "json",
      "controller": "ChamadoController",
      "action": "criar",
      "session": { "required": true, "keys": ["idUsuario"] }
    }
  }
}
```

### Passo 3: Criar o Controller (`app/controllers/ChamadoController.php`)
```php
<?php
declare(strict_types=1);

namespace app\controllers;

use app\core\database\DBQuery;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;

class ChamadoController {
    public function index(): void {
        $chamados = (new DBQuery('chamados c', 'c.*, u.nome AS solicitante'))
            ->joinRaw('INNER JOIN usuarios u ON u.idUsuario = c.idUsuario')
            ->orderBy('c.idChamado', 'DESC')
            ->select();

        require dirname(__DIR__) . '/views/chamados.php';
    }

    public function criar(): void {
        $titulo    = Sanitize::string($_POST['titulo'] ?? '');
        $descricao = Sanitize::string($_POST['descricao'] ?? '');
        $idUsuario = (int)($_SESSION['user']['idUsuario'] ?? 0);

        if (empty($titulo)) {
            JsonResponse::error(400, 'ERR_EMPTY_TITLE', 'O título é obrigatório.');
            return;
        }

        $id = (new DBQuery('chamados'))->insert([
            'idUsuario' => $idUsuario,
            'titulo'    => $titulo,
            'descricao' => $descricao,
            'status'    => 'aberto'
        ]);

        JsonResponse::success([
            'idChamado'    => $id,
            'titulo'       => $titulo,
            'solicitante'  => $_SESSION['user']['nome'] ?? 'Eu',
            'dataAbertura' => date('d/m/Y H:i')
        ], 'Chamado registrado com sucesso!');
    }
}
```

### Passo 4: Criar a View HTML com Bootstrap 5 (`app/views/chamados.php`)
```html
<div class="container my-5">
    <div class="d-flex justify-content-between mb-4">
        <h2>Central de Chamados</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoChamado">+ Abrir Chamado</button>
    </div>
    <table class="table table-hover">
        <thead>
            <tr><th># ID</th><th>Título</th><th>Solicitante</th><th>Data</th><th>Status</th></tr>
        </thead>
        <tbody id="listaChamadosBody">
            <!-- Linhas renderizadas pelo PHP e dinâmicas pelo AJAX -->
        </tbody>
    </table>
</div>
```

### Passo 5: O JavaScript com jQuery AJAX
```javascript
$('#formNovoChamado').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'api/chamados/novo',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(res) {
        if (res.status) {
            const d = res.data;
            const linha = `<tr><td>#${d.idChamado}</td><td>${d.titulo}</td><td>${d.solicitante}</td><td>${d.dataAbertura}</td><td><span class="badge bg-warning text-dark">Aberto</span></td></tr>`;
            $('#listaChamadosBody').prepend(linha);
            bootstrap.Modal.getInstance($('#modalNovoChamado')[0]).hide();
        }
    });
});
```

---

## 13. Suíte de Testes Automatizados

Execute todas as 8 suítes com:
```bash
php tests/run_all.php
```
Aprovação de 100% cobrindo Config, Router, DBQuery, BCrypt com Pepper, Mail, AJAX Mimes, Fluxo Completo de Negócio e Suíte de Segurança OWASP Top 10.

---

> **Zait Tiny Framework 2.0** — Desenvolvido pelos Professores **Drº Cleber S. Oliveira** e **Drª Marcia Aparecida Silva Bissaco**.
