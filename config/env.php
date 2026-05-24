<?php
declare(strict_types=1);

if (!function_exists('hasEnvironmentValue')) {
    function hasEnvironmentValue(string $key): bool
    {
        return array_key_exists($key, $_ENV)
            || array_key_exists($key, $_SERVER)
            || getenv($key) !== false;
    }
}

if (!function_exists('parseEnvironmentValue')) {
    function parseEnvironmentValue(string $rawValue): string
    {
        $trimmedValue = trim($rawValue);

        if ($trimmedValue === '') {
            return '';
        }

        $firstCharacter = $trimmedValue[0];
        $lastCharacter = $trimmedValue[strlen($trimmedValue) - 1];

        if (
            ($firstCharacter === '"' || $firstCharacter === '\'')
            && $lastCharacter === $firstCharacter
            && strlen($trimmedValue) >= 2
        ) {
            $unquotedValue = substr($trimmedValue, 1, -1);

            return $firstCharacter === '"'
                ? stripcslashes($unquotedValue)
                : $unquotedValue;
        }

        $inlineCommentPosition = strpos($trimmedValue, ' #');

        if ($inlineCommentPosition !== false) {
            $trimmedValue = substr($trimmedValue, 0, $inlineCommentPosition);
        }

        return trim($trimmedValue);
    }
}

if (!function_exists('loadEnvironmentFile')) {
    function loadEnvironmentFile(string $filePath, bool $overrideExisting = false): void
    {
        static $loadedFiles = [];

        $normalizedPath = str_replace('\\', '/', $filePath);

        if (isset($loadedFiles[$normalizedPath]) || !is_file($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmedLine = trim((string) $line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            if (str_starts_with($trimmedLine, 'export ')) {
                $trimmedLine = trim(substr($trimmedLine, 7));
            }

            $separatorPosition = strpos($trimmedLine, '=');

            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $separatorPosition));

            if ($key === '' || preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
                continue;
            }

            if (!$overrideExisting && hasEnvironmentValue($key)) {
                continue;
            }

            $value = parseEnvironmentValue(substr($trimmedLine, $separatorPosition + 1));

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $loadedFiles[$normalizedPath] = true;
    }
}
