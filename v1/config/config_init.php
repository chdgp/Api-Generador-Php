<?php 
require_once("base_init.php");

class config_init {
  
  const DB_PROD = 'wordpres505';
  const PASS_PROD = '';
  const USER_PROD = 'root';
  const HOST_PROD = 'localhost';


  public function __construct() {
      @define('DB_PROD', self::DB_PROD);
      @define('PASS_PROD', self::PASS_PROD);
      @define('USER_PROD', self::USER_PROD);
      @define('HOST_PROD', self::HOST_PROD);
  }

}
$config_init = new config_init();
?>