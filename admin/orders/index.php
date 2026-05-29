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

$allowedStatuses = [
    'pendiente',
    'pago_confirmado',
    'en_proceso',
    'terminado',
    'entregado',
    'cancelado',
];
$allowedSorts = ['recent', 'event_date', 'status'];
$allowedPerPage = ['10', '20'];

$searchValue = sanitizeString($_GET['search'] ?? '');
$statusValue = sanitizeString($_GET['status'] ?? '');
$sortValue = sanitizeString($_GET['sort'] ?? 'recent');
$perPageValue = sanitizeString($_GET['per_page'] ?? '10');

if (!in_array($statusValue, array_merge([''], $allowedStatuses), true)) {
    $statusValue = '';
}

if (!in_array($sortValue, $allowedSorts, true)) {
    $sortValue = 'recent';
}

if (!in_array($perPageValue, $allowedPerPage, true)) {
    $perPageValue = '10';
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
    <title><?= $appName ?> | Gestion de pedidos</title>
    <meta name="description" content="Panel administrativo para consultar, filtrar y gestionar pedidos de InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260528-1">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/admin_orders.js" defer></script>
</head>
<body class="admin-body" data-page="admin-orders-index">
    <a class="skip-link" href="#admin-content">Saltar a gestion de pedidos</a>

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
                        <p class="admin-breadcrumb">Panel / Pedidos</p>
                        <h1>Gestion operativa de pedidos</h1>
                        <p class="admin-topbar__meta">Consulta, filtra y actualiza el flujo administrativo sin depender de herramientas externas.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
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
                data-admin-orders-page
                data-orders-endpoint="../../api/orders/list.php"
                data-details-page="./details.php"
            >
                <section class="admin-panel admin-panel--hero">
                    <div class="admin-section-heading">
                        <p class="eyebrow">Pedidos</p>
                        <h2>Modulo central de seguimiento</h2>
                        <p class="lead">
                            La vista consolida filtros, paginacion simple, estados visuales y accesos rapidos al
                            detalle de cada pedido para sostener el flujo operativo del panel administrativo.
                        </p>
                    </div>

                    <form class="admin-orders-toolbar" data-orders-filter-form novalidate>
                        <div class="form-field form-field--full admin-orders-toolbar__search">
                            <label for="orders-search">Buscar pedido</label>
                            <input
                                id="orders-search"
                                name="search"
                                type="search"
                                maxlength="150"
                                placeholder="Numero pedido, cliente o correo"
                                value="<?= htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="form-field">
                            <label for="orders-status">Estado pedido</label>
                            <select id="orders-status" name="status">
                                <option value="">Todos</option>
                                <option value="pendiente" <?= $statusValue === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="pago_confirmado" <?= $statusValue === 'pago_confirmado' ? 'selected' : '' ?>>Pago confirmado</option>
                                <option value="en_proceso" <?= $statusValue === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                                <option value="terminado" <?= $statusValue === 'terminado' ? 'selected' : '' ?>>Terminado</option>
                                <option value="entregado" <?= $statusValue === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                <option value="cancelado" <?= $statusValue === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="orders-sort">Ordenar por</label>
                            <select id="orders-sort" name="sort">
                                <option value="recent" <?= $sortValue === 'recent' ? 'selected' : '' ?>>Mas recientes</option>
                                <option value="event_date" <?= $sortValue === 'event_date' ? 'selected' : '' ?>>Fecha evento</option>
                                <option value="status" <?= $sortValue === 'status' ? 'selected' : '' ?>>Estado</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="orders-per-page">Por pagina</label>
                            <select id="orders-per-page" name="per_page">
                                <option value="10" <?= $perPageValue === '10' ? 'selected' : '' ?>>10 pedidos</option>
                                <option value="20" <?= $perPageValue === '20' ? 'selected' : '' ?>>20 pedidos</option>
                            </select>
                        </div>

                        <div class="admin-orders-toolbar__actions">
                            <button type="submit" class="button button-primary">Aplicar filtros</button>
                            <button type="button" class="button button-secondary" data-clear-filters>Limpiar</button>
                        </div>
                    </form>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Listado</p>
                            <h2>Pedidos registrados</h2>
                        </div>
                        <span class="chip" data-orders-summary-chip>Consultando...</span>
                    </div>

                    <div class="admin-feedback" data-orders-feedback hidden></div>

                    <div class="admin-orders-summary">
                        <p data-orders-summary-text>Preparando listado de pedidos.</p>
                        <p class="admin-orders-summary__filters" data-orders-filters-text></p>
                    </div>

                    <div class="admin-table-wrap admin-orders-table-wrap">
                        <table class="admin-table admin-orders-table">
                            <thead>
                                <tr>
                                    <th scope="col">Numero pedido</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Evento</th>
                                    <th scope="col">Fecha evento</th>
                                    <th scope="col">Estado pedido</th>
                                    <th scope="col">Estado pago</th>
                                    <th scope="col">Fecha creacion</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody data-orders-table-body>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-orders-mobile-list" data-orders-mobile-list aria-live="polite">
                        <article class="admin-order-card admin-order-card--loading">
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--pill"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
                        </article>
                    </div>

                    <div class="admin-pagination">
                        <button type="button" class="button button-secondary" data-pagination-prev disabled>Anterior</button>
                        <p data-pagination-label>Pagina 1 de 1</p>
                        <button type="button" class="button button-secondary" data-pagination-next disabled>Siguiente</button>
                    </div>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio panel de pedidos.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
