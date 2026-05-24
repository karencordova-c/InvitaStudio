<?php
declare(strict_types=1);

if (!function_exists('getAllowedMessageUserTypes')) {
    function getAllowedMessageUserTypes(): array
    {
        return ['admin', 'cliente'];
    }
}

if (!function_exists('getMessageUploadDefinitions')) {
    function getMessageUploadDefinitions(): array
    {
        return [
            'jpg' => [
                'mime_types' => ['image/jpeg'],
            ],
            'jpeg' => [
                'mime_types' => ['image/jpeg'],
            ],
            'png' => [
                'mime_types' => ['image/png'],
            ],
            'pdf' => [
                'mime_types' => ['application/pdf'],
            ],
        ];
    }
}

if (!function_exists('getMaxMessageUploadSizeBytes')) {
    function getMaxMessageUploadSizeBytes(): int
    {
        return 10 * 1024 * 1024;
    }
}

if (!function_exists('mapMessageUploadErrorCode')) {
    function mapMessageUploadErrorCode(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Archivo demasiado grande.',
            UPLOAD_ERR_PARTIAL => 'La carga del archivo no se completo.',
            UPLOAD_ERR_NO_FILE => 'No se recibio ningun archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal para uploads.',
            UPLOAD_ERR_CANT_WRITE => 'No fue posible guardar el archivo cargado.',
            UPLOAD_ERR_EXTENSION => 'La carga del archivo fue detenida por el servidor.',
            default => 'No fue posible procesar el archivo cargado.',
        };
    }
}

if (!function_exists('validateMessageAttachmentUpload')) {
    function validateMessageAttachmentUpload(array $fileUpload): array
    {
        $errors = [];
        $uploadError = (int) ($fileUpload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            addValidationMessage($errors, 'archivo_adjunto', mapMessageUploadErrorCode($uploadError));
            assertValidInput($errors);
        }

        $temporaryPath = (string) ($fileUpload['tmp_name'] ?? '');
        $originalName = sanitizeString($fileUpload['name'] ?? '');
        $fileSize = (int) ($fileUpload['size'] ?? 0);
        $originalExtension = normalizeString(pathinfo($originalName, PATHINFO_EXTENSION));
        $definitions = getMessageUploadDefinitions();

        if ($originalExtension === '' || !array_key_exists($originalExtension, $definitions)) {
            addValidationMessage($errors, 'archivo_adjunto', 'Formato no permitido.');
        }

        if ($fileSize <= 0) {
            addValidationMessage($errors, 'archivo_adjunto', 'El archivo cargado esta vacio.');
        }

        if ($fileSize > getMaxMessageUploadSizeBytes()) {
            addValidationMessage($errors, 'archivo_adjunto', 'Archivo demasiado grande.');
        }

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            addValidationMessage($errors, 'archivo_adjunto', 'El upload no es valido.');
        }

        $detectedMimeType = $temporaryPath !== '' ? detectUploadedFileMimeType($temporaryPath) : '';

        if ($detectedMimeType === '') {
            addValidationMessage($errors, 'archivo_adjunto', 'No fue posible validar el MIME del archivo.');
        }

        if ($originalExtension !== '' && array_key_exists($originalExtension, $definitions)) {
            $allowedMimeTypes = $definitions[$originalExtension]['mime_types'];

            if ($detectedMimeType !== '' && !in_array($detectedMimeType, $allowedMimeTypes, true)) {
                addValidationMessage($errors, 'archivo_adjunto', 'Archivo invalido por tipo MIME.');
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

if (!function_exists('sanitizeMessageOrderDirectory')) {
    function sanitizeMessageOrderDirectory(string $orderNumber): string
    {
        $normalizedOrderNumber = normalizeOrderNumber($orderNumber);
        $sanitizedDirectory = preg_replace('/[^A-Z0-9-]/', '-', $normalizedOrderNumber) ?? '';
        $sanitizedDirectory = trim($sanitizedDirectory, '-');

        return $sanitizedDirectory !== '' ? $sanitizedDirectory : 'PEDIDO';
    }
}

if (!function_exists('getMessageUploadsBaseDirectory')) {
    function getMessageUploadsBaseDirectory(): string
    {
        $uploadsRoot = $GLOBALS['appConfig']['UPLOADS_PATH']
            ?? $GLOBALS['appConfig']['uploads_path']
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';

        return rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'messages';
    }
}

if (!function_exists('ensureMessageUploadsProtection')) {
    function ensureMessageUploadsProtection(): void
    {
        $baseDirectory = getMessageUploadsBaseDirectory();
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

if (!function_exists('buildMessageAttachmentStoragePaths')) {
    function buildMessageAttachmentStoragePaths(string $orderNumber, string $extension): array
    {
        $baseDirectory = getMessageUploadsBaseDirectory();
        $orderDirectoryName = sanitizeMessageOrderDirectory($orderNumber);
        $absoluteDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $orderDirectoryName;

        ensureMessageUploadsProtection();
        ensureDirectoryExists($absoluteDirectory);

        $randomCode = bin2hex(random_bytes(4));
        $fileName = date('Ymd_His') . '_' . $randomCode . '.' . strtolower($extension);
        $relativePath = 'uploads/messages/' . $orderDirectoryName . '/' . $fileName;

        return [
            'file_name' => $fileName,
            'absolute_directory' => $absoluteDirectory,
            'absolute_path' => $absoluteDirectory . DIRECTORY_SEPARATOR . $fileName,
            'relative_path' => $relativePath,
        ];
    }
}

if (!function_exists('getMessageAttachmentFileName')) {
    function getMessageAttachmentFileName(string $storedPath): string
    {
        $normalizedPath = str_replace('\\', '/', trim($storedPath));
        $segments = explode('/', $normalizedPath);

        return end($segments) ?: '';
    }
}

if (!function_exists('resolveStoredMessageAbsolutePath')) {
    function resolveStoredMessageAbsolutePath(string $storedPath): ?string
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

if (!function_exists('resolveStoredMessageMimeType')) {
    function resolveStoredMessageMimeType(string $filePath): string
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
            default => 'application/octet-stream',
        };
    }
}

if (!function_exists('resolveStoredMessageDownloadName')) {
    function resolveStoredMessageDownloadName(string $orderNumber, string $filePath): string
    {
        $extension = normalizeString(pathinfo($filePath, PATHINFO_EXTENSION));
        $normalizedOrderNumber = normalizeOrderNumber($orderNumber);
        $baseName = $normalizedOrderNumber . '-mensaje-adjunto';

        return $extension === ''
            ? $baseName
            : ($baseName . '.' . $extension);
    }
}

if (!function_exists('getMessageAuthorLabel')) {
    function getMessageAuthorLabel(string $userType): string
    {
        return $userType === 'admin' ? 'Administrador' : 'Cliente';
    }
}
