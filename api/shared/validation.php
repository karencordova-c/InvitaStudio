<?php
declare(strict_types=1);

if (!function_exists('isEmptyValue')) {
    function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}

if (!function_exists('addValidationMessage')) {
    function addValidationMessage(array &$errors, string $fieldName, string $message): void
    {
        if (!array_key_exists($fieldName, $errors)) {
            $errors[$fieldName] = [];
        }

        $errors[$fieldName][] = $message;
    }
}

if (!function_exists('validateRequiredFields')) {
    function validateRequiredFields(array $input, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $fieldName) {
            if (!array_key_exists($fieldName, $input) || isEmptyValue($input[$fieldName])) {
                addValidationMessage($errors, $fieldName, 'El campo es obligatorio.');
            }
        }

        return $errors;
    }
}

if (!function_exists('validateEmailValue')) {
    function validateEmailValue(mixed $value, string $fieldName, bool $required = true): array
    {
        $errors = [];

        if (isEmptyValue($value)) {
            if ($required) {
                addValidationMessage($errors, $fieldName, 'El correo es obligatorio.');
            }

            return $errors;
        }

        if (!is_scalar($value) || filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
            addValidationMessage($errors, $fieldName, 'El correo no tiene un formato valido.');
        }

        return $errors;
    }
}

if (!function_exists('validateStringLength')) {
    function validateStringLength(
        mixed $value,
        string $fieldName,
        int $minLength = 0,
        ?int $maxLength = null,
        bool $required = true
    ): array {
        $errors = [];

        if (isEmptyValue($value)) {
            if ($required) {
                addValidationMessage($errors, $fieldName, 'El campo es obligatorio.');
            }

            return $errors;
        }

        if (!is_scalar($value)) {
            addValidationMessage($errors, $fieldName, 'El valor debe ser texto.');

            return $errors;
        }

        $length = function_exists('mb_strlen')
            ? mb_strlen(trim((string) $value))
            : strlen(trim((string) $value));

        if ($length < $minLength) {
            addValidationMessage($errors, $fieldName, 'La longitud minima no es valida.');
        }

        if ($maxLength !== null && $length > $maxLength) {
            addValidationMessage($errors, $fieldName, 'La longitud maxima no es valida.');
        }

        return $errors;
    }
}

if (!function_exists('validateNumericValue')) {
    function validateNumericValue(
        mixed $value,
        string $fieldName,
        ?float $min = null,
        ?float $max = null,
        bool $required = true
    ): array {
        $errors = [];

        if (isEmptyValue($value)) {
            if ($required) {
                addValidationMessage($errors, $fieldName, 'El campo es obligatorio.');
            }

            return $errors;
        }

        if (!is_scalar($value) || !is_numeric((string) $value)) {
            addValidationMessage($errors, $fieldName, 'El valor debe ser numerico.');

            return $errors;
        }

        $numericValue = (float) $value;

        if ($min !== null && $numericValue < $min) {
            addValidationMessage($errors, $fieldName, 'El valor es menor al minimo permitido.');
        }

        if ($max !== null && $numericValue > $max) {
            addValidationMessage($errors, $fieldName, 'El valor supera el maximo permitido.');
        }

        return $errors;
    }
}

if (!function_exists('validateEnumValue')) {
    function validateEnumValue(mixed $value, string $fieldName, array $allowedValues, bool $required = true): array
    {
        $errors = [];

        if (isEmptyValue($value)) {
            if ($required) {
                addValidationMessage($errors, $fieldName, 'El campo es obligatorio.');
            }

            return $errors;
        }

        if (!is_scalar($value) || !in_array((string) $value, $allowedValues, true)) {
            addValidationMessage($errors, $fieldName, 'El valor no pertenece al catalogo permitido.');
        }

        return $errors;
    }
}

if (!function_exists('mergeValidationErrors')) {
    function mergeValidationErrors(array ...$errorGroups): array
    {
        $mergedErrors = [];

        foreach ($errorGroups as $errorGroup) {
            foreach ($errorGroup as $fieldName => $messages) {
                if (!array_key_exists($fieldName, $mergedErrors)) {
                    $mergedErrors[$fieldName] = [];
                }

                foreach ((array) $messages as $message) {
                    $mergedErrors[$fieldName][] = $message;
                }
            }
        }

        return $mergedErrors;
    }
}

if (!function_exists('assertValidInput')) {
    function assertValidInput(array $errors): void
    {
        if ($errors !== []) {
            validationErrorResponse($errors);
        }
    }
}
