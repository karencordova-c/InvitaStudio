<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/common.php';

$appConfig = $GLOBALS['appConfig'] ?? require dirname(__DIR__, 2) . '/config/app.php';
$adminUser = requireAdminAuth();

function formatAdminMessagesDetailRoleLabel(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super admin',
        'disenador' => 'Disenador',
        default => 'Operador',
    };
}

$orderId = filter_var($_GET['order_id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
$resolvedOrderId = $orderId !== false && (int) $orderId > 0 ? (int) $orderId : 0;

$adminName = htmlspecialchars((string) ($adminUser['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars((string) ($adminUser['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars(formatAdminMessagesDetailRoleLabel((string) ($adminUser['rol'] ?? '')), ENT_QUOTES, 'UTF-8');
$appName = htmlspecialchars((string) ($appConfig['app_name'] ?? 'InvitaStudio'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> | Detalle de conversacion</title>
    <meta name="description" content="Detalle administrativo de conversacion por pedido en InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260514-1">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/messages.js" defer></script>
</head>
<body class="admin-body" data-page="admin-messages-details">
    <a class="skip-link" href="#admin-content">Saltar al detalle de la conversacion</a>

    <div class="admin-shell" data-admin-shell>
        <button class="admin-overlay" type="button" data-admin-overlay aria-label="Cerrar menu lateral" hidden></button>

        <aside id="admin-sidebar" class="admin-sidebar" data-admin-sidebar aria-label="Navegacion administrativa">
            <div class="admin-sidebar__header">
                <a class="brand-mark" href="../../public/index.html" aria-label="<?= $appName ?> inicio">
                    <span class="brand-mark__symbol brand-mark__symbol--image" aria-hidden="true">
                        <img class="brand-mark__image" src="../../public/assets/img/invita_stuidio_isotipo.png" alt="">
                    </span>
                    <span class="brand-mark__text">
                        <strong><?= $appName ?></strong>
                        <small>Panel administrativo</small>
                    </span>
                </a>

                <button class="admin-sidebar__close" type="button" data-admin-sidebar-close aria-label="Cerrar menu">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="admin-sidebar__summary" hidden>
                <span class="chip">Sesion activa</span>
                <strong><?= $adminName ?></strong>
                <p><?= $adminRole ?></p>
                <small><?= $adminEmail ?></small>
            </div>

            <nav class="admin-nav" aria-label="Modulos">
                <a class="admin-nav__link" href="../index.php">Dashboard</a>
                <a class="admin-nav__link" href="../orders/index.php">Pedidos</a>
                <a class="admin-nav__link" href="../services/index.php">Servicios</a>
                <a class="admin-nav__link" href="../index.php#payments-overview">Pagos</a>
                <a class="admin-nav__link" href="../deliveries/index.php">Entregas</a>
                <a class="admin-nav__link is-current" href="./index.php">Mensajes</a>
            </nav>

            <div class="admin-sidebar__footer">
                <a class="button button-secondary" href="../../public/index.html">Ver sitio publico</a>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar__left">
                    <button
                        class="admin-mobile-toggle"
                        type="button"
                        data-admin-sidebar-toggle
                        aria-expanded="false"
                        aria-controls="admin-sidebar"
                        aria-label="Abrir menu lateral"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div class="admin-topbar__copy">
                        <p class="admin-breadcrumb">Panel / Mensajes / Detalle</p>
                        <h1 data-admin-message-heading>Conversacion del pedido</h1>
                        <p class="admin-topbar__meta">Historial cronologico con respuesta y adjuntos controlados.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
                    <a class="button button-secondary" href="./index.php">Volver al listado</a>

                    <div class="admin-user-chip">
                        <span class="admin-user-chip__label">Cuenta</span>
                        <strong><?= $adminName ?></strong>
                        <small><?= $adminEmail ?></small>
                    </div>

                    <form class="admin-topbar__logout-form" action="../../api/auth/logout.php" method="post">
                        <button type="submit" class="button button-primary">Cerrar sesion</button>
                    </form>
                </div>
            </header>

            <main
                id="admin-content"
                class="admin-content"
                data-admin-message-details-page
                data-order-id="<?= $resolvedOrderId ?>"
                data-list-endpoint="../../api/messages/list.php"
                data-create-endpoint="../../api/messages/create.php"
            >
                <section class="admin-panel admin-panel--hero admin-message-hero">
                    <div class="admin-order-hero__copy">
                        <p class="eyebrow">Conversacion</p>
                        <h2 data-admin-message-order-number>Consultando pedido...</h2>
                        <p class="lead" data-admin-message-subtitle>Cargando informacion de la conversacion.</p>
                    </div>

                    <div class="admin-order-hero__meta">
                        <div class="admin-order-badges" data-admin-message-badges>
                            <span class="admin-skeleton admin-skeleton--pill"></span>
                            <span class="admin-skeleton admin-skeleton--pill"></span>
                        </div>

                        <div class="admin-order-meta-list" data-admin-message-meta-list>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                        </div>
                    </div>
                </section>

                <div class="admin-feedback" data-admin-message-details-feedback hidden></div>

                <section class="admin-messages-layout">
                    <article class="admin-panel admin-messages-thread-panel">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Historial</p>
                                <h2>Mensajes asociados al pedido</h2>
                            </div>
                            <button class="button button-secondary admin-button-compact" type="button" data-admin-message-refresh>
                                Actualizar
                            </button>
                        </div>

                        <div class="conversation-thread conversation-thread--admin" data-admin-message-thread>
                            <div class="conversation-empty">
                                <strong>Cargando mensajes</strong>
                                <p>Preparando historial cronologico.</p>
                            </div>
                        </div>
                    </article>

                    <aside class="admin-panel admin-messages-form">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Responder</p>
                                <h2>Nueva aclaracion</h2>
                            </div>
                            <a class="button button-secondary admin-button-compact" href="../orders/details.php?id=<?= $resolvedOrderId ?>">Ver pedido</a>
                        </div>

                        <form data-admin-message-form enctype="multipart/form-data" novalidate>
                            <div class="form-field">
                                <label for="admin-message-text">Mensaje</label>
                                <textarea
                                    id="admin-message-text"
                                    name="mensaje"
                                    rows="6"
                                    maxlength="2000"
                                    placeholder="Escribe una aclaracion para el cliente."
                                    required
                                ></textarea>
                                <p class="field-note">Minimo 5 caracteres. El historial no se elimina.</p>
                                <p class="field-error" data-admin-message-error aria-live="polite"></p>
                            </div>

                            <div class="form-field">
                                <label for="admin-message-file">Adjunto opcional</label>
                                <input id="admin-message-file" name="archivo_adjunto" type="file" accept=".jpg,.jpeg,.png,.pdf">
                                <p class="field-note">Formatos permitidos: JPG, PNG, PDF. Upload con validacion de MIME y renombrado seguro.</p>
                            </div>

                            <div class="form-actions">
                                <button
                                    class="button button-primary"
                                    type="submit"
                                    data-admin-message-submit
                                    data-default-label="Enviar mensaje"
                                    data-loading-label="Enviando..."
                                >
                                    Enviar mensaje
                                </button>
                            </div>

                            <p class="form-feedback" data-admin-message-feedback aria-live="polite"></p>
                        </form>
                    </aside>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio detalle de conversacion.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
