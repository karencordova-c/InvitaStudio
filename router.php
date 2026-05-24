<?php
declare(strict_types=1);

$basePath = __DIR__;
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$requestPath = '/' . ltrim(rawurldecode($requestPath), '/');

$blockedPrefixes = [
    '/.env',
    '/config/',
    '/database/',
    '/docs/',
    '/storage/',
    '/templates/',
    '/vendor/',
];

foreach ($blockedPrefixes as $blockedPrefix) {
    if ($requestPath === rtrim($blockedPrefix, '/') || str_starts_with($requestPath, $blockedPrefix)) {
        http_response_code(404);
        return true;
    }
}

$routeMap = [
    '/' => '/public/index.html',
    '/favicon.ico' => '/public/favicon.ico',
];

if (isset($routeMap[$requestPath])) {
    return serveStaticFile($basePath . $routeMap[$requestPath]);
}

if (str_starts_with($requestPath, '/assets/')) {
    return serveStaticFile($basePath . '/public' . $requestPath);
}

if (str_starts_with($requestPath, '/public/')) {
    return serveStaticFile($basePath . $requestPath);
}

if (str_starts_with($requestPath, '/api/') || str_starts_with($requestPath, '/admin/')) {
    $targetPath = realpath($basePath . $requestPath);

    if ($targetPath !== false && is_file($targetPath)) {
        return false;
    }
}

http_response_code(404);
return true;

function serveStaticFile(string $filePath): bool
{
    $resolvedPath = realpath($filePath);

    if ($resolvedPath === false || !is_file($resolvedPath)) {
        http_response_code(404);
        return true;
    }

    $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8',
        'gif' => 'image/gif',
        'html' => 'text/html; charset=UTF-8',
        'ico' => 'image/svg+xml',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'js' => 'application/javascript; charset=UTF-8',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }

    readfile($resolvedPath);
    return true;
}
