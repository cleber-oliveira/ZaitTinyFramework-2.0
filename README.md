# ZTFramework 2.0

> **Framework PHP 8.2+ Moderno, Modular e Seguro por Padrão**

### Autores Oficiais:
- **Professor Drº Cleber S. Oliveira**
- **Professora Drª Marcia Aparecida Silva Bissaco**

---

### Principais Características
- **Padrão K&R (Ken Thompson & Dennis Ritchie)**: Estilo de código rigoroso com `{` na mesma linha de declarações.
- **Tipagem Estrita**: `declare(strict_types=1);` em todo o núcleo.
- **Auto-Provisionamento no Router**: O banco de dados e suas tabelas (`script.sql`) são conferidos e criados automaticamente no boot.
- **Query Builder (`DBQuery`)**: Consultas SQL fluentes com Prepared Statements e travas de segurança contra UPDATE/DELETE sem WHERE.
- **Segurança Máxima**: Senhas com BCrypt + Salt + HMAC-SHA256 Pepper (sem limite de 72 bytes e protegido contra vazamentos de dump).
- **Uploads 100% em Base64**: Storage privado com controle de acesso granular (ACL) por usuário e grupos.
- **Frontend Responsivo**: Bootstrap 5 + jQuery AJAX com respostas padronizadas em JSON e tratamento estrito de erros HTTP (401, 403, 404, 500).

---

### Manual Completo de Uso
Consulte o guia didático e passo a passo em:
👉 **[MANUAL.md](MANUAL.md)**

---

### Executando os Testes Automatizados
```bash
php tests/run_all.php
```
