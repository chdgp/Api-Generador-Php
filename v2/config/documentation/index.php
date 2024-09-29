<?php

function generateApiDocs() {
    $modulesPath = __DIR__ . '/../../module';
    $cachePath = __DIR__ . '/../cache';
    $modules = scandir($modulesPath);
    $documentation = "# API Documentation\n\n";

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;

        $controllerPath = $modulesPath . '/' . $module . '/controller';
        if (!is_dir($controllerPath)) continue;

        $controllers = scandir($controllerPath);
        foreach ($controllers as $controller) {
            if (pathinfo($controller, PATHINFO_EXTENSION) !== 'php') continue;

            $controllerName = pathinfo($controller, PATHINFO_FILENAME);
            $endpoint = str_replace('.controller', '', $controllerName);

            $documentation .= "## {$endpoint}\n\n";
            $documentation .= "**Endpoint:** `POST /{$endpoint}/controller/{$endpoint}.controller.php`\n\n";
            $documentation .= "**Parameters (Body):**\n\n";
            
            $modes = ['select', 'insert', 'update', 'delete', 'describe'];
            foreach ($modes as $mode) {
                $documentation .= "```json\n{\n  \"mode\": \"{$mode}_{$endpoint}\"\n}\n```\n\n";
            }

            // Read and include the JSON description file
            $jsonFile = "{$cachePath}/{$endpoint}_description.json";
            if (file_exists($jsonFile)) {
                $jsonContent = file_get_contents($jsonFile);
                $fieldDescriptions = json_decode($jsonContent, true);

                if ($fieldDescriptions) {
                    $documentation .= "**Fields:**\n\n";
                    $documentation .= "| Field | Type | Null | Key | Default | Extra |\n";
                    $documentation .= "|-------|------|------|-----|---------|-------|\n";

                    foreach ($fieldDescriptions as $field) {
                        $documentation .= "| {$field['Field']} | {$field['Type']} | {$field['Null']} | {$field['Key']} | " . 
                                          ($field['Default'] === null ? 'NULL' : $field['Default']) . " | {$field['Extra']} |\n";
                    }

                    $documentation .= "\n";
                }
            }

            $documentation .= "---\n\n";
        }
    }

    file_put_contents(__DIR__ . '/../../module/api_documentation.md', $documentation);
    return (object) ['resp'=>200, 'msj'=>"Documentation generated successfully."];
}

echo json_encode(generateApiDocs());