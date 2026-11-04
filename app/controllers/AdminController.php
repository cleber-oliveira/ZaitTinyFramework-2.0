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

namespace app\controllers;

use app\models\Usuario;
use app\core\database\DBConnection;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;
use PDO;

class AdminController {
    /**
     * Renderiza o painel administrativo (View).
     */
    public function index(): void {
        require dirname(__DIR__) . '/views/admin.php';
    }

    /**
     * Retorna a lista de todos os usuários com seus grupos.
     */
    public function listarUsuarios(): void {
        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->listarTodosComGrupos();
        JsonResponse::success($usuarios);
    }

    /**
     * Atribui ou revoga grupos de um usuário.
     */
    public function salvarGruposUsuario(): void {
        $idUsuario = Sanitize::int($_POST['idUsuario'] ?? 0);
        $grupos = $_POST['grupos'] ?? [];

        if (is_string($grupos)) {
            $grupos = json_decode($grupos, true) ?: [];
        }

        if ($idUsuario <= 0) {
            JsonResponse::error(400, 'ERR_INVALID_ID', 'ID de usuário inválido.');
            return;
        }

        // Garante que o usuário permaneça com pelo menos um grupo (padrão 'usuario')
        if (empty($grupos)) {
            $db = DBConnection::getConnection();
            $stmt = $db->query("SELECT idGrupo FROM grupos WHERE grupo = 'usuario' LIMIT 1");
            $padrao = $stmt->fetchColumn();
            if ($padrao) {
                $grupos = [(int)$padrao];
            }
        }

        $usuarioModel = new Usuario();
        $usuarioModel->sincronizarGruposUsuario($idUsuario, (array)$grupos);

        JsonResponse::success(null, 'Grupos do usuário atualizados com sucesso!');
    }

    /**
     * Ativa ou desativa um usuário.
     */
    public function alternarStatus(): void {
        $idUsuario = Sanitize::int($_POST['idUsuario'] ?? 0);
        $novoStatus = Sanitize::int($_POST['status'] ?? 0);

        if ($idUsuario <= 0) {
            JsonResponse::error(400, 'ERR_INVALID_ID', 'ID de usuário inválido.');
            return;
        }

        $usuarioModel = new Usuario();
        $usuarioModel->alternarStatus($idUsuario, $novoStatus);

        JsonResponse::success(null, 'Status do usuário alterado com sucesso!');
    }

    /**
     * Retorna dados completos de Grupos e Permissões.
     */
    public function gruposEPermissoes(): void {
        $db = DBConnection::getConnection();

        $grupos = $db->query("SELECT * FROM grupos ORDER BY idGrupo ASC")->fetchAll(PDO::FETCH_ASSOC);
        $permissoes = $db->query("SELECT * FROM permissoes ORDER BY idPermissao ASC")->fetchAll(PDO::FETCH_ASSOC);

        $stmtGp = $db->query("SELECT idGrupo, idPermissao FROM gruposPermissoes");
        $relacoes = $stmtGp->fetchAll(PDO::FETCH_ASSOC);

        $mapPermissoes = [];
        foreach ($relacoes as $r) {
            $mapPermissoes[(int)$r['idGrupo']][] = (int)$r['idPermissao'];
        }

        JsonResponse::success([
            'grupos'         => $grupos,
            'permissoes'     => $permissoes,
            'grupoPermissoes'=> $mapPermissoes
        ]);
    }

    /**
     * Sincroniza permissões atribuídas a um grupo.
     */
    public function salvarPermissoesGrupo(): void {
        $idGrupo = Sanitize::int($_POST['idGrupo'] ?? 0);
        $permissoes = $_POST['permissoes'] ?? [];

        if (is_string($permissoes)) {
            $permissoes = json_decode($permissoes, true) ?: [];
        }

        if ($idGrupo <= 0) {
            JsonResponse::error(400, 'ERR_INVALID_ID', 'ID de grupo inválido.');
            return;
        }

        $db = DBConnection::getConnection();

        $stmtDel = $db->prepare("DELETE FROM gruposPermissoes WHERE idGrupo = ?");
        $stmtDel->execute([$idGrupo]);

        $stmtIns = $db->prepare("INSERT INTO gruposPermissoes (idGrupo, idPermissao) VALUES (?, ?)");
        foreach ($permissoes as $idPerm) {
            $stmtIns->execute([$idGrupo, (int)$idPerm]);
        }

        JsonResponse::success(null, 'Permissões do grupo atualizadas com sucesso!');
    }
}
