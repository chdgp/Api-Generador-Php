<?php
chdir("../");
if (isset($_POST["tabla"]) && isset($_POST["subcarpeta"]))
{
    require_once('config/config_init.php');

    $tabla = trim($_POST["tabla"]);
    $obj = (object) [];


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

        // Generar el código PHP para la clase
        $codigo = "<?php\n"
                . "class $tabla extends conexion {\n";

        // Propiedades
        foreach ($columnas as $columna) {
        $nombre = $columna['Field'];
        $tipo = $columna['Type'];
        $codigo .= "  private \$$nombre;\n";
        }

        // Constructor 
        $codigo .= "\n  public function __construct(\$request = null) {\n"
        . "    if (\$request !== null)\n"
        . "    foreach (\$request as \$key => \$value) {\n"
        . "        if (strpos(\$key, '_g') === 0) {\n"
        . "            unset(\$request[\$key]);\n"
        . "        }\n"
        . "    }\n"
        . "    // Código del constructor aquí\n"
        . "    if (\$request !== null)\n"
        . "    foreach (\$request as \$key => \$value) {\n"
        . "      if(\$key !=='mode')\n"
        . "      @\$this->{\$key} = \$value;\n"
        . "    }\n\n"
        . "    \$this->switchcase(\$request);\n"
        . "  }\n\n";




        // Funciones CRUD
        foreach ($columnas as $columna) {
        $nombre = $columna['Field'];
        $tipo = $columna['Type'];
            $codigo .= "\n"
            . "  public function get$nombre() {\n"
            . "    return \$this->$nombre;\n"
            . "  }\n\n"
            . "  public function set$nombre(\$valor) {\n"
            . "    \$this->$nombre = \$valor;\n"
            . "  }\n";
        }

        // Función switchcase
            $codigo .= "\n"
        . "  public function switchcase(\$req) {\n"
        .'    switch ($req["mode"]) {'
        . "\n    case 'insert_$tabla':".'
                echo json_encode( $this->insert() );
                break;'
        . "\n    case 'select_$tabla':".'
                echo json_encode( $this->select($this) );
                break;'
        . "\n    case 'update_$tabla':".'
                echo json_encode( $this->update() );
                break;'
        . "\n    case 'delete_$tabla':".'
                echo json_encode( $this->delete() );
                break;
            }
            '  
        . "  }\n\n";

        // Select
        $codigo .= "\n  public static function select(\$_this) {\n"
        . "    \$obj = (object) [];\n"
        . "    try {\n"
        . "    \$pdo = parent::getPDO();\n"
        . "    \$sql = \"SELECT * FROM $tabla WHERE \";\n"
        . "    \$params = array();\n"
        . "    \$first = true; \n"
        . "    foreach (\$_this as \$key => \$value) { \n"
        . "      if(property_exists(\$_this, \$key) && !empty(\$value)) { \n"
        . "            if (!\$first) \$sql .= ' AND '; \n"
        . "             \$sql .= \"\$key = :\$key\"; \n"
        . "             \$params[\$key] = \$value;\n"
        . "             \$first = false; \n"
        . "        }\n"
        . "    }\n"
        . "    if (empty(\$params)) \$sql = str_replace('WHERE', '', \$sql);\n"
        . "    \$stmt = \$pdo->prepare(\$sql);\n"
        . "     foreach (\$params as \$key => \$value) { \n"
        . "        \$stmt->bindValue(':' . \$key, \$value); \n"
        . "    } \n"
        . "    \$stmt->execute();\n"
        . "     // \$obj->result=\$stmt->fetchAll(PDO::FETCH_OBJ);\n"
        . "      while (\$row = \$stmt->fetch(PDO::FETCH_OBJ)) {\n"
        . "          foreach (\$row as \$col => \$val) {\n"
        . "              \$values[\$col] = conexion::getValueForType(\$pdo, '$tabla', \$col, \$val);\n"
        . "          }\n"
        . "        \$obj->result[] = (object)\$values;\n"
        . "      }\n"

        . "      \$obj->count=\$stmt->rowCount();\n"
        . "      \$obj->resp = (\$obj->count)?'ok':'no_data';\n"
        . "    } catch (PDOException \$e) {\n"
        . "      \$obj->mensaje = 'Error al insertar: ' . \$e->getMessage();\n"
        . "      \$obj->resp='err';\n"
        . "    }\n"
        . "      return \$obj;\n"
        . "  }\n\n";
        

        
        // Función insertar
        $codigo .= "\n"
        . "  public function insert() {\n"
        . "    \$obj = (object) [];\n"
        . "    try {\n"
        . "      \$pdo = parent::getPDO();\n";
        foreach ($columnas as $columna) {
        $nombre = $columna['Field'];
        if (strpos($nombre, "register") !== false)
        $codigo .= "      \$this->set{$nombre}( date('Y-m-d H:i:s') );\n";

        if (strpos($nombre, "status") !== false)
        $codigo .= "      \$this->set{$nombre}( 1 );\n";
        }
        $codigo .= "      \$consulta = \"INSERT INTO $tabla (";
        foreach ($columnas as $columna) {
        $nombre = $columna['Field'];
        if($nombre!= "id{$tabla}")
        $codigo .= "$nombre, ";
        }
        $codigo = substr($codigo, 0, -2); // Eliminar última coma y espacio
        $codigo .= ") VALUES (";
        foreach ($columnas as $columna) {
        $nombre = $columna['Field'];
        if($nombre!= "id{$tabla}")
        $codigo .= "'\$this->{$nombre}', ";
        }
        $codigo = substr($codigo, 0, -2); // Eliminar última coma y espacio
        $codigo .= ")\";\n"
        . "      \$stmt = \$pdo->prepare(\$consulta);\n";
        $codigo .= "      \$stmt->execute();\n"
        . "      \$this->setid{$tabla}( \$pdo->lastInsertId() );\n"
        . "      \$obj->id$tabla=\$this->getid{$tabla}();\n"
        . "      \$obj->resp='insert';\n"
        . "    } catch (PDOException \$e) {\n"
        . "      \$obj->mensaje = 'Error al insertar: ' . \$e->getMessage();\n"
        . "      \$obj->resp='err';\n"
        . "    }\n"
        . "      return \$obj;\n"
        . "  }\n\n";
        

            // Función actualizar
        $codigo .= "  public function update() {\n"
        . "      \$obj = (object) [];\n"
        . "       try {\n"
        . "      \$pdo = parent::getPDO();\n"
        . "      \$consulta = 'UPDATE $tabla SET ';\n\n"
        . "      \$updateValues = [];\n"
        . "      foreach (\$this as \$key => \$value) {\n"
        . "        if (property_exists(\$this, \$key)) {\n"
        . "          if (\$key !== 'id$tabla' && \$value !='') {\n"
        . "            \$updateValues[] = \$key . ' = :' . \$key;\n"
        . "          }\n"
        . "        }\n"
        . "      }\n"
        . "      \$consulta .= implode(',', \$updateValues);\n"
        . "      \$consulta .= ' WHERE id$tabla = :id$tabla';\n"
        . "      \$stmt = \$pdo->prepare(\$consulta);\n"
        . "      foreach (\$this as \$key => \$value) {\n"
        . "        if (property_exists(\$this, \$key))\n"
        . "          if (\$value !='') {\n"
        . "          \$stmt->bindValue(':' . \$key, \$value);\n"
        . "        }\n"
        . "      }\n"
        . "      \$stmt->execute();\n"
        . "        \$obj->resp='update';\n"
        . "      } catch (PDOException \$e) {\n"
        . "        \$obj->mensaje = 'Error al actualizar: ' . \$e->getMessage();\n"
        . "        \$obj->resp='err';\n"
        . "      }\n"
        . "    return \$obj;\n"
        . "  }\n\n";





        // Función delete
        $pk = $columnas[0]['Field'];
        $codigo .= "\n"
        . "  public function delete() {\n"
        . "    \$obj = (object) [];\n"
        . "    try {\n"
        . "      \$pdo = parent::getPDO();\n"
        . "      \$sql = \"DELETE FROM $tabla WHERE $pk = :$pk\";\n"
        . "      \$stmt = \$pdo->prepare(\$sql);\n"
        . "      \$stmt->bindValue(':$pk', \$this->$pk);\n"
        . "      \$stmt->execute();\n"
        . "      \$obj->resp='delete';\n"
        . "    } catch (PDOException \$e) {\n"
        . "      \$obj->mensaje = 'Error al delete: ' . \$e->getMessage();\n"
        . "      \$obj->resp='err';\n"
        . "    }\n"
        . "      return \$obj;\n"
        . "  }\n\n";
            


        $codigo .= "}\n\n";
        $codigo .= "\$$tabla = new $tabla(".'$_REQUEST'.");\n";
        $codigo .= "?>";
        file_put_contents($archivo, $codigo);

    }else{
        $obj->model="El archivo ya existe";
    }//END VERIF FILE


    if (!file_exists($archivo2)) { 
    $codigo2 = '<?php 
    chdir("../../../");
    require_once("config/config_init.php");
    require_once("config/conexion.php");
    require_once("config/util.model.php");
    require_once("module/'.$subcarpeta.'/model/'.$tabla.'.model.php");
    ?>';
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