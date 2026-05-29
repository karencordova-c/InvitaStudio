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

$serviceId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if ($serviceId === false || (int) $serviceId <= 0) {
    redirectToLocation('./index.php');
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
    <title><?= $appName ?> | Editar servicio</title>
    <meta name="description" content="Formulario administrativo para editar servicios del catalogo de InvitaStudio.">
    <link rel="stylesheet" href="../../public/assets/css/base.css">
    <link rel="stylesheet" href="../../public/assets/css/components.css">
    <link rel="stylesheet" href="../../public/assets/css/admin.css?v=20260528-2">
    <script src="../../public/assets/js/admin.js" defer></script>
    <script src="../../public/assets/js/admin_services.js" defer></script>
</head>
<body class="admin-body" data-page="admin-services-edit">
    <a class="skip-link" href="#admin-content">Saltar a editar servicio</a>

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
                        <p class="admin-breadcrumb">Panel / Servicios / Editar</p>
                        <h1>Editar servicio</h1>
                        <p class="admin-topbar__meta">Actualiza datos visibles del catalogo o cambia su estado sin eliminar el registro.</p>
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
                data-admin-service-form-page
                data-mode="edit"
                data-service-id="<?= (int) $serviceId ?>"
                data-create-endpoint="../../api/services/create.php"
                data-update-endpoint="../../api/services/update.php"
                data-details-endpoint="../../api/services/details.php"
                data-services-index-url="./index.php"
                data-edit-page-url="./edit.php"
            >
                <section class="admin-panel admin-panel--hero">
                    <div class="admin-section-heading">
                        <p class="eyebrow">Edicion</p>
                        <h2>Ajusta informacion del catalogo</h2>
                        <p class="lead">
                            Cambia descripcion, precio, formato, tiempo o visibilidad segun las necesidades del
                            catalogo publico, manteniendo el historial en activity log.
                        </p>
                    </div>
                </section>

                <div class="admin-service-form-layout">
                    <section class="admin-panel">
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Formulario</p>
                                <h2>Datos del servicio</h2>
                            </div>
                            <a class="button button-secondary admin-button-compact" href="./index.php">Volver al listado</a>
                        </div>

                        <div class="admin-feedback" data-service-form-feedback hidden></div>

                        <form class="admin-service-form" data-admin-service-form novalidate>
                            <input type="hidden" name="id" value="<?= (int) $serviceId ?>">

                            <div class="form-field" data-field-container="nombre">
                                <label for="service-name">Nombre</label>
                                <input id="service-name" name="nombre" type="text" maxlength="150" required>
                                <p class="field-error" data-error-for="nombre" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="categoria">
                                <label for="service-category">Categoria</label>
                                <input id="service-category" name="categoria" type="text" maxlength="100" list="service-category-list" required>
                                <datalist id="service-category-list">
                                    <option value="Boda"></option>
                                    <option value="XV anos"></option>
                                    <option value="Cumpleanos"></option>
                                    <option value="Bautizo"></option>
                                    <option value="Graduacion"></option>
                                    <option value="Evento general"></option>
                                </datalist>
                                <p class="field-error" data-error-for="categoria" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="precio">
                                <label for="service-price">Precio</label>
                                <input id="service-price" name="precio" type="number" min="0" step="0.01" required>
                                <p class="field-error" data-error-for="precio" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="formato_entrega">
                                <label for="service-format">Formato de entrega</label>
                                <select id="service-format" name="formato_entrega" required>
                                    <option value="">Selecciona una opcion</option>
                                    <option value="imagen">Imagen</option>
                                    <option value="pdf">PDF</option>
                                    <option value="video">Video</option>
                                </select>
                                <p class="field-error" data-error-for="formato_entrega" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="tiempo_entrega">
                                <label for="service-time">Tiempo estimado</label>
                                <input id="service-time" name="tiempo_entrega" type="text" maxlength="100" required>
                                <p class="field-error" data-error-for="tiempo_entrega" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="activo">
                                <label for="service-active">Visibilidad</label>
                                <select id="service-active" name="activo" required>
                                    <option value="1">Activo</option>
                                    <option value="0">Oculto</option>
                                </select>
                                <p class="field-error" data-error-for="activo" aria-live="polite"></p>
                            </div>

                            <div class="form-field form-field--full" data-field-container="descripcion">
                                <label for="service-description">Descripcion</label>
                                <textarea id="service-description" name="descripcion" rows="5" maxlength="1000" required></textarea>
                                <p class="field-error" data-error-for="descripcion" aria-live="polite"></p>
                            </div>

                            <div class="form-field form-field--full" data-field-container="imagen_referencia">
                                <label for="service-image">Imagen referencia opcional</label>
                                <input id="service-image" name="imagen_referencia" type="text" maxlength="255">
                                <p class="field-note">Usa una URL absoluta o una ruta visible desde public, por ejemplo <code>assets/img/servicios/demo.jpg</code>.</p>
                                <p class="field-error" data-error-for="imagen_referencia" aria-live="polite"></p>
                            </div>

                            <div class="admin-actions-row">
                                <button class="button button-primary" type="submit" data-service-submit-button data-default-label="Guardar cambios" data-loading-label="Guardando cambios...">
                                    Guardar cambios
                                </button>
                                <a class="button button-outline" href="./index.php">Cancelar</a>
                            </div>
                        </form>
                    </section>

                    <aside class="admin-panel admin-service-preview" data-service-preview>
                        <div class="admin-panel__header">
                            <div>
                                <p class="eyebrow">Vista previa</p>
                                <h2>Resumen del servicio</h2>
                            </div>
                            <span class="chip" data-service-preview-status>Cargando...</span>
                        </div>

                        <div class="admin-service-preview__media">
                            <img data-service-preview-image alt="Vista previa del servicio" hidden>
                            <div class="admin-service-preview__placeholder" data-service-preview-placeholder>IS</div>
                        </div>

                        <div class="admin-service-preview__copy">
                            <h3 data-service-preview-name>Cargando servicio...</h3>
                            <p class="admin-service-preview__price" data-service-preview-price>$0.00 MXN</p>
                            <p data-service-preview-description>La informacion del servicio se cargara desde la API.</p>
                        </div>

                        <div class="admin-key-value-grid">
                            <div class="admin-key-value-item">
                                <span>Categoria</span>
                                <strong data-service-preview-category>Sin definir</strong>
                            </div>
                            <div class="admin-key-value-item">
                                <span>Formato</span>
                                <strong data-service-preview-format>Sin definir</strong>
                            </div>
                            <div class="admin-key-value-item">
                                <span>Tiempo</span>
                                <strong data-service-preview-time>Sin definir</strong>
                            </div>
                            <div class="admin-key-value-item">
                                <span>Imagen</span>
                                <strong data-service-preview-image-text>Placeholder</strong>
                            </div>
                        </div>
                    </aside>
                </div>
            </main>

            <footer class="admin-footer">
                <p>InvitaStudio edicion de servicios.</p>
                <p>&copy; <span data-current-year></span> <?= $appName ?>.</p>
            </footer>
        </div>
    </div>
</body>
</html>
