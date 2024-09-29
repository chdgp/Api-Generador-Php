<?php 
require_once("env.php");
require_once("init.php");

class ConfigInit {
  
  private static $variableActiva = false;


  public function __construct() {
    global $dbconect;
      // init.php declaration
      @define('DB_PROD', $dbconect['DB_PROD']);
      @define('PASS_PROD', $dbconect['PASS_PROD']);
      @define('USER_PROD', $dbconect['USER_PROD']);
      @define('HOST_PROD', $dbconect['HOST_PROD']);
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