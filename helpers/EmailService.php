<?php

require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    private $mail;

    public function __construct(){
        $this->mail = new PHPMailer(true);
        $this->configurar();
    }

    private function configurar(){

        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = "vichachifederico@gmail.com";
        $this->mail->Password = "oxpwelseptrvadst";
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = 465;
    }

    public function enviarCorreo($destinatario, $asunto, $cuerpo){
        try {
            $this->mail->setFrom('vichachifederico@gmail.com', 'Federico Vichachi');
            $this->mail->addAddress($destinatario);
            $this->mail->isHTML(true);
            $this->mail->Subject = $asunto;
            $this->mail->Body = $cuerpo;

            $this->mail->send();
            error_log("Email enviado exitosamente a: $destinatario");
            return true;
        } catch (Exception $e) {
            echo '<script>alert("Error al enviar el correo: ' . $this->mail->ErrorInfo . '");</script>';
            error_log("Error al enviar el correo a $destinatario: " . $this->mail->ErrorInfo);
            return false;
        }
    }


}