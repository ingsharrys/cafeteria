<?php
namespace Core;

/**
 * Clase centralizada para validaciones
 */
class Validator
{
    private $errors = [];

    /**
     * Validar email
     */
    public function validateEmail($email, $fieldName = 'email')
    {
        if (empty($email)) {
            $this->errors[$fieldName] = 'El email es requerido';
            return false;
        }

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = 'El formato del email es inválido';
            return false;
        }

        // Validación adicional de dominio
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, "MX")) {
            $this->errors[$fieldName] = 'El dominio del email no es válido';
            return false;
        }

        return true;
    }

    /**
     * Validar contraseña con políticas de seguridad
     */
    public function validatePassword($password, $fieldName = 'password')
    {
        if (empty($password)) {
            $this->errors[$fieldName] = 'La contraseña es requerida';
            return false;
        }

        $errors = [];

        // Longitud mínima
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = 'mínimo ' . MIN_PASSWORD_LENGTH . ' caracteres';
        }

        // Mayúscula
        if (REQUIRE_PASSWORD_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'al menos una mayúscula';
        }

        // Minúscula
        if (REQUIRE_PASSWORD_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'al menos una minúscula';
        }

        // Número
        if (REQUIRE_PASSWORD_NUMBER && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'al menos un número';
        }

        // Carácter especial
        if (REQUIRE_PASSWORD_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'al menos un carácter especial';
        }

        if (!empty($errors)) {
            $this->errors[$fieldName] = 'La contraseña debe contener: ' . implode(', ', $errors);
            return false;
        }

        return true;
    }

    /**
     * Validar que el campo no esté vacío
     */
    public function required($value, $fieldName)
    {
        if (empty($value) && $value !== '0') {
            $this->errors[$fieldName] = "El campo {$fieldName} es requerido";
            return false;
        }
        return true;
    }

    /**
     * Validar longitud mínima
     */
    public function minLength($value, $min, $fieldName)
    {
        if (strlen($value) < $min) {
            $this->errors[$fieldName] = "El campo {$fieldName} debe tener al menos {$min} caracteres";
            return false;
        }
        return true;
    }

    /**
     * Validar longitud máxima
     */
    public function maxLength($value, $max, $fieldName)
    {
        if (strlen($value) > $max) {
            $this->errors[$fieldName] = "El campo {$fieldName} no debe exceder {$max} caracteres";
            return false;
        }
        return true;
    }

    /**
     * Validar que sea numérico
     */
    public function numeric($value, $fieldName)
    {
        if (!is_numeric($value)) {
            $this->errors[$fieldName] = "El campo {$fieldName} debe ser numérico";
            return false;
        }
        return true;
    }

    /**
     * Validar URL
     */
    public function url($value, $fieldName)
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$fieldName] = "El campo {$fieldName} debe ser una URL válida";
            return false;
        }
        return true;
    }

    /**
     * Validar que coincidan dos valores (ej: confirmar contraseña)
     */
    public function matches($value1, $value2, $fieldName)
    {
        if ($value1 !== $value2) {
            $this->errors[$fieldName] = "Los campos no coinciden";
            return false;
        }
        return true;
    }

    /**
     * Validar formato de fecha
     */
    public function date($value, $format = 'Y-m-d', $fieldName = 'date')
    {
        $d = \DateTime::createFromFormat($format, $value);
        if (!($d && $d->format($format) === $value)) {
            $this->errors[$fieldName] = "El formato de fecha es inválido";
            return false;
        }
        return true;
    }

    /**
     * Obtener todos los errores
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Obtener el primer error
     */
    public function getFirstError()
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Verificar si hay errores
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Limpiar errores
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Sanitizar string
     */
    public static function sanitizeString($string)
    {
        $string = trim($string);
        $string = stripslashes($string);
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return $string;
    }

    /**
     * Sanitizar array
     */
    public static function sanitizeArray($array)
    {
        return array_map([self::class, 'sanitizeString'], $array);
    }
}