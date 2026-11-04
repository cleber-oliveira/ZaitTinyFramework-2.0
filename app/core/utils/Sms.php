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

use RuntimeException;

/**
 * ZTFramework - Sms
 * 
 * Componente oficial de envio de mensagens SMS via Twilio REST API
 * com suporte a modo de simulação em desenvolvimento (storage/logs/sms.log).
 */
class Sms {
    /**
     * Envia uma mensagem SMS para o destinatário informado.
     * 
     * @param string $to Número com código do país (ex: '+5511999998888')
     * @param string $message Texto da mensagem (ex: código OTP de 6 dígitos)
     * @return bool
     */
    public static function send(string $to, string $message): bool {
        $enabled = (bool)Config::get('sms.enabled', true);
        if (!$enabled) {
            return false;
        }

        $simulate = (bool)Config::get('sms.simulate_in_dev', true);
        if ($simulate) {
            self::logSimulation($to, $message);
            return true;
        }

        $accountSid = (string)Config::get('sms.twilio.account_sid');
        $authToken  = (string)Config::get('sms.twilio.auth_token');
        $fromNumber = (string)Config::get('sms.twilio.from_number');

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            throw new RuntimeException("Credenciais do Twilio não configuradas no config.json.");
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $data = [
            'From' => $fromNumber,
            'To'   => $to,
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Registra o SMS em log durante o ambiente de desenvolvimento.
     */
    private static function logSimulation(string $to, string $message): void {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/sms.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [SIMULAÇÃO SMS TWILIO] Destinatário: {$to} | Mensagem: {$message}\n";

        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
