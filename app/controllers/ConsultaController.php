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

namespace app\controllers;

use app\core\database\DBConnection;
use app\core\database\DBQuery;
use app\core\utils\JsonResponse;

class ConsultaController {
    /**
     * Retorna JSON resultante de consulta direta SQL via PDO.
     * Rota: GET /api/consulta-sql
     */
    public function consultaDireta(): void {
        $db = DBConnection::getConnection();
        $stmt = $db->query("SELECT idUsuario, nome, email, status, dataCriacao FROM usuarios ORDER BY idUsuario ASC");
        $dados = $stmt->fetchAll();

        JsonResponse::success($dados, 'Consulta direta SQL executada com sucesso.');
    }

    /**
     * Retorna JSON resultante de consulta via Fluent DBQuery com JOIN.
     * Rota: GET /api/consulta-dbquery
     */
    public function consultaDbQuery(): void {
        $dados = (new DBQuery('usuarios u', 'u.idUsuario, u.nome, u.email, u.status, u.dataCriacao, g.grupo'))
            ->joinRaw('LEFT JOIN usuariosGrupos ug ON ug.idUsuario = u.idUsuario')
            ->joinRaw('LEFT JOIN grupos g ON g.idGrupo = ug.idGrupo')
            ->orderBy('u.idUsuario', 'ASC')
            ->select();

        JsonResponse::success($dados, 'Consulta DBQuery com JOIN executada com sucesso.');
    }
}
