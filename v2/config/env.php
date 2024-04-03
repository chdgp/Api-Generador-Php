<?php 
ini_set('display_errors','on'); error_reporting(E_ERROR); // STRICT DEVELOPMENT
ini_set("session.cookie_lifetime",21200);
ini_set("session.gc_maxlifetime",21200); 
ini_set("session.save_path","/tmp");
session_cache_expire(21200);
date_default_timezone_set('Etc/GMT+5');
$_THEME='sbadmin';
$_RUTA_THEME="theme/{$_THEME}/php/";
@$ymdhms = strftime('%Y%m%d%H%M%S');
@define('_START', $_SESSION);
define('VERSION', $ymdhms);

$data =(object)[
	'data' => '',
];

$lang   = "es";
$ruta   =(empty($_GET['route']) )?'login':$_GET['route'];
$url    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$x      = pathinfo($url);
$modulo = explode("module", $x['dirname']); 
$raiz   = is_array($modulo) ? $modulo[0] : $modulo;
$path   = $x['filename'];
if (strpos($path, "controller") !== false){
	$_DIR = (object) [];
	$_DIR->PATH = str_replace(array("controller"), "model", $path);
	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
	header("Access-Control-Allow-Headers: X-Requested-With");
	header('Content-Type: text/html; charset=utf-8');
	header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
}
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 1 Jul 2000 05:00:00 GMT"); // Fecha en el pasado
?>