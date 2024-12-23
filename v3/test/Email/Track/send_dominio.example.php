<?php
chdir(directory: "../../../");
//print_r(getcwd());die;

require_once "config/Core/Init.php";
require_once "config/Core/ConfigurationManager.php";
require_once "config/Library/Email/EmailService.php";
require_once "config/Library/Email/TrackedEmailService.php";

// Configura tu cuenta de correo y credenciales
$username = 'user@dominio.com'; // Reemplaza con tu correo de dominio propio
$password = 'pass_email_dominio';           // Reemplaza con tu contraseña
$fromName = 'User Name public';
$smtpServer = 'smtp.dominio.com';
// Opcional: CC y BCC
$bcc = []; //['email1@gmail.com', 'email2@hotmail.com'];
$cc = [];

// Crear una instancia de la clase EmailService
$emailService = new EmailService($username, $password, $fromName);

// Establecer el servidor SMTP para el dominio propio
$emailService->setSMTPSettings($smtpServer, 587);

// Información del correo
$to = 'chdgp1988@gmail.com';
$subject = 'Prueba de Envío de Correo';
$body = '<h1>Hola!</h1><p>Este es un correo de prueba enviado desde la clase EmailService Bcc ' . date('Y-m-d H:m:i') . ' con un dominio propio.</p>';



// Enviar el correo
$result = $emailService->sendEmail($to, $subject, $body, true, $cc, $bcc);

if ($result === true) {
    echo 'Correo enviado correctamente.';
} else {
    echo 'Error al enviar el correo: ' . $result;
}
