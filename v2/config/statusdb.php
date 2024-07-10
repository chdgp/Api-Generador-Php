<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process List</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2>Process List</h2>

<table>
    <tr>
        <th>ID</th>
        <th>User</th>
        <th>Host</th>
        <th>DB</th>
        <th>Command</th>
        <th>Time</th>
        <th>State</th>
        <th>Info</th>
        <th>Progress</th>
    </tr>
    <?php
    include 'config.init.php';
    include 'mysql.pdo.php';
    try {
        // Conectar a la base de datos
        $pdo = MySQLPdo::getPDO();
        // Ejecutar la consulta SHOW PROCESSLIST
        $stmt = $pdo->query("SHOW FULL PROCESSLIST;");

        // Iterar sobre los resultados y mostrarlos en la tabla
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Id'] . "</td>";
            echo "<td>" . $row['User'] . "</td>";
            echo "<td>" . $row['Host'] . "</td>";
            echo "<td>" . $row['db'] . "</td>";
            echo "<td>" . $row['Command'] . "</td>";
            echo "<td>" . $row['Time'] . "</td>";
            echo "<td>" . $row['State'] . "</td>";
            echo "<td>" . $row['Info'] . "</td>";
            echo "<td>" . $row['Progress'] . "</td>";
            echo "</tr>";
        }
    } catch (PDOException $e) {
        // Mostrar un mensaje de error si hay problemas de conexión o consulta
        echo "<tr><td colspan='9'>Error: " . $e->getMessage() . "</td></tr>";
    }

    // Cerrar la conexión
    $pdo = null;
    ?>
</table>

</body>
</html>
