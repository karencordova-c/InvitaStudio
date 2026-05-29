<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/common.php';

$appConfig = $GLOBALS['appConfig'] ?? require __DIR__ . '/../config/app.php';
$adminUser = requireAdminAuth();

function formatAdminRoleLabel(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super admin',
        'disenador' => 'Disenador',
        default => 'Operador',
    };
}

$adminName = htmlspecialchars($adminUser['nombre'], ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($adminUser['correo'], ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars(formatAdminRoleLabel((string) $adminUser['rol']), ENT_QUOTES, 'UTF-8');
$appName = htmlspecialchars($appConfig['app_name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> | Dashboard Admin</title>
    <meta name="description" content="Dashboard administrativo principal de InvitaStudio para seguimiento de pedidos, pagos y entregas.">
    <link rel="stylesheet" href="../public/assets/css/base.css">
    <link rel="stylesheet" href="../public/assets/css/components.css">
    <link rel="stylesheet" href="../public/assets/css/admin.css?v=20260528-2">
    <script src="../public/assets/js/admin.js" defer></script>
</head>
<body class="admin-body" data-page="admin-dashboard">
    <a class="skip-link" href="#admin-content">Saltar al dashboard</a>

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
                <a class="brand-mark" href="../public/index.html" aria-label="<?= $appName ?> inicio">
                    <span class="brand-mark__symbol brand-mark__symbol--image" aria-hidden="true">
                        <img class="brand-mark__image" src="../public/assets/img/invita_stuidio_isotipo.png" alt="">
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
                <a class="admin-nav__link is-current" href="./index.php#dashboard-overview">Dashboard</a>
                <a class="admin-nav__link" href="./orders/index.php">Pedidos</a>
                <a class="admin-nav__link" href="./services/index.php">Servicios</a>
                <a class="admin-nav__link" href="#payments-overview">Pagos</a>
                <a class="admin-nav__link" href="./deliveries/index.php">Entregas</a>
                <a class="admin-nav__link" href="./messages/index.php">Mensajes</a>
                <a class="admin-nav__link" href="#settings-overview">Configuracion</a>
            </nav>

            <div class="admin-sidebar__footer">
                <a class="button button-secondary" href="../public/index.html">Ver sitio publico</a>
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
                        <p class="admin-breadcrumb">Panel / Dashboard</p>
                        <h1>Centro operativo de InvitaStudio</h1>
                        <p class="admin-topbar__meta">Resumen general de pedidos, pagos, entregas y actividad reciente.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
                    <div class="admin-user-chip">
                        <span class="admin-user-chip__label">Cuenta</span>
                        <strong><?= $adminName ?></strong>
                        <small><?= $adminEmail ?></small>
                    </div>

                    <form class="admin-topbar__logout-form" action="../api/auth/logout.php" method="post">
                        <button type="submit" class="button button-primary">Cerrar sesion</button>
                    </form>
                </div>
            </header>

            <main
                id="admin-content"
                class="admin-content"
                data-admin-dashboard
                data-stats-endpoint="../api/admin/dashboard_stats.php"
                data-activity-endpoint="../api/admin/recent_activity.php"
            >
                <section id="dashboard-overview" class="admin-panel admin-panel--hero">
                    <div class="admin-section-heading">
                        <p class="eyebrow">Dashboard</p>
                        <h2>Vista general del flujo administrativo</h2>
                        <p class="lead">
                            El panel concentra indicadores basicos, actividad operativa y el seguimiento
                            reciente de pedidos para mantener una lectura clara del estado del sistema.
                        </p>
                    </div>

                    <div class="admin-feedback" data-stats-feedback hidden></div>

                    <div class="admin-kpi-grid">
                        <article class="admin-kpi-card">
                            <span class="chip">Pedidos</span>
                            <strong data-kpi-value="total_orders"><span class="admin-skeleton admin-skeleton--number"></span></strong>
                            <h3>Total pedidos</h3>
                            <p>Pedidos registrados en la base actual.</p>
                        </article>

                        <article class="admin-kpi-card">
                            <span class="chip">Pendientes</span>
                            <strong data-kpi-value="pending_orders"><span class="admin-skeleton admin-skeleton--number"></span></strong>
                            <h3>Pedidos pendientes</h3>
                            <p>Solicitudes aun sin validacion completa.</p>
                        </article>

                        <article class="admin-kpi-card">
                            <span class="chip">En proceso</span>
                            <strong data-kpi-value="processing_orders"><span class="admin-skeleton admin-skeleton--number"></span></strong>
                            <h3>Pedidos en proceso</h3>
                            <p>Pedidos actualmente en ejecucion de diseno.</p>
                        </article>

                        <article class="admin-kpi-card">
                            <span class="chip">Entregados</span>
                            <strong data-kpi-value="completed_orders"><span class="admin-skeleton admin-skeleton--number"></span></strong>
                            <h3>Pedidos entregados</h3>
                            <p>Entregas finales registradas para el cliente.</p>
                        </article>

                        <article class="admin-kpi-card">
                            <span class="chip">Pagos</span>
                            <strong data-kpi-value="pending_payments"><span class="admin-skeleton admin-skeleton--number"></span></strong>
                            <h3>Pagos pendientes</h3>
                            <p>Pagos pendientes de revision administrativa.</p>
                        </article>
                    </div>
                </section>

                <section class="admin-grid admin-grid--feature-cards">
                    <article class="admin-panel" id="activity-overview">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Actividad reciente</p>
                                <h2>Ultimos movimientos del sistema</h2>
                            </div>
                            <span class="chip">Log</span>
                        </div>

                        <div class="admin-feedback" data-activity-feedback hidden></div>

                        <ul class="admin-activity-feed" data-activity-feed>
                            <li class="admin-activity-feed__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                            <li class="admin-activity-feed__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                            <li class="admin-activity-feed__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                        </ul>
                    </article>

                    <article class="admin-panel" id="payments-overview">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Pagos</p>
                                <h2>Ultimos pagos</h2>
                            </div>
                            <span class="chip">Revision</span>
                        </div>

                        <ul class="admin-list" data-payments-list>
                            <li class="admin-list__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                            <li class="admin-list__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                        </ul>
                    </article>

                    <article class="admin-panel" id="deliveries-overview">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Entregas</p>
                                <h2>Ultimas entregas</h2>
                            </div>
                            <a class="button button-secondary admin-button-compact" href="./deliveries/index.php">Gestionar entregas</a>
                        </div>

                        <ul class="admin-list" data-deliveries-list>
                            <li class="admin-list__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                            <li class="admin-list__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                        </ul>
                    </article>
                </section>

                <section id="orders-overview" class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Pedidos recientes</p>
                            <h2>Ultimos pedidos registrados</h2>
                        </div>
                        <a class="button button-secondary admin-button-compact" href="./orders/index.php">Gestionar pedidos</a>
                    </div>

                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Numero pedido</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Fecha</th>
                                </tr>
                            </thead>
                            <tbody data-orders-table-body>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-grid admin-grid--secondary">
                    <article class="admin-panel admin-placeholder-card" id="messages-overview">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Mensajes</p>
                                <h2>Modulo en preparacion</h2>
                            </div>
                            <span class="chip">Proximo</span>
                        </div>
                        <p>
                            Esta zona queda lista para listar aclaraciones, respuestas al cliente y futuras
                            notificaciones operativas sin cambiar la base del layout administrativo.
                        </p>
                    </article>

                    <article class="admin-panel admin-placeholder-card" id="settings-overview">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Configuracion</p>
                                <h2>Base reusable del panel</h2>
                            </div>
                            <span class="chip">V1</span>
                        </div>
                        <p>
                            El dashboard ya define sidebar, topbar, tarjetas, tablas y dropdowns para extender
                            el panel admin en modulos futuros sin introducir frameworks adicionales.
                        </p>
                    </article>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio admin dashboard.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
