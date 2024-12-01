<?php

/**
 * Database Middleware class for field validations
 */
class DatabaseMiddleware {
    private array $rules = [];
    private array $messages = [];
    private array $errors = [];

    /**
     * Add validation rules for a specific table
     * @param string $table Table name
     * @param array $fieldRules Array of field rules
     * @return void
     */
    public function addRules(string $table, array $fieldRules): void {
        $this->rules[$table] = $fieldRules;
    }

    /**
     * Add custom error messages
     * @param array $messages Custom error messages
     * @return void
     */
    public function setMessages(array $messages): void {
        $this->messages = $messages;
    }

    /**
     * Validate data against rules
     * @param string $table Table name
     * @param array $data Data to validate
     * @param string $operation CRUD operation (insert, update, delete)
     * @return bool
     */
    public function validate(string $table, array $data, string $operation): bool {
        $this->errors = [];
        
        if (!isset($this->rules[$table])) {
            return true;
        }


        foreach ($this->rules[$table] as $field => $rules) {

            if (!is_array($rules)) {
                continue;
            }

            // Skip validation if field is not present in data for updates
            if ($operation === 'update' && !isset($data[$field])) {
                continue;
            }

            foreach ($rules as $rule => $parameter) {
                switch ($rule) {
                    case 'required':
                        if ($parameter && (!isset($data[$field]) || empty($data[$field]))) {
                            $this->addError($field, $this->getMessage($field, 'required'));
                        }
                        break;

                    case 'type':
                        if (isset($data[$field])) {
                            if (!$this->validateType($data[$field], $parameter)) {
                                $this->addError($field, $this->getMessage($field, 'type', $parameter));
                            }
                        }
                        break;

                    case 'min':
                        if (isset($data[$field])) {
                            if (is_string($data[$field]) && strlen($data[$field]) < $parameter) {
                                $this->addError($field, $this->getMessage($field, 'min', $parameter));
                            } elseif (is_numeric($data[$field]) && $data[$field] < $parameter) {
                                $this->addError($field, $this->getMessage($field, 'min', $parameter));
                            }
                        }
                        break;

                    case 'max':
                        if (isset($data[$field])) {
                            if (is_string($data[$field]) && strlen($data[$field]) > $parameter) {
                                $this->addError($field, $this->getMessage($field, 'max', $parameter));
                            } elseif (is_numeric($data[$field]) && $data[$field] > $parameter) {
                                $this->addError($field, $this->getMessage($field, 'max', $parameter));
                            }
                        }
                        break;

                    case 'pattern':
                        if (isset($data[$field]) && !preg_match($parameter, $data[$field])) {
                            $this->addError($field, $this->getMessage($field, 'pattern'));
                        }
                        break;

                    case 'enum':
                        if (isset($data[$field]) && !in_array($data[$field], $parameter)) {
                            $this->addError($field, $this->getMessage($field, 'enum'));
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Add an error message
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError(string $field, string $message): void {
        $this->errors[$field][] = $message;
    }

    /**
     * Get error message for a field and rule
     * @param string $field Field name
     * @param string $rule Rule name
     * @param mixed $parameter Rule parameter
     * @return string
     */
    private function getMessage(string $field, string $rule, $parameter = null): string {
        $key = "{$field}.{$rule}";
        if (isset($this->messages[$key])) {
            return str_replace(':parameter', $parameter, $this->messages[$key]);
        }

        // Default messages
        $defaultMessages = [
            'required' => "El campo {$field} es requerido",
            'type' => "El campo {$field} debe ser de tipo {$parameter}",
            'min' => "El campo {$field} debe tener un mínimo de {$parameter}",
            'max' => "El campo {$field} debe tener un máximo de {$parameter}",
            'pattern' => "El campo {$field} no tiene un formato válido",
            'enum' => "El valor del campo {$field} no es válido"
        ];

        return $defaultMessages[$rule] ?? "El campo {$field} no es válido";
    }

    /**
     * Validate type of a value
     * @param mixed $value Value to validate
     * @param string $type Expected type
     * @return bool
     */
    private function validateType($value, string $type): bool {
        // Si el valor es un array y no debería serlo, retornar false
        if (is_array($value) && $type !== 'array') {
            return false;
        }

        switch ($type) {
            case 'int':
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'float':
            case 'double':
                return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
            case 'string':
                return is_string($value) || is_numeric($value);  // Permitir números como strings
            case 'bool':
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'ip':
                return filter_var($value, FILTER_VALIDATE_IP) !== false;
            case 'date':
                // Si es array, convertir a string para la validación
                if (is_array($value)) {
                    $value = implode('-', $value);
                }
                // Verificar si es una fecha válida usando DateTime
                try {
                    $date = new DateTime($value);
                    return true;
                } catch (Exception $e) {
                    return false;
                }
            case 'array':
                return is_array($value);
            default:
                return true;
        }
    }
}
