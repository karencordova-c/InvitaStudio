<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/common.php';

$appConfig = $GLOBALS['appConfig'] ?? require dirname(__DIR__, 2) . '/config/app.php';
$adminUser = requireAdminAuth();

function formatAdminMessagesRoleLabel(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super admin',
        'disenador' => 'Disenador',
        default => 'Operador',
    };
}

$adminName = htmlspecialchars((string) ($adminUser['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars((string) ($adminUser['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars(formatAdminMessagesRoleLabel((string) ($adminUser['rol'] ?? '')), ENT_QUOTES, 'UTF-8');
$appName = htmlspecialchars((string) ($appConfig['app_name'] ?? 'InvitaStudio'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> | Conversaciones</title>
    <meta name="description" content="Panel administrativo de mensajes y aclaraciones por pedido en InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260514-1">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/messages.js" defer></script>
</head>
<body class="admin-body" data-page="admin-messages-index">
    <a class="skip-link" href="#admin-content">Saltar a conversaciones</a>

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
                        <p class="admin-breadcrumb">Panel / Mensajes</p>
                        <h1>Conversaciones por pedido</h1>
                        <p class="admin-topbar__meta">Revision centralizada de aclaraciones entre cliente y administrador.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
                    <a class="button button-secondary" href="../orders/index.php">Ver pedidos</a>

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
                data-admin-messages-index-page
                data-list-endpoint="../../api/messages/list.php"
                data-details-page="./details.php"
            >
                <section class="admin-panel admin-panel--hero">
                    <div class="admin-orders-summary">
                        <p class="eyebrow">Mensajeria</p>
                        <h2>Historial estable de aclaraciones</h2>
                        <p class="lead">Cada conversacion permanece ligada a su pedido y muestra el ultimo intercambio registrado.</p>
                    </div>
                </section>

                <div class="admin-feedback" data-admin-messages-feedback hidden></div>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Listado</p>
                            <h2>Pedidos con mensajes</h2>
                        </div>
                        <button class="button button-secondary admin-button-compact" type="button" data-admin-messages-refresh>
                            Actualizar
                        </button>
                    </div>

                    <div class="admin-table-wrap admin-orders-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Numero pedido</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Ultimo mensaje</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Accion</th>
                                </tr>
                            </thead>
                            <tbody data-admin-messages-table-body>
                                <tr>
                                    <td colspan="7">
                                        <div class="admin-empty-state">
                                            <strong>Cargando conversaciones</strong>
                                            <p>Preparando historial asociado a pedidos.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-orders-mobile-list" data-admin-messages-mobile-list></div>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio conversaciones administrativas.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
