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

/**
 * ZTFramework - PasswordSecurity
 * 
 * Gerenciador de segurança de senhas com:
 * 1. BCrypt (algoritmo adaptativo Blowfish com fator de custo configurável)
 * 2. Salt criptográfico gerado automaticamente por hash
 * 3. Pepper secreto vindo do config.json (HMAC-SHA256), que impede ataques
 *    mesmo em caso de vazamento total do banco de dados e remove a limitação
 *    de 72 bytes do BCrypt.
 */
class PasswordSecurity {
    /**
     * Gera um hash seguro BCrypt com Salt e Pepper.
     * 
     * @param string $password Senha em texto plano (marcada como SensitiveParameter no PHP 8.2+)
     * @return string Hash de 60 caracteres
     */
    public static function hash(#[\SensitiveParameter] string $password): string {
        if (trim($password) === '') {
            throw new RuntimeException("A senha não pode ser vazia.");
        }

        $pepper = self::getPepper();
        // HMAC-SHA256 com o Pepper secreto: protege contra vazamentos de dump de BD
        // e normaliza a senha para 64 caracteres hexadecimais, eliminando o limite de 72 bytes do BCrypt
        $peppered = hash_hmac('sha256', $password, $pepper);

        $cost = (int)Config::get('security.bcrypt_cost', 12);
        $options = ['cost' => $cost];

        $hash = password_hash($peppered, PASSWORD_BCRYPT, $options);
        if ($hash === false) {
            throw new RuntimeException("Falha na geração do hash criptográfico.");
        }

        return $hash;
    }

    /**
     * Valida uma senha em texto plano contra o hash armazenado.
     */
    public static function verify(#[\SensitiveParameter] string $password, string $storedHash): bool {
        if ($password === '' || $storedHash === '') {
            return false;
        }

        $pepper = self::getPepper();
        $peppered = hash_hmac('sha256', $password, $pepper);

        return password_verify($peppered, $storedHash);
    }

    /**
     * Verifica se o hash precisa ser recalculado devido à alteração no custo (cost) do BCrypt.
     */
    public static function needsRehash(string $storedHash): bool {
        $cost = (int)Config::get('security.bcrypt_cost', 12);
        return password_needs_rehash($storedHash, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Obtém a chave Pepper secreta definida no config.json.
     */
    private static function getPepper(): string {
        return (string)Config::get('security.password_pepper', 'ZTFrameworkDefaultPepper#2026!');
    }
}
