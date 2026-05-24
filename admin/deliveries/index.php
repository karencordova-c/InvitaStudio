<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/api/common.php';

$appConfig = $GLOBALS['appConfig'] ?? require dirname(__DIR__, 2) . '/config/app.php';
$adminUser = requireAdminAuth();

function formatAdminRoleLabel(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super admin',
        'disenador' => 'Disenador',
        default => 'Operador',
    };
}

$adminName = htmlspecialchars((string) ($adminUser['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars((string) ($adminUser['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars(formatAdminRoleLabel((string) ($adminUser['rol'] ?? '')), ENT_QUOTES, 'UTF-8');
$appName = htmlspecialchars((string) ($appConfig['app_name'] ?? 'InvitaStudio'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> | Entregas</title>
    <meta name="description" content="Panel administrativo para registrar y revisar entregas finales en InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260514-1">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/delivery_upload.js" defer></script>
</head>
<body class="admin-body" data-page="admin-deliveries-index">
    <a class="skip-link" href="#admin-content">Saltar a entregas</a>

    <div class="admin-shell" data-admin-shell>
        <button
            class="admin-overlay"
            type="button"
            data-admin-overlay
            aria-label="Cerrar menu lateral"
            hidden
        ></button>

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

                <button
                    class="admin-sidebar__close"
                    type="button"
                    data-admin-sidebar-close
                    aria-label="Cerrar menu"
                >
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
                <a class="admin-nav__link is-current" href="./index.php">Entregas</a>
                <a class="admin-nav__link" href="../messages/index.php">Mensajes</a>
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
                        <p class="admin-breadcrumb">Panel / Entregas</p>
                        <h1>Entrega de archivos finales</h1>
                        <p class="admin-topbar__meta">Gestiona uploads finales, revisa pedidos listos y mantén visible el historial reciente de entregas.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
                    <a class="button button-primary" href="./upload.php">Registrar entrega</a>

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
                data-admin-deliveries-page
                data-orders-endpoint="../../api/orders/list.php"
                data-activity-endpoint="../../api/admin/recent_activity.php"
                data-upload-page="./upload.php"
            >
                <section class="admin-panel admin-panel--hero">
                    <div class="admin-section-heading">
                        <p class="eyebrow">Entregas</p>
                        <h2>Flujo final de publicación</h2>
                        <p class="lead">
                            Este módulo centraliza los pedidos terminados listos para carga final y las últimas
                            entregas registradas. El upload definitivo vive en una vista separada para mantener el flujo claro.
                        </p>
                    </div>

                    <div class="admin-actions-row">
                        <a class="button button-primary" href="./upload.php">Subir archivo final</a>
                        <a class="button button-secondary" href="../orders/index.php">Volver a pedidos</a>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Pedidos listos</p>
                            <h2>Pedidos con estado terminado</h2>
                        </div>
                        <span class="chip">Preparados para entrega</span>
                    </div>

                    <div class="admin-feedback" data-ready-orders-feedback hidden></div>

                    <div class="admin-table-wrap admin-orders-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Numero pedido</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Evento</th>
                                    <th scope="col">Fecha evento</th>
                                    <th scope="col">Accion</th>
                                </tr>
                            </thead>
                            <tbody data-ready-orders-body>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-orders-mobile-list" data-ready-orders-mobile-list aria-live="polite">
                        <article class="admin-order-card admin-order-card--loading">
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                        </article>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Historial</p>
                            <h2>Ultimas entregas registradas</h2>
                        </div>
                        <span class="chip">Ultima actividad</span>
                    </div>

                    <div class="admin-feedback" data-recent-deliveries-feedback hidden></div>

                    <ul class="admin-list" data-recent-deliveries-list>
                        <li class="admin-list__item">
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                        </li>
                    </ul>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio modulo de entregas.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
