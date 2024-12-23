<?php
declare(strict_types=1);

require_once(__DIR__ . '/EmailService.php');
require_once(__DIR__ . '/../../Core/Init.php');
require_once(__DIR__ . '/../../Database/DatabaseConnection.php');

/**
 * TrackedEmailService extends EmailService to add email tracking capabilities
 */
class TrackedEmailService extends EmailService
{
    private string $trackingTable = '__email_tracking';
    private string $primaryKey;
    private ?PDO $db;
    private bool $trackingEnabled = false;
    private string $trackingPath = '/v3/test/Email/Track/';

    public function __construct(?PDO $db = null, string $trackingPath = '', array $db_mail = [])
    {

        // Intentamos establecer la conexión a la base de datos
        $this->db = $db ?? $this->createDatabaseConnection();
        $this->initializeTrackingTable();

        $this->init_env($db_mail);

        if (!empty($trackingPath))
            $this->trackingPath = $trackingPath;

        $this->trackingEnabled = true;

    }

    public function init_env($db_mail = [])
    {
        global $con_mail;
        $fromname = $con_mail['fromname'] ?? '';

        if (!$db_mail) {

            if (!isset($con_mail['username']) || !isset($con_mail['pass'])) {
                throw new Exception('Email configuration not found in Init.php');
            }

            $username = $con_mail['username'];
            $pass = $con_mail['pass'];
        } else {

            if (empty($db_mail['username']) || empty($db_mail['pass'])) {
                throw new Exception('Database email configuration missing "username" or "pass".');
            }
            $username = $db_mail['username'];
            $pass = $db_mail['pass'];
        }

        parent::__construct($username, $pass, $fromname);

        if (isset($db_mail['smtp_server']) && $db_mail['smtp_server']) {
            parent::setSMTPSettings($db_mail['smtp_server'], $db_mail['port'] ?? 587);
        }

    }

    private function createDatabaseConnection(): ?PDO
    {
        try {
            $dbConnection = new DatabaseConnection();
            return $dbConnection->getPDO();
        } catch (Exception $e) {
            SecurityUtil::saveLog(__CLASS__, "Error connecting to database: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Creates the tracking table if it doesn't exist
     */
    private function initializeTrackingTable(): void
    {

        $this->primaryKey = 'id' . $this->trackingTable;

        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->trackingTable} (
                id{$this->primaryKey} BIGINT AUTO_INCREMENT PRIMARY KEY,
                tracking_id VARCHAR(50) NOT NULL UNIQUE,
                recipient_email TEXT NOT NULL,
                subject VARCHAR(255) NOT NULL,
                sent_date DATETIME NOT NULL,
                opened BOOLEAN DEFAULT FALSE,
                first_opened DATETIME NULL,
                last_opened DATETIME NULL,
                open_count INT DEFAULT 0,
                user_agent VARCHAR(255) NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_tracking_id (tracking_id)
            )";
            $this->db->exec($sql);
        } catch (PDOException $e) {
            SecurityUtil::saveLog(__CLASS__, "Error creating tracking table: " . $e->getMessage());
            $this->trackingEnabled = false;
        }
    }

    /**
     * Generates a unique tracking ID based on the last inserted ID or a random ID
     */
    private function generateTrackingId(): string
    {
        try {
            $query = "SELECTt $this->primaryKey + 1 FROM {$this->trackingTable} ORDER BY 1 DESC LIMIT 1";

            // incrementar el ID
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $lastTrackingId = $stmt->fetchColumn();

            if ($lastTrackingId) {
                return $lastTrackingId . bin2hex(random_bytes(16)); // Incrementar el último id
            }

            // Si no hay registros, generar un nuevo trackingId
            return '1' . bin2hex(random_bytes(16));
        } catch (PDOException $e) {
            SecurityUtil::saveLog(__CLASS__, "Error generating tracking ID: " . $e->getMessage());
            return bin2hex(random_bytes(16)); // Fallback en caso de error
        }
    }

    /**
     * Inserts email tracking information into the database
     */
    private function insertTrackingData(string $trackingId, string $to, string $subject): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->trackingTable} 
                (tracking_id, recipient_email, subject, sent_date, opened) 
                VALUES (?, ?, ?, NOW(), 0)"
            );
            $stmt->execute([$trackingId, $to, $subject]);
            return true;
        } catch (PDOException $e) {
            SecurityUtil::saveLog(__CLASS__, "Error inserting tracking data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Builds the tracking URL for the pixel
     */
    private function buildTrackingUrl(string $trackingId): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $output = preg_split('(v3|' . $host . ')', __FILE__);
        $folder = (count($output) === 3) ? $output[1] : '';


        return $protocol . $host . $folder . $this->trackingPath . "?id={$trackingId}";
    }

    /**
     * Constructs the tracking pixel image to insert in the email body
     */
    private function buildTrackingPixel(string $trackingUrl): string
    {
        return "
            <div style='display:none'>
                <img src='{$trackingUrl}' 
                     width='1' 
                     height='1' 
                     alt='' 
                     style='border:0;width:1px;height:1px;display:none' />
            </div>
        ";
    }

    /**
     * Sends an email with tracking capability if enabled
     */
    public function sendEmail($to, string $subject, string $body, bool $isHTML = true, array $cc = [], array $bcc = [])
    {

        if (!$this->trackingEnabled || !$isHTML) {
            return parent::sendEmail($to, $subject, $body, $isHTML);
        }

        try {
            $trackingId = $this->generateTrackingId();
            if (!$this->insertTrackingData($trackingId, $to, $subject)) {
                return [];
            }

            $trackingUrl = $this->buildTrackingUrl($trackingId);
            $trackingPixel = $this->buildTrackingPixel($trackingUrl);

            // Insert the tracking pixel into the email body
            $body = $this->insertTrackingPixel($body, $trackingPixel);

            // Send the email
            $result = parent::sendEmail($to, $subject, $body, true);
            return $result ? [$trackingId, $trackingUrl] : [];

        } catch (PDOException $e) {
            SecurityUtil::saveLog(__CLASS__, "Error in tracked email: " . $e->getMessage());
            return parent::sendEmail($to, $subject, $body, $isHTML);
        }
    }

    /**
     * Inserts the tracking pixel into the email body
     */
    private function insertTrackingPixel(string $body, string $trackingPixel): string
    {
        if (stripos($body, '</body>') !== false) {
            return str_ireplace('</body>', $trackingPixel . '</body>', $body);
        } else {
            return $body . $trackingPixel;
        }
    }

    public function handleTrackingPixel(string $trackingId): void
    {
        if (!$this->trackingEnabled) {
            $this->returnTransparentPixel();
            return;
        }

        try {
            // Capturar información adicional
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

            $stmt = $this->db->prepare(
                "UPDATE {$this->trackingTable} 
                SET opened = 1, 
                    first_opened = CASE WHEN first_opened IS NULL THEN NOW() ELSE first_opened END,
                    last_opened = NOW(),
                    open_count = open_count + 1,
                    user_agent = CASE WHEN user_agent IS NULL THEN ? ELSE user_agent END,
                    ip_address = CASE WHEN ip_address IS NULL THEN ? ELSE ip_address END
                WHERE tracking_id = ?"
            );
            $stmt->execute([$userAgent, $ipAddress, $trackingId]);
        } catch (PDOException $e) {
            SecurityUtil::saveLog(__CLASS__, "Error updating tracking info: " . $e->getMessage());
        }

        $this->returnTransparentPixel();
    }

    private function returnTransparentPixel(): void
    {
        // Asegurar que la imagen no se cachee
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        // GIF transparente de 1x1
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    /**
     * Checks if an email has been opened
     */
    public function isEmailOpened(string $trackingId): bool
    {
        if (!$this->trackingEnabled) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT opened FROM {$this->trackingTable} WHERE tracking_id = ?"
            );
            $stmt->execute([$trackingId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result && $result['opened'] == 1;
        } catch (PDOException $e) {
            SecurityUtil::saveLog(__CLASS__, "Error checking email status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets tracking statistics for a specific email
     */
    public function getEmailStats(string $trackingId): array
    {
        if (!$this->trackingEnabled) {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->trackingTable} WHERE tracking_id = ?"
            );
            $stmt->execute([$trackingId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error getting email statistics: " . $e->getMessage());
            return [];
        }
    }
}
