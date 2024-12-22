<?php
require_once(__DIR__ . '/../../../../Core/Init.php');
require_once(__DIR__ . '/../../TrackedEmailService.php');

// Obtener el ID de tracking
$trackingId = $_GET['id'] ?? null;
if (!$trackingId) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

try {
    $emailService = new TrackedEmailService();
    $emailService->handleTrackingPixel($trackingId);
} catch (Exception $e) {
    error_log("Error in tracking pixel handler: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
}