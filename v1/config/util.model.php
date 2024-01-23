<?php
class util 
{
    const SECRET_IV = 'B6PLR7@bmD$SRm?!GT3HS,Uc]3wgv8';
    const METHOD = "AES-256-CBC";

    public function __construct ($request = null)
    {
        $request = self::getObj($request);
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
}

$util = new util($_REQUEST);



?>