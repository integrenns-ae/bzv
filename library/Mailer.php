<?php
declare(strict_types=1);

/**
 * Sehr schlanker Mail-Versand über PHP mail().
 * MAIL_TRANSPORT='log' schreibt statt zu senden — für lokale Entwicklung.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        require_once __DIR__ . '/../config/db.php';
        $headers = [
            'From: ' . APP_MAIL_FROM,
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ];
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $hdr = implode("\r\n", $headers);

        if (defined('MAIL_TRANSPORT') && MAIL_TRANSPORT === 'log') {
            $line = "---\nTO: $to\nSUBJECT: $subject\n$hdr\n\n$body\n";
            if (defined('MAIL_LOG_FILE')) {
                @file_put_contents(MAIL_LOG_FILE, $line, FILE_APPEND);
            } else {
                error_log($line);
            }
            return true;
        }
        return mail($to, $subject, $body, $hdr);
    }
}
