<?php
declare(strict_types=1);

/**
 * Email service class for handling email operations
 */
class EmailService
{
    private string $smtpServer = 'smtp.gmail.com'; // Cambia esto a tu servidor SMTP
    private int $port = 587;
    private string $username;
    private string $password;
    private string $fromName;

    public function __construct(string $username, string $password, string $fromName = '')
    {
        $this->username = $username;
        $this->password = $password;
        $this->fromName = $fromName ?: $username;
    }

    /**
     * Función para configurar el servidor SMTP personalizado.
     *
     * @param string $smtpServer Dirección del servidor SMTP (e.g., smtp.tudominio.com)
     * @param int $port El puerto SMTP (normalmente 587 o 465 para SSL/TLS)
     */
    public function setSMTPSettings(string $smtpServer, int $port = 587)
    {
        $this->smtpServer = $smtpServer;
        $this->port = $port;
    }

    public function sendEmail($to, string $subject, string $body, bool $isHTML = true, array $cc = [], array $bcc = [])
    {
        // Asegúrate de que $to sea un array, si no lo es, conviértelo
        $to = is_array($to) ? $to : [$to];

        // Preparar los encabezados
        $headers = [
            'From' => $this->fromName . ' <' . $this->username . '>',
            'Reply-To' => $this->username,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => $isHTML ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8'
        ];

        // Añadir CC y BCC a los encabezados si se proporcionan
        if (!empty($cc) && is_array($cc)) {
            $headers['CC'] = implode(', ', $cc);
        }

        if (!empty($bcc) && is_array($bcc)) {
            $headers['BCC'] = implode(', ', $bcc);
        }

        // Abrir la conexión SMTP
        $smtp_conn = fsockopen($this->smtpServer, $this->port, $errno, $errstr, 30);
        if (!$smtp_conn) {
            return "Conexión fallida: $errstr ($errno)";
        }

        $this->serverParse($smtp_conn, '220');
        fwrite($smtp_conn, 'EHLO ' . $this->smtpServer . "\r\n");
        $this->serverParse($smtp_conn, '250');
        fwrite($smtp_conn, "STARTTLS\r\n");
        $this->serverParse($smtp_conn, '220');

        stream_socket_enable_crypto($smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        fwrite($smtp_conn, 'EHLO ' . $this->smtpServer . "\r\n");
        $this->serverParse($smtp_conn, '250');
        fwrite($smtp_conn, 'AUTH LOGIN' . "\r\n");
        $this->serverParse($smtp_conn, '334');
        fwrite($smtp_conn, base64_encode($this->username) . "\r\n");
        $this->serverParse($smtp_conn, '334');
        fwrite($smtp_conn, base64_encode($this->password) . "\r\n");
        $this->serverParse($smtp_conn, '235');

        // Establecer la dirección de "From"
        fwrite($smtp_conn, 'MAIL FROM: <' . $this->username . '>' . "\r\n");
        $this->serverParse($smtp_conn, '250');

        // Enviar los destinatarios "To"
        foreach ($to as $recipient) {
            fwrite($smtp_conn, 'RCPT TO: <' . $recipient . '>' . "\r\n");
            $this->serverParse($smtp_conn, '250');
        }

        // Enviar los destinatarios "CC" (si hay)
        if (!empty($cc)) {
            foreach ($cc as $recipient) {
                fwrite($smtp_conn, 'RCPT TO: <' . $recipient . '>' . "\r\n");
                $this->serverParse($smtp_conn, '250');
            }
        }

        // Enviar los destinatarios "BCC" (si hay)
        if (!empty($bcc)) {
            foreach ($bcc as $recipient) {
                fwrite($smtp_conn, 'RCPT TO: <' . $recipient . '>' . "\r\n");
                $this->serverParse($smtp_conn, '250');
            }
        }

        fwrite($smtp_conn, 'DATA' . "\r\n");
        $this->serverParse($smtp_conn, '354');

        // Crear el cuerpo del mensaje
        $message = '';
        foreach ($headers as $key => $value) {
            $message .= $key . ': ' . $value . "\r\n";
        }
        $message .= 'Subject: ' . $subject . "\r\n";
        $message .= "\r\n" . $body . "\r\n.\r\n";

        // Enviar el mensaje
        fwrite($smtp_conn, $message);
        $this->serverParse($smtp_conn, '250');

        // Cerrar la conexión SMTP
        fwrite($smtp_conn, 'QUIT' . "\r\n");
        fclose($smtp_conn);

        return true;
    }

    private function serverParse($conn, $expected_response)
    {
        $response = '';
        while (substr($response, 3, 1) != ' ') {
            if (!($response = fgets($conn, 256))) {
                return 'Problemas de lectura';
            }
        }
        if (substr($response, 0, 3) != $expected_response) {
            return 'Error: ' . $response;
        }
    }
}