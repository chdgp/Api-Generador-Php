<?php

require_once 'util.model.php';

class MySQLTable extends MySQLPdo
{
    /** @var PDO La instancia de conexión a la base de datos. */
    private $db;
    private $time;
    
    /**
     * Constructor de la clase MySQLTable.
     * Inicializa la conexión a la base de datos a través de la clase MySQL.
     */
    public function __construct()
    {

        parent::__construct(); // Llamar al constructor de la clase padre (MySQL)
       # $this->db = parent::getPDO();
        $this->time = microtime(true);
    }

    /**
     * Combina dos objetos en uno nuevo.
     *
     * @param object $obj1 el primer objeto a combinar
     * @param object $obj2 el segundo objeto a combinar
     *
     * @return object el objeto resultante que es la combinación de los dos objetos
     */
    public function combineObjects($obj1, $obj2)
    {
        // Crea un nuevo objeto a partir del arreglo combinado
        return (object) array_merge((array) $obj1, (array) $obj2);
    }

    public static function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        return $obj;
    }

    public function getInsert($datatable_name, $name)
    {
        return $datatable_name->insert_data[$name];
    }

    public function describeTable($table_name)
    {
        $adb = self::getPDO();
        $stmt = $adb->prepare("DESCRIBE $table_name");
        $stmt->execute();
        $adb = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Realiza una consulta SELECT en la tabla de la base de datos.
     *
     * @param string $table_name el nombre de la tabla a consultar
     * @param array  $data       un arreglo asociativo con los campos y valores de filtro
     * 
     *
     * @return array|object un arreglo de resultados de la consulta
     */
    public function selectAll($table_name, $data = [])
    {
        $adb = self::getPDO();
        $obj = (object) [];
        $valid_fields =[];
        $data = self::objectToArray($data);

        // Obtener la descripción de la tabla
        $table_description = $this->describeTable($table_name);


        // Obtener los campos válidos del arreglo $data
        $valid_data = array_intersect_key($data, array_flip(array_column($table_description, 'Field')));

        if (empty($valid_data)) {
            // Si no hay campos válidos en $data, seleccionar todos los campos
            $valid_field_list = '*';
        } else {
            // Construir la lista de campos válidos para la consulta
            $valid_field_list = '*'; // implode(', ', array_keys($valid_data));
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

        $stmt = $adb->prepare($query);
        $stmt->execute($where_values);
        $obj->row = $stmt->fetchAll(PDO::FETCH_OBJ);
        $obj->resp = ($obj->count = $stmt->rowCount()) ? 'select' : 'no_data';
        $obj->TIME_SECOND = util::timeSecond($this->time);
        $adb = null;

        return $obj;
    }

    /**
     * Inserta datos en una tabla de la base de datos.
     *
     * @param string       $table_name el nombre de la tabla en la que se insertarán los datos
     * @param array|object $data       los datos que se insertarán en la tabla
     *
     * @return object un objeto que contiene la respuesta de la inserción, incluyendo el ID generado si corresponde
     */
    public function insert($table_name, $data)
    {
        $adb = self::getPDO();
        $obj = (object) [];
        $data = self::objectToArray($data);



        // Obtener la descripción de la tabla
        $table_description = $this->describeTable($table_name);

        // Filtrar los campos de $data para asegurarse de que sean válidos y no sean NULL,
        // y que no sean claves primarias o autoincrementables
        $valid_data = [];

        foreach ($table_description as $column) {
            $key = $column['Field'];

            // Verifica si el campo cumple con las condiciones
            if ($column['Key'] != 'PRI'
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



        if (ConfigInit::debugCtrl()) {
            $obj->insert_data = $valid_data;
        }

        $keys = implode(', ', array_keys($valid_data));
        $placeholders = implode(', ', array_fill(0, count($valid_data), '?'));

        // Construir la consulta SQL de inserción de forma dinámica
        $sql = "INSERT INTO $table_name ($keys) VALUES ($placeholders)";


        $stmt = $adb->prepare($sql);

        $values = array_values($valid_data);
        try {
            $stmt->execute($values);

            if (ConfigInit::debugCtrl()) {
                $obj->debug['insert'] = (object) [
                    'sql' => $sql,
                    'keys' => $keys,
                    'placeholders' => $placeholders,
                ];
            }
        } catch (PDOException $e) {
            $obj = (object) ['msj' => 'Error al insertar los datos en la ['.$table_name.']: '.$e->getMessage(), 'resp' => 'err'];
        }
        $newname = 'id'.$table_name;
        $obj->insert_data[$newname] = $adb->lastInsertId();
        $obj->resp = ($obj->insert_data[$newname]) ? 'add' : 'err';
        $obj->TIME_SECOND = util::timeSecond($this->time);
        $adb = null;
       # echo '<pre>'; print_r($values);die;

        return $obj;
    }

    /**
     * Realiza una actualización de datos en la tabla de la base de datos.
     *
     * @param string $table_name el nombre de la tabla en la que actualizar los datos
     * @param array  $data       un arreglo asociativo con los datos a actualizar
     * @param array  $where      un arreglo asociativo con los criterios de actualización
     *
     * @return array|object el número de filas afectadas por la actualización o false si falla
     */
    public function update($table_name, $data, $where)
    {
        $adb = self::getPDO();
        $obj = (object) [];
        $data = self::objectToArray($data);
        $where_values = [];

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

                if (
                    $column['Type'] == 'datetime'
                    && substr($column['Field'], 0, 6) === 'update'
                ) {
                    $valid_data[$column['Field']] = date('Y-m-d H:i:s');
                    // var_dump($valid_data); die;
                }
            }
        }

        if (empty($valid_data)) {
            return ['msj' => 'No se proporcionaron campos válidos para la actualización', 'resp' => 'err'];
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
            return ['msj' => 'No se proporcionaron campos válidos para la cláusula WHERE', 'resp' => 'err'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        $nameset_values = [];
        foreach ($valid_data as $key => $value) {
            $nameset_values[] = "$key = ?";
            $set_values[] = $value;
        }

        $set_clause = implode(', ', $nameset_values);

        $xsql = "UPDATE $table_name SET $set_clause WHERE $where_clause";
        $stmt = $adb->prepare($xsql);

        $values = array_merge($set_values, $where_values);
        $stmt->execute($values);

        if (ConfigInit::debugCtrl()) {
            $obj->debug['update'] = (object) [
                'sql' => $xsql,
                'valid_data' => $valid_data,
                'where_conditions' => $where_conditions,
                'where_values' => $where_values,
            ];
        }

        $obj->resp = ($obj->count = $stmt->rowCount()) ? 'edit' : 'no_change';
        $obj->TIME_SECOND = util::timeSecond($this->time);

        $adb = null;

        return $obj;
    }

    /**
     * Realiza una eliminación de registros en la tabla de la base de datos.
     *
     * @param string $table_name el nombre de la tabla en la que realizar la eliminación
     * @param array  $where      un arreglo asociativo con los criterios de eliminación
     *
     * @return array|object el número de filas afectadas por la eliminación o false si falla
     */
    public function delete($table_name, $where)
    {
        $adb = self::getPDO();
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
        $obj->delete_data = $valid_where;

        if (empty($valid_where)) {
            return ['msj' => 'La condición de borrado no contiene un campo válido con [id]', 'resp' => 'err'];
        }

        $where_clause = implode(' AND ', array_map(function ($key) {
            return "$key = ?";
        }, array_keys($valid_where)));

        $stmt = $adb->prepare("DELETE FROM $table_name WHERE $where_clause");
        $stmt->execute(array_values($valid_where));
        $obj->resp = ($obj->count = $stmt->rowCount()) ? 'dele' : 'no_registro';
        $obj->TIME_SECOND = util::timeSecond($this->time);

        $adb = null;

        return $obj;
    }

    /**
     * Realiza una consulta SQL para seleccionar datos de múltiples tablas con NATURAL JOIN, filtros dinámicos y joins personalizados.
     *
     * @param array  $tables      un array de nombres de tablas que se incluirán en la consulta
     * @param array  $data        un array asociativo de campos y valores para aplicar filtros dinámicos (opcional)
     * @param string $filter      un filtro adicional personalizado en forma de cadena SQL (opcional)
     * @param string $customJoins un filtro adicional personalizado para agregar joins a la consulta (opcional)
     * @param string $subquery    un filtro basado en la consulta principal (opcional)
     * @param array  $fieldper    pasar valores a consultar en caso contrario obtienes todos los campos (opcional)
     *
     * @return object un objeto que contiene la información de la consulta, incluyendo las tablas, datos, filtro, consulta SQL y los resultados
     */
    public function selectAllTables($tables, $data = [], $filter = null, $customJoins = null, $subquery = null, $fieldper = [], $alias_activar = true, $limit = null, $orden = null)
    {
        $adb = self::getPDO();
        $obj = (object) [];

        $joins = [];
        $where_clause = '';
        $where_values = [];
        $subquery_clause = '';
        $subquery_begin = '';
        $data = self::objectToArray($data);

        foreach ($tables as $table) {
            $table_description = $this->describeTable($table);
            $squemas[] = $table_description;

            // Genera un alias basado en el nombre de la tabla
            if ($alias_activar) {
                $alias = substr($table, 0, 1).rand(100, 999); // Puedes personalizar esto de acuerdo a tus necesidades
            } else {
                $alias = '';
            }

            $aliases[$table] = $alias;

            if (!empty($data)) {
                // Verifica si se proporcionan campos en data y construye el filtro dinámico
                foreach ($data as $field => $value) {
                    if (in_array($field, array_column($table_description, 'Field'))) {
                        if (!empty($where_clause)) {
                            $where_clause .= ' AND ';
                        }

                        $where_clause .= ($alias != '') ? "$alias.$field = ?" : "$field = ?";
                        $where_values[] = $value;
                    }
                }
            }
        }

        // Construir NATURAL JOIN entre todas las tablas
        $firstTable = reset($tables);

        foreach ($tables as $table) {
            if ($table !== $firstTable) {
                // $joins[] = "NATURAL JOIN $table AS {$aliases[$table]}";
                $joins[] = ($alias != '')
                ? "NATURAL JOIN $table AS {$aliases[$table]} "
                : "NATURAL JOIN {$table}";
            }
        }

        // Agregar joins personalizados si se proporcionan
        if (!is_null($customJoins)) {
            $joins[] = $customJoins;
        }

        // Aplicar filtro adicional si se proporciona
        if (!is_null($filter)) {
            if (!empty($where_clause)) {
                $where_clause .= ' AND ';
            }
            $where_clause .= $filter;
        }

        if (!is_null($subquery)) {
            $subquery_clause .= ') as subquery ';
            $subquery_begin .= 'SELECT * FROM ( ';
            $subquery_clause .= $subquery;
            $fieldSelect = ' *, ';
        }

        if (!empty($fieldper)) {
            implode(', ', $fieldper);
            $fieldSelect .= implode(', ', $fieldper);
        } else {
            $fieldSelect = ' * ';
        }
        $query = ($alias != '')
        ? "{$subquery_begin} SELECT {$fieldSelect} FROM $firstTable AS {$aliases[$firstTable]} ".implode(' ', $joins).' '
        : "{$subquery_begin} SELECT {$fieldSelect} FROM $firstTable ".implode(' ', $joins).' ';

        if (!empty($where_clause)) {
            $query .= " WHERE $where_clause";
        }

        if (!empty($subquery_clause)) {
            $query .= $subquery_clause;
        }
        
        if (!is_null($orden)) {
            $query .= $orden;
        }

        if (!is_null($limit)) {
            $query .= " LIMIT {$limit}";
        }

        if (ConfigInit::debugCtrl()) {
            $obj->debug['select'] = (object) [
                'sql' => $query,
                'tables' => $tables,
                'filter' => $filter,
                'customJoins' => $customJoins,
                'fieldper' => $fieldper,
                'squemas' => $squemas,
            ];
        }

        // echo '<pre>';print_r($obj);die;

        try {
            // echo '<pre>';print_r($query);die;

            $stmt = $adb->prepare($query);
            $stmt->execute($where_values);
            $obj->count = (int) $stmt->rowCount();

            /* FORCE: TYPE DATO FOR FIELD
            if ($obj->count > 0)
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                foreach ($row as $col => $val) {
                    $values[$col] = MySQLPdo::getValueForType($tables[0], $col, $val);
                }
            $obj->row[] = (object)$values;
            }
            */

            if ($obj->count > 0) {
                $obj->row = $stmt->fetchAll(PDO::FETCH_OBJ);
            }
            $obj->resp = ($obj->count) ? 'select' : 'no_data';
            $obj->TIME_SECOND = util::timeSecond($this->time);
            // util::debug($obj);
        } catch (\Throwable $th) {
            $obj->resp = 'err';
            $obj->error = $th;
            // echo '<pre>';print_r($obj);die;
            echo json_encode($obj);
            exit;
        }

        $adb = null;

        return $obj;
    }
}
