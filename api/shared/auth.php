<?php
declare(strict_types=1);

if (!function_exists('startAdminSession')) {
    function startAdminSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $cookieParams = session_get_cookie_params();
        $isSecureRequest = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_name('INVITASTUDIO_ADMIN_SESSION');
        session_set_cookie_params(
            [
                'lifetime' => 0,
                'path' => $cookieParams['path'] ?: '/',
                'domain' => $cookieParams['domain'] ?: '',
                'secure' => $isSecureRequest,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        session_start();
    }
}

if (!function_exists('isAuthenticationEnabled')) {
    function isAuthenticationEnabled(): bool
    {
        return true;
    }
}

if (!function_exists('hashAdminPassword')) {
    function hashAdminPassword(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }
}

if (!function_exists('persistAdminSession')) {
    function persistAdminSession(array $adminUser): void
    {
        startAdminSession();
        session_regenerate_id(true);

        $_SESSION['admin_id'] = (int) ($adminUser['id'] ?? 0);
        $_SESSION['admin_nombre'] = (string) ($adminUser['nombre'] ?? '');
        $_SESSION['admin_correo'] = (string) ($adminUser['correo'] ?? '');
        $_SESSION['admin_rol'] = (string) ($adminUser['rol'] ?? '');
        $_SESSION['admin_logged_in'] = true;
    }
}

if (!function_exists('clearAdminSession')) {
    function clearAdminSession(): void
    {
        startAdminSession();

        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_nombre'],
            $_SESSION['admin_correo'],
            $_SESSION['admin_rol'],
            $_SESSION['admin_logged_in']
        );
    }
}

if (!function_exists('destroyAdminSession')) {
    function destroyAdminSession(): void
    {
        startAdminSession();
        clearAdminSession();

        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $cookieParams['path'] ?: '/',
                    'domain' => $cookieParams['domain'] ?: '',
                    'secure' => (bool) $cookieParams['secure'],
                    'httponly' => (bool) $cookieParams['httponly'],
                    'samesite' => $cookieParams['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }
}

if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn(): bool
    {
        startAdminSession();

        return ($_SESSION['admin_logged_in'] ?? false) === true
            && (int) ($_SESSION['admin_id'] ?? 0) > 0
            && trim((string) ($_SESSION['admin_correo'] ?? '')) !== '';
    }
}

if (!function_exists('getAdminUser')) {
    function getAdminUser(): ?array
    {
        if (!isAdminLoggedIn()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['admin_id'],
            'nombre' => (string) $_SESSION['admin_nombre'],
            'correo' => (string) $_SESSION['admin_correo'],
            'rol' => (string) $_SESSION['admin_rol'],
        ];
    }
}

if (!function_exists('getApplicationBasePath')) {
    function getApplicationBasePath(): string
    {
        $baseUrl = (string) ($GLOBALS['appConfig']['APP_URL'] ?? $GLOBALS['appConfig']['BASE_URL'] ?? '');
        $basePath = (string) parse_url($baseUrl, PHP_URL_PATH);

        if ($basePath !== '' && $basePath !== '/') {
            return '/' . trim($basePath, '/');
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        if ($scriptName === '') {
            return '';
        }

        foreach (['/api/', '/admin/', '/public/'] as $segment) {
            $position = strpos($scriptName, $segment);

            if ($position === false) {
                continue;
            }

            $detectedBasePath = substr($scriptName, 0, $position);

            if ($detectedBasePath === '' || $detectedBasePath === '/') {
                return '';
            }

            return '/' . trim($detectedBasePath, '/');
        }

        return '';
    }
}

if (!function_exists('buildAbsoluteBackendUrl')) {
    function buildAbsoluteBackendUrl(string $path = '', array $query = []): string
    {
        $baseUrl = rtrim((string) ($GLOBALS['appConfig']['APP_URL'] ?? $GLOBALS['appConfig']['BASE_URL'] ?? ''), '/');
        $relativePath = ltrim($path, '/');
        $url = $relativePath === '' ? $baseUrl : ($baseUrl . '/' . $relativePath);

        if ($query === []) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }
}

if (!function_exists('buildAbsoluteApiUrl')) {
    function buildAbsoluteApiUrl(string $path = '', array $query = []): string
    {
        $baseUrl = rtrim(
            (string) ($GLOBALS['appConfig']['API_BASE_URL'] ?? $GLOBALS['appConfig']['api_base_url'] ?? ''),
            '/'
        );

        if ($baseUrl === '') {
            return buildAbsoluteBackendUrl('api/' . ltrim($path, '/'), $query);
        }

        $relativePath = ltrim($path, '/');
        $url = $relativePath === '' ? $baseUrl : ($baseUrl . '/' . $relativePath);

        if ($query === []) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }
}

if (!function_exists('buildAbsolutePublicUrl')) {
    function buildAbsolutePublicUrl(string $path = '', array $query = []): string
    {
        $baseUrl = rtrim(
            (string) ($GLOBALS['appConfig']['PUBLIC_BASE_URL'] ?? $GLOBALS['appConfig']['public_base_url'] ?? ''),
            '/'
        );

        if ($baseUrl === '') {
            return buildAbsoluteBackendUrl('public/' . ltrim($path, '/'), $query);
        }

        $relativePath = ltrim($path, '/');
        $url = $relativePath === '' ? $baseUrl : ($baseUrl . '/' . $relativePath);

        if ($query === []) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }
}

if (!function_exists('getAdminLoginUrl')) {
    function getAdminLoginUrl(): string
    {
        return getApplicationBasePath() . '/admin/login.php';
    }
}

if (!function_exists('getAdminDashboardUrl')) {
    function getAdminDashboardUrl(): string
    {
        return getApplicationBasePath() . '/admin/index.php';
    }
}

if (!function_exists('isApiRequest')) {
    function isApiRequest(): bool
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $requestUri = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));
        $acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return str_contains($scriptName, '/api/')
            || str_contains($requestUri, '/api/')
            || str_contains($acceptHeader, 'application/json')
            || $requestedWith === 'xmlhttprequest';
    }
}

if (!function_exists('redirectToLocation')) {
    function redirectToLocation(string $location, int $statusCode = 302): void
    {
        header('Location: ' . $location, true, $statusCode);
        exit;
    }
}

if (!function_exists('buildAdminLoginRedirectUrl')) {
    function buildAdminLoginRedirectUrl(): string
    {
        $loginUrl = getAdminLoginUrl();

        return $loginUrl . '?auth=required';
    }
}

if (!function_exists('requireAdminAuth')) {
    function requireAdminAuth(): array
    {
        $adminUser = getAdminUser();

        if ($adminUser !== null) {
            return $adminUser;
        }

        if (isApiRequest()) {
            errorResponse('No autorizado.', [], 401);
        }

        redirectToLocation(buildAdminLoginRedirectUrl());
    }
}

if (!function_exists('requireAuthentication')) {
    function requireAuthentication(): array
    {
        return requireAdminAuth();
    }
}
