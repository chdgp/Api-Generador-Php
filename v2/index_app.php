<?php
require('config\config.init.php');
require('config\mysql.pdo.php');

?>


<!DOCTYPE html>
<html>
<head>
    <title>Crear API PHP</title>
</head>
<body>
    <h1>Crear Api en PHP en una Carpeta </h1>
    <form method="POST" action="config/crear_module.php">
        <label for="tabla">Seleccione la tabla:</label>
        <select id="tabla" name="tabla">
            <?= MySQLPdo::getTablesDB(); ?>
        </select>
        <br>
        <label for="texto">Modulo/{Carpeta}</label>
        <input type="text" id="subcarpeta" name="subcarpeta" pattern="^[a-z_]+$" required>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
