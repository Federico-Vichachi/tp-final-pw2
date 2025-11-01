<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mail;

    public function __construct(){
        $this->mail = new PHPMailer(true);
        $this->configurar();
    }

    private function configurar(){

        $username = getenv('EMAIL') ?: '';
        $password = getenv('PASSWORD') ?: '';

        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $username;
        $this->mail->Password = $password;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
    }

    public function enviarCorreo($destinatario, $asunto, $cuerpo){
        try {
            $this->mail->setFrom(getenv('EMAIL') ?: '', 'Tu Nombre');
            $this->mail->addAddress($destinatario);
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body    = $cuerpo;

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar el correo: {$this->mail->ErrorInfo}");
            return false;
        }
    }

}