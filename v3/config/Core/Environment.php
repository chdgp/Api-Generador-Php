<?php
declare(strict_types=1);

// Error reporting configuration
ini_set('display_errors', 'on');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

// Session configuration
$sessionLifetime = 21200;
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', (string) $sessionLifetime);
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    ini_set('session.save_path', sys_get_temp_dir());
    ini_set('session.cookie_secure', 'on');
    ini_set('session.cookie_httponly', 'on');
    ini_set('session.use_strict_mode', 'on');
}

// Timezone configuration
date_default_timezone_set('Etc/GMT+5');

// Theme configuration
const THEME_NAME = 'sbadmin';
const THEME_PATH = 'theme/' . THEME_NAME . '/php/';

// Version control
define('VERSION', date('YmdHis'));

// Request information
$requestInfo = [
    'lang' => $_GET['lang'] ?? 'es',
    'route' => $_GET['route'] ?? 'login',
    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
        "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
];

// Parse URL information
$urlInfo = parse_url($requestInfo['url']);
$modulePath = explode('module', $urlInfo['path'] ?? '');
$requestInfo['root'] = $modulePath[0] ?? '';
$requestInfo['path'] = basename($urlInfo['path'] ?? '');

// API Controller detection and configuration
if (strpos($requestInfo['path'], 'controller') !== false) {
    // Configure headers for API responses
    require_once "Cors.php";

    // Configure API directory
    $_DIR = (object) [
        'PATH' => str_replace('controller', 'model', $requestInfo['path'])
    ];
} else {
    // Set cache control headers for non-API requests
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() - 3600));
}

// Make request information globally available
$GLOBALS['requestInfo'] = $requestInfo;