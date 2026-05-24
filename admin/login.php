<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

$appConfig = $GLOBALS['appConfig'] ?? require __DIR__ . '/../config/app.php';

if (isAdminLoggedIn()) {
    redirectToLocation(getAdminDashboardUrl());
}

$feedbackMessage = '';
$feedbackState = '';
$authQuery = sanitizeString($_GET['auth'] ?? '');
$logoutQuery = sanitizeString($_GET['logout'] ?? '');

if ($authQuery === 'required') {
    $feedbackMessage = 'Inicia sesion para acceder al panel administrativo.';
    $feedbackState = 'is-info';
}

if ($logoutQuery === '1') {
    $feedbackMessage = 'La sesion se cerro correctamente.';
    $feedbackState = 'is-success';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appConfig['app_name'], ENT_QUOTES, 'UTF-8') ?> | Login Admin</title>
    <link rel="stylesheet" href="../public/assets/css/base.css">
    <link rel="stylesheet" href="../public/assets/css/layout.css">
    <link rel="stylesheet" href="../public/assets/css/components.css">
    <link rel="stylesheet" href="../public/assets/css/pages.css">
    <link rel="stylesheet" href="../public/assets/css/responsive.css">
    <script src="../public/assets/js/app.js" defer></script>
    <script src="../public/assets/js/admin_login.js" defer></script>
</head>
<body data-page="admin-login">
    <div class="page-shell">
        <header class="site-header" data-site-header>
            <a class="brand" href="../public/index.html"><?= htmlspecialchars($appConfig['app_name'], ENT_QUOTES, 'UTF-8') ?></a>
            <nav id="site-nav" class="site-nav is-open" aria-label="Accesos principales">
                <a href="../public/index.html">Inicio</a>
                <a href="../public/services.html">Servicios</a>
                <a href="../public/request.html">Solicitar</a>
            </nav>
        </header>

        <main>
            <section class="content-section admin-auth-layout">
                <article class="admin-auth-copy">
                    <p class="eyebrow">Admin access</p>
                    <h1>Acceso administrativo protegido.</h1>
                    <p class="lead">
                        Esta area usa autenticacion con sesiones PHP, validacion backend y registro
                        de actividad para accesos administrativos basicos en V1.
                    </p>

                    <div class="mini-showcase-grid">
                        <article class="mini-showcase">
                            <span class="chip">Sesion</span>
                            <h3>Sesion PHP con regeneracion segura</h3>
                            <p>El login crea una sesion nueva y el logout destruye el contexto activo.</p>
                        </article>
                        <article class="mini-showcase">
                            <span class="chip">Seguridad</span>
                            <h3>Passwords con hash</h3>
                            <p>Las credenciales se validan con <code>password_verify()</code> sobre <code>password_hash()</code>.</p>
                        </article>
                    </div>
                </article>

                <article class="card-surface form-panel admin-auth-panel">
                    <div class="form-panel__header">
                        <p class="eyebrow">Login</p>
                        <h2>Ingresar al panel</h2>
                        <p>Usa el correo y la contrasena del administrador configurado en base de datos.</p>
                    </div>

                    <form
                        action="../api/auth/login.php"
                        method="post"
                        class="form-grid"
                        data-admin-login-form
                        data-api-endpoint="../api/auth/login.php"
                        data-dashboard-url="./index.php"
                        novalidate
                    >
                        <div class="form-field form-field--full" data-field-container="correo">
                            <label for="correo">Correo</label>
                            <input id="correo" name="correo" type="email" inputmode="email" autocomplete="username" maxlength="150" required>
                            <p class="field-error" data-error-for="correo"></p>
                        </div>

                        <div class="form-field form-field--full" data-field-container="password">
                            <label for="password">Contrasena</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" maxlength="255" required>
                            <p class="field-error" data-error-for="password"></p>
                        </div>

                        <div
                            class="form-feedback form-field--full <?= htmlspecialchars($feedbackState, ENT_QUOTES, 'UTF-8') ?>"
                            data-form-feedback
                        ><?= htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8') ?></div>

                        <div class="form-actions form-field--full">
                            <button
                                type="submit"
                                class="button button-primary"
                                data-submit-button
                                data-default-label="Iniciar sesion"
                                data-loading-label="Validando..."
                            >Iniciar sesion</button>
                            <a class="button button-secondary" href="../public/index.html">Volver al sitio</a>
                        </div>
                    </form>
                </article>
            </section>
        </main>

        <footer class="site-footer">
            <p>Acceso restringido al equipo administrativo de InvitaStudio.</p>
            <p>&copy; <span data-current-year></span> <?= htmlspecialchars($appConfig['app_name'], ENT_QUOTES, 'UTF-8') ?>.</p>
        </footer>
    </div>
</body>
</html>
