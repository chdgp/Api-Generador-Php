<?php

class util
{
    public const SECRET_IV = 'B6Po)&dha%$#%$#hus]3wgv8';
    public const METHOD = 'AES-256-CBC';

    public function __construct($request = null)
    {
        if ($request !== null) {
            switch ($request->mode) {
                case 'encode':
                    echo json_encode($this->encodeToken($request));
                    break;
                case 'decode':
                    echo json_encode($this->decodeToken($request));
                    break;
            }
        }
    }

    /**
     * transforma cualquier array en objeto.
     *
     * @param [array] $req
     *
     * @return void
     */
    public static function getObj($req)
    {
        return json_decode(json_encode($req));
    }

    /**
     * Crea objetos únicos a partir de un array utilizando una clave específica.
     *
     * @param array  $array el array del que se extraerán los valores únicos
     * @param string $clave la clave que se utilizará para crear los objetos
     *
     * @return array un array de objetos únicos
     */
    public static function crearObjetosUnicos($array, $clave)
    {
        return array_values(array_map(function ($value) use ($clave) {
            $objeto = new stdClass();
            $objeto->$clave = $value;

            return $objeto;
        }, array_unique(array_column($array, $clave))));
    }

    /**
     * Guarda un mensaje de registro en un archivo de registro.
     *
     * @param string $prefix  prefijo para el nombre del archivo de registro
     * @param string $message mensaje a ser registrado
     *
     * @return void
     */
    public static function save_log($prefix, $message)
    {
        date_default_timezone_set('America/Lima');
        $log = date('F j, Y, g:i a').PHP_EOL.
            $message.PHP_EOL.
            '-------------------------'.PHP_EOL;
        // Save string to log, use FILE_APPEND to append.
        $r = file_put_contents('AD/'.$prefix.'_log_'.date('j.n.Y').'.log', $log, FILE_APPEND);
        date_default_timezone_set('UTC');
    }

    /**
     * Imprime una variable de manera formateada para facilitar la depuración.
     *
     * @param mixed $value   valor a imprimir
     * @param bool  $vardump indica si se debe utilizar var_dump en lugar de print_r (opcional, por defecto es false)
     */
    public static function debug($value, $vardump = false)
    {
        if ($vardump === 2) {
            echo json_encode($value);

            return;
        }

        echo '<pre>';
        if ($vardump === 1) {
            var_dump($value);
        } else {
            print_r($value);
        }
        echo '</pre>';
    }

    /**
     * encriptar un string basado en sha256 requiere ssl.
     *
     * @param [object] $req:{string}
     *
     * @return object
     */
    public static function encodeToken($req)
    {
        $obj = (object) [];
        try {
            $output = false;
            $key = hex2bin(hash('sha256', date('Y-m')));
            $iv = substr(hex2bin(hash('sha256', self::SECRET_IV)), 0, 16);
            $output = openssl_encrypt($req, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
            $obj->output = base64_encode($output);
        } catch (PDOException $e) {
            $obj->mensaje = 'Error al encode: '.$e->getMessage();
            $obj->resp = 'err';
        }

          return $obj;
    }

    /**
     * desencriptar un string basado en sha256 requiere ssl.
     *
     * @param [object] $req:{string}
     *
     * @return object 
     */
    public static function decodeToken($req)
    {
        $obj = (object) [];
        try {
            $key = hex2bin(hash('sha256', date('Y-m')));
            $iv = substr(hex2bin(hash('sha256', self::SECRET_IV)), 0, 16);
            $obj->output = openssl_decrypt(base64_decode($req), self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
        } catch (PDOException $e) {
            $obj->mensaje = 'Error al decode: '.$e->getMessage();
            $obj->resp = 'err';
        }

          return $obj;
    }

    public static function getConex($token)
    {
        $obj = (object) [];
        try {
            $obj->string = $token;

            return self::decodeToken($obj->string);
        } catch (PDOException $e) {
            $obj->mensaje = 'Error al decode: '.$e->getMessage();
            $obj->resp = 'err';
        }

          return $obj;
    }

    public static function delObj($_this)
    {
        unset($_this->mode);
        unset($_this->token);

        return $_this;
    }

    public static function timeSecond($inicio)
    {
        list($inicioSeg, $inicioMicro) = explode(' ', $inicio);
        list($finSeg, $finMicro) = explode(' ', microtime(true));

        $diferenciaSegundos = $finSeg - $inicioSeg;
        $diferenciaMicrosegundos = $finMicro - $inicioMicro;

        return $diferenciaSegundos + ($diferenciaMicrosegundos / 1000000);
    }

    public static function tableFromDatabase($data, $columnMap, $tableId = 'myTable', $className = 'table-bordered')
    {
        $html = '';
        $obj = (object) [];
        $html .= '<table id="'.$tableId.'" class="table '.$className.' table-hover table-condensed">';
        $html .= '<thead><tr>';

        // Generate table header (th) based on $columnMap keys
        foreach ($columnMap as $th => $columnKey) {
            $html .= '<th>'.$th.'</th>';
        }

        $html .= '</tr></thead><tbody>';

        // Generate table rows and cells (td) based on $data and $columnMap
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($columnMap as $th => $columnValue) {
                if (is_callable($columnValue)) {
                    // Si la columna se mapea a una función, llama a la función con el valor actual de la fila
                    $value = $columnValue($row);
                } else {
                    // De lo contrario, asume que es una propiedad de la fila
                    $value = $row->$columnValue;
                }
                $html .= '<td>'.$value.'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $obj->html = trim($html);

        return $obj;
    }

    /**
     * Obtiene la URL base actual del servidor.
     *
     * @return string la URL base actual (incluyendo protocolo HTTP o HTTPS)
     */
    public static function get_dominio_now($path = false)
    {
        // Obtener el protocolo (HTTP o HTTPS)
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

        // Obtener el nombre de dominio o la dirección IP del servidor
        $dominio = $_SERVER['HTTP_HOST'];

        // Combinar el protocolo y el dominio para formar la URL base
        $url_base = "$protocolo://$dominio";

        $ruta_actual = __FILE__;

        // Obtener la carpeta (directorio) que contiene el archivo actual
        $carpeta_actual = dirname($ruta_actual);
        $fulldir = str_replace('/config', '', $carpeta_actual);

        $url_base .= ($path)
        ? str_replace($_SERVER['DOCUMENT_ROOT'], '', $fulldir)
        : '';

        return $url_base;
    }

    /**
     * Crea una carpeta de almacenamiento y guarda una imagen en formato base64.
     *
     * @param string $base64string la cadena base64 que representa la imagen
     * @param int    $idusuario    el ID del usuario al que pertenece la imagen
     * @param bool   $createWebp   (Opcional) Indica si se debe crear una versión WebP de la imagen (predeterminado: true)
     * @param string $folder       (Opcional) El nombre de la carpeta donde se almacenará la imagen (predeterminado: 'image')
     *
     * @return object un objeto con las rutas de los archivos guardados y un mensaje de respuesta
     */
    public static function createStorageAndImageBase64($base64string, $idusuario, $createWebp = true, $folder = 'image', $randon = '',$create_original = false)
    {
        $_DOMINIO = self::get_dominio_now(true);
        // Verificar si el base64string contiene una coma para dividir
        if (strpos($base64string, ',') === false) {
            return (object) ['original' => '', 'webp' => '', 'resp' => 'file_not_base64'];
        }

        // Verificar si la carpeta 'storage' existe, si no, crearla
        $storagePath = 'storage/';
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true); // Crear carpeta con permisos de lectura, escritura y ejecución para todos
        }

        $storagePath .= $folder.'/';
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true); // Crear carpeta con permisos de lectura, escritura y ejecución para todos
        }

