<?php
declare(strict_types=1);
require_once(__DIR__ . '/TrackedEmailService.php');

class EmailServicePool
{
    private $db;
    private string $tableName;
    private TrackedEmailService $trackedEmailService;

    public function __construct(?PDO $db = null, string $tableName = '__email_pool')
    {
        $this->db = $db ?? $this->createDatabaseConnection();
        $this->tableName = $tableName;

        // Crear la tabla si no existe
        $this->createTableIfNotExists();

        // Inicializar TrackedEmailService con los valores globales y de la base de datos
        $this->initTrackedEmailService();
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


    public function getTrackedEmailService(): TrackedEmailService
    {
        return $this->trackedEmailService;
    }


    /**
     * Crea la tabla si no existe.
     */
    private function createTableIfNotExists(): void
    {
        $tableName = $this->tableName;
        $primaryKey = 'id' . $tableName;

        $sql = "
        CREATE TABLE IF NOT EXISTS `$tableName` (
            `$primaryKey` INT AUTO_INCREMENT PRIMARY KEY,
            `smtp_server` VARCHAR(255) NOT NULL,
            `port` INT DEFAULT 587,
            `username` VARCHAR(255) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `email_count` INT DEFAULT 0,  -- Contador de los correos enviados con este email
            `last_used` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `is_active` BOOLEAN DEFAULT 1  -- Para marcar si la configuración está activa
        );
        ";

        // Ejecutar la consulta SQL para crear la tabla
        $this->db->exec($sql);
    }

    /**
     * Obtiene la siguiente configuración de correo del pool de correos.
     * @return array|null El correo SMTP de la siguiente configuración a usar, o null si no hay disponible.
     */
    private function getNextEmailConfig()
    {
        $tableName = $this->tableName;
        $stmt = $this->db->prepare("SELECT * FROM `$tableName` WHERE is_active = 1 ORDER BY email_count ASC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el contador de envíos de correo después de usar una configuración de correo.
     * @param int $emailId El ID de la configuración de correo que se ha utilizado.
     */
    private function updateEmailCount(int $emailId): void
    {
        $tableName = $this->tableName;
        $stmt = $this->db->prepare("UPDATE `$tableName` SET email_count = email_count + 1, last_used = NOW() WHERE id_$tableName = :id");
        $stmt->bindParam(':id', $emailId);
        $stmt->execute();
    }

    /**
     * Inicializa la clase TrackedEmailService usando la configuración global o de la base de datos.
     */
    private function initTrackedEmailService(): TrackedEmailService
    {
        $dbMail = [];

        // Obtener la configuración del siguiente correo del pool de correos
        $emailConfig = $this->getNextEmailConfig();

        if ($emailConfig) {
            // Si hay configuración en el pool, usamos esos valores
            $dbMail = [
                'username' => $emailConfig['username'],
                'pass' => $emailConfig['password'],
                'smtp_server' => $emailConfig['smtp_server'],
                'port' => $emailConfig['port']
            ];
            $this->updateEmailCount($emailConfig['id_' . $this->tableName]);
        }
        $this->trackedEmailService = new TrackedEmailService($this->db, '', $dbMail);

        return $this->trackedEmailService;
    }


}