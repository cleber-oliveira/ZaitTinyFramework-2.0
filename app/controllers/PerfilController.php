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
use app\core\utils\JsonResponse;
use app\core\utils\Sanitize;

class PerfilController {
    private function initSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent() && php_sapi_name() !== 'cli') {
            session_start();
        }
    }

    /**
     * Atualiza dados de perfil do usuário logado (Nome, Nova Senha, Foto Base64).
     * O e-mail NUNCA é alterado.
     */
    public function atualizar(): void {
        $this->initSession();

        $userId = $_SESSION['user']['idUsuario'] ?? null;
        if (!$userId) {
            JsonResponse::error(401, 'ERR_UNAUTHORIZED', 'Sessão expirada. Faça login novamente.');
            return;
        }

        $nome       = Sanitize::string($_POST['nome'] ?? '');
        $novaSenha  = (string)($_POST['nova_senha'] ?? '');
        $confSenha  = (string)($_POST['confirmar_nova_senha'] ?? '');
        $fotoBase64 = $_POST['foto_base64'] ?? null;

        if (empty($nome)) {
            JsonResponse::error(400, 'ERR_EMPTY_NAME', 'O nome é obrigatório.');
            return;
        }

        if (!empty($novaSenha)) {
            if (strlen($novaSenha) < 6) {
                JsonResponse::error(400, 'ERR_SHORT_PASSWORD', 'A nova senha deve possuir no mínimo 6 caracteres.');
                return;
            }
            if ($novaSenha !== $confSenha) {
                JsonResponse::error(400, 'ERR_PASSWORD_MISMATCH', 'A confirmação da nova senha não confere.');
                return;
            }
        } else {
            $novaSenha = null;
        }

        if (!empty($fotoBase64) && is_string($fotoBase64)) {
            if (!str_starts_with($fotoBase64, 'data:image/') && base64_decode($fotoBase64, true) === false) {
                JsonResponse::error(400, 'ERR_INVALID_IMAGE', 'Arquivo de foto em formato Base64 inválido.');
                return;
            }
        } else {
            $fotoBase64 = null;
        }

        $usuarioModel = new Usuario();
        $usuarioModel->atualizarPerfil((int)$userId, $nome, $novaSenha, $fotoBase64);

        $_SESSION['user']['nome'] = $nome;
        if ($fotoBase64 !== null) {
            $_SESSION['user']['foto'] = $fotoBase64;
        }

        JsonResponse::success([
            'nome' => $nome,
            'foto' => $_SESSION['user']['foto'] ?? null
        ], 'Perfil atualizado com sucesso!');
    }
}
