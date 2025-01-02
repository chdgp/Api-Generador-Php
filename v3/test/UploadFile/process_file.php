<?php
chdir(directory: "../../");
require_once "config/Generator/StorageLocal.php";

class FileUploadHandler
{
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_FOLDERS = ['files', 'image', 'documents', 'perfiles'];
    private const MAX_FILE_SIZE = 10485760; // 10MB

    public function handleUpload()
    {
        try {
            $this->validateUpload();

            $fileType = $_FILES['file']['type'];
            $isImage = in_array($fileType, self::ALLOWED_IMAGE_TYPES);

            if ($isImage) {
                return $this->handleImageUpload();
            } else {
                return $this->handleGenericFileUpload();
            }

        } catch (Exception $e) {
            return [
                'resp' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function validateUpload()
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No se recibió ningún archivo o hubo un error en la subida');
        }

        if ($_FILES['file']['size'] > self::MAX_FILE_SIZE) {
            throw new Exception('El archivo excede el tamaño máximo permitido');
        }

        $folder = $this->sanitizeFolder($_POST['folder'] ?? 'files');
        if (!in_array($folder, self::ALLOWED_FOLDERS)) {
            throw new Exception('Carpeta no permitida');
        }
    }

    private function handleImageUpload()
    {
        // Leer la imagen y convertir a base64
        $imageData = file_get_contents($_FILES['file']['tmp_name']);
        if ($imageData === false) {
            throw new Exception('No se pudo leer la imagen');
        }

        $base64Image = 'data:' . $_FILES['file']['type'] . ';base64,' . base64_encode($imageData);

        // Obtener y validar parámetros
        $userId = $this->validateUserId();
        $createWebp = filter_input(INPUT_POST, 'createWebp', FILTER_VALIDATE_BOOLEAN) ?? true;
        $saveOriginal = filter_input(INPUT_POST, 'saveOriginal', FILTER_VALIDATE_BOOLEAN) ?? false;
        $folder = $this->sanitizeFolder($_POST['folder'] ?? 'image');
        $prefix = uniqid('file_');

        // Procesar la imagen usando StorageLocal
        $result = StorageLocal::createStorageAndImageBase64(
            $base64Image,
            $userId,
            $createWebp,
            $folder,
            $prefix,
            $saveOriginal
        );

        $result->type = $_FILES['file']['type'];
        return $result;
    }

    private function handleGenericFileUpload()
    {
        $userId = $this->validateUserId();
        $folder = $this->sanitizeFolder($_POST['folder'] ?? 'files');

        $uploadInfo = $_FILES['file'];

        $fileName = uniqid('file_') . '_' . $userId . '_' . date('Ymd');

        return StorageLocal::storeFile( $fileName, $uploadInfo, $userId, $folder);
    }

    private function validateUserId()
    {
        $userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
        if (!$userId) {
            throw new Exception('ID de usuario inválido');
        }
        return $userId;
    }

    private function sanitizeFolder($folder)
    {
        $folder = htmlspecialchars(strip_tags(trim($folder)), ENT_QUOTES, 'UTF-8');
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) {
            throw new Exception('Nombre de carpeta inválido');
        }
        return $folder;
    }
}

// Procesar la subida
$handler = new FileUploadHandler();
echo json_encode($handler->handleUpload());
