<?php 
declare(strict_types=1);

require_once(__DIR__ . "/../Core/ConfigurationManager.php");

/**
 * MySQL PDO connection manager with connection pooling
 */
class DatabaseConnection extends ConfigurationManager {
    private ?PDO $pdo = null;
    private static array $connections = [];
    private const MAX_CONNECTIONS = 10;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get a PDO connection with connection pooling
     * @param string $setName Optional character set name
     * @return PDO
     * @throws PDOException
     */
    public static function getPDO(string $setName = ''): PDO {
        $host = ConfigurationManager::get('HOST_PROD');
        $db = ConfigurationManager::get('DB_PROD');
        $user = ConfigurationManager::get('USER_PROD');
        $pass = ConfigurationManager::get('PASS_PROD');
        
        $connectionKey = $host . $db . $user;
        
        if (isset(self::$connections[$connectionKey]) && self::$connections[$connectionKey] instanceof PDO) {
            try {
                // Test if connection is still alive
                self::$connections[$connectionKey]->query('SELECT 1');
                return self::$connections[$connectionKey];
            } catch (PDOException $e) {
                // Connection lost, remove it from pool
                unset(self::$connections[$connectionKey]);
            }
        }

        // Create new connection if pool isn't full
        if (count(self::$connections) < self::MAX_CONNECTIONS) {
            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ];

                $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db);
                $pdo = new PDO($dsn, $user, $pass, $options);
                
                if ($setName) {
                    $pdo->exec("SET NAMES " . $pdo->quote($setName));
                }

                self::$connections[$connectionKey] = $pdo;
                return $pdo;
            } catch (PDOException $e) {
                throw new PDOException("Connection failed: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        throw new PDOException("Connection pool is full");
    }

    /**
     * Create a PDO connection from configuration object
     * @param mixed $config Database configuration object or JSON string
     * @return PDO
     * @throws PDOException|JsonException
     */
    public static function dbPDO($config): PDO {
        try {
            if (is_string($config)) {
                $config = json_decode($config, false, 512, JSON_THROW_ON_ERROR);
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', 
                $config->db_host, 
                $config->db_name
            );

            return new PDO($dsn, $config->db_username, $config->db_password, $options);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Close all connections in the pool
     */
    public static function close(): void {
        foreach (self::$connections as $key => $connection) {
            self::$connections[$key] = null;
        }
        self::$connections = [];
    }

    /**
     * Get and cast value based on column type
     * @param string $table Table name
     * @param string $key Column name
     * @param mixed $value Value to cast
     * @return mixed
     */
    public static function getValueForType(string $table, string $key, $value) {
        return self::castToType($value, self::getPhpType(self::getColumnType($table, $key)));
    }

    /**
     * Cast value to specified PHP type
     * @param mixed $value Value to cast
     * @param string $type Target PHP type
     * @return mixed
     */
    private static function castToType($value, string $type) {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            case 'datetime':
                return new DateTime($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Get column type from database schema
     * @param string $table Table name
     * @param string $column Column name
     * @return string|null
     */
    private static function getColumnType(string $table, string $column): ?string {
        $pdo = self::getPDO();
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            if ($col['Field'] == $column) {
                return $col['Type'];
            }
        }

        return null;
    }

    /**
     * Get PHP type from column type
     * @param string $columnType Column type
     * @return string
     */
    private static function getPhpType(string $columnType): string {
        if (strpos($columnType, 'int') !== false) {
            return 'int';
        } elseif (strpos($columnType, 'float') !== false || strpos($columnType, 'double') !== false || strpos($columnType, 'decimal') !== false) {
            return 'float';
        } elseif (strpos($columnType, 'char') !== false || strpos($columnType, 'text') !== false) {
            return 'string';
        } elseif (strpos($columnType, 'datetime') !== false) {
            return 'datetime';
        } elseif (strpos($columnType, 'date') !== false) {
            return 'date';
        } elseif (strpos($columnType, 'time') !== false) {
            return 'time';
        } else {
            return 'string'; // Default type for other types
        }
    }

    /**
     * Get tables from database
     * @return string
     */
    public static function getTablesDB(): string {
        $option = '';
        $pdo = self::getPDO();
        $stmt = $pdo->query("SHOW TABLES");

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $option .= "<option value='{$row[0]}'>{$row[0]}</option>";
            }
        }

        return $option;
    }

    /**
     * Encripta una cadena utilizando base64 repetidamente.
     *
     * @param string $text La cadena a encriptar.
     * @param int $iterations El número de veces que se aplicará base64_encode.
     * @return string La cadena encriptada.
     */
    public static function customEncrypt(string $text, int $iterations = 3): string {
        $encryptedText = $text;
        for ($i = 0; $i < $iterations; $i++) {
            $encryptedText = base64_encode($encryptedText);
        }
        return $encryptedText;
    }

    /**
     * Desencripta una cadena previamente encriptada con customEncrypt.
     *
     * @param string $encryptedText La cadena encriptada.
     * @param int $iterations El número de veces que se aplicó base64_encode.
     * @return string La cadena desencriptada.
     */
    public static function customDecrypt(string $encryptedText, int $iterations = 3): string {
        $decryptedText = $encryptedText;
        for ($i = 0; $i < $iterations; $i++) {
            $decryptedText = base64_decode($decryptedText);
        }
        return $decryptedText;
    }

    /**
     * Execute a query and return the result
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param string $setName Optional character set
     * @return array Query results
     * @throws PDOException
     */
    public function executeQuery(string $query, array $params = [], string $setName = ''): array {
        try {
            $pdo = self::getPDO($setName);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (self::isDebugMode()) {
                throw $e;
            }
            throw new PDOException("Query execution failed", (int)$e->getCode(), $e);
        }
    }

    /**
     * Execute a query that doesn't return results
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param string $setName Optional character set
     * @return int Number of affected rows
     * @throws PDOException
     */
    public function executeNonQuery(string $query, array $params = [], string $setName = ''): int {
        try {
            $pdo = self::getPDO($setName);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (self::isDebugMode()) {
                throw $e;
            }
            throw new PDOException("Query execution failed", (int)$e->getCode(), $e);
        }
    }

    /**
     * Begin a transaction
     * @param string $setName Optional character set
     * @throws PDOException
     */
    public function beginTransaction(string $setName = ''): void {
        self::getPDO($setName)->beginTransaction();
    }

    /**
     * Commit a transaction
     * @param string $setName Optional character set
     * @throws PDOException
     */
    public function commit(string $setName = ''): void {
        self::getPDO($setName)->commit();
    }

    /**
     * Rollback a transaction
     * @param string $setName Optional character set
     * @throws PDOException
     */
    public function rollback(string $setName = ''): void {
        self::getPDO($setName)->rollBack();
    }

    /**
     * Get the last inserted ID
     * @param string $setName Optional character set
     * @return string Last inserted ID
     * @throws PDOException
     */
    public function getLastInsertId(string $setName = ''): string {
        return self::getPDO($setName)->lastInsertId();
    }

    /**
     * Close all connections in the pool
     */
    public static function closeConnections(): void {
        foreach (self::$connections as $key => $connection) {
            self::$connections[$key] = null;
            unset(self::$connections[$key]);
        }
    }
}
