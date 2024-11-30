<?php
require('config/Mysql/TableManager.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear API PHP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
            color: #4CAF50;
        }
        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
        select, input[type="text"], button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        input[type="text"]:focus, select:focus {
            border-color: #4CAF50;
            outline: none;
        }
        button {
            background-color: #4CAF50;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear API en PHP en una Carpeta</h1>
        <form method="POST" action="config\Module\ModuleGenerator.php">
            <label for="tabla">Seleccione la tabla:</label>
            <select id="tabla" name="tabla">
                <?= DatabaseConnection::getTablesDB(); ?>
            </select>
            <label for="subcarpeta">Modulo/{Carpeta}</label>
            <input type="text" id="subcarpeta" name="subcarpeta" pattern="^[a-z_]+$" required>
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>
