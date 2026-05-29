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

$orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT);
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
    <title><?= $appName ?> | Registrar entrega</title>
    <meta name="description" content="Carga segura de archivos finales para pedidos de InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260528-1">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/delivery_upload.js" defer></script>
</head>
<body class="admin-body" data-page="admin-delivery-upload">
    <a class="skip-link" href="#admin-content">Saltar al formulario de entrega</a>

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
                        <p class="admin-breadcrumb">Panel / Entregas / Upload</p>
                        <h1>Registrar archivo final</h1>
                        <p class="admin-topbar__meta">Sube archivos finales validados por MIME, extensión y tamaño antes de liberar la descarga al cliente.</p>
                    </div>
                </div>

                <div class="admin-topbar__right">
                    <a class="button button-secondary" href="./index.php">Volver a entregas</a>

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
                data-admin-delivery-upload-page
                data-orders-endpoint="../../api/orders/list.php"
                data-order-details-endpoint="../../api/orders/details.php"
                data-delivery-details-endpoint="../../api/deliveries/details.php"
                data-upload-endpoint="../../api/deliveries/upload.php"
                data-initial-order-id="<?= $resolvedOrderId ?>"
            >
                <section class="admin-panel admin-panel--hero">
                    <div class="admin-section-heading">
                        <p class="eyebrow">Upload seguro</p>
                        <h2>Entrega final controlada</h2>
                        <p class="lead">
                            El archivo final queda disponible para descarga pública solo después del registro,
                            la validación del upload y la actualización automática del pedido a estado entregado.
                        </p>
                    </div>

                    <div class="admin-actions-row">
                        <a class="button button-secondary" href="../orders/index.php">Buscar en pedidos</a>
                        <a class="button button-secondary" href="./index.php">Ver historial de entregas</a>
                    </div>
                </section>

                <section class="admin-upload-layout">
                    <article class="admin-panel admin-upload-card">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Pedido</p>
                                <h2>Seleccion y validacion previa</h2>
                            </div>
                            <span class="chip">Paso 1</span>
                        </div>

                        <div class="admin-feedback" data-order-search-feedback hidden></div>

                        <form class="admin-select-inline" data-order-search-form novalidate>
                            <div class="form-field form-field--full">
                                <label for="delivery-order-search">Buscar pedido</label>
                                <input
                                    id="delivery-order-search"
                                    name="search"
                                    type="search"
                                    maxlength="150"
                                    placeholder="Numero pedido, cliente o correo"
                                >
                            </div>

                            <button type="submit" class="button button-secondary">Buscar</button>
                        </form>

                        <form class="admin-upload-form" data-delivery-upload-form novalidate>
                            <div class="form-field">
                                <label for="delivery-order-select">Pedido</label>
                                <select id="delivery-order-select" name="order_id" data-order-select required>
                                    <option value="">Selecciona un pedido</option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="delivery-format">Tipo entrega</label>
                                <select id="delivery-format" name="formato_entrega" data-delivery-format required>
                                    <option value="imagen">Imagen</option>
                                    <option value="pdf">PDF</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="delivery-file">Archivo final</label>
                                <input
                                    id="delivery-file"
                                    name="archivo_final"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.pdf,.mp4"
                                    data-delivery-file-input
                                    required
                                >
                                <p class="admin-upload-hint">Permitidos: JPG, JPEG, PNG, PDF y MP4. Maximo 50MB.</p>
                                <p class="admin-upload-file" data-selected-file-name>Ningun archivo seleccionado.</p>
                            </div>

                            <div class="form-field form-field--full">
                                <label for="delivery-notes">Notas entrega</label>
                                <textarea
                                    id="delivery-notes"
                                    name="notas_entrega"
                                    rows="5"
                                    maxlength="2000"
                                    placeholder="Observaciones para la entrega final"
                                ></textarea>
                            </div>

                            <div class="admin-feedback" data-delivery-form-feedback hidden></div>

                            <div class="admin-upload-progress-wrap" data-upload-progress-wrap hidden>
                                <div class="admin-upload-progress">
                                    <span class="admin-upload-progress__bar" data-upload-progress-bar></span>
                                </div>
                                <p class="admin-upload-progress__label" data-upload-progress-label>0%</p>
                            </div>

                            <div class="admin-actions-row">
                                <button
                                    type="submit"
                                    class="button button-primary"
                                    data-delivery-submit
                                    data-default-label="Registrar entrega"
                                    data-loading-label="Subiendo archivo..."
                                >Registrar entrega</button>
                                <button type="button" class="button button-secondary" data-refresh-orders>Actualizar pedidos</button>
                            </div>
                        </form>
                    </article>

                    <div class="admin-detail-grid">
                        <article class="admin-panel admin-detail-card">
                            <div class="admin-panel__header">
                                <div>
                                    <p class="eyebrow">Resumen</p>
                                    <h2>Pedido seleccionado</h2>
                                </div>
                                <span class="chip">Paso 2</span>
                            </div>

                            <div class="admin-feedback" data-order-summary-feedback hidden></div>

                            <div class="admin-key-value-grid" data-order-summary>
                                <p class="admin-empty-state__copy">Selecciona un pedido para revisar su informacion.</p>
                            </div>
                        </article>

                        <article class="admin-panel admin-detail-card">
                            <div class="admin-panel__header">
                                <div>
                                    <p class="eyebrow">Entrega actual</p>
                                    <h2>Ultimo archivo registrado</h2>
                                </div>
                                <span class="chip">Paso 3</span>
                            </div>

                            <div class="admin-feedback" data-current-delivery-feedback hidden></div>

                            <div class="admin-key-value-grid" data-current-delivery>
                                <p class="admin-empty-state__copy">Aun no existe una entrega registrada para este pedido.</p>
                            </div>
                        </article>
                    </div>
                </section>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio upload de entregas.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
