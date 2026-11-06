<?php
declare(strict_types=1);

/**
 * ============================================================================
 * ZTFramework 2.0
 * ============================================================================
 * 
 * @package   ZTFramework
 * @version   2.0.0
 * @author    Professor Drº Cleber S. Oliveira
 * @author    Professora Drª Marcia Aparecida Silva Bissaco
 * @copyright (c) 2026 ZTFramework
 * ============================================================================
 */

namespace app\models;

use app\core\database\DBConnection;
use app\core\database\DBQuery;
use app\core\utils\PasswordSecurity;
use app\core\utils\CodeGenerator;
use PDO;
use Throwable;

class Usuario {
    /**
     * Autentica usuário via DBQuery e retorna dados completos da sessão (com grupos e foto).
     */
    public function autenticar(string $email, string $senha): ?array {
        try {
            // 1. Busca usuário por e-mail utilizando DBQuery
            $user = (new DBQuery('usuarios', 'idUsuario, nome, email, senha, foto, status, primeiroAcesso'))
                ->addCondition('AND', 'email', '=', $email)
                ->first();

            if (!$user) {
                return null;
            }

            if ((int)$user['status'] !== 1) {
                return null;
            }

            $storedHash = (string)$user['senha'];
            $senhaValida = false;

            if (PasswordSecurity::verify($senha, $storedHash)) {
                $senhaValida = true;
            } elseif (password_verify($senha, $storedHash)) {
                $senhaValida = true;
                $novoHash = PasswordSecurity::hash($senha);
                // Rehash seguro via DBQuery
                (new DBQuery('usuarios'))
                    ->addCondition('AND', 'idUsuario', '=', (int)$user['idUsuario'])
                    ->update(['senha' => $novoHash]);
            }

            if (!$senhaValida) {
                return null;
            }

            $idUsuario = (int)$user['idUsuario'];

            // 2. Busca grupos atribuídos via DBQuery com JOIN
            $gruposRows = (new DBQuery('grupos g', 'g.idGrupo, g.grupo, g.isAdmin'))
                ->joinRaw('INNER JOIN usuariosGrupos ug ON ug.idGrupo = g.idGrupo')
                ->addCondition('AND', 'ug.idUsuario', '=', $idUsuario)
                ->select();

            $grupos = [];
            $isAdmin = false;
            foreach ($gruposRows as $g) {
                $grupos[] = (string)$g['grupo'];
                if (!empty($g['isAdmin'])) {
                    $isAdmin = true;
                }
            }

            // 3. Busca permissões granulares via DBQuery com múltiplos JOINs
            $permsRows = (new DBQuery('permissoes p', 'DISTINCT p.permissao'))
                ->joinRaw('INNER JOIN gruposPermissoes gp ON gp.idPermissao = p.idPermissao')
                ->joinRaw('INNER JOIN usuariosGrupos ug ON ug.idGrupo = gp.idGrupo')
                ->addCondition('AND', 'ug.idUsuario', '=', $idUsuario)
                ->select();

            $permissoes = array_column($permsRows, 'permissao');

            if ($isAdmin && !in_array('admin', $grupos, true)) {
                $grupos[] = 'admin';
            }

            return [
                'idUsuario'      => $idUsuario,
                'nome'           => (string)$user['nome'],
                'email'          => (string)$user['email'],
                'foto'           => $user['foto'] ? (string)$user['foto'] : null,
                'primeiroAcesso' => (int)$user['primeiroAcesso'],
                'grupos'         => $grupos,
                'permissoes'     => $permissoes
            ];

        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Cadastro de novo usuário via DBQuery:
     * - Gera a senha inicial automaticamente.
     * - Atribui nível inicial 'usuario'.
     * - Retorna a senha temporária gerada para ser enviada por e-mail.
     */
    public function cadastrarComSenhaAutomatica(string $nome, string $email): array {
        // Verifica existência de e-mail via DBQuery
        $existe = (new DBQuery('usuarios', 'idUsuario'))
            ->addCondition('AND', 'email', '=', $email)
            ->first();

        if ($existe) {
            return ['status' => false, 'message' => 'Este e-mail já está cadastrado no sistema.'];
        }

        // Gera senha inicial aleatória
        $senhaInicial = CodeGenerator::generatePassword(10);
        $hash = PasswordSecurity::hash($senhaInicial);

        // Insere novo usuário com primeiroAcesso = 1 via DBQuery
        $idUsuario = (int)(new DBQuery('usuarios'))->insert([
            'nome'           => $nome,
            'email'          => $email,
            'senha'          => $hash,
            'status'         => 1,
            'primeiroAcesso' => 1
        ]);

        // Vincula obrigatoriamente ao grupo inicial 'usuario' via DBQuery
        $grupoRow = (new DBQuery('grupos', 'idGrupo'))
            ->addCondition('AND', 'grupo', '=', 'usuario')
            ->first();

        if ($grupoRow) {
            (new DBQuery('usuariosGrupos'))->insert([
                'idUsuario' => $idUsuario,
                'idGrupo'   => (int)$grupoRow['idGrupo']
            ]);
        }

        return [
            'status'       => true,
            'idUsuario'    => $idUsuario,
            'senhaInicial' => $senhaInicial,
            'message'      => 'Usuário registrado com sucesso!'
        ];
    }

    /**
     * Atualiza dados de perfil do próprio usuário via DBQuery:
     * - O e-mail NUNCA é alterado aqui por regra de negócio.
     * - Permite alterar nome, nova senha e foto (em Base64).
     */
    public function atualizarPerfil(int $idUsuario, string $nome, ?string $novaSenha = null, ?string $fotoBase64 = null): array {
        $updateData = ['nome' => $nome];

        if (!empty($novaSenha)) {
            $updateData['senha'] = PasswordSecurity::hash($novaSenha);
            $updateData['primeiroAcesso'] = 0;
        }

        if ($fotoBase64 !== null) {
            $updateData['foto'] = $fotoBase64;
        }

        (new DBQuery('usuarios'))
            ->addCondition('AND', 'idUsuario', '=', $idUsuario)
            ->update($updateData);

        return ['status' => true, 'message' => 'Perfil atualizado com sucesso!'];
    }

    /**
     * Redefine a senha por e-mail via DBQuery (esqueci minha senha).
     */
    public function redefinirSenhaPorEmail(string $email, string $novaSenha): bool {
        $user = (new DBQuery('usuarios', 'idUsuario'))
            ->addCondition('AND', 'email', '=', $email)
            ->first();

        if (!$user) {
            return false;
        }

        $novoHash = PasswordSecurity::hash($novaSenha);
        $afetados = (new DBQuery('usuarios'))
            ->addCondition('AND', 'idUsuario', '=', (int)$user['idUsuario'])
            ->update([
                'senha'          => $novoHash,
                'primeiroAcesso' => 1
            ]);

        return $afetados >= 0;
    }

    /**
     * Lista todos os usuários com seus respectivos grupos (Área Administrativa).
     */
    public function listarTodosComGrupos(): array {
        $db = DBConnection::getConnection();
        $sql = "
            SELECT u.idUsuario, u.nome, u.email, u.foto, u.status, u.dataCriacao,
                   GROUP_CONCAT(g.grupo ORDER BY g.grupo SEPARATOR ', ') AS grupos,
                   GROUP_CONCAT(g.idGrupo ORDER BY g.idGrupo SEPARATOR ',') AS idsGrupos
            FROM usuarios u
            LEFT JOIN usuariosGrupos ug ON ug.idUsuario = u.idUsuario
            LEFT JOIN grupos g ON g.idGrupo = ug.idGrupo
            GROUP BY u.idUsuario
            ORDER BY u.idUsuario DESC
        ";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza os grupos atribuídos a um usuário via DBQuery (Área Administrativa).
     */
    public function sincronizarGruposUsuario(int $idUsuario, array $idsGrupos): void {
        (new DBQuery('usuariosGrupos'))
            ->addCondition('AND', 'idUsuario', '=', $idUsuario)
            ->delete();

        foreach ($idsGrupos as $idGrupo) {
            (new DBQuery('usuariosGrupos'))->insert([
                'idUsuario' => $idUsuario,
                'idGrupo'   => (int)$idGrupo
            ]);
        }
    }

    /**
     * Altera o status (Ativo/Inativo) de um usuário via DBQuery (Área Administrativa).
     */
    public function alternarStatus(int $idUsuario, int $novoStatus): void {
        (new DBQuery('usuarios'))
            ->addCondition('AND', 'idUsuario', '=', $idUsuario)
            ->update(['status' => $novoStatus]);
    }

    /**
     * Retorna a lista de nomes dos grupos associados ao usuário.
     */
    public function obterGrupos(int $idUsuario): array {
        $rows = (new DBQuery('grupos g', 'g.grupo'))
            ->joinRaw('INNER JOIN usuariosGrupos ug ON ug.idGrupo = g.idGrupo')
            ->addCondition('AND', 'ug.idUsuario', '=', $idUsuario)
            ->select();
        return array_column($rows, 'grupo');
    }

    /**
     * Retorna a lista de permissões granulares associadas ao usuário.
     */
    public function obterPermissoes(int $idUsuario): array {
        $rows = (new DBQuery('permissoes p', 'DISTINCT p.permissao'))
            ->joinRaw('INNER JOIN gruposPermissoes gp ON gp.idPermissao = p.idPermissao')
            ->joinRaw('INNER JOIN usuariosGrupos ug ON ug.idGrupo = gp.idGrupo')
            ->addCondition('AND', 'ug.idUsuario', '=', $idUsuario)
            ->select();
        return array_column($rows, 'permissao');
    }

    /**
     * Alias didático para cadastrarComSenhaAutomatica().
     */
    public function criarComSenhaTemporaria(string $nome, string $email): array {
        return $this->cadastrarComSenhaAutomatica($nome, $email);
    }

    /**
     * Alias didático para redefinirSenhaPorEmail().
     */
    public function alterarSenhaPorEmail(string $email, string $novaSenha): bool {
        return $this->redefinirSenhaPorEmail($email, $novaSenha);
    }
}
