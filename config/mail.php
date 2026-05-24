<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

loadEnvironmentFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

$mailDriver = getenv('MAIL_DRIVER') ?: 'smtp';
$mailHost = getenv('MAIL_HOST') ?: '';
$mailPort = (int) (getenv('MAIL_PORT') ?: 587);
$mailUsername = getenv('MAIL_USERNAME') ?: '';
$mailPassword = getenv('MAIL_PASSWORD') ?: '';
$mailFrom = getenv('MAIL_FROM') ?: '';
$mailFromName = getenv('MAIL_FROM_NAME') ?: 'InvitaStudio';
$mailEncryption = getenv('MAIL_ENCRYPTION') ?: 'tls';
$mailReplyTo = getenv('MAIL_REPLY_TO') ?: '';
$mailReplyToName = getenv('MAIL_REPLY_TO_NAME') ?: $mailFromName;
$mailAdminNotificationEmail = getenv('MAIL_ADMIN_NOTIFICATION_EMAIL') ?: $mailFrom;
$mailAdminNotificationName = getenv('MAIL_ADMIN_NOTIFICATION_NAME') ?: 'InvitaStudio Admin';
$mailTimeout = (int) (getenv('MAIL_TIMEOUT') ?: 15);
$mailEnabledRaw = getenv('MAIL_ENABLED');
$mailEnabled = $mailEnabledRaw === false
    ? false
    : filter_var($mailEnabledRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($mailEnabled === null) {
    $mailEnabled = false;
}

return [
    'MAIL_DRIVER' => $mailDriver,
    'MAIL_HOST' => $mailHost,
    'MAIL_PORT' => $mailPort,
    'MAIL_USERNAME' => $mailUsername,
    'MAIL_PASSWORD' => $mailPassword,
    'MAIL_FROM' => $mailFrom,
    'MAIL_FROM_NAME' => $mailFromName,
    'MAIL_ENCRYPTION' => $mailEncryption,
    'MAIL_REPLY_TO' => $mailReplyTo,
    'MAIL_REPLY_TO_NAME' => $mailReplyToName,
    'MAIL_ADMIN_NOTIFICATION_EMAIL' => $mailAdminNotificationEmail,
    'MAIL_ADMIN_NOTIFICATION_NAME' => $mailAdminNotificationName,
    'MAIL_TIMEOUT' => $mailTimeout,
    'MAIL_ENABLED' => $mailEnabled,
    'driver' => $mailDriver,
    'host' => $mailHost,
    'port' => $mailPort,
    'username' => $mailUsername,
    'password' => $mailPassword,
    'from_email' => $mailFrom,
    'from_name' => $mailFromName,
    'encryption' => $mailEncryption,
    'reply_to_email' => $mailReplyTo,
    'reply_to_name' => $mailReplyToName,
    'admin_notification_email' => $mailAdminNotificationEmail,
    'admin_notification_name' => $mailAdminNotificationName,
    'timeout' => $mailTimeout,
    'enabled' => $mailEnabled,
];
