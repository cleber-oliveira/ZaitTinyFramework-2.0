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

use app\core\utils\Url;

class DashboardController {
    /**
     * Ponto de entrada do /dashboard.
     * Despacha o usuário autenticado diretamente para o painel exclusivo do seu perfil.
     */
    public function index(): void {
        $user = $_SESSION['user'] ?? [];
        $grupos = (array)($user['grupos'] ?? []);
        $perfilReq = $_GET['perfil'] ?? null;

        // Se houver alternância explícita via ?perfil= (ex: Meu Perfil ou Simulação de Admin), delega para main.php
        if ($perfilReq !== null && $perfilReq !== '') {
            require dirname(__DIR__) . '/views/main.php';
            return;
        }

        $perfil = 'usuario';
        if (in_array('admin', $grupos, true)) {
            $perfil = 'admin';
        } elseif (in_array('gerente', $grupos, true)) {
            $perfil = 'gerente';
        } elseif (in_array('financeiro', $grupos, true)) {
            $perfil = 'financeiro';
        } elseif (in_array('suporte', $grupos, true)) {
            $perfil = 'suporte';
        }

        // Redirecionamento HTTP 302 dinâmico respeitando o rootPath
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            Url::redirect("dashboard/{$perfil}");
            exit;
        }

        // Fallback em ambiente de teste ou CLI
        $viewMap = [
            'admin'      => dirname(__DIR__) . '/views/main_admin.php',
            'gerente'    => dirname(__DIR__) . '/views/main_gerente.php',
            'financeiro' => dirname(__DIR__) . '/views/main_financeiro.php',
            'suporte'    => dirname(__DIR__) . '/views/main_suporte.php',
            'usuario'    => dirname(__DIR__) . '/views/main.php',
        ];

        require $viewMap[$perfil];
    }
}
