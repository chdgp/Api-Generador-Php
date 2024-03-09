<?php
require_once("util.model.php");

class MySQLTable extends MySQLPdo {

    /** @var PDO La instancia de conexión a la base de datos. */
    private $db;
    private $time;


    /**
     * Constructor de la clase MySQLTable.
     * Inicializa la conexión a la base de datos a través de la clase MySQL.
     */
    public function __construct() {
        parent::__construct(); // Llamar al constructor de la clase padre (MySQL)
        $this->db = self::getPDO();
        $this->time   = microtime(true);

    }

    /**
     * Combina dos objetos en uno nuevo.
     *
     * @param object $obj1 El primer objeto a combinar.
     * @param object $obj2 El segundo objeto a combinar.
     * @return object El objeto resultante que es la combinación de los dos objetos.
     */
    public function combineObjects($obj1, $obj2) {

        // Crea un nuevo objeto a partir del arreglo combinado
        return (object) array_merge((array)$obj1, (array)$obj2);

    }

    public static function objectToArray($obj) {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }
        return $obj;
    }

    public function getInsert($datatable_name, $name) {
        return $datatable_name->insert_data[$name];
    }

    /**
     * Obtiene la descripción de una tabla en la base de datos.
     * 
     * @param string $table_name El nombre de la tabla a describir.
     * 
     * @return array Un arreglo asociativo con la descripción de la tabla.
     */
    public function describeTable($table_name) {
        $stmt = $this->db->prepare("DESCRIBE $table_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    /**
     * Realiza una consulta SELECT en la tabla de la base de datos.
     *
     * @param string $table_name El nombre de la tabla a consultar.
     * @param array $data Un arreglo asociativo con los campos y valores de filtro.
     *
     * @return array Un arreglo de resultados de la consulta.
     */
    public function selectAll($table_name, $data = []) {
        $data = self::objectToArray($data);

        // Obtener la descripción de la tabla
        $table_description = $this->describeTable($table_name);
        
        // Obtener los nombres de los campos válidos
        $valid_fields = array_column($table_description, 'Field');
        
        // Obtener los campos válidos del arreglo $data
        $valid_data = array_intersect_key($data, array_flip($valid_fields));
        
        if (empty($valid_data)) {
            // Si no hay campos válidos en $data, seleccionar todos los campos
            $valid_field_list = '*';
        } else {
            // Construir la lista de campos válidos para la consulta
            $valid_field_list = '*';//implode(', ', array_keys($valid_data));
        }
    
        // Construir la cláusula WHERE basada en los valores de $valid_data
        $where_clause = '';
        $where_values = [];
    
        foreach ($valid_data as $key => $value) {
            if ($where_clause !== '') {
                $where_clause .= ' AND ';
            }
            $where_clause .= "$key = ?";
            $where_values[] = $value;
        }
    
        // Construir la consulta SQL
        $query = "SELECT $valid_field_list FROM $table_name";
        if (!empty($where_clause)) {
            $query .= " WHERE $where_clause";
        }
    
        $stmt = $this->db->prepare($query);
        $stmt->execute($where_values);
        $obj->row = $stmt->fetchAll(PDO::FETCH_OBJ);
        $obj->resp = ( $obj->count = $stmt->rowCount() )? 'select':'no_data';
        $obj->TIME_SECOND = util::timeSecond( $this->time );
    
        return $obj;
    }
    
    


/**
 * Inserta datos en una tabla de la base de datos.
 *
 * @param string $table_name El nombre de la tabla en la que se insertarán los datos.
 * @param array|object $data Los datos que se insertarán en la tabla.
 *
 * @return object Un objeto que contiene la respuesta de la inserción, incluyendo el ID generado si corresponde.
 */
public function insert($table_name, $data) {
    $obj = (object) [];
    $data = self::objectToArray($data);

    // Obtener la descripción de la tabla
    $table_description = $this->describeTable($table_name);

    // Filtrar los campos de $data para asegurarse de que sean válidos y no sean NULL,
    // y que no sean claves primarias o autoincrementables
    $valid_data = array();

    foreach ($table_description as $column) {
        $key = $column['Field'];

        // Verifica si el campo cumple con las condiciones
        if ( $column['Key'] != 'PRI'
            && strpos($column['Extra'], 'auto_increment') === false
            && isset($data[$key])
        ) {
            $valid_data[$key] = $data[$key];
        }

        // Agregar campos de fecha
        if (
            $column['Null'] == 'NO'
            && $column['Type'] == 'datetime'
        ) {
            $valid_data[$key] = date('Y-m-d H:i:s');
        }
    }

    if (empty($valid_data)) {
        return (object) ['msj' => 'No se proporcionaron campos válidos para la inserción', 'resp' => 'err'];
    }
    
    if( ConfigInit::debugCtrl() ) $obj->insert_data = $valid_data;

    $keys = implode(', ', array_keys($valid_data));
    $placeholders = implode(', ', array_fill(0, count($valid_data), '?'));

    // Construir la consulta SQL de inserción de forma dinámica
    $sql = "INSERT INTO $table_name ($keys) VALUES ($placeholders)";

    $stmt = $this->db->prepare($sql);

    $values = array_values($valid_data);
    try {
        $stmt->execute($values);
    } catch (PDOException $e) {
        $obj = (object) ['msj' => 'Error al insertar los datos en la ['.$table_name.']: ' . $e->getMessage(), 'resp' => 'err'];
    }
    $newname = 'id' . $table_name;
    $obj->insert_data[$newname] = $this->db->lastInsertId();
    $obj->resp = ($obj->insert_data[$newname])?'add':'err';
    $obj->TIME_SECOND = util::timeSecond( $this->time );
    return $obj;
}


    
    
    


    /**
     * Realiza una actualización de datos en la tabla de la base de datos.
     *
     * @param string $table_name El nombre de la tabla en la que actualizar los datos.
     * @param array $data Un arreglo asociativo con los datos a actualizar.
     * @param array $where Un arreglo asociativo con los criterios de actualización.
     *
     * @return int El número de filas afectadas por la actualización o false si falla.
     */
    public function update($table_name, $data, $where) {
        $obj = (object) [];
        $data = self::objectToArray($data);
    
        // Obtener la descripción de la tabla
        $table_description = $this->describeTable($table_name);
    
        // Filtrar los campos de $data para asegurarse de que sean válidos y no sean NULL,
        // y que no sean claves primarias o autoincrementables
        $valid_data = [];
        foreach ($data as $key => $value) {
            foreach ($table_description as $column) {
                if (
                    $column['Field'] == $key
                    && $column['Key'] != 'PRI'
                    && strpos($column['Extra'], 'auto_increment') === false
                ) {
                    $valid_data[$key] = $value;
                    break;
                }
            }
        }
    
        if (empty($valid_data)) {
            return ['msj' =>'No se proporcionaron campos válidos para la actualización','resp' =>'err'];
        }
    
        // Construir la cláusula WHERE basada en el arreglo $where
        $where_conditions = [];
        foreach ($where as $field => $value) {
            foreach ($table_description as $column) {
                if ($column['Field'] == $field) {
                    $where_conditions[] = "$field = ?";
                    $where_values[] = $value;
                    break;
                }
            }
        }

        if (empty($where_conditions)) {
            return ['msj' =>'No se proporcionaron campos válidos para la cláusula WHERE','resp' =>'err'];
        }
    
        $where_clause = implode(' AND ', $where_conditions);
    
        $nameset_values = [];
        foreach ($valid_data as $key => $value) {
            $nameset_values[] = "$key = ?";
            $set_values[] = $value;
        }
    
        $set_clause = implode(', ', $nameset_values);
    
        $xsql = "UPDATE $table_name SET $set_clause WHERE $where_clause";
        $stmt = $this->db->prepare($xsql);
    
        $values = array_merge($set_values, $where_values);
        $stmt->execute($values);
        
        if( ConfigInit::debugCtrl() ){    
            $obj->update_data['sql']      = $xsql;
            $obj->update_data['valid_where']      = $valid_data;
            $obj->update_data['where_conditions'] = $where_conditions;
            $obj->update_data['where_values']     = $where_values;
        }

        $obj->resp = ( $obj->count = $stmt->rowCount() )? 'edit':'no_change';
        $obj->TIME_SECOND = util::timeSecond( $this->time );
    
        return $obj;
    }
    


    /**
     * Realiza una eliminación de registros en la tabla de la base de datos.
     *
     * @param string $table_name El nombre de la tabla en la que realizar la eliminación.
     * @param array $where Un arreglo asociativo con los criterios de eliminación.
     *
     * @return int El número de filas afectadas por la eliminación o false si falla.
     */
    public function delete($table_name, $where) {
        $obj = (object) [];
        // Obtener la descripción de la tabla
        $table_description = $this->describeTable($table_name);
    
        // Verificar que el campo en la condición de borrado sea un campo que contenga "id"
        $valid_where = [];
        foreach ($where as $key => $value) {
            foreach ($table_description as $column) {
                if ($column['Field'] == $key && stripos($key, 'id') !== false) {
                    $valid_where[$key] = $value;
                    break;
                }
            }
        }
        $obj->delete_data['valid_where'] = $valid_where;
    
        if (empty($valid_where)) {
            return ['msj' =>'La condición de borrado no contiene un campo válido con [id]','resp' =>'err'];
        }
    
        $where_clause = implode(' AND ', array_map(function($key) {
            return "$key = ?";
        }, array_keys($valid_where)));
    
        $stmt = $this->db->prepare("DELETE FROM $table_name WHERE $where_clause");
        $stmt->execute(array_values($valid_where));
        $obj->resp = ( $obj->count = $stmt->rowCount() )? 'dele':'no_registro';
    $obj->TIME_SECOND = util::timeSecond( $this->time );
    return $obj;
    }



/**
 * Realiza una consulta SQL para seleccionar datos de múltiples tablas con NATURAL JOIN, filtros dinámicos y joins personalizados.
 *
 * @param array $tables      Un array de nombres de tablas que se incluirán en la consulta.
 * @param array $data        Un array asociativo de campos y valores para aplicar filtros dinámicos (opcional).
 * @param string $filter     Un filtro adicional personalizado en forma de cadena SQL (opcional).
 * @param string $customJoins Un filtro adicional personalizado para agregar joins a la consulta (opcional).
 * @param string $subquery Un filtro basado en la consulta principal (opcional).
 * @param array $fieldper Pasar valores a consultar en caso contrario obtienes todos los campos (opcional).
 *
 * @return stdClass Un objeto que contiene la información de la consulta, incluyendo las tablas, datos, filtro, consulta SQL y los resultados.
 */
public function selectAllTables($tables, $data = [], $filter = null, $customJoins = null, $subquery = null, $fieldper = []) {
    $obj = (object) [];
    
    $joins = [];
    $where_clause = '';
    $where_values = [];
    $data = self::objectToArray($data);

    foreach ($tables as $table) {
        $table_description = $this->describeTable($table);

        // Genera un alias basado en el nombre de la tabla
        $alias = substr($table, 0, 1) . rand(100, 999); // Puedes personalizar esto de acuerdo a tus necesidades

        $aliases[$table] = $alias;

        if (!empty($data)) {
            // Verifica si se proporcionan campos en data y construye el filtro dinámico
            foreach ($data as $field => $value) {
                if (in_array($field, array_column($table_description, 'Field'))) {

                    if (!empty($where_clause)) {
                        $where_clause .= ' AND ';
                    }

                    $where_clause .= "$alias.$field = ?";
                    $where_values[] = $value;

                }
            }
        }
    }

    // Construir NATURAL JOIN entre todas las tablas
    $firstTable = reset($tables);

    foreach ($tables as $table) {
        if ($table !== $firstTable) {
            $joins[] = "NATURAL JOIN $table AS {$aliases[$table]}";
        }
    }

    // Agregar joins personalizados si se proporcionan
    if (!is_null($customJoins)) {
    $obj->customJoins = $customJoins;
    $joins[] = $customJoins;
    }

    // Aplicar filtro adicional si se proporciona
    if (!is_null($filter)) {
    $obj->filter = $filter;
        if (!empty($where_clause)) {
                $where_clause .= ' AND ';
            }
        $where_clause .= $filter;
    }

    if (!is_null($subquery)){
        $subquery_clause .= ') as subquery ';
        $subquery_begin .= 'SELECT * FROM ( ';
        $subquery_clause .= $subquery;
        $fieldSelect= ' *, ';
    }



    if (!empty($fieldper)) {
        implode(', ', $fieldper);
        $fieldSelect.= implode(', ', $fieldper);
    }else{
        $fieldSelect =' * ';
    }
     $query = "{$subquery_begin} SELECT {$fieldSelect} FROM $firstTable AS {$aliases[$firstTable]} " . implode(' ', $joins) ;

    if (!empty($where_clause)) {
        $query .= " WHERE $where_clause";
    }
    if (!empty($subquery_clause)) {
        $query .= $subquery_clause;
    }



    
    try {
    
        if( ConfigInit::debugCtrl() ){
        $obj->sql         = $query;
        $obj->tables      = $tables;
        $obj->data        = $data;
        $obj->filter      = $filter;
        $obj->customJoins = $customJoins;
        $obj->fieldper    = $fieldper;
        }


        $stmt = $this->db->prepare($query);
        $stmt->execute($where_values);
        $obj->count = $stmt->rowCount();

        /* FORCE: TYPE DATO FOR FIELD
        if ($obj->count > 0) 
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            foreach ($row as $col => $val) {
                $values[$col] = MySQLPdo::getValueForType($tables[0], $col, $val);
            }
        $obj->row[] = (object)$values;
        }
        */

        if ($obj->count > 0) 
        $obj->row = $stmt->fetchAll(PDO::FETCH_OBJ);
        $obj->resp = ( $obj->count )? 'select':'no_data';
        $obj->TIME_SECOND = util::timeSecond( $this->time );
        //util::debug($obj);

    } catch (\Throwable $th) {
        $obj->resp= 'err';
        $obj->error= $th;
        echo '<pre>';print_r($obj);die; 
    }


    return $obj;
}


    
}
?>