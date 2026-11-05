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

namespace app\core\utils;

class Url {
    /**
     * Retorna o rootPath / basePath do projeto com barra final garantida.
     * Exemplo: '/ZTFramework/' se hospedado em subpasta, ou '/' se na raiz do domínio.
     */
    public static function base(): string {
        $configured = Config::get('app.base_path', null);
        if ($configured !== null && $configured !== '') {
            return rtrim((string)$configured, '/') . '/';
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($scriptName);
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '/';
        }

        return rtrim(str_replace('\\', '/', $dir), '/') . '/';
    }

    /**
     * Retorna o rootPath sem a barra final (ou vazio se na raiz do domínio).
     * Exemplo: '/ZTFramework' ou ''
     */
    public static function rootPath(): string {
        $base = self::base();
        return rtrim($base, '/');
    }

    /**
     * Gera uma URL relativa à raiz da aplicação.
     * Exemplo: Url::to('login') => '/ZTFramework/login'
     * Exemplo: Url::to('dashboard/admin') => '/ZTFramework/dashboard/admin'
     */
    public static function to(string $path = ''): string {
        $base = self::base();
        $trimmedPath = ltrim($path, '/');
        if ($trimmedPath === '') {
            return $base;
        }
        return rtrim($base, '/') . '/' . $trimmedPath;
    }

    /**
     * Retorna a URL absoluta completa com protocolo e host.
     * Exemplo: http://localhost/ZTFramework/login
     */
    public static function full(string $path = ''): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . self::to($path);
    }

    /**
     * Gera URL para arquivos estáticos públicos.
     * Exemplo: Url::asset('assets/css/bootstrap.min.css') => '/ZTFramework/public/assets/css/bootstrap.min.css'
     */
    public static function asset(string $assetPath): string {
        return self::to('public/' . ltrim($assetPath, '/'));
    }

    /**
     * Redireciona o navegador respeitando a pasta raiz do projeto.
     */
    public static function redirect(string $path = ''): void {
        $target = self::to($path);
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            header("Location: {$target}");
            exit;
        }
    }
}
