<?php
declare(strict_types=1);

require_once(__DIR__ . "/../../Core/ConfigurationManager.php");

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

    public function __construct(string $username, string $password, string $fromName = '')
    {
        $this->username = $username;
        $this->password = $password;
        $this->fromName = $fromName ?: $username;
    }

    public function sendEmail(string $to, string $subject, string $body, bool $isHTML = true)
    {
        $headers = [
            'From' => $this->fromName . ' <' . $this->username . '>',
            'Reply-To' => $this->username,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => $isHTML ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8'
        ];

        $smtp_conn = fsockopen($this->smtpServer, $this->port, $errno, $errstr, 30);
        if (!$smtp_conn)
            return "Conexión fallida: $errstr ($errno)";

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

        fwrite($smtp_conn, 'MAIL FROM: <' . $this->username . '>' . "\r\n");
        $this->serverParse($smtp_conn, '250');
        fwrite($smtp_conn, 'RCPT TO: <' . $to . '>' . "\r\n");
        $this->serverParse($smtp_conn, '250');

        fwrite($smtp_conn, 'DATA' . "\r\n");
        $this->serverParse($smtp_conn, '354');

        $message = '';
        foreach ($headers as $key => $value) {
            $message .= $key . ': ' . $value . "\r\n";
        }
        $message .= 'Subject: ' . $subject . "\r\n";
        $message .= "\r\n" . $body . "\r\n.\r\n";
        fwrite($smtp_conn, $message);
        $this->serverParse($smtp_conn, '250');

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