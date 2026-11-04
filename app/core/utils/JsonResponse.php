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

class JsonResponse {
    /**
     * Emite resposta de sucesso (200, 201, etc.).
     */
    public static function success(mixed $data = null, string $message = 'Operação realizada com sucesso.', int $statusCode = 200, bool $terminate = true): string {
        return self::send([
            'status'  => true,
            'code'    => $statusCode,
            'message' => $message,
            'data'    => $data
        ], $statusCode, $terminate);
    }

    /**
     * Emite resposta de erro (400, 401, 403, 404, 500, etc.).
     */
    public static function error(int $statusCode, string $errorCode, string $message, mixed $details = null, bool $terminate = true): string {
        $payload = [
            'status'  => false,
            'code'    => $statusCode,
            'error'   => $errorCode,
            'message' => $message
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        return self::send($payload, $statusCode, $terminate);
    }

    /**
     * Envia o JSON com cabeçalhos HTTP adequados.
     */
    public static function send(array $payload, int $statusCode = 200, bool $terminate = true): string {
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        echo $json;

        if ($terminate && php_sapi_name() !== 'cli') {
            exit;
        }

        return (string)$json;
    }
}
