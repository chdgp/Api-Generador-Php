<?php
declare(strict_types=1);

/**
 * Email service class for handling email operations
 */
class EmailService
{
    private string $smtpServer = 'smtp.gmail.com';
    private int $port = 587;
    private string $username;
    private string $password;
    private string $fromName;
    private string $boundary;

    public function __construct(string $username, string $password, string $fromName = '')
    {
        $this->username = $username;
        $this->password = $password;
        $this->fromName = $fromName ?: $username;
        $this->boundary = md5(uniqid('', true));
    }

    public function setSMTPSettings(string $smtpServer, int $port = 587)
    {
        $this->smtpServer = $smtpServer;
        $this->port = $port;
    }

    public function sendEmail($to, string $subject, string $body, bool $isHTML = true, array $cc = [], array $bcc = [], array $attachments = [])
    {
        $to = is_array($to) ? $to : [$to];

        // Preparar los encabezados base
        $headers = [
            'From' => $this->fromName . ' <' . $this->username . '>',
            'Reply-To' => $this->username,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0'
        ];

        // Configurar el tipo de contenido según si hay adjuntos
        if (!empty($attachments)) {
            $headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->boundary . '"';
        } else {
            $headers['Content-Type'] = $isHTML ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';
        }

        if (!empty($cc)) {
            $headers['CC'] = implode(', ', $cc);
        }

        if (!empty($bcc)) {
            $headers['BCC'] = implode(', ', $bcc);
        }

        // Conexión SMTP
        $smtp_conn = fsockopen($this->smtpServer, $this->port, $errno, $errstr, 30);
        if (!$smtp_conn) {
            throw new Exception("Conexión fallida: $errstr ($errno)");
        }

        // Autenticación SMTP
        $this->handleSMTPAuthentication($smtp_conn);

        // Enviar remitente y destinatarios
        $this->sendRecipients($smtp_conn, $to, $cc, $bcc);

        // Construir el mensaje
        $message = $this->buildMessageHeaders($headers, $subject);

        if (!empty($attachments)) {
            // Agregar el cuerpo del mensaje con límite
            $message .= "--" . $this->boundary . "\r\n";
            $message .= "Content-Type: " . ($isHTML ? 'text/html' : 'text/plain') . "; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $body . "\r\n\r\n";

            // Agregar los archivos adjuntos
            foreach ($attachments as $path => $name) {
                
                $attachment = $this->prepareAttachment($path, $name);
                if ($attachment) {
                    $message .= $attachment;
                }
            }

            // Cerrar el límite
            $message .= "--" . $this->boundary . "--\r\n";
        } else {
            $message .= "\r\n" . $body;
        }

        // Finalizar el mensaje
        $message .= "\r\n.\r\n";

        // Enviar el mensaje
        fwrite($smtp_conn, "DATA\r\n");
        $this->serverParse($smtp_conn, '354');
        fwrite($smtp_conn, $message);
        $this->serverParse($smtp_conn, '250');

        // Cerrar conexión
        fwrite($smtp_conn, "QUIT\r\n");
        fclose($smtp_conn);

        return true;
    }

    private function handleSMTPAuthentication($smtp_conn): void
    {
        $this->serverParse($smtp_conn, '220');
        fwrite($smtp_conn, "EHLO " . $this->smtpServer . "\r\n");
        $this->serverParse($smtp_conn, '250');
        fwrite($smtp_conn, "STARTTLS\r\n");
        $this->serverParse($smtp_conn, '220');

        stream_socket_enable_crypto($smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        fwrite($smtp_conn, "EHLO " . $this->smtpServer . "\r\n");
        $this->serverParse($smtp_conn, '250');
        fwrite($smtp_conn, "AUTH LOGIN\r\n");
        $this->serverParse($smtp_conn, '334');
        fwrite($smtp_conn, base64_encode($this->username) . "\r\n");
        $this->serverParse($smtp_conn, '334');
        fwrite($smtp_conn, base64_encode($this->password) . "\r\n");
        $this->serverParse($smtp_conn, '235');
    }

    private function sendRecipients($smtp_conn, array $to, array $cc, array $bcc): void
    {
        fwrite($smtp_conn, 'MAIL FROM: <' . $this->username . '>' . "\r\n");
        $this->serverParse($smtp_conn, '250');

        foreach ($to as $recipient) {
            fwrite($smtp_conn, 'RCPT TO: <' . $recipient . '>' . "\r\n");
            $this->serverParse($smtp_conn, '250');
        }

        foreach ($cc as $recipient) {
            fwrite($smtp_conn, 'RCPT TO: <' . $recipient . '>' . "\r\n");
            $this->serverParse($smtp_conn, '250');
        }

        foreach ($bcc as $recipient) {
            fwrite($smtp_conn, 'RCPT TO: <' . $recipient . '>' . "\r\n");
            $this->serverParse($smtp_conn, '250');
        }
    }

    private function buildMessageHeaders(array $headers, string $subject): string
    {
        $message = '';
        foreach ($headers as $key => $value) {
            $message .= $key . ': ' . $value . "\r\n";
        }
        $message .= 'Subject: ' . $subject . "\r\n";
        return $message;
    }

    private function prepareAttachment(string $path, string $name): ?string
    {
        if (!file_exists($path) || !is_readable($path)) {
            error_log("No se puede acceder al archivo adjunto: $path");
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            error_log("Error al leer el archivo adjunto: $path");
            return null;
        }

        $attachment = "--" . $this->boundary . "\r\n";
        $attachment .= "Content-Type: application/octet-stream; name=\"" . basename($name) . "\"\r\n";
        $attachment .= "Content-Disposition: attachment; filename=\"" . basename($name) . "\"\r\n";
        $attachment .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $attachment .= chunk_split(base64_encode($content)) . "\r\n";

        return $attachment;
    }

    private function serverParse($conn, $expected_response)
    {
        $response = '';
        while (substr($response, 3, 1) != ' ') {
            if (!($response = fgets($conn, 256))) {
                throw new Exception('Problemas de lectura en la conexión SMTP');
            }
        }
        if (substr($response, 0, 3) != $expected_response) {
            throw new Exception('Error SMTP: ' . $response);
        }
    }
}



/**
 * Class AttachmentValidator
 * Validates email attachments before sending
 */
class AttachmentValidator
{
    private array $errors = [];
    private array $validFiles = [];

    /**
     * Validates an array of attachments
     * 
     * @param array $attachments Array of file paths with their names
     * @return bool True if all files are valid
     */
    public function validateAttachments(array $attachments): bool
    {
        $this->errors = [];
        $this->validFiles = [];

        foreach ($attachments as $path => $name) {
            if (!$this->validateFile($path, $name)) {
                continue;
            }

            $this->validFiles[$path] = $name;
        }

        return empty($this->errors);
    }

    /**
     * Validates a single file
     */
    private function validateFile(string $path, string $name): bool
    {
        // Verificar si el archivo existe
        if (!file_exists($path)) {
            $this->errors[] = "El archivo no existe: $path";
            return false;
        }

        // Verificar si es un archivo regular (no directorio u otro tipo)
        if (!is_file($path)) {
            $this->errors[] = "La ruta no es un archivo válido: $path";
            return false;
        }

        // Verificar permisos de lectura
        if (!is_readable($path)) {
            $this->errors[] = "No hay permisos de lectura para el archivo: $path";
            return false;
        }

        // Verificar que el archivo tenga contenido
        if (filesize($path) === 0) {
            $this->errors[] = "El archivo está vacío: $path";
            return false;
        }

        // Verificar que el nombre del archivo sea válido
        if (!$this->isValidFileName($name)) {
            $this->errors[] = "Nombre de archivo inválido: $name";
            return false;
        }

        return true;
    }

    /**
     * Validates filename
     */
    private function isValidFileName(string $name): bool
    {
        return preg_match('/^[a-zA-Z0-9_\-\. ]+$/', $name) === 1;
    }

    /**
     * Returns validation errors if any
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns validated files
     */
    public function getValidFiles(): array
    {
        return $this->validFiles;
    }
}