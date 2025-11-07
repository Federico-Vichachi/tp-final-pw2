<?php

// Cargar PHPMailer manualmente
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mail;
    private $enabled = false;
    private $cfg = [];

    public function __construct(){
        $iniPath = __DIR__ . '/../config/config.ini';
        if (is_file($iniPath)) {
            $this->cfg = parse_ini_file($iniPath);
        }

        try {
            $this->mail = new PHPMailer(true);
            $this->configurar();
            $this->enabled = true;
        } catch (\Throwable $e) {
            error_log('EmailService init error: ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    private function configurar(){
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER; // Cambiar a DEBUG_SERVER solo para debugging
        $this->mail->isSMTP();
        $this->mail->Host = $this->cfg['smtp_host'] ?? 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->cfg['smtp_user'] ?? '';
        $this->mail->Password = $this->cfg['smtp_pass'] ?? '';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = 465;

    }

    public function enviarCorreo($destinatario, $asunto, $cuerpo){
        if (!$this->enabled) {
            error_log('EmailService deshabilitado: PHPMailer no disponible o configuración faltante.');
            return false;
        }

        try {
            $fromEmail = $this->cfg['smtp_from_email'] ?? ($this->cfg['smtp_username'] ?? '');
            $fromName = $this->cfg['smtp_from_name'] ?? 'Aplicación';
            if (!$fromEmail) {
                throw new Exception('Remitente no configurado (smtp_from_email/smtp_user).');
            }

            $this->mail->setFrom($fromEmail, $fromName);
            $this->mail->addAddress($destinatario);
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $cuerpo;

            $this->mail->send();
            error_log("Email enviado exitosamente a: $destinatario");
            return true;
        } catch (\Throwable $e) {
            error_log("Error al enviar el correo a $destinatario: " . $e->getMessage());
            return false;
        }
    }

}