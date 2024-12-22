<?php
    $html='';
    require_once(__DIR__ . "/../../Database/DatabaseConnection.php");
    try {
        // Conectar a la base de datos
        $pdo = DatabaseConnection::getPDO();
        // Ejecutar la consulta SHOW PROCESSLIST
        $stmt = $pdo->query("SHOW FULL PROCESSLIST;");

        // Iterar sobre los resultados y mostrarlos en la tabla
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $html .= "<tr>";
            $html .= "<td>" . $row['Id'] . "</td>";
            $html .= "<td>" . $row['User'] . "</td>";
            $html .= "<td>" . $row['Host'] . "</td>";
            $html .= "<td>" . $row['db'] . "</td>";
            $html .= "<td>" . $row['Command'] . "</td>";
            $html .= "<td>" . $row['Time'] . "</td>";
            $html .= "<td>" . $row['State'] . "</td>";
            $html .= "<td>" . $row['Info'] . "</td>";
            $html .= "<td>" . $row['Progress'] . "</td>";
            $html .= "</tr>";
        }
    } catch (PDOException $e) {
        // Mostrar un mensaje de error si hay problemas de conexión o consulta
        $html .= "<tr><td colspan='9'>Error: " . $e->getMessage() . "</td></tr>";
    }

    // Cerrar la conexión
    $pdo = null;
    ?>


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
        <?= $html ?>
</table>

</body>
</html>
