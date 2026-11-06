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

class CodeGenerator {
    /**
     * Gera uma senha temporária forte e aleatória.
     * 
     * @param int $length Quantidade de caracteres (padrão: 10)
     * @return string
     */
    public static function generatePassword(int $length = 10): string {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // sem I, O para evitar confusão visual
        $lowercase = 'abcdefghijkmnopqrstuvwxyz'; // sem l
        $numbers   = '23456789';                // sem 0, 1
        $symbols   = '@#$%&*!';

        $all = $uppercase . $lowercase . $numbers . $symbols;
        $password = '';

        // Garante ao menos um caractere de cada grupo
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Preenche o restante aleatoriamente
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Embaralha para que os tipos não fiquem em posições fixas
        return str_shuffle($password);
    }

    /**
     * Gera um token hexadecimal criptograficamente seguro.
     */
    public static function generateSecureToken(int $bytes = 32): string {
        return bin2hex(random_bytes(max(16, $bytes)));
    }

    /**
     * Alias didático para generateSecureToken().
     */
    public static function generateToken(int $bytes = 32): string {
        return self::generateSecureToken($bytes);
    }

    /**
     * Gera um código numérico de tamanho fixo (ideal para validação 2FA / OTP / SMS).
     */
    public static function generateNumericCode(int $digits = 6): string {
        $digits = max(4, min(10, $digits));
        $min = (int)('1' . str_repeat('0', $digits - 1));
        $max = (int)str_repeat('9', $digits);
        return (string)random_int($min, $max);
    }

    /**
     * Gera um identificador único baseado no timestamp com alta entropia.
     */
    public static function generateUniqId(string $prefix = '', bool $moreEntropy = true): string {
        return uniqid($prefix, $moreEntropy);
    }
}
