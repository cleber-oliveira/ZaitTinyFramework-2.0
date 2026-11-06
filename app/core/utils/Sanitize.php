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

/**
 * ZTFramework - Sanitize
 * 
 * Sanitização e limpeza de dados de entrada contra XSS e injeção de tags indesejadas.
 */
class Sanitize {
    /**
     * Sanitiza uma string removendo scripts, tags HTML e caracteres invisíveis nocivos.
     */
    public static function string(mixed $value): string {
        if ($value === null) {
            return '';
        }
        $str = (string)$value;
        // 1. Remove blocos inteiros de <script> e <style> incluindo o código interno
        $str = (string)preg_replace('#<(script|style)\b[^>]*>.*?</(script|style)>#is', '', $str);
        // 2. Remove quaisquer tags HTML/XML restantes
        $str = strip_tags($str);
        // 3. Remove caracteres nulos e de controle perigosos
        $str = (string)preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        return trim($str);
    }

    /**
     * Sanitiza e-mail.
     */
    public static function email(mixed $value): string {
        return filter_var((string)$value, FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Sanitiza inteiros.
     */
    public static function int(mixed $value, int $default = 0): int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? (int)$filtered : $default;
    }

    /**
     * Sanitiza floats / decimais.
     */
    public static function float(mixed $value, float $default = 0.0): float {
        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $filtered !== false ? (float)$filtered : $default;
    }

    /**
     * Mantém apenas caracteres alfanuméricos, underscores e hífens.
     */
    public static function alphaNum(mixed $value): string {
        return (string)preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$value);
    }

    /**
     * Sanitiza recursivamente um array de dados (ex: $_POST).
     */
    public static function array(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $val) {
            $cleanKey = self::string($key);
            if (is_array($val)) {
                $sanitized[$cleanKey] = self::array($val);
            } else {
                $sanitized[$cleanKey] = self::string($val);
            }
        }
        return $sanitized;
    }
}
