<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function getMailConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/mail.php';
    }

    return $config;
}

function sendHtmlEmail(string $to, string $subject, string $htmlBody, ?string $toName = null): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $config = getMailConfig();
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $config['smtp_port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to, $toName ?? '');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags($htmlBody)));

        return $mail->send();
    } catch (Exception $e) {
        error_log('FleetElite mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
