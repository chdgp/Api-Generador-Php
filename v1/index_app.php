<!DOCTYPE html>
<html>
<head>
    <title>Crear clase PHP</title>
</head>
<body>
    <h1>Crear clase PHP</h1>
    <form method="POST" action="config/crear_module.php">
        <label for="tabla">Seleccione la tabla:</label>
        <select id="tabla" name="tabla">
            <?php
                require('config\config_init.php');
                $conexion = new mysqli(HOST_PROD, USER_PROD, PASS_PROD, DB_PROD);

            // Consulta para obtener todas las tablas de la base de datos
            $sql = "SHOW TABLES";
            $resultado = $conexion->query($sql);

            // Mostrar opciones en el select
            if ($resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_array()) {
                    echo "<option value='" . $fila[0] . "'>" . $fila[0] . "</option>";
                }
            }
            ?>
        </select>
        <br>
        <label for="texto">Modulo</label>
        <input type="text" id="subcarpeta" name="subcarpeta" pattern="^[a-z]+$" required>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