        $storagePath .= date('Y').'/';
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true); // Crear carpeta con permisos de lectura, escritura y ejecución para todos
        }

        $storagePath .= date('m').'/';
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true); // Crear carpeta con permisos de lectura, escritura y ejecución para todos
        }

        $base64string_parts = explode(',', $base64string);
        $base64_data = $base64string_parts[1];

        // Decodificamos los datos base64
        $fileContents = base64_decode($base64_data);

        // Obtenemos información sobre la imagen para determinar su tipo
        $image_info = getimagesizefromstring($fileContents);
        if ($image_info !== false && isset($image_info['mime'])) {
            // Extraemos el tipo MIME de la imagen
            $mime_type = $image_info['mime'];

            // Definimos el nombre del archivo y la extensión basados en el tipo MIME
            switch ($mime_type) {
                case 'image/jpeg':
                    $extension = '.jpg';
                    break;
                case 'image/png':
                    $extension = '.png';
                    break;
                case 'image/gif':
                    $extension = '.gif';
                    break;
                default:
                    // Si el tipo MIME no es soportado, asignamos una extensión genérica
                    $extension = '.dat';
                    break;
            }

            // Verificamos si se trata de una imagen JPEG o PNG para redimensionarla
            if ($extension == '.jpg' || $extension == '.png' && $createWebp) {
                // Cargar la imagen
                $image = @imagecreatefromstring($fileContents);

                // Verificar si la imagen se ha creado correctamente
                if ($image !== false) {
                    // Redimensionar la imagen
                    $nuevo_ancho = 500; // Nuevo ancho deseado
                    $nuevo_alto = 0; // El alto se calculará automáticamente para mantener la proporción
                    $ancho_original = imagesx($image);
                    $alto_original = imagesy($image);
                    $nuevo_alto = floor($alto_original * ($nuevo_ancho / $ancho_original));
                    $imagen_redimensionada = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
                    imagecopyresampled($imagen_redimensionada, $image, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho_original, $alto_original);

                    $storagePathWebp = $storagePath.'webp/';
                    if (!file_exists($storagePathWebp)) {
                        mkdir($storagePathWebp, 0777, true); // Crear carpeta con permisos de lectura, escritura y ejecución para todos
                    }

                    // Guardar la imagen redimensionada en formato WebP
                    $webpFilePath = $storagePathWebp.$randon.'_'.$idusuario.'_'.date('Ymd').'.webp';
                    imagewebp($imagen_redimensionada, $webpFilePath);

                    // Liberar memoria
                    imagedestroy($image);
                    imagedestroy($imagen_redimensionada);

                    // Retorna el nombre del archivo guardado en formato WebP
                    $basenameWebp = basename($webpFilePath);
                }
            }

            

            if ($create_original){
                $storagePathOrig = $storagePath.'original/';
                if (!file_exists($storagePathOrig)) {
                    mkdir($storagePathOrig, 0777, true); // Crear carpeta con permisos de lectura, escritura y ejecución para todos
                }
                // Definimos el nombre del archivo y la ruta donde será guardado
                $fileName = $idusuario.'_'.date('Ymd').$extension;
                $filePath = $storagePathOrig.$fileName;

                // Intentamos guardar el archivo en el servidor
                file_put_contents($filePath, $fileContents);

            } 

            return (object) ['original' => $create_original ?? $_DOMINIO.'/'.$filePath, 'webp' => $_DOMINIO.'/'.$webpFilePath, 'resp' => 'add_file_create'];
        }

        // Si hubo algún error, retornar NULL
        return (object) ['original' => '', 'webp' => '', 'resp' => 'file_create_err'];
    }
}

$util = new util((object) $_REQUEST);
