<?php
declare(strict_types=1);

if (!function_exists('sendOrderConfirmation')) {
    function sendOrderConfirmation(PDO $connection, array $orderData): void
    {
        dispatchEmailNotification(
            $connection,
            [
                'notification_type' => 'order_confirmation',
                'recipient_email' => (string) ($orderData['cliente_correo'] ?? ''),
                'recipient_name' => (string) ($orderData['cliente_nombre'] ?? ''),
                'subject' => 'Confirmacion de pedido ' . (string) ($orderData['numero_pedido'] ?? ''),
                'template' => 'order_confirmation',
                'reference_id' => isset($orderData['pedido_id']) ? (int) $orderData['pedido_id'] : null,
                'template_data' => [
                    'numero_pedido' => (string) ($orderData['numero_pedido'] ?? ''),
                    'fecha_evento' => formatEmailDateTime((string) ($orderData['fecha_evento'] ?? '')),
                    'estado_inicial' => getOrderStatusLabelForEmail((string) ($orderData['estado_pedido'] ?? 'pendiente')),
                    'nombre_evento' => (string) ($orderData['nombre_evento'] ?? ''),
                    'tipo_evento' => (string) ($orderData['tipo_evento'] ?? ''),
                    'servicio' => (string) ($orderData['servicio'] ?? ''),
                    'formato_entrega' => getDeliveryFormatLabelForEmail((string) ($orderData['formato_entrega'] ?? '')),
                    'monto_pago' => formatEmailCurrency((float) ($orderData['monto_pago'] ?? 0)),
                    'payment_url' => buildPublicPaymentUrl(
                        (string) ($orderData['numero_pedido'] ?? ''),
                        (string) ($orderData['cliente_correo'] ?? '')
                    ),
                    'status_url' => buildPublicStatusUrl(
                        (string) ($orderData['numero_pedido'] ?? ''),
                        (string) ($orderData['cliente_correo'] ?? '')
                    ),
                ],
            ]
        );
    }
}

if (!function_exists('sendPaymentConfirmation')) {
    function sendPaymentConfirmation(PDO $connection, array $paymentData): void
    {
        dispatchEmailNotification(
            $connection,
            [
                'notification_type' => 'payment_confirmation',
                'recipient_email' => (string) ($paymentData['cliente_correo'] ?? ''),
                'recipient_name' => (string) ($paymentData['cliente_nombre'] ?? ''),
                'subject' => 'Pago confirmado para ' . (string) ($paymentData['numero_pedido'] ?? ''),
                'template' => 'payment_confirmation',
                'reference_id' => isset($paymentData['pedido_id']) ? (int) $paymentData['pedido_id'] : null,
                'template_data' => [
                    'numero_pedido' => (string) ($paymentData['numero_pedido'] ?? ''),
                    'monto_pago' => formatEmailCurrency((float) ($paymentData['monto_pago'] ?? 0)),
                    'estado_pago' => getPaymentStatusLabelForEmail((string) ($paymentData['estado_pago'] ?? 'confirmado')),
                    'referencia_pago' => (string) ($paymentData['referencia_pago'] ?? ''),
                    'fecha_pago' => formatEmailDateTime((string) ($paymentData['fecha_pago'] ?? '')),
                    'status_url' => buildPublicStatusUrl(
                        (string) ($paymentData['numero_pedido'] ?? ''),
                        (string) ($paymentData['cliente_correo'] ?? '')
                    ),
                ],
            ]
        );
    }
}

