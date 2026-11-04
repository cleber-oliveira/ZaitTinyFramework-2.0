# ZTFramework 2.0 — Manual Oficial & Guia Prático do Desenvolvedor

```
================================================================================
                           ZTFramework 2.0 (PHP 8.2+)
================================================================================
Autores Oficiais:
  - Professor Drº Cleber S. Oliveira
  - Professora Drª Marcia Aparecida Silva Bissaco

Padrão de Código:
  - K&R Style (Ken Thompson & Dennis Ritchie)
  - Strict Types (declare(strict_types=1);)
  - UTF-8 (utf8mb4) de ponta a ponta
================================================================================
```

---

## Sumário
1. [Visão Geral & Filosofia](#1-visão-geral--filosofia)
2. [Estrutura de Diretórios](#2-estrutura-de-diretórios)
3. [Autoloader Recursivo Inteligente](#3-autoloader-recursivo-inteligente)
4. [Configurações Globais (`config.json`)](#4-configurações-globais-configjson)
5. [Roteamento Avançado (`routes.json` & `Router.php`)](#5-roteamento-avançado-routesjson--routerphp)
6. [Banco de Dados & Auto-Provisionamento](#6-banco-de-dados--auto-provisionamento)
7. [Query Builder Fluent (`DBQuery`)](#7-query-builder-fluent-dbquery)
8. [Segurança Criptográfica & Autenticação (BCrypt + Salt + Pepper)](#8-segurança-criptográfica--autenticação)
9. [Uploads em Base64 & Lista de Controle de Acesso (ACL)](#9-uploads-em-base64--acl-de-arquivos)
10. [Frontend Responsivo (Bootstrap 5 & jQuery AJAX)](#10-frontend-responsivo-bootstrap-5--jquery-ajax)
11. [Tutorial Prático: Construindo um Módulo Completo Passo a Passo](#11-tutorial-prático-construindo-um-módulo-completo)
12. [Suíte de Testes Automatizados](#12-suíte-de-testes-automatizados)

---

## 1. Visão Geral & Filosofia

O **ZTFramework 2.0** foi concebido pelos professores **Drº Cleber S. Oliveira** e **Drª Marcia Aparecida Silva Bissaco** com uma premissa clara: **máximo desempenho, clareza didática, altíssima segurança nativa e zero dependências pesadas de terceiros**.

### Características Fundamentais:
- **Padrão K&R (Kernighan & Ritchie / Ken Thompson)**: abertura de chaves `{` na mesma linha da declaração da classe, método e blocos de controle.
- **Tipagem Forte Estrita**: `declare(strict_types=1);` em todos os scripts do núcleo.
- **Auto-Provisionamento no Boot**: Se o banco de dados configurado não existir, o framework o cria e popula sua estrutura (DDL + DML) automaticamente no primeiro acesso.
- **Isolamento e Segurança Criptográfica**: Senhas protegidas com BCrypt + Salt + Pepper HMAC-SHA256 (eliminando o limite clássico de 72 bytes e protegendo contra vazamentos de banco).
- **Arquitetura 100% Base64**: Todos os uploads (incluindo fotos de perfil e documentos privados) são tratados em Base64 com ACL individual e por grupos.
- **AJAX Transparente**: Requisições assíncronas do jQuery recebem estritamente JSON padronizado com HTTP Status Codes adequados (200, 400, 401, 403, 404, 500).

```
+-------------------------------------------------------------------------+
|                              NAVEGADOR                                  |
|   (Landing Page / Modal Bootstrap 5 / jQuery AJAX / Uploads Base64)     |
+------------------------------------+------------------------------------+
                                     |  HTTP (GET, POST, PUT, DELETE...)
                                     v
+------------------------------------+------------------------------------+
|                         index.php (Front Controller)                   |
|                        autoload.php (PSR-4 + Fallback)                  |
+------------------------------------+------------------------------------+
                                     |
                                     v
+------------------------------------+------------------------------------+
|                          Router.php (Despachador)                      |
|  1. Entrega estáticos da publicZone com validação estrita de Mimes      |
|  2. Verifica / Auto-Provisiona Banco (DatabaseProvisioner)              |
|  3. Casa URLs exatas ou dinâmicas (/arquivo/{hash})                    |
|  4. Valida Sessão, Níveis (usuario, admin) e Permissões (ACL)          |
+------------------------------------+------------------------------------+
                                     |
                                     v
+------------------------------------+------------------------------------+
|                      Controllers & Models (DBQuery)                     |
|  - PerfilController / Usuario (BCrypt + Salt + Pepper)                  |
|  - FileController (Storage Privado Base64 + ACL de Acesso)              |
|  - AdminController (Gestão de Usuários, Grupos e Permissões)            |
+-------------------------------------------------------------------------+
```

---

## 2. Estrutura de Diretórios

```text
ZTFramework/
├── app/
│   ├── controllers/         # Controladores da aplicação (ex: PerfilController, AdminController)
│   ├── core/
│   │   ├── database/        # DBConnection (PDO), DBQuery (Builder), DatabaseProvisioner, script.sql
│   │   └── utils/           # Router, Config, PasswordSecurity, CodeGenerator, Mail, Sanitize, JsonResponse
│   ├── documentation/       # Diagramas de arquitetura (PlantUML) e manuais técnicos
│   ├── etc/
│   │   ├── config.json      # Configurações globais (banco, e-mail, pepper, mimes da publicZone)
│   │   └── routes.json      # Catálogo centralizado de rotas, verbos e regras de ACL
│   ├── models/              # Modelos de dados (ex: Usuario.php com DBQuery)
│   ├── storage/             # Armazenamento privado (uploads base64, logs de e-mail, sessões)
│   └── views/               # Telas do sistema (home.php, main.php, admin.php e errors/ 401, 403, 404, 500)
├── public/
│   └── assets/              # Arquivos públicos entregues pelo Router (css, js, imagens)
├── tests/                   # Suíte de testes automatizados com 100% de cobertura
├── autoload.php             # Autoloader recursivo com suporte a Composer
├── composer.json            # Dependências gerenciadas (PHPMailer)
└── index.php                # Front Controller (Ponto de Entrada Único)
```

---

## 3. Autoloader Recursivo Inteligente

O arquivo `autoload.php` é carregado no início da aplicação. Ele opera em duas camadas de alta performance:

1. **Resolução Direta PSR-4**: Traduz `app\controllers\MeuController` diretamente para `app/controllers/MeuController.php`.
2. **Fallback Recursivo Case-Insensitive**: Se uma classe for instanciada sem namespace ou em subpasta dinâmica, percorre a árvore de `app/` e a resolve instantaneamente no Linux:

```php
// No index.php ou em qualquer script de teste:
require_once __DIR__ . '/autoload.php';

use app\core\database\DBQuery;
use app\models\Usuario;

$usuario = new Usuario(); // Carregamento instantâneo
```

---

## 4. Configurações Globais (`config.json`)

Toda a parametrização do ZTFramework fica concentrada em `app/etc/config.json`. Os valores são acessados em qualquer ponto do código através da classe estática `Config` usando **notação de ponto**:

```json
{
  "app": {
    "name": "ZTFramework",
    "version": "2.0.0",
    "env": "development"
  },
  "security": {
    "password_pepper": "ZTFramework_Secur3_P3pp3r_#2026!@K9",
    "bcrypt_cost": 12
  },
  "database": {
    "default": "mysql",
    "connections": {
      "mysql": {
        "host": "localhost",
        "port": 3306,
        "dbname": "ztframework",
        "user": "root",
        "password": "senha",
        "charset": "utf8mb4"
      }
    }
  }
}
```

### Como consumir configurações no código:
```php
use app\core\utils\Config;

$appName = Config::get('app.name'); // "ZTFramework"
$dbHost  = Config::get('database.connections.mysql.host', '127.0.0.1'); // com valor fallback
```

---

## 5. Roteamento Avançado (`routes.json` & `Router.php`)

O ZTFramework adota um modelo declarativo de rotas. Todas as rotas ficam mapeadas em `app/etc/routes.json`.

### Exemplo de Rota Declarativa com Parâmetros Dinâmicos e ACL:

```json
{
  "/dashboard": {
    "get": {
      "responseType": "html",
      "path": "app/views/main.php",
      "session": {
        "required": true,
        "keys": ["idUsuario"]
      }
    }
  },
  "/admin": {
    "get": {
      "responseType": "html",
      "controller": "AdminController",
      "action": "index",
      "session": { "required": true },
      "groups": ["admin"]
    }
  },
  "/arquivo/{hash}": {
    "get": {
      "responseType": "file",
      "controller": "FileController",
      "action": "download"
    }
  }
}
```

### Recursos Nativos do Router:
- **Verbos HTTP Suportados**: `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `HEAD`.
- **Entrega Segura de Estáticos (`publicZone`)**: Arquivos em `/public/...` são entregues com cache e Content-Type estrito (`.css`, `.js`, `.png`, `.svg`, etc.), com bloqueio de tentativas de *Path Traversal* (`../`).
- **Respostas AJAX Inteligentes**: Se a rota falhar (401, 403, 404, 500) e a requisição vier do jQuery (`X-Requested-With: XMLHttpRequest`), o Router responde estritamente em **JSON** para evitar quebras no frontend.

---

## 6. Banco de Dados & Auto-Provisionamento

No início do processamento do `Router`, o componente `DatabaseProvisioner` entra em ação automaticamente:

1. **Checagem de Conexão**: Conecta ao servidor MySQL usando os dados do `config.json`.
2. **Criação do Banco**: Caso o banco `ztframework` não exista, executa:
   ```sql
   CREATE DATABASE IF NOT EXISTS `ztframework` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. **Criação das Tabelas e Seeds (DDL & DML)**: Caso as tabelas essenciais não estejam presentes, executa o arquivo oficial `app/core/database/script.sql`:
   - `usuarios`: Cadastro de usuários com senhas em BCrypt+Pepper e foto em Base64.
   - `grupos`: Níveis de acesso (`admin`, `usuario`, `gerente`, `financeiro`, `suporte`).
   - `permissoes`: Permissões granulares de sistema.
   - `usuariosGrupos`: Associação M:N entre usuários e grupos.
   - `gruposPermissoes`: Matriz M:N de permissões por grupo.
   - `arquivosUpload`: Metadados e regras de ACL de arquivos privados em Base64.
4. **Continuação Fluida**: Concluído o provisionamento, a execução da rota segue sem interrupções!

---

## 7. Query Builder Fluent (`DBQuery`)

O `DBQuery` é o construtor oficial de consultas SQL do ZTFramework. Ele implementa o padrão **K&R** e garante proteção nativa contra SQL Injection via *Prepared Statements*.

### 7.1 Consultas SELECT com Condições e Paginação
```php
use app\core\database\DBQuery;

// Retorna lista de usuários ativos
$usuarios = (new DBQuery('usuarios', 'idUsuario, nome, email'))
    ->addCondition('AND', 'status', '=', 1)
    ->orderBy('nome', 'ASC')
    ->limit(10, 0)
    ->select();

// Retorna apenas um registro direto (ou null)
$usuario = (new DBQuery('usuarios'))
    ->addCondition('AND', 'email', '=', 'admin@ztframework.local')
    ->first();
```

### 7.2 Cláusula WHERE IN
```php
$perfis = (new DBQuery('grupos'))
    ->addIn('AND', 'grupo', ['admin', 'gerente', 'financeiro'])
    ->select();
```

### 7.3 Relacionamentos e JOINs
```php
// Busca permissões com múltiplos JOINs
$permissoes = (new DBQuery('permissoes p', 'DISTINCT p.permissao'))
    ->joinRaw('INNER JOIN gruposPermissoes gp ON gp.idPermissao = p.idPermissao')
    ->joinRaw('INNER JOIN usuariosGrupos ug ON ug.idGrupo = gp.idGrupo')
    ->addCondition('AND', 'ug.idUsuario', '=', 1)
    ->select();
```

### 7.4 Inserções Seguras (INSERT)
```php
$idNovoUsuario = (new DBQuery('usuarios'))->insert([
    'nome'           => 'Carlos Eduardo',
    'email'          => 'carlos@empresa.com',
    'senha'          => $hashBcryptPepper,
    'status'         => 1,
    'primeiroAcesso' => 1
]);
```

### 7.5 Atualizações Seguras (UPDATE com trava contra falta de WHERE)
```php
// Trava obrigatória de segurança: UPDATE sem addCondition() lança RuntimeException!
(new DBQuery('usuarios'))
    ->addCondition('AND', 'idUsuario', '=', 5)
    ->update([
        'nome'   => 'Carlos E. Silva',
        'status' => 1
    ]);
```

### 7.6 Exclusão Segura (DELETE com trava de segurança)
```php
// DELETE sem WHERE também é bloqueado por segurança!
(new DBQuery('usuariosGrupos'))
    ->addCondition('AND', 'idUsuario', '=', 5)
    ->delete();
```

---

## 8. Segurança Criptográfica & Autenticação

A classe `PasswordSecurity` resolve de forma definitiva o risco de vazamento de banco de dados e a limitação de 72 bytes do algoritmo BCrypt através da combinação:

$$\text{Hash} = \text{BCrypt}(\text{HMAC-SHA256}(\text{senha}, \text{Pepper}), \text{Salt})$$

```php
use app\core\utils\PasswordSecurity;

// 1. Gerando o hash seguro
$hash = PasswordSecurity::hash('MinhaSenhaSecreta#2026');

// 2. Verificando credenciais
if (PasswordSecurity::verify($senhaDigitada, $hashArmazenado)) {
    // Autenticação bem sucedida!
}
```

### Por que usar o Pepper Secreto?
1. **Proteção contra Dump de Banco**: Se um invasor baixar o banco de dados SQL, ele não conseguirá quebrar as senhas por ataques offline (Rainbow Tables ou GPUs), pois o segredo HMAC do Pepper fica exclusivamente no `config.json` do servidor.
2. **Eliminação do Limite de 72 Bytes do BCrypt**: O pré-hash normaliza qualquer tamanho de senha (mesmo > 100 caracteres) em 64 caracteres hexadecimais únicos antes de entrar no BCrypt.

---

## 9. Uploads em Base64 & ACL de Arquivos

No ZTFramework, todos os arquivos enviados por formulários são convertidos em string **Base64** no navegador antes do envio via AJAX.

### 9.1 O Componente HTML/JS `.uploadB64`
Basta adicionar a classe `uploadB64` em qualquer `<input type="file">`. O script `public/assets/js/ztfBase64File.js` intercepta a seleção automaticamente:

```html
<!-- Componente no seu formulário -->
<div class="mb-3">
    <label class="form-label">Selecione o Documento:</label>
    <input type="file" class="form-control uploadB64" 
           data-target-base64="#campoBase64" 
           data-target-filename="#campoNomeArquivo"
           data-target-mime="#campoMime">
</div>

<!-- Campos ocultos atualizados automaticamente em Base64 -->
<input type="hidden" id="campoBase64" name="arquivo_base64">
<input type="hidden" id="campoNomeArquivo" name="nome_original">
<input type="hidden" id="campoMime" name="mime_type">
```

### 9.2 Controle de Acesso (ACL) no Servidor
O endpoint `FileController` armazena o arquivo no storage privado (`app/storage/uploads/{hash}.{ext}.base64`) e grava as regras de ACL no banco:

```php
$publico           = 0; // 0=Privado, 1=Público
$gruposPermitidos   = ['gerente', 'financeiro']; // Níveis com acesso
$usuariosPermitidos = [1, 2, 8];                 // Usuários com acesso específico
```

Ao acessar `/arquivo/{hash}`:
- SuperAdmins (`admin`) e o Dono do arquivo (`idDono`) têm acesso irrestrito.
- Usuários pertencentes aos grupos ou lista permitida recebem o arquivo binário com cabeçalhos defensivos (`X-Content-Type-Options: nosniff`).
- Terceiros não autorizados são bloqueados com **HTTP 403 Acesso Negado**.

---

## 10. Frontend Responsivo (Bootstrap 5 & jQuery AJAX)

### 10.1 Modal de Autenticação na Landing Page (`home.php`)
A página inicial contém uma barra superior com o botão que aciona o **Modal Bootstrap 5** com três abas:
1. **Entrar**: Envia credenciais via AJAX. Em caso de sucesso, redireciona o usuário para `/dashboard`.
2. **Cadastrar**: Usuário informa apenas Nome e E-mail. A senha inicial é gerada automaticamente pelo `CodeGenerator`, associada ao nível `usuario` e despachada para o e-mail via `Mail.php`.
3. **Lembrar Senha**: Submete o e-mail cadastrado, gera nova senha temporária, aplica o hash seguro com Pepper e envia ao e-mail.

### 10.2 Cliente AJAX Padronizado (`public/assets/js/zait.ajax.js`)
```javascript
// Exemplo de submissão de formulário com Promise:
ZTF.ajax.submitForm('#formMeuModulo', {
    onSuccess: function(res) {
        alert(res.message);
        window.location.reload();
    },
    onError: function(err) {
        alert("Erro (" + err.code + "): " + err.message);
    }
});
```

---

## 11. Tutorial Prático: Construindo um Módulo Completo

Vamos demonstrar como criar um módulo completo de **Produtos** em 4 passos rápidos.

### Passo 1: Criar a Tabela no Banco
```sql
CREATE TABLE IF NOT EXISTS `produtos` (
    `idProduto` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) NOT NULL,
    `preco` DECIMAL(10,2) NOT NULL,
    `dataCriacao` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Passo 2: Registrar a Rota em `app/etc/routes.json`
```json
{
  "/produtos": {
    "get": {
      "responseType": "html",
      "controller": "ProdutoController",
      "action": "index",
      "session": { "required": true }
    }
  },
  "/api/produtos/salvar": {
    "post": {
      "responseType": "json",
      "controller": "ProdutoController",
      "action": "salvar",
      "session": { "required": true }
    }
  }
}
```

### Passo 3: Criar o Controller em `app/controllers/ProdutoController.php` (Padrão K&R)
```php
<?php
declare(strict_types=1);

namespace app\controllers;

use app\core\database\DBQuery;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;

class ProdutoController {
    public function index(): void {
        // Busca produtos usando DBQuery
        $produtos = (new DBQuery('produtos'))
            ->orderBy('nome', 'ASC')
            ->select();

        require dirname(__DIR__) . '/views/produtos.php';
    }

    public function salvar(): void {
        $nome  = Sanitize::string($_POST['nome'] ?? '');
        $preco = Sanitize::float($_POST['preco'] ?? 0.0);

        if (empty($nome) || $preco <= 0) {
            JsonResponse::error(400, 'ERR_VALIDATION', 'Nome e preço válidos são obrigatórios.');
            return;
        }

        $id = (new DBQuery('produtos'))->insert([
            'nome'  => $nome,
            'preco' => $preco
        ]);

        JsonResponse::success(['idProduto' => $id], 'Produto cadastrado com sucesso!');
    }
}
```

### Passo 4: Criar a View em `app/views/produtos.php`
```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produtos - ZTFramework</title>
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h2>Catálogo de Produtos</h2>
        <ul class="list-group my-3">
            <?php foreach ($produtos as $p): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <?= htmlspecialchars($p['nome']) ?>
                    <span class="badge bg-primary">R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
```

---

## 12. Suíte de Testes Automatizados

O framework conta com **8 suítes integradas de testes**, garantindo cobertura completa de todos os subsistemas:

Para executar toda a suíte de testes:
```bash
php tests/run_all.php
```

### Resultados da Suíte:
1. `test_config.php`: Resolução por ponto, valores padrão e sobrescrita em runtime.
2. `test_router.php`: Dispatcher, normalização de rotas, ACL e bloqueio de traversal.
3. `test_dbquery.php`: Compilação de SELECT, WHERE IN, JOINs e travas de UPDATE/DELETE.
4. `test_password_security.php`: BCrypt + Salt + Pepper, mitigação dos 72 bytes e resistência a vazamentos de BD.
5. `test_mail_and_auth.php`: CodeGenerator (senhas fortes e tokens) e PHPMailer.
6. `test_ajax_and_mimes.php`: Entrega de estáticos e garantia de respostas JSON em chamadas AJAX.
7. `test_full_workflow.php`: Ciclo completo no banco de dados real (Login, Cadastro automático, Edição de perfil, Upload Base64 com ACL e Gestão de níveis pelo Admin).
8. `test_security.php`: 19 verificações rigorosas do OWASP Top 10 (Escalação de privilégios, SQL Injection, XSS e integridade de dados).

---

> **ZTFramework 2.0** — Desenvolvido com rigor acadêmico, excelência técnica e padrões modernos de engenharia de software pelos Professores **Drº Cleber S. Oliveira** e **Drª Marcia Aparecida Silva Bissaco**.
