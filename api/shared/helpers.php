<?php
declare(strict_types=1);

if (!function_exists('sanitizeString')) {
    function sanitizeString(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $cleanValue = strip_tags((string) $value);
        $cleanValue = trim($cleanValue);

        return preg_replace('/\s+/', ' ', $cleanValue) ?? '';
    }
}

if (!function_exists('sanitizeArray')) {
    function sanitizeArray(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = sanitizeArray($value);
                continue;
            }

            $sanitized[$key] = is_scalar($value) ? sanitizeString($value) : $value;
        }

        return $sanitized;
    }
}

if (!function_exists('normalizeString')) {
    function normalizeString(mixed $value): string
    {
        $sanitizedValue = sanitizeString($value);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($sanitizedValue, 'UTF-8');
        }

        return strtolower($sanitizedValue);
    }
}

if (!function_exists('normalizeOrderNumber')) {
    function normalizeOrderNumber(mixed $value): string
    {
        $normalizedValue = sanitizeString($value);

        if ($normalizedValue === '') {
            return '';
        }

        $normalizedValue = str_replace(' ', '', $normalizedValue);

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($normalizedValue, 'UTF-8');
        }

        return strtoupper($normalizedValue);
    }
}

if (!function_exists('getCurrentDateTime')) {
    function getCurrentDateTime(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('formatDateValue')) {
    function formatDateValue(?string $value, string $outputFormat = 'Y-m-d H:i:s'): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date($outputFormat, $timestamp);
    }
}

if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber(int $sequence, ?int $year = null): string
    {
        $normalizedSequence = max(1, $sequence);
        $resolvedYear = $year ?? (int) date('Y');

        return sprintf('INV-%04d-%06d', $resolvedYear, $normalizedSequence);
    }
}

if (!function_exists('generateReferenceCode')) {
    function generateReferenceCode(string $prefix): string
    {
        return sprintf(
            '%s-%s-%04d',
            strtoupper(sanitizeString($prefix)),
            date('YmdHis'),
            random_int(1000, 9999)
        );
    }
}

if (!function_exists('getClientIpAddress')) {
    function getClientIpAddress(): string
    {
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

        if (is_string($forwardedFor) && trim($forwardedFor) !== '') {
            $ipList = explode(',', $forwardedFor);

            return trim($ipList[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }
}
