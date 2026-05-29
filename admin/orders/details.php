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

$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$resolvedOrderId = $orderId !== false && (int) $orderId > 0 ? (int) $orderId : 0;

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
    <title><?= $appName ?> | Detalle de pedido</title>
    <meta name="description" content="Vista detallada del pedido administrativo para InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260528-2">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/admin_orders.js" defer></script>
</head>
<body class="admin-body" data-page="admin-order-details">
    <a class="skip-link" href="#admin-content">Saltar al detalle del pedido</a>

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
                <a class="admin-nav__link is-current" href="./index.php">Pedidos</a>
                <a class="admin-nav__link" href="../services/index.php">Servicios</a>
                <a class="admin-nav__link" href="../index.php#payments-overview">Pagos</a>
                <a class="admin-nav__link" href="../deliveries/index.php">Entregas</a>
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
                        <p class="admin-breadcrumb">Panel / Pedidos / Detalle</p>
                        <h1 data-order-heading>Detalle del pedido</h1>
                        <p class="admin-topbar__meta">Revision de cliente, evento, pago, entrega y cambio de estado desde una sola vista.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
                    <a class="button button-secondary" href="./index.php">Volver al listado</a>
                    <a class="button button-secondary" href="../messages/details.php?order_id=<?= $resolvedOrderId ?>">Ver mensajes</a>

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
                data-admin-order-details-page
                data-order-id="<?= $resolvedOrderId ?>"
                data-details-endpoint="../../api/orders/details.php"
                data-update-endpoint="../../api/orders/update_status.php"
            >
                <section class="admin-panel admin-panel--hero admin-order-hero">
                    <div class="admin-order-hero__copy">
                        <p class="eyebrow">Pedido</p>
                        <h2 data-order-number>Consultando pedido...</h2>
                        <p class="lead" data-order-subtitle>Cargando informacion principal del pedido.</p>
                    </div>

                    <div class="admin-order-hero__meta">
                        <div class="admin-order-badges" data-order-badges>
                            <span class="admin-skeleton admin-skeleton--pill"></span>
                            <span class="admin-skeleton admin-skeleton--pill"></span>
                        </div>

                        <div class="admin-order-meta-list" data-order-meta-list>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                        </div>
                    </div>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Estado</p>
                            <h2>Actualizar flujo del pedido</h2>
                        </div>
                        <span class="chip">Activity log</span>
                    </div>

                    <div class="admin-feedback" data-order-status-feedback hidden></div>

                    <form class="admin-status-form" data-order-status-form>
                        <div class="form-field">
                            <label for="order-status-select">Estado pedido</label>
                            <select id="order-status-select" name="estado_pedido" disabled>
                                <option value="pendiente">Pendiente</option>
                                <option value="pago_confirmado">Pago confirmado</option>
                                <option value="en_proceso">En proceso</option>
                                <option value="terminado">Terminado</option>
                                <option value="entregado">Entregado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="admin-status-form__actions">
                            <button
                                type="submit"
                                class="button button-primary"
                                data-status-submit
                                data-default-label="Guardar estado"
                                data-loading-label="Actualizando..."
                                disabled
                            >Guardar estado</button>
                            <a class="button button-secondary" href="./index.php">Regresar</a>
                        </div>
                    </form>
                </section>

                <div class="admin-feedback" data-order-details-feedback hidden></div>

                <section class="admin-detail-grid">
                    <article class="admin-panel admin-detail-card">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Cliente</p>
                                <h2>Informacion de contacto</h2>
                            </div>
                            <span class="chip">Cliente</span>
                        </div>
                        <div class="admin-key-value-grid" data-detail-customer>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                        </div>
                    </article>

                    <article class="admin-panel admin-detail-card">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Evento</p>
                                <h2>Datos del evento</h2>
                            </div>
                            <span class="chip">Brief</span>
                        </div>
                        <div class="admin-key-value-grid" data-detail-event>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                        </div>
                    </article>

                    <article class="admin-panel admin-detail-card">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Pago</p>
                                <h2>Estado del pago</h2>
                            </div>
                            <span class="chip">Finanzas</span>
                        </div>
                        <div class="admin-key-value-grid" data-detail-payment>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                        </div>
                    </article>

                    <article class="admin-panel admin-detail-card">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Entrega</p>
                                <h2>Formato y archivo final</h2>
                            </div>
                            <span class="chip">Delivery</span>
                        </div>
                        <div class="admin-key-value-grid" data-detail-delivery>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                        </div>
                    </article>
                </section>

                <section class="admin-grid admin-grid--secondary">
                    <article class="admin-panel admin-detail-card">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Notas</p>
                                <h2>Informacion adicional</h2>
                            </div>
                            <span class="chip">Contexto</span>
                        </div>
                        <div class="admin-detail-note" data-detail-notes>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                        </div>
                    </article>

                    <article class="admin-panel">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Actividad</p>
                                <h2>Bitacora del pedido</h2>
                            </div>
                            <span class="chip">Log</span>
                        </div>
                        <ul class="admin-activity-feed" data-order-activity>
                            <li class="admin-activity-feed__item">
                                <span class="admin-skeleton admin-skeleton--line"></span>
                                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                            </li>
                        </ul>
                    </article>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio detalle de pedido.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
