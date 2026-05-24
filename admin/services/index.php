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

$searchValue = sanitizeString($_GET['search'] ?? '');
$activeValue = sanitizeString($_GET['activo'] ?? '');

if (!in_array($activeValue, ['', '1', '0'], true)) {
    $activeValue = '';
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
    <title><?= $appName ?> | Catalogo de servicios</title>
    <meta name="description" content="Panel administrativo para gestionar el catalogo de servicios de InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260514-1">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/admin_services.js" defer></script>
</head>
<body class="admin-body" data-page="admin-services-index">
    <a class="skip-link" href="#admin-content">Saltar a gestion de servicios</a>

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
                <a class="admin-nav__link is-current" href="./index.php">Servicios</a>
                <a class="admin-nav__link" href="../deliveries/index.php">Entregas</a>
                <a class="admin-nav__link" href="../messages/index.php">Mensajes</a>
            </nav>

            <div class="admin-sidebar__footer">
                <a class="button button-secondary" href="../../public/services.html">Ver catalogo publico</a>
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
                        <p class="admin-breadcrumb">Panel / Servicios</p>
                        <h1>Administracion del catalogo</h1>
                        <p class="admin-topbar__meta">Alta, edicion y control de visibilidad del catalogo publico sin eliminar servicios.</p>
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
                data-admin-services-page
                data-services-endpoint="../../api/services/list.php?include_inactive=1"
                data-toggle-endpoint="../../api/services/toggle_active.php"
                data-create-page="./create.php"
                data-edit-page="./edit.php"
            >
                <section class="admin-panel admin-panel--hero">
                    <div class="admin-section-heading">
                        <p class="eyebrow">Servicios</p>
                        <h2>Catalogo editable para el sitio publico</h2>
                        <p class="lead">
                            Registra precios, categorias, tiempos estimados, formatos de entrega y la
                            visibilidad publica de cada servicio desde un solo modulo administrativo.
                        </p>
                    </div>

                    <form class="admin-services-toolbar" data-services-filter-form novalidate>
                        <div class="form-field form-field--full">
                            <label for="services-search">Buscar servicio</label>
                            <input
                                id="services-search"
                                name="search"
                                type="search"
                                maxlength="150"
                                placeholder="Nombre, categoria o descripcion"
                                value="<?= htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="form-field">
                            <label for="services-active">Visibilidad</label>
                            <select id="services-active" name="activo">
                                <option value="">Todos</option>
                                <option value="1" <?= $activeValue === '1' ? 'selected' : '' ?>>Activos</option>
                                <option value="0" <?= $activeValue === '0' ? 'selected' : '' ?>>Ocultos</option>
                            </select>
                        </div>

                        <div class="admin-services-toolbar__actions">
                            <button type="submit" class="button button-primary">Aplicar filtros</button>
                            <button type="button" class="button button-secondary" data-clear-service-filters>Limpiar</button>
                            <a class="button button-outline" href="./create.php">Nuevo servicio</a>
                        </div>
                    </form>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__header">
                        <div>
                            <p class="eyebrow">Listado</p>
                            <h2>Servicios registrados</h2>
                        </div>
                        <span class="chip" data-services-summary-chip>Consultando...</span>
                    </div>

                    <div class="admin-feedback" data-services-feedback hidden></div>

                    <div class="admin-orders-summary">
                        <p data-services-summary-text>Preparando catalogo de servicios.</p>
                        <p class="admin-orders-summary__filters" data-services-filters-text></p>
                    </div>

                    <div class="admin-table-wrap admin-services-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Categoria</th>
                                    <th scope="col">Precio</th>
                                    <th scope="col">Formato</th>
                                    <th scope="col">Activo</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody data-services-table-body>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                                <tr>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--pill"></span></td>
                                    <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-services-mobile-list" data-services-mobile-list aria-live="polite">
                        <article class="admin-service-card admin-service-card--loading">
                            <span class="admin-skeleton admin-skeleton--line"></span>
                            <span class="admin-skeleton admin-skeleton--pill"></span>
                            <span class="admin-skeleton admin-skeleton--line"></span>
                        </article>
                    </div>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio catalogo de servicios.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