if (!function_exists('sendDeliveryNotification')) {
    function sendDeliveryNotification(PDO $connection, array $deliveryData): void
    {
        dispatchEmailNotification(
            $connection,
            [
                'notification_type' => 'delivery_notification',
                'recipient_email' => (string) ($deliveryData['cliente_correo'] ?? ''),
                'recipient_name' => (string) ($deliveryData['cliente_nombre'] ?? ''),
                'subject' => 'Entrega disponible para ' . (string) ($deliveryData['numero_pedido'] ?? ''),
                'template' => 'delivery_notification',
                'reference_id' => isset($deliveryData['pedido_id']) ? (int) $deliveryData['pedido_id'] : null,
                'template_data' => [
                    'numero_pedido' => (string) ($deliveryData['numero_pedido'] ?? ''),
                    'formato_entrega' => getDeliveryFormatLabelForEmail((string) ($deliveryData['formato_entrega'] ?? '')),
                    'fecha_entrega' => formatEmailDateTime((string) ($deliveryData['fecha_entrega'] ?? '')),
                    'notas_entrega' => (string) ($deliveryData['notas_entrega'] ?? ''),
                    'status_url' => buildPublicStatusUrl(
                        (string) ($deliveryData['numero_pedido'] ?? ''),
                        (string) ($deliveryData['cliente_correo'] ?? '')
                    ),
                ],
            ]
        );
    }
}

if (!function_exists('sendClarificationNotification')) {
    function sendClarificationNotification(PDO $connection, array $messageData): void
    {
        $mailConfig = getMailConfig();
        $actorType = (string) ($messageData['tipo_usuario'] ?? 'cliente');
        $isAdminMessage = $actorType === 'admin';
        $recipientEmail = $isAdminMessage
            ? (string) ($messageData['cliente_correo'] ?? '')
            : (string) ($mailConfig['admin_notification_email'] ?? '');
        $recipientName = $isAdminMessage
            ? (string) ($messageData['cliente_nombre'] ?? '')
            : (string) ($mailConfig['admin_notification_name'] ?? 'InvitaStudio Admin');

        dispatchEmailNotification(
            $connection,
            [
                'notification_type' => 'clarification_notification',
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $isAdminMessage
                    ? 'Nueva aclaracion para tu pedido ' . (string) ($messageData['numero_pedido'] ?? '')
                    : 'Nuevo mensaje del cliente en ' . (string) ($messageData['numero_pedido'] ?? ''),
                'template' => 'clarification_notification',
                'reference_id' => isset($messageData['pedido_id']) ? (int) $messageData['pedido_id'] : null,
                'template_data' => [
                    'numero_pedido' => (string) ($messageData['numero_pedido'] ?? ''),
                    'autor' => getMessageActorLabelForEmail($actorType),
                    'mensaje' => (string) ($messageData['mensaje'] ?? ''),
                    'destinatario_tipo' => $isAdminMessage ? 'cliente' : 'admin',
                    'cliente_nombre' => (string) ($messageData['cliente_nombre'] ?? ''),
                    'cliente_correo' => (string) ($messageData['cliente_correo'] ?? ''),
                    'status_url' => buildPublicStatusUrl(
                        (string) ($messageData['numero_pedido'] ?? ''),
                        (string) ($messageData['cliente_correo'] ?? '')
                    ),
                ],
            ]
        );
    }
}

if (!function_exists('dispatchEmailNotification')) {
    function dispatchEmailNotification(PDO $connection, array $notification): void
    {
        $notificationType = (string) ($notification['notification_type'] ?? 'mail');
        $recipientEmail = normalizeString($notification['recipient_email'] ?? '');
        $recipientName = sanitizeString($notification['recipient_name'] ?? '');
        $referenceId = isset($notification['reference_id']) ? (int) $notification['reference_id'] : null;

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            logNotificationOutcome(
                $connection,
                'correo_error',
                $notificationType,
                $referenceId,
                'Destinatario invalido u omitido para ' . $notificationType . '.'
            );

            return;
        }

        $mailConfig = getMailConfig();

        if (!isMailConfigurationEnabled($mailConfig)) {
            logNotificationOutcome(
                $connection,
                'correo_omitido',
                $notificationType,
                $referenceId,
                'Notificacion omitida para ' . $notificationType . ' por MAIL_ENABLED=false.'
            );

            return;
        }

        $configurationError = validateMailConfiguration($mailConfig);

        if ($configurationError !== null) {
            logNotificationOutcome(
                $connection,
                'correo_error',
                $notificationType,
                $referenceId,
                'Configuracion SMTP invalida para ' . $notificationType . '.'
            );

            error_log('InvitaStudio mail configuration error: ' . $configurationError);

            return;
        }

        try {
            $mailer = buildPhpMailerInstance($mailConfig);
            $mailer->setFrom((string) $mailConfig['from_email'], (string) $mailConfig['from_name']);
            $mailer->addAddress($recipientEmail, $recipientName);

            $replyToEmail = normalizeString($mailConfig['reply_to_email'] ?? '');

            if (filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mailer->addReplyTo($replyToEmail, sanitizeString($mailConfig['reply_to_name'] ?? ''));
            }

            $htmlBody = renderEmailTemplate(
                (string) ($notification['template'] ?? ''),
                is_array($notification['template_data'] ?? null) ? $notification['template_data'] : []
            );

            $mailer->isHTML(true);
            $mailer->CharSet = 'UTF-8';
            $mailer->Subject = sanitizeString($notification['subject'] ?? 'Notificacion InvitaStudio');
            $mailer->Body = $htmlBody;
            $mailer->AltBody = convertEmailHtmlToText($htmlBody);
            $mailer->send();

            logNotificationOutcome(
                $connection,
                'correo_enviado',
                $notificationType,
                $referenceId,
                'Correo ' . $notificationType . ' enviado a ' . $recipientEmail . '.'
            );
        } catch (Throwable $exception) {
            logNotificationOutcome(
                $connection,
                'correo_error',
                $notificationType,
                $referenceId,
                'Fallo al enviar ' . $notificationType . ' a ' . $recipientEmail . '.'
            );

            logMailFailure($notificationType, $recipientEmail, $exception);
        }
    }
}

if (!function_exists('buildPhpMailerInstance')) {
    function buildPhpMailerInstance(array $mailConfig): object
    {
        loadPhpMailerClasses();

        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            throw new RuntimeException('PHPMailer no esta disponible en el proyecto.');
        }

        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = (string) $mailConfig['host'];
        $mailer->Port = (int) $mailConfig['port'];
        $mailer->SMTPAuth = trim((string) ($mailConfig['username'] ?? '')) !== '';
        $mailer->Username = (string) ($mailConfig['username'] ?? '');
        $mailer->Password = (string) ($mailConfig['password'] ?? '');
        $mailer->Timeout = (int) ($mailConfig['timeout'] ?? 15);

        $encryption = normalizeString($mailConfig['encryption'] ?? '');

        if ($encryption === 'tls') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        }

        return $mailer;
    }
}

if (!function_exists('loadPhpMailerClasses')) {
    function loadPhpMailerClasses(): void
    {
        $basePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'phpmailer'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR;

        $requiredFiles = [
            $basePath . 'Exception.php',
            $basePath . 'SMTP.php',
            $basePath . 'PHPMailer.php',
        ];

        foreach ($requiredFiles as $requiredFile) {
            if (is_file($requiredFile)) {
                require_once $requiredFile;
            }
        }
    }
}

if (!function_exists('getMailConfig')) {
    function getMailConfig(): array
    {
        static $mailConfig = null;

        if (is_array($mailConfig)) {
            return $mailConfig;
        }

        $configPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'mail.php';

        if (!is_file($configPath)) {
            $mailConfig = [];
            return $mailConfig;
        }

        $loadedConfig = require $configPath;
        $mailConfig = is_array($loadedConfig) ? $loadedConfig : [];

        return $mailConfig;
    }
}

if (!function_exists('isMailConfigurationEnabled')) {
    function isMailConfigurationEnabled(array $mailConfig): bool
    {
        return (bool) ($mailConfig['enabled'] ?? false) === true
            && normalizeString($mailConfig['driver'] ?? '') === 'smtp';
    }
}

if (!function_exists('validateMailConfiguration')) {
    function validateMailConfiguration(array $mailConfig): ?string
    {
        if (sanitizeString($mailConfig['host'] ?? '') === '') {
            return 'MAIL_HOST es obligatorio.';
        }

        if ((int) ($mailConfig['port'] ?? 0) <= 0) {
            return 'MAIL_PORT debe ser mayor a cero.';
        }

        if (!filter_var($mailConfig['from_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return 'MAIL_FROM debe ser un correo valido.';
        }

        if (sanitizeString($mailConfig['from_name'] ?? '') === '') {
            return 'MAIL_FROM_NAME es obligatorio.';
        }

        $username = sanitizeString($mailConfig['username'] ?? '');
        $password = (string) ($mailConfig['password'] ?? '');

        if (($username === '' && $password !== '') || ($username !== '' && $password === '')) {
            return 'MAIL_USERNAME y MAIL_PASSWORD deben configurarse juntos.';
        }

        return null;
    }
}

if (!function_exists('renderEmailTemplate')) {
    function renderEmailTemplate(string $templateName, array $emailData): string
    {
        $templatePath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . 'emails'
            . DIRECTORY_SEPARATOR . $templateName . '.php';

        if (!is_file($templatePath)) {
            throw new RuntimeException('No existe la plantilla de correo ' . $templateName . '.');
        }

        $renderedTemplate = require $templatePath;

        if (!is_string($renderedTemplate) || trim($renderedTemplate) === '') {
            throw new RuntimeException('La plantilla de correo ' . $templateName . ' devolvio contenido invalido.');
        }

        return $renderedTemplate;
    }
}

if (!function_exists('renderEmailLayout')) {
    function renderEmailLayout(array $content): string
    {
        $appName = emailEscape($GLOBALS['appConfig']['APP_NAME'] ?? 'InvitaStudio');
        $title = emailEscape($content['title'] ?? 'Notificacion');
        $preheader = emailEscape($content['preheader'] ?? '');
        $intro = emailEscape($content['intro'] ?? '');
        $ctaLabel = emailEscape($content['cta_label'] ?? '');
        $ctaUrl = is_string($content['cta_url'] ?? null) ? trim((string) $content['cta_url']) : '';
        $footer = emailEscape($content['footer'] ?? 'InvitaStudio | Sistema escolar de invitaciones digitales');
        $sections = is_array($content['sections'] ?? null) ? $content['sections'] : [];
        $bodyLines = is_array($content['body_lines'] ?? null) ? $content['body_lines'] : [];
        $bodyHtml = '';
        $sectionsHtml = '';
        $ctaHtml = '';

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $label = emailEscape($section['label'] ?? '');
            $value = nl2br(emailEscape($section['value'] ?? ''));

            if ($label === '' || trim(strip_tags($value)) === '') {
                continue;
            }

            $sectionsHtml .= '
                <tr>
                    <td style="padding: 0 0 14px;">
                        <p style="margin: 0 0 4px; color: #6b7280; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase;">' . $label . '</p>
                        <p style="margin: 0; color: #111827; font-size: 15px; line-height: 1.6;">' . $value . '</p>
                    </td>
                </tr>';
        }

        foreach ($bodyLines as $bodyLine) {
            $bodyLineText = sanitizeString($bodyLine);

            if ($bodyLineText === '') {
                continue;
            }

            $bodyHtml .= '<p style="margin: 0 0 14px; color: #374151; font-size: 15px; line-height: 1.7;">'
                . emailEscape($bodyLineText)
                . '</p>';
        }

        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $ctaHtml = '
                <tr>
                    <td style="padding: 10px 0 0;">
                        <a href="' . emailEscape($ctaUrl) . '" style="display: inline-block; padding: 12px 20px; background: #111827; color: #ffffff; text-decoration: none; border-radius: 999px; font-weight: 600;">' . $ctaLabel . '</a>
                    </td>
                </tr>';
        }

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
</head>
<body style="margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0;">' . $preheader . '</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f3f4f6; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; background: #ffffff; border-radius: 20px; overflow: hidden;">
                    <tr>
                        <td style="padding: 28px 32px; background: #111827;">
                            <p style="margin: 0 0 10px; color: #cbd5e1; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase;">' . $appName . '</p>
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; line-height: 1.2;">' . $title . '</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 32px;">
                            <p style="margin: 0 0 18px; color: #111827; font-size: 16px; line-height: 1.7;">' . $intro . '</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px;">' . $sectionsHtml . '</table>
                            <div style="padding-top: 20px;">' . $bodyHtml . '</div>
                            <table role="presentation" cellpadding="0" cellspacing="0">' . $ctaHtml . '</table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 28px; color: #6b7280; font-size: 13px; line-height: 1.6;">
                            <p style="margin: 0;">' . $footer . '</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}

if (!function_exists('convertEmailHtmlToText')) {
    function convertEmailHtmlToText(string $html): string
    {
        $plainText = str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</tr>', '</table>', '</h1>', '</h2>', '</h3>'],
            ["\n", "\n", "\n", "\n\n", "\n", "\n", "\n\n", "\n\n", "\n\n"],
            $html
        );

        $plainText = html_entity_decode(strip_tags($plainText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = preg_replace("/\n{3,}/", "\n\n", $plainText) ?? $plainText;

        return trim($plainText);
    }
}

if (!function_exists('emailEscape')) {
    function emailEscape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('buildPublicStatusUrl')) {
    function buildPublicStatusUrl(string $orderNumber, string $email): string
    {
        return buildAbsolutePublicUrl(
            'status.html',
            [
                'numero_pedido' => $orderNumber,
                'correo' => $email,
            ]
        );
    }
}

if (!function_exists('buildPublicPaymentUrl')) {
    function buildPublicPaymentUrl(string $orderNumber, string $email): string
    {
        return buildAbsolutePublicUrl(
            'payment.html',
            [
                'numero_pedido' => $orderNumber,
                'correo' => $email,
            ]
        );
    }
}

if (!function_exists('formatEmailDateTime')) {
    function formatEmailDateTime(string $value): string
    {
        if (trim($value) === '') {
            return 'Sin fecha registrada';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return sanitizeString($value);
        }

        return date('d/m/Y H:i', $timestamp);
    }
}

if (!function_exists('formatEmailCurrency')) {
    function formatEmailCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',') . ' MXN';
    }
}

if (!function_exists('getOrderStatusLabelForEmail')) {
    function getOrderStatusLabelForEmail(string $status): string
    {
        return match ($status) {
            'pago_confirmado' => 'Pago confirmado',
            'en_proceso' => 'En proceso',
            'terminado' => 'Terminado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            default => 'Pendiente',
        };
    }
}

if (!function_exists('getPaymentStatusLabelForEmail')) {
    function getPaymentStatusLabelForEmail(string $status): string
    {
        return match ($status) {
            'confirmado' => 'Confirmado',
            'rechazado' => 'Rechazado',
            'reembolsado' => 'Reembolsado',
            default => 'Pendiente',
        };
    }
}

if (!function_exists('getDeliveryFormatLabelForEmail')) {
    function getDeliveryFormatLabelForEmail(string $deliveryFormat): string
    {
        return match ($deliveryFormat) {
            'pdf' => 'PDF',
            'video', 'mp4' => 'Video',
            default => 'Imagen',
        };
    }
}

if (!function_exists('getMessageActorLabelForEmail')) {
    function getMessageActorLabelForEmail(string $actorType): string
    {
        return $actorType === 'admin' ? 'Administrador InvitaStudio' : 'Cliente';
    }
}

if (!function_exists('logNotificationOutcome')) {
    function logNotificationOutcome(
        PDO $connection,
        string $action,
        string $notificationType,
        ?int $referenceId,
        string $description
    ): void {
        try {
            createActivityLogEntry(
                $connection,
                'sistema',
                null,
                $action,
                'notifications',
                $referenceId,
                $description . ' Tipo: ' . $notificationType . '.',
                '127.0.0.1'
            );
        } catch (Throwable $exception) {
            error_log('InvitaStudio notification log error: ' . $exception->getMessage());
        }
    }
}

if (!function_exists('logMailFailure')) {
    function logMailFailure(string $notificationType, string $recipientEmail, Throwable $exception): void
    {
        $payload = [
            'notification_type' => $notificationType,
            'recipient_email' => $recipientEmail,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        error_log('InvitaStudio mail error: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
