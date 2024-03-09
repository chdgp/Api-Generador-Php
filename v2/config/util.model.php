<?php

class util 
{
    const SECRET_IV = 'B6Po)&dha%$#%$#hus]3wgv8';
    const METHOD = "AES-256-CBC";


    public function __construct ($request = null)
    {
        if($request !== null)
        switch ($request->mode) {        
            case 'encode':
                echo json_encode( $this->encodeToken($request) );
                break;
            case 'decode':
                echo json_encode( $this->decodeToken($request) );
                break;

        }
    }



    /**
     * transforma cualquier array en objeto
     *
     * @param [array] $req
     * @return void
     */
    public static function getObj($req)
    {
        return json_decode(json_encode($req));
    }



    /**
     * Guarda un mensaje de registro en un archivo de registro.
     *
     * @param string $prefix  Prefijo para el nombre del archivo de registro.
     * @param string $message Mensaje a ser registrado.
     *
     * @return void
     */
    function save_log($prefix,$message)
    { 
        date_default_timezone_set('America/Lima');
        $log  = date("F j, Y, g:i a").PHP_EOL.
            $message.PHP_EOL.
            "-------------------------".PHP_EOL;
        //Save string to log, use FILE_APPEND to append.
        $r = file_put_contents('AD/'.$prefix.'_log_'.date("j.n.Y").'.log', $log, FILE_APPEND);
        date_default_timezone_set('UTC');
    }



    /**
     * Imprime una variable de manera formateada para facilitar la depuración.
     *
     * @param mixed $value   Valor a imprimir.
     * @param bool  $vardump Indica si se debe utilizar var_dump en lugar de print_r (opcional, por defecto es false).
     */
    public static function debug($value, $vardump = false)
    {
        echo "<pre>";
        if($vardump) var_dump($value);
        else print_r($value);
        echo "</pre>";
    }    



    /**
     * encriptar un string basado en sha256 requiere ssl
     *
     * @param [object] $req:{string}
     * @return void object
     */
    public static function encodeToken($req)
    {
        $obj = (object) [];
        try { 
            $output = FALSE;
			$key = hash('sha256', date('Y-m')); //un mes de duracion
			$iv = substr(hash('sha256', self::SECRET_IV), 0, 16);
			$output = openssl_encrypt(@$req->string, self::METHOD, $key, 0, $iv);
			$obj->token = base64_encode($output);
            $obj->resp='encode';
            
        } catch (PDOException $e) {
          $obj->mensaje = 'Error al encode: ' . $e->getMessage();
          $obj->resp='err';
        }
          return $obj;
    }



    /**
     * desencriptar un string basado en sha256 requiere ssl
     *
     * @param [object] $req:{string}
     * @return void object {token }
     */
    public static function decodeToken($req)
    {
        $obj = (object) [];
        try {
            $key = hash('sha256', date('Y-m')); //un mes de duracion
			$iv = substr(hash('sha256', self::SECRET_IV), 0, 16);
			$obj->token = openssl_decrypt(base64_decode(@$req->string), self::METHOD, $key, 0, $iv);
            $obj->resp='decode';
            
        } catch (PDOException $e) {
          $obj->mensaje = 'Error al decode: ' . $e->getMessage();
          $obj->resp='err';
        }
          return $obj;
    }



    public static function getConex($token)
    {
        $obj = (object) [];
        try {
            $obj->string = $token;
            return self::decodeToken($obj)->token;
        } catch (PDOException $e) {
          $obj->mensaje = 'Error al decode: ' . $e->getMessage();
          $obj->resp='err';
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
        list($inicioSeg, $inicioMicro) = explode(" ", $inicio);
        list($finSeg, $finMicro) = explode(" ", microtime(true) );
    
        $diferenciaSegundos      = $finSeg - $inicioSeg;
        $diferenciaMicrosegundos = $finMicro - $inicioMicro;
        return $diferenciaSegundos + ($diferenciaMicrosegundos / 1000000);
    }



    public static function tableFromDatabase($data, $columnMap, $tableId = 'myTable', $className ='table-bordered')
    {
        $html ='';
        $obj =(object) [];
        $html.='<table id="' . $tableId . '" class="table '.$className.' table-hover table-condensed">';
        $html.='<thead><tr>';
        
        // Generate table header (th) based on $columnMap keys
        foreach ($columnMap as $th => $columnKey) {
            $html.='<th>' . $th . '</th>';
        }
        
        $html.='</tr></thead><tbody>';
        
        // Generate table rows and cells (td) based on $data and $columnMap
        foreach ($data as $row) {
            $html.='<tr>';
            foreach ($columnMap as $th => $columnValue) {
                if (is_callable($columnValue)) {
                    // Si la columna se mapea a una función, llama a la función con el valor actual de la fila
                    $value = $columnValue($row);
                } else {
                    // De lo contrario, asume que es una propiedad de la fila
                    $value = $row->$columnValue;
                }
                $html.='<td>' . $value . '</td>';
            }
            $html.='</tr>';
        }
        
        $html.='</tbody></table>';
        $obj->html= trim($html);
    
        return $obj;
      }


}

$util = new util( (object) $_REQUEST);

?>