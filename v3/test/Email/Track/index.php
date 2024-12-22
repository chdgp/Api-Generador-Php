<?php
chdir(directory: "../../../");
require_once "config/Library/Email/TrackedEmailService.php";

// Obtener el ID de tracking
$trackingId = $_GET['id'] ?? null;

// Verificar que el trackingId existe
if (!$trackingId) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

try {
    $emailService = new TrackedEmailService();
    // Llamar al método para manejar el pixel de tracking
    $emailService->handleTrackingPixel($trackingId);
} catch (Exception $e) {
    // Registrar error y devolver un código de error adecuado
    error_log("Error in tracking pixel handler: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
}