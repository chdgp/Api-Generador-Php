<?php
// Error reporting configuration
ini_set('display_errors', 'on');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once '../Security/SecurityUtil.php';


function extractModesFromSwitch($filePath, $className) {
    $modes = [];
    $content = file_get_contents($filePath);

    //$pattern = "/public\s+function\s+switch_{$className}\s*\(\s*\\\$request\s*\)\s*\{(.*?)\}\s*(?:public|private|protected|$)/s";
    $pattern = '/match\s*\(.*?\)\s*\{(.*?)\}/s';
    if (preg_match($pattern, $content, $matches)) {
        $switchBody = $matches[1];
       
        // Check for match structure
        preg_match_all("/['\"](.*?)['\"]\s*=>/", $switchBody, $matchModes);
        $modes = $matchModes[1];         
           
        // Check for switch structure
        if (empty($modes) && strpos($switchBody, 'switch') !== false) {
            $casePattern = "/case\s+['\"](.+?)['\"]\s*:/";
            
            preg_match_all($casePattern, $switchBody, $caseModes);
            $modes = $caseModes[1];

        }
    }
    return $modes;
}


function generateApiDocs($token) {
    global $util;

    // add seguridad para generar documentacion
    //if ($util->verifyToken($token)['valid'] === false) return (object) ['resp' => 403, 'msj' => "Invalid token token or token file not found."];

    $modulesPath = __DIR__ . '/../../module';
    $cachePath = __DIR__ . '/../Cache';
    $modules = scandir($modulesPath);
    $documentation = "# API Documentation\n\n";

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;

        $controllerPath = $modulesPath . '/' . $module . '/controller';
        $modelPath = $modulesPath . '/' . $module . '/model';
        if (!is_dir($controllerPath)|| !is_dir($modelPath)) continue;

        $controllers = scandir($controllerPath);
        foreach ($controllers as $controller) {
            if (pathinfo($controller, PATHINFO_EXTENSION) !== 'php') continue;

           
            $controllerName = pathinfo($controller, PATHINFO_FILENAME);
            $endpoint = str_replace('.controller', '', $controllerName);
            $modelFile = $modelPath . '/' . $endpoint . '.model.php';
            if (!file_exists($modelFile))continue;

            //$documentation .= "## {$endpoint}\n\n";
            $documentation .= "<details><summary>{$endpoint}</summary>\n\n";
            $documentation .= "**POST:** ` module/{$module}/controller/{$endpoint}.controller.php`\n\n";
            $documentation .= "**Parameters (Body):**\n\n";
            
            // Extract modes from the model file
            $modes_scan = extractModesFromSwitch($modelFile, $endpoint);

            $modes_default = ["select_{$endpoint}", "insert_{$endpoint}", "update_{$endpoint}", "delete_{$endpoint}", "describe_{$endpoint}"]; // Fallback to default modes
            $arratemp = array_merge($modes_scan, $modes_default);
            $modes = array_unique($arratemp );

            $documentation .= "```json\n{\n";
            foreach ($modes as $index => $mode) {
                $documentation .= "   \"mode\": \"{$mode}\"" . ($index < count($modes) - 1 ? "," : "") ."\n";
            }
            $documentation .= "}\n```\n\n";

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
            $documentation .= "</details>\n\n";

        }
    }

    file_put_contents(__DIR__ . '/../../module/api_documentation.md', $documentation);
    return (object) ['resp'=>200, 'msj'=>"Documentation generated successfully."];
}
$token = isset($_GET['token']) ? $_GET['token'] : null;
echo json_encode(generateApiDocs($token));