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

namespace app\core\utils;

use RuntimeException;

class Config {
    private static ?array $data = null;
    private static ?string $configPath = null;

    /**
     * Define um caminho alternativo para o arquivo de configuração (útil em testes).
     */
    public static function setConfigPath(string $path): void {
        self::$configPath = $path;
        self::$data = null; // força recarga
    }

    /**
     * Carrega as configurações do arquivo JSON.
     */
    public static function load(): array {
        if (self::$data !== null) {
            return self::$data;
        }

        $path = self::$configPath ?? dirname(__DIR__, 2) . '/etc/config.json';

        if (!is_file($path)) {
            throw new RuntimeException("Arquivo de configuração não encontrado em: {$path}");
        }

        $content = (string)file_get_contents($path);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new RuntimeException("Erro ao decodificar config.json: " . json_last_error_msg());
        }

        self::$data = $decoded;
        return self::$data;
    }

    /**
     * Recupera um valor de configuração usando notação com ponto.
     * 
     * @param string $key Chave aninhada (ex: 'app.name', 'database.default')
     * @param mixed $default Valor padrão caso a chave não exista
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed {
        $config = self::load();

        if (empty($key)) {
            return $config;
        }

        $segments = explode('.', $key);
        $current = $config;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Verifica se uma chave existe na configuração.
     */
    public static function has(string $key): bool {
        $config = self::load();
        $segments = explode('.', $key);
        $current = $config;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Altera ou define uma configuração em memória (runtime).
     */
    public static function set(string $key, mixed $value): void {
        self::load();
        $segments = explode('.', $key);
        $current = &self::$data;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * Limpa a configuração carregada em memória.
     */
    public static function reset(): void {
        self::$data = null;
        self::$configPath = null;
    }
}
