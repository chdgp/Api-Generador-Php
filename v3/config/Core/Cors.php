<?php

class Cors
{
    /**
     * Define security headers with descriptions
     * @return array
     */
    private static function getSecurityHeaders(): array
    {
        return [
            // CORS Headers
            'Access-Control-Allow-Origin' => '*', // Consider restricting to specific domains in production
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, Authorization, Accept',
            'Access-Control-Max-Age' => '86400', // 24 hours cache for preflight requests

            // Content Security Headers
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; object-src 'none';",

            // Cache Control Headers
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',

            // Security Headers
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains', // HSTS for 1 year
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',

            // Remove PHP Version
            'X-Powered-By' => '', // Remove PHP version information
        ];
    }

    /**
     * Set all security headers
     * @return void
     */
    public static function setSecurityHeaders(): void
    {
        // Remove PHP version from response headers
        ini_set('expose_php', 'off');

        // Set headers
        foreach (self::getSecurityHeaders() as $header => $value) {
            if (!empty($value)) {
                header("$header: $value");
            } else {
                header_remove($header);
            }
        }
    }

    /**
     * Set CORS headers specifically for preflight requests
     * @return void
     */
    public static function handlePreflightRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $corsHeaders = array_filter(self::getSecurityHeaders(), function ($key) {
                return strpos($key, 'Access-Control') === 0;
            }, ARRAY_FILTER_USE_KEY);

            foreach ($corsHeaders as $header => $value) {
                header("$header: $value");
            }
            exit(0);
        }
    }

    /**
     * Validate and set specific origin for CORS
     * @param array $allowedOrigins List of allowed origins
     * @return void
     */
    public static function setAllowedOrigins(array $allowedOrigins): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: " . $allowedOrigins[0]);
        }
    }
}

// Usage example:
try {
    // Handle preflight requests
    Cors::handlePreflightRequest();

    // Set allowed origins (for production)
    // Cors::setAllowedOrigins(['https://yourdomain.com', 'https://api.yourdomain.com']);

    // Set all security headers
    Cors::setSecurityHeaders();

} catch (Exception $e) {
    // Log the error
    error_log("Error setting security headers: " . $e->getMessage());

    // Send a generic error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}