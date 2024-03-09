<?php 
class MySQLPdo extends ConfigInit {
  private $pdo;
  private $xcone;

  public function __construct() {
    parent::__construct();
  }

  public static function getPDO($setName = '') {
    try {
        //utf8mb4 =>aceptaEmogi
      $pdo = new PDO('mysql:host='.HOST_PROD.';dbname='.DB_PROD.';charset=utf8',  USER_PROD, PASS_PROD);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
      if($setName) $pdo->exec("set names {$setName}");

      return $pdo;
    } catch (PDOException $e) {

      echo "Error ".__CLASS__.": " . $e->getMessage();
    }

  }

  public static function dbPDO($_row) {
    try {
      $_row = (is_string($_row) )? json_decode($_row) : $_row;
      //echo '<pre>';echo print_r($_row);
      $pdo = new PDO('mysql:host='.$_row->db_host.';dbname='.$_row->db_name.';charset=utf8',  $_row->db_username, $_row->db_password);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
      return $pdo;
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }

  }
  public static function close() {
    return NULL;
  }

  public static function getValueForType( $table, $key, $value) {
    return self::castToType($value, self::getPhpType( self::getColumnType( $table, $key) ));
  }


  private static function castToType($value, $type) {
    // Convertir el valor al tipo correspondiente
    switch ($type) {
        case 'int':
            return (int) $value;
        case 'float':
            return (float) $value;
        case 'string':
            return (string) $value;
        default:
            return $value;
    }
  }

  private static function getPhpType($columnType) {
    // Mapear el tipo de columna al tipo de PHP
    if (strpos($columnType, 'int') !== false) {
        return 'int';
    } elseif (strpos($columnType, 'float') !== false || strpos($columnType, 'double') !== false|| strpos($columnType, 'decimal') !== false) {
        return 'float';
    } elseif (strpos($columnType, 'char') !== false || strpos($columnType, 'text') !== false) {
        return 'string';
    } elseif (strpos($columnType, 'datetime') !== false) {
        return 'datetime';
    } elseif (strpos($columnType, 'date') !== false) {
        return 'date';
    } elseif (strpos($columnType, 'time') !== false) {
        return 'time';
    } else {
        return 'string'; // Tipo predeterminado para otros tipos
    }
  }


  private static function getColumnType( $table, $column) {
    // Obtener información del tipo de columna desde el esquema
    $pdo = self::getPDO();
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if ($col['Field'] == $column) {
            return $col['Type'];
        }
    }

  return null;
  }


  public static function getTablesDB() {
    // Obtener información del tipo de columna desde el 
    $pdo = self::getPDO();
    $stmt = $pdo->query("SHOW TABLES");
    
    if ($stmt->rowCount() > 0) 
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $option.= "<option value='{$row[0]}'>{$row[0]}</option>";
    }

  return $option;
  }

}//END CLASS
?>