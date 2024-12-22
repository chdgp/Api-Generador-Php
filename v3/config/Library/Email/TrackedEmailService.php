<?php
declare(strict_types=1);

require_once(__DIR__ . '/EmailService.php');
require_once(__DIR__ . '/../../Core/Init.php');
require_once(__DIR__ . '/../../Database/DatabaseConnection.php');

/**
 * TrackedEmailService extends EmailService to add email tracking capabilities
 */
class TrackedEmailService extends EmailService {
    private string $trackingTable = 'email_tracking';
    private ?PDO $db;
    private bool $trackingEnabled = false;
    private string $trackingPath;

    public function __construct(
        ?PDO $db = null,
        string $trackingPath = '/v3/config/Library/Email/Example/Track/'
    ) {
        // Obtener configuración del Init.php
        global $con_mail;
        
        if (!isset($con_mail['username']) || !isset($con_mail['pass'])) {
            throw new Exception('Email configuration not found in Init.php');
        }

        parent::__construct();

        // Si no se proporciona conexión, intentar crear una usando DatabaseConnection
        if ($db === null) {
            try {
                $dbConnection = new DatabaseConnection();
                $this->db = $dbConnection->getPDO();
            } catch (Exception $e) {
                error_log("Error connecting to database: " . $e->getMessage());
                return;
            }
        } else {
            $this->db = $db;
        }

        $this->trackingPath = $trackingPath;
        $this->trackingEnabled = true;
        $this->initializeTrackingTable();
    }

    /**
     * Creates the tracking table if it doesn't exist
     */
    private function initializeTrackingTable(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->trackingTable} (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                tracking_id VARCHAR(32) NOT NULL UNIQUE,
                recipient_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                sent_date DATETIME NOT NULL,
                opened BOOLEAN DEFAULT FALSE,
                first_opened DATETIME NULL,
                last_opened DATETIME NULL,
                open_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_tracking_id (tracking_id)
            )";
            
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating tracking table: " . $e->getMessage());
            $this->trackingEnabled = false;
        }
    }

    /**
     * Sends an email with tracking capability if enabled
     */
    public function sendEmail(string $to, string $subject, string $body, bool $isHTML = true): mixed {
        if (!$this->trackingEnabled || !$isHTML) {
            return parent::sendEmail($to, $subject, $body, $isHTML);
        }

        try {
            // Generate unique tracking ID
            $trackingId = bin2hex(random_bytes(16));

            // Store tracking information
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->trackingTable} 
                (tracking_id, recipient_email, subject, sent_date, opened) 
                VALUES (?, ?, ?, NOW(), 0)"
            );
            $stmt->execute([$trackingId, $to, $subject]);

            // Create tracking pixel URL using the server's hostname
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $trackingUrl = $protocol . $host . $this->trackingPath . "?id={$trackingId}";
            
            // Add tracking pixel to email body
            $trackingPixel = "<img src='{$trackingUrl}' width='1' height='1' style='display:none;' alt='' />";
            $body .= $trackingPixel;

            $result = parent::sendEmail($to, $subject, $body, true);
            return $result ? $trackingId : false;

        } catch (PDOException $e) {
            error_log("Error in tracked email: " . $e->getMessage());
            return parent::sendEmail($to, $subject, $body, $isHTML);
        }
    }

    /**
     * Handles the tracking pixel request
     */
    public function handleTrackingPixel(string $trackingId): void {
        if (!$this->trackingEnabled) {
            $this->returnTransparentPixel();
            return;
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->trackingTable} 
                SET opened = 1, 
                    first_opened = CASE WHEN first_opened IS NULL THEN NOW() ELSE first_opened END,
                    last_opened = NOW(),
                    open_count = open_count + 1
                WHERE tracking_id = ?"
            );
            $stmt->execute([$trackingId]);
        } catch (PDOException $e) {
            error_log("Error updating tracking info: " . $e->getMessage());
        }

        $this->returnTransparentPixel();
    }

    private function returnTransparentPixel(): void {
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    /**
     * Checks if an email has been opened
     */
    public function isEmailOpened(string $trackingId): bool {
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
            error_log("Error checking email status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets tracking statistics for a specific email
     */
    public function getEmailStats(string $trackingId): array {
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