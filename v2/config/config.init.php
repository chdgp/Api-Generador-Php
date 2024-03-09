<?php 
require_once("env.php");

class ConfigInit {
  
  const DB_PROD = 'dolibarr';
  const PASS_PROD = '';
  const USER_PROD = 'root';
  const HOST_PROD = 'localhost';
  private static $variableActiva = false;


  public function __construct() {
      @define('DB_PROD', self::DB_PROD);
      @define('PASS_PROD', self::PASS_PROD);
      @define('USER_PROD', self::USER_PROD);
      @define('HOST_PROD', self::HOST_PROD);
  }


  public static function debugObj($bool =false) {
      if($bool)
      self::$variableActiva = true;
      else
      self::$variableActiva = false;
  }

  public static function debugCtrl() {
      return self::$variableActiva;
  }

}
$ConfigInit = new ConfigInit();
?>