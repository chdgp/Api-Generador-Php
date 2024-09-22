<?php
chdir("../");
if (isset($_POST["tabla"]) && isset($_POST["subcarpeta"]))
{
    require_once('config/config.init.php');

    $tabla = trim($_POST["tabla"]);
    $obj = (object) [];
    $php_version = phpversion();
    $is_php_version_less_than_8 = version_compare($php_version, '8.0.0', '<');

    $carpeta    = 'module';
    $subcarpeta = trim($_POST["subcarpeta"]);
    $controller = $carpeta.'/'.$subcarpeta.'/controller';
    $model      = $carpeta.'/'.$subcarpeta.'/model';
    $obj->ruta_model=$archivo    = $model.'/'."$tabla.model.php";
    $obj->ruta_controller=$archivo2   = $controller.'/'."$tabla.controller.php";

    if (!is_dir($carpeta)) {
        // Crear la carpeta si no existe
        mkdir($carpeta);
    }

    // Verificar si la subcarpeta existe dentro de la carpeta
    if (!is_dir($carpeta.'/'.$subcarpeta)) {
        // Si la subcarpeta no existe, la creamos dentro de la carpeta
        mkdir($carpeta.'/'.$subcarpeta);
    }

    if (!is_dir($controller)) {
        // Si la subcarpeta no existe, la creamos dentro de la carpeta
        mkdir($controller);
    }
    if (!is_dir($model)) {
        // Si la subcarpeta no existe, la creamos dentro de la carpeta
        mkdir($model);
    }


    if (!file_exists($archivo)) {


        // Conectar a la base de datos
        $dsn = "mysql:host=".HOST_PROD.";dbname=".DB_PROD.";charset=utf8";
        $pdo = new PDO($dsn, USER_PROD, PASS_PROD);
        // Obtener la estructura de la tabla
        $consulta = "SHOW COLUMNS FROM $tabla";
        $stmt = $pdo->query($consulta);
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generar el código PHP para la clase echo json_encode($request)

        $codigo = "<?php\n"
        . "\n"
        . "\n"
        . "class $tabla extends MySQLTable \n{\n\n"
        . "     private \$pdo;\n"
        . "     private \$time;\n\n"
        . "     public function __CONSTRUCT()\n"
        . "     {\n"
        . "           parent::__construct(); // Llamar al constructor de la clase padre (MySQLTable)\n"
        . "           \$this->pdo = parent::getPDO();\n"
        . "           \$this->time   = microtime(true);\n"
        . "           //ConfigInit::debugObj(true);\n"
        . "     }\n"
        . "\n"
        . "\n"
        . "     public function switch_$tabla(\$request , \$returnjson= true)\n"
        . "     {\n";
            if ($is_php_version_less_than_8) {
        
        $codigo.= "       switch (\$request->data->mode)\n"
        . "       {\n"
        . "         case 'insert_$tabla':\n"
        . "            \$request->{__CLASS__}= self::_insert_$tabla(\$request);\n"
        . "         break;\n"
        . "         case 'update_$tabla':\n"
        . "            \$request->{__CLASS__}= self::_update_$tabla(\$request);\n"
        . "         break;\n"
        . "         case 'select_$tabla':\n"
        . "            \$request->{__CLASS__}= self::_select_$tabla(\$request);\n"
        . "         break;\n"
        . "         case 'delete_$tabla':\n"
        . "            \$request->{__CLASS__}= self::_delete_$tabla(\$request);\n"
        . "         break;\n"
        . "         case 'table_$tabla':\n"
        . "            \$request->{__CLASS__}= self::_table_$tabla(\$request);\n"
        . "         break;\n"
        . "         case 'describe_$tabla':\n"
        . "            \$request->{__CLASS__}= parent::describeTable(__CLASS__ );\n"
        . "         break;\n"
        . "       }\n"
        . "       if (is_object(\$request->{__CLASS__}) || is_array(\$request->{__CLASS__}) && \$returnjson) {\n"
        . "         header('Content-Type: application/json');\n"
        . "         echo json_encode(\$request);\n"
        . "         exit;\n"
        . "       }\n"
        . "       return \$request;\n";
        
            } else {
        $codigo.= "      \$request->{__CLASS__} = match (\$request->data->mode) {\n"
        . "       'insert_$tabla' => self::_insert_$tabla(\$request),\n"
        . "       'update_$tabla' => self::_update_$tabla(\$request),\n"
        . "       'select_$tabla' => self::_select_$tabla(\$request),\n"
        . "       'delete_$tabla' => self::_delete_$tabla(\$request),\n"
        . "       'table_$tabla'  => self::_table_$tabla(\$request),\n"
        . "       'describe_$tabla' => parent::describeTable(__CLASS__),\n"
        . "        default => null,\n"
        . "       };\n"
        . "       if (is_object(\$request->{__CLASS__}) || is_array(\$request->{__CLASS__}) && \$returnjson) {\n"
        . "         header('Content-Type: application/json');\n"
        . "         echo json_encode(\$request);\n"
        . "         exit;\n"
        . "       }\n"
        . "       return \$request;\n";
    }


        $codigo.= "     }\n"
        . "\n"
        . "\n"
        . "     public function _insert_$tabla(\$request)\n"
        . "     { \n"
        . "         \$data = \$request->data;\n"
        . "         return parent::insert(__CLASS__, \$data);\n"
        . "     }\n"
        . "\n"
        . "\n"
        . "     public function _delete_$tabla(\$request)\n"
        . "     { \n"
        . "       \$data = \$request->data;\n"
        . "       return parent::delete(__CLASS__, \$data);\n"
        . "     }\n"
        . "\n"
        . "\n"
        . "     public function _update_$tabla(\$request)\n"
        . "     { \n"
        . "       \$data = \$request->data;\n"
        . "       if ( empty(\$data->id$tabla) ) return \$data;\n"
        . "\n"
        . "       return parent::update(__CLASS__, \$data, ['id$tabla' => \$data->id$tabla]);\n"
        . "     }\n"
        . "\n"
        . "\n"
        . "     public function _select_$tabla(\$request)\n"
        . "     { \n"
        . "       \$data = \$request->data;\n"
        . "       //if ( empty(\$data->id$tabla) ) return \$data;\n"
        . "\n"
        . "       return parent::selectAllTables([__CLASS__], \$data, \$filter = null, \$customJoins = null, \$subquery = null, \$fieldper = [], \$alias_activar = true, \$limit = null, \$orden = null);\n"
        . "     }\n"
        . "\n"
        . "\n"
        . "     public function _table_$tabla(\$request)\n"
        . "     {\n"
        . "       \$obj = (object) [];\n"
        . "       \$data = \$request->data;\n"
        . "       if (empty(\$data->id$tabla)) return ['resp' => 'requiere_id', 'data' => \$data]; \n"
        . "       \$get = parent::selectAllTables([__CLASS__], \$data, \$filter = null, \$customJoins = null, \$subquery = null, \$fieldper = [], \$alias_activar = true, \$limit = null, \$orden = null);\n"
        . "\n"
        . "       \$columnMap = [\n"
        . "     //  head  => body : table\n"
        . "         ''         => '',\n"
        . "         '#'        => 'id$tabla',\n"
        . "         'campo' => 'meta_key',\n"
        . "     //  'Estado.' => fn(\$row) => self::namefunction(\$row->idestado),\n"
        . "         ];\n"
        . "\n"
        . "       \$obj = util::tableFromDatabase(\$get->row , \$columnMap, __CLASS__.'TableID','');\n"
        . "       \$obj->data   = \$data;\n"
        . "       \$obj->select = \$get;\n"
        . "       \$obj->resp   = 'table_true';\n"
        . "       \$obj->TIME_SECOND = util::timeSecond( \$this->time );\n"
        . "       return \$obj;\n"
        . "     }\n"       
        . "\n"
        . "\n"
        . "}//END CLASS\n\n"
        . "\n"
        . "\$x$tabla = new $tabla();\n"
        . "\n"
        . "?>";
        file_put_contents($archivo, $codigo);

    }else{
        $obj->model="El archivo ya existe";
    }//END VERIF FILE


    if (!file_exists($archivo2)) { 
$codigo2 = '<?php 
chdir("../../../");
session_start();
require_once("config/config.init.php");
require_once("config/mysql.pdo.php");
require_once("config/util.model.php");
require_once("config/mysql.table.php");
require_once("module/'.$subcarpeta.'/model/'.$tabla.'.model.php");';
$codigo2 .="\n"
. "\$requestBody = file_get_contents('php://input');\n"
. "\$requestData = json_decode(\$requestBody);\n"
. "\$data->data  = (!empty(\$_GET))?(object)\$_GET:\$requestData;\n"
. "\$x{$tabla}->switch_{$tabla}(\$data);\n"
. "\n"
. "?>";


    //{$_DIR->PATH}
        file_put_contents($archivo2, $codigo2);
    }else{
        $obj->controller="El archivo ya existe";
    }

    $obj->resp='create';
    $obj->endpoint= [ 
        "insert" => "$obj->ruta_controller?mode=insert_$tabla",
        "update" => "$obj->ruta_controller?mode=update_$tabla",
        "delete" => "$obj->ruta_controller?mode=delete_$tabla",
        "select" => "$obj->ruta_controller?mode=select_$tabla",
    ];

}else{
$obj->resp='err';

}
echo json_encode($obj);

?>