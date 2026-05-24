<?php
declare(strict_types=1);

if (!function_exists('getDeliveryUploadDefinitions')) {
    function getDeliveryUploadDefinitions(): array
    {
        return [
            'jpg' => [
                'mime_types' => ['image/jpeg'],
                'format' => 'imagen',
            ],
            'jpeg' => [
                'mime_types' => ['image/jpeg'],
                'format' => 'imagen',
            ],
            'png' => [
                'mime_types' => ['image/png'],
                'format' => 'imagen',
            ],
            'pdf' => [
                'mime_types' => ['application/pdf'],
                'format' => 'pdf',
            ],
            'mp4' => [
                'mime_types' => ['video/mp4', 'application/mp4'],
                'format' => 'video',
            ],
        ];
    }
}

if (!function_exists('getAllowedDeliveryFormats')) {
    function getAllowedDeliveryFormats(): array
    {
        return ['imagen', 'pdf', 'video'];
    }
}

if (!function_exists('getMaxDeliveryUploadSizeBytes')) {
    function getMaxDeliveryUploadSizeBytes(): int
    {
        return 50 * 1024 * 1024;
    }
}

if (!function_exists('mapDeliveryUploadErrorCode')) {
    function mapDeliveryUploadErrorCode(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Archivo demasiado grande.',
            UPLOAD_ERR_PARTIAL => 'La carga del archivo no se completo.',
            UPLOAD_ERR_NO_FILE => 'Debes seleccionar un archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal para uploads.',
            UPLOAD_ERR_CANT_WRITE => 'No fue posible guardar el archivo cargado.',
            UPLOAD_ERR_EXTENSION => 'La carga del archivo fue detenida por el servidor.',
            default => 'No fue posible procesar el archivo cargado.',
        };
    }
}

if (!function_exists('detectUploadedFileMimeType')) {
    function detectUploadedFileMimeType(string $filePath): string
    {
        if (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($fileInfo !== false) {
                $mimeType = finfo_file($fileInfo, $filePath);
                finfo_close($fileInfo);

                if (is_string($mimeType) && trim($mimeType) !== '') {
                    return trim($mimeType);
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);

            if (is_string($mimeType) && trim($mimeType) !== '') {
                return trim($mimeType);
            }
        }

        return '';
    }
}

if (!function_exists('resolveDeliveryFormatFromExtension')) {
    function resolveDeliveryFormatFromExtension(string $extension): ?string
    {
        $definitions = getDeliveryUploadDefinitions();

        return $definitions[$extension]['format'] ?? null;
    }
}

if (!function_exists('validateDeliveryUploadFile')) {
    function validateDeliveryUploadFile(array $fileUpload, string $selectedFormat): array
    {
        $errors = [];
        $uploadError = (int) ($fileUpload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            addValidationMessage($errors, 'archivo_final', mapDeliveryUploadErrorCode($uploadError));
            assertValidInput($errors);
        }

        $temporaryPath = (string) ($fileUpload['tmp_name'] ?? '');
        $originalName = sanitizeString($fileUpload['name'] ?? '');
        $fileSize = (int) ($fileUpload['size'] ?? 0);
        $originalExtension = normalizeString(pathinfo($originalName, PATHINFO_EXTENSION));
        $definitions = getDeliveryUploadDefinitions();

        if ($originalExtension === '' || !array_key_exists($originalExtension, $definitions)) {
            addValidationMessage($errors, 'archivo_final', 'Formato no permitido.');
        }

        if ($fileSize <= 0) {
            addValidationMessage($errors, 'archivo_final', 'El archivo cargado esta vacio.');
        }

        if ($fileSize > getMaxDeliveryUploadSizeBytes()) {
            addValidationMessage($errors, 'archivo_final', 'Archivo demasiado grande.');
        }

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            addValidationMessage($errors, 'archivo_final', 'El upload no es valido.');
        }

        $detectedMimeType = $temporaryPath !== '' ? detectUploadedFileMimeType($temporaryPath) : '';

        if ($detectedMimeType === '') {
            addValidationMessage($errors, 'archivo_final', 'No fue posible validar el MIME del archivo.');
        }

        if ($originalExtension !== '' && array_key_exists($originalExtension, $definitions)) {
            $allowedMimeTypes = $definitions[$originalExtension]['mime_types'];

            if ($detectedMimeType !== '' && !in_array($detectedMimeType, $allowedMimeTypes, true)) {
                addValidationMessage($errors, 'archivo_final', 'Archivo invalido por tipo MIME.');
            }

            $detectedFormat = (string) ($definitions[$originalExtension]['format'] ?? '');

            if ($selectedFormat !== '' && $detectedFormat !== '' && $selectedFormat !== $detectedFormat) {
                addValidationMessage($errors, 'formato_entrega', 'El formato seleccionado no coincide con el archivo.');
            }
        }

        assertValidInput($errors);

        return [
            'temporary_path' => $temporaryPath,
            'original_name' => $originalName,
            'original_extension' => $originalExtension,
            'detected_mime_type' => $detectedMimeType,
            'size' => $fileSize,
        ];
    }
}

if (!function_exists('sanitizeDeliveryOrderDirectory')) {
    function sanitizeDeliveryOrderDirectory(string $orderNumber): string
    {
        $normalizedOrderNumber = normalizeOrderNumber($orderNumber);
        $sanitizedDirectory = preg_replace('/[^A-Z0-9-]/', '-', $normalizedOrderNumber) ?? '';
        $sanitizedDirectory = trim($sanitizedDirectory, '-');

        return $sanitizedDirectory !== '' ? $sanitizedDirectory : 'PEDIDO';
    }
}

if (!function_exists('getDeliveryUploadsBaseDirectory')) {
    function getDeliveryUploadsBaseDirectory(): string
    {
        $uploadsRoot = $GLOBALS['appConfig']['UPLOADS_PATH']
            ?? $GLOBALS['appConfig']['uploads_path']
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';

        return rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'deliveries';
    }
}

if (!function_exists('ensureDirectoryExists')) {
    function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar el directorio de almacenamiento.');
        }
    }
}

if (!function_exists('ensureDeliveryUploadsProtection')) {
    function ensureDeliveryUploadsProtection(): void
    {
        $baseDirectory = getDeliveryUploadsBaseDirectory();
        ensureDirectoryExists($baseDirectory);

        $htaccessPath = $baseDirectory . DIRECTORY_SEPARATOR . '.htaccess';

        if (is_file($htaccessPath)) {
            return;
        }

        $rules = <<<HTACCESS
Options -Indexes
<FilesMatch "\.(php|phtml|phar|js|sh|bat|exe)$">
    Require all denied
</FilesMatch>
HTACCESS;

        @file_put_contents($htaccessPath, $rules . PHP_EOL, LOCK_EX);
    }
}

if (!function_exists('buildDeliveryStoragePaths')) {
    function buildDeliveryStoragePaths(string $orderNumber, string $extension): array
    {
        $baseDirectory = getDeliveryUploadsBaseDirectory();
        $orderDirectoryName = sanitizeDeliveryOrderDirectory($orderNumber);
        $absoluteDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $orderDirectoryName;

        ensureDeliveryUploadsProtection();
        ensureDirectoryExists($absoluteDirectory);

        $randomCode = bin2hex(random_bytes(4));
        $fileName = date('Ymd_His') . '_' . $randomCode . '.' . strtolower($extension);
        $relativePath = 'uploads/deliveries/' . $orderDirectoryName . '/' . $fileName;

        return [
            'file_name' => $fileName,
            'absolute_directory' => $absoluteDirectory,
            'absolute_path' => $absoluteDirectory . DIRECTORY_SEPARATOR . $fileName,
            'relative_path' => $relativePath,
        ];
    }
}

if (!function_exists('getDeliveryFileName')) {
    function getDeliveryFileName(string $storedPath): string
    {
        $normalizedPath = str_replace('\\', '/', trim($storedPath));
        $segments = explode('/', $normalizedPath);

        return end($segments) ?: '';
    }
}

if (!function_exists('resolveStoredDeliveryAbsolutePath')) {
    function resolveStoredDeliveryAbsolutePath(string $storedPath): ?string
    {
        $trimmedPath = trim($storedPath);

        if ($trimmedPath === '') {
            return null;
        }

        $uploadsRoot = $GLOBALS['appConfig']['UPLOADS_PATH']
            ?? $GLOBALS['appConfig']['uploads_path']
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';

        $resolvedUploadsRoot = realpath($uploadsRoot);

        if ($resolvedUploadsRoot === false) {
            return null;
        }

        $normalizedRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmedPath);
        $normalizedRelativePath = ltrim($normalizedRelativePath, DIRECTORY_SEPARATOR);

        if (str_contains($normalizedRelativePath, '..')) {
            return null;
        }

        $uploadsPrefix = 'uploads' . DIRECTORY_SEPARATOR;

        if (str_starts_with(strtolower($normalizedRelativePath), strtolower($uploadsPrefix))) {
            $normalizedRelativePath = substr($normalizedRelativePath, strlen($uploadsPrefix));
        }

        $candidatePath = $resolvedUploadsRoot . DIRECTORY_SEPARATOR . $normalizedRelativePath;
        $resolvedFilePath = realpath($candidatePath);

        if ($resolvedFilePath === false || !is_file($resolvedFilePath)) {
            return null;
        }

        $normalizedUploadsRoot = rtrim(strtolower($resolvedUploadsRoot), DIRECTORY_SEPARATOR) . strtolower(DIRECTORY_SEPARATOR);

        if (!str_starts_with(strtolower($resolvedFilePath), $normalizedUploadsRoot)) {
            return null;
        }

        return $resolvedFilePath;
    }
}

if (!function_exists('resolveStoredDeliveryMimeType')) {
    function resolveStoredDeliveryMimeType(string $filePath): string
    {
        $detectedMimeType = detectUploadedFileMimeType($filePath);

        if ($detectedMimeType !== '') {
            return $detectedMimeType;
        }

        $extension = normalizeString(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'mp4' => 'video/mp4',
            default => 'application/octet-stream',
        };
    }
}

if (!function_exists('resolveStoredDeliveryDownloadName')) {
    function resolveStoredDeliveryDownloadName(string $orderNumber, string $filePath): string
    {
        $extension = normalizeString(pathinfo($filePath, PATHINFO_EXTENSION));
        $normalizedOrderNumber = normalizeOrderNumber($orderNumber);

        return $extension === ''
            ? $normalizedOrderNumber
            : ($normalizedOrderNumber . '.' . $extension);
    }
}
