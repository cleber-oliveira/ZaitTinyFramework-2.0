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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use RuntimeException;
use Throwable;

/**
 * ZTFramework - Mail
 * 
 * Serviço de envio de e-mails via PHPMailer integrado ao config.json.
 * Suporta templates HTML, anexos e modo simulação em ambiente de desenvolvimento.
 */
class Mail {
    private PHPMailer $mailer;
    private bool $simulateMode;
    private string $fluentTo = '';
    private string $fluentSubject = '';
    private string $fluentBody = '';

    public function __construct() {
        $mailConfig = (array)Config::get('mail', []);
        $enabled = (bool)($mailConfig['enabled'] ?? false);
        $this->simulateMode = (bool)($mailConfig['simulate_in_dev'] ?? false);

        if (!$enabled && !$this->simulateMode) {
            throw new RuntimeException("O serviço de e-mail está desativado no config.json.");
        }

        $this->mailer = new PHPMailer(true);

        if ($enabled) {
            $this->mailer->isSMTP();
            $this->mailer->Host       = (string)($mailConfig['host'] ?? 'localhost');
            $this->mailer->SMTPAuth   = (bool)($mailConfig['auth'] ?? true);
            $this->mailer->Username   = (string)($mailConfig['user'] ?? '');
            $this->mailer->Password   = (string)($mailConfig['password'] ?? '');

            $port = (int)($mailConfig['port'] ?? 587);
            $this->mailer->Port = $port;

            if ($port === 465) {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $fromEmail = (string)($mailConfig['user'] ?: 'no-reply@ztframework.local');
            $fromName  = (string)($mailConfig['from_name'] ?? 'ZTFramework');
            $this->mailer->setFrom($fromEmail, $fromName);
        }

        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Define o destinatário (interface fluente).
     */
    public function setRecipient(string $email, string $name = ''): self {
        $this->fluentTo = $email;
        return $this;
    }

    /**
     * Define o assunto do e-mail (interface fluente).
     */
    public function setSubject(string $subject): self {
        $this->fluentSubject = $subject;
        return $this;
    }

    /**
     * Define o corpo HTML do e-mail (interface fluente).
     */
    public function setBody(string $htmlBody, string $altText = ''): self {
        $this->fluentBody = $htmlBody;
        return $this;
    }

    /**
     * Envia um e-mail formatado.
     * 
     * @param string|null $to Destinatário (ou usa o definido via setRecipient)
     * @param string|null $subject Assunto (ou usa o definido via setSubject)
     * @param string|null $htmlBody Conteúdo em HTML (ou usa o definido via setBody)
     * @param array $attachments Lista opcional de caminhos de arquivos anexos
     * @return bool
     */
    public function send(?string $to = null, ?string $subject = null, ?string $htmlBody = null, array $attachments = []): bool {
        $to       = $to ?? $this->fluentTo;
        $subject  = $subject ?? $this->fluentSubject;
        $htmlBody = $htmlBody ?? $this->fluentBody;

        $mailConfig = (array)Config::get('mail', []);
        $enabled = (bool)($mailConfig['enabled'] ?? false);

        // Modo Simulação (Ambiente Local / Dev)
        if ($this->simulateMode && (!$enabled || empty($mailConfig['password']))) {
            $logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logContent = sprintf(
                "[%s] SIMULAÇÃO DE E-MAIL\nPara: %s\nAssunto: %s\nCorpo:\n%s\n%s\n",
                date('Y-m-d H:i:s'),
                $to,
                $subject,
                strip_tags($htmlBody),
                str_repeat('-', 60)
            );
            file_put_contents($logDir . '/mail.log', $logContent, FILE_APPEND);
            return true;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            foreach ($attachments as $filePath) {
                if (is_file($filePath)) {
                    $this->mailer->addAttachment($filePath);
                }
            }

            return $this->mailer->send();

        } catch (Throwable $e) {
            throw new RuntimeException("Falha no envio de e-mail via PHPMailer: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Método estático facilitador para envio rápido de e-mails em HTML.
     * Suporta 3 argumentos ($to, $subject, $htmlBody) ou 4 argumentos ($toEmail, $toName, $subject, $htmlBody).
     */
    public static function quickSend(string $to, string $subjectOrName, string $htmlBodyOrSubject, ?string $optionalBody = null): bool {
        $mail = new self();
        if ($optionalBody !== null) {
            return $mail->send($to, $htmlBodyOrSubject, $optionalBody);
        }
        return $mail->send($to, $subjectOrName, $htmlBodyOrSubject);
    }
}
