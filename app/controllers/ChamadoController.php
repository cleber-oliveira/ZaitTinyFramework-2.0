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

use app\core\database\DBQuery;
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;

/**
 * ChamadoController
 * 
 * Controlador de exemplo didático para o módulo de Chamados de Suporte.
 */
class ChamadoController {
    /**
     * Renderiza a página principal de chamados com listagem via DBQuery.
     */
    public function index(): void {
        // Busca todos os chamados fazendo JOIN com a tabela de usuários
        $chamados = (new DBQuery('chamados c', 'c.*, u.nome AS solicitante, u.email AS emailSolicitante'))
            ->joinRaw('INNER JOIN usuarios u ON u.idUsuario = c.idUsuario')
            ->orderBy('c.idChamado', 'DESC')
            ->select();

        require dirname(__DIR__) . '/views/chamados.php';
    }

    /**
     * Registra um novo chamado vindo de submissão AJAX.
     */
    public function criar(): void {
        $titulo    = Sanitize::string($_POST['titulo'] ?? '');
        $descricao = Sanitize::string($_POST['descricao'] ?? '');
        $idUsuario = (int)($_SESSION['user']['idUsuario'] ?? 0);

        if ($idUsuario <= 0) {
            JsonResponse::error(401, 'ERR_UNAUTHORIZED', 'Sessão expirada. Faça login novamente.');
            return;
        }

        if (empty($titulo)) {
            JsonResponse::error(400, 'ERR_EMPTY_TITLE', 'O título do chamado é obrigatório.');
            return;
        }

        // Insere o chamado no banco via DBQuery
        $idChamado = (new DBQuery('chamados'))->insert([
            'idUsuario'    => $idUsuario,
            'titulo'       => $titulo,
            'descricao'    => $descricao,
            'status'       => 'aberto'
        ]);

        JsonResponse::success([
            'idChamado'    => $idChamado,
            'titulo'       => $titulo,
            'status'       => 'aberto',
            'solicitante'  => $_SESSION['user']['nome'] ?? 'Eu',
            'dataAbertura' => date('d/m/Y H:i')
        ], 'Chamado registrado com sucesso!');
    }

    /**
     * Atualiza o status de um chamado (ex: em_andamento, fechado).
     */
    public function alternarStatus(): void {
        $idChamado   = Sanitize::int($_POST['idChamado'] ?? 0);
        $novoStatus  = Sanitize::string($_POST['status'] ?? 'aberto');

        $statusValidos = ['aberto', 'em_andamento', 'fechado'];
        if (!in_array($novoStatus, $statusValidos, true)) {
            JsonResponse::error(400, 'ERR_INVALID_STATUS', 'Status informado é inválido.');
            return;
        }

        (new DBQuery('chamados'))
            ->addCondition('AND', 'idChamado', '=', $idChamado)
            ->update(['status' => $novoStatus]);

        JsonResponse::success(null, 'Status do chamado atualizado com sucesso!');
    }
}
