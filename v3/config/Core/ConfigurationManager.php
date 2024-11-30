<?php
declare(strict_types=1);

require_once(__DIR__ . "/Environment.php");
require_once(__DIR__ . "/Init.php");

/**
 * Core configuration class for managing database and application settings
 */
class ConfigurationManager {
    protected static bool $debugMode = false;
    protected static array $config = [];
    protected const REQUIRED_DB_KEYS = ['DB_PROD', 'PASS_PROD', 'USER_PROD', 'HOST_PROD'];

    public function __construct() {
        $this->initializeConfig();
    }

    /**
     * Initialize configuration with database settings
     * @throws RuntimeException if required database configuration is missing
     */
    private function initializeConfig(): void {
        global $dbconect;

        // Validate database configuration
        foreach (self::REQUIRED_DB_KEYS as $key) {
            if (!isset($dbconect[$key]) || (empty($dbconect[$key]) && $key !== 'PASS_PROD')) {
                throw new RuntimeException("Missing required database configuration: {$key}");
            }
            
            self::$config[$key] = $dbconect[$key];
            if (!defined($key)) {
                define($key, $dbconect[$key]);
            }
        }
    }

    /**
     * Enable or disable debug mode
     * @param bool $enabled
     */
    public static function setDebugMode(bool $enabled = true): void {
        self::$debugMode = $enabled;
    }

    /**
     * Check if debug mode is enabled
     * @return bool
     */
    public static function isDebugMode(): bool {
        return self::$debugMode;
    }

    /**
     * Get a configuration value
     * @param string $key Configuration key
     * @return mixed Configuration value
     * @throws RuntimeException if key not found
     */
    public static function get(string $key) {
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        throw new RuntimeException("Configuration key not found: {$key}");
    }

    /**
     * Set configuration value
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value): void {
        self::$config[$key] = $value;
    }

    /**
     * Get all configuration values
     * @return array
     */
    public static function all(): array {
        return self::$config;
    }

    /**
     * Clear all configuration values
     */
    public static function clear(): void {
        self::$config = [];
    }

    /**
     * @deprecated Use setDebugMode() instead
     */
    public static function debugObj(bool $bool = false): void {
        self::setDebugMode($bool);
    }

    /**
     * @deprecated Use isDebugMode() instead
     */
    public static function debugCtrl(): bool {
        return self::isDebugMode();
    }
}

// Initialize configuration
$ConfigurationManager = new ConfigurationManager();