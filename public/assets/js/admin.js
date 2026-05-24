document.addEventListener("DOMContentLoaded", () => {
    initAdminSidebar();
    initAdminDropdowns();
    initAdminCurrentYear();
    initAdminDashboard();
});

function initAdminSidebar() {
    const body = document.body;
    const sidebar = document.querySelector("[data-admin-sidebar]");
    const overlay = document.querySelector("[data-admin-overlay]");
    const openButton = document.querySelector("[data-admin-sidebar-toggle]");
    const closeButton = document.querySelector("[data-admin-sidebar-close]");

    if (!(body instanceof HTMLBodyElement) || !(sidebar instanceof HTMLElement)) {
        return;
    }

    const closeSidebar = () => {
        body.classList.remove("is-admin-sidebar-open");

        if (overlay instanceof HTMLButtonElement) {
            overlay.hidden = true;
        }

        if (openButton instanceof HTMLButtonElement) {
            openButton.setAttribute("aria-expanded", "false");
            openButton.setAttribute("aria-label", "Abrir menu lateral");
        }
    };

    const openSidebar = () => {
        body.classList.add("is-admin-sidebar-open");

        if (overlay instanceof HTMLButtonElement) {
            overlay.hidden = false;
        }

        if (openButton instanceof HTMLButtonElement) {
            openButton.setAttribute("aria-expanded", "true");
            openButton.setAttribute("aria-label", "Cerrar menu lateral");
        }
    };

    if (openButton instanceof HTMLButtonElement) {
        openButton.addEventListener("click", () => {
            if (body.classList.contains("is-admin-sidebar-open")) {
                closeSidebar();
                return;
            }

            openSidebar();
        });
    }

    if (closeButton instanceof HTMLButtonElement) {
        closeButton.addEventListener("click", closeSidebar);
    }

    if (overlay instanceof HTMLButtonElement) {
        overlay.addEventListener("click", closeSidebar);
    }

    const navLinks = sidebar.querySelectorAll(".admin-nav__link");

    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.forEach((navLink) => {
                navLink.classList.remove("is-current");
            });

            link.classList.add("is-current");

            if (window.innerWidth < 900) {
                closeSidebar();
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeSidebar();
            closeAllAdminDropdowns();
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth >= 900) {
            closeSidebar();
        }
    });
}

function initAdminDropdowns() {
    document.querySelectorAll("[data-admin-dropdown]").forEach((dropdown) => {
        const toggle = dropdown.querySelector("[data-admin-dropdown-toggle]");
        const menu = dropdown.querySelector("[data-admin-dropdown-menu]");

        if (!(toggle instanceof HTMLButtonElement) || !(menu instanceof HTMLElement)) {
            return;
        }

        toggle.addEventListener("click", (event) => {
            event.stopPropagation();
            const isExpanded = toggle.getAttribute("aria-expanded") === "true";

            closeAllAdminDropdowns();

            if (!isExpanded) {
                toggle.setAttribute("aria-expanded", "true");
                menu.hidden = false;
            }
        });
    });

    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Node)) {
            return;
        }

        document.querySelectorAll("[data-admin-dropdown]").forEach((dropdown) => {
            if (dropdown.contains(event.target)) {
                return;
            }

            const toggle = dropdown.querySelector("[data-admin-dropdown-toggle]");
            const menu = dropdown.querySelector("[data-admin-dropdown-menu]");

            if (toggle instanceof HTMLButtonElement) {
                toggle.setAttribute("aria-expanded", "false");
            }

            if (menu instanceof HTMLElement) {
                menu.hidden = true;
            }
        });
    });
}

function closeAllAdminDropdowns() {
    document.querySelectorAll("[data-admin-dropdown]").forEach((dropdown) => {
        const toggle = dropdown.querySelector("[data-admin-dropdown-toggle]");
        const menu = dropdown.querySelector("[data-admin-dropdown-menu]");

        if (toggle instanceof HTMLButtonElement) {
            toggle.setAttribute("aria-expanded", "false");
        }

        if (menu instanceof HTMLElement) {
            menu.hidden = true;
        }
    });
}

function initAdminCurrentYear() {
    const currentYear = String(new Date().getFullYear());

    document.querySelectorAll("[data-current-year]").forEach((element) => {
        element.textContent = currentYear;
    });
}

function initAdminDashboard() {
    const dashboard = document.querySelector("[data-admin-dashboard]");

    if (!(dashboard instanceof HTMLElement)) {
        return;
    }

    const statsEndpoint = dashboard.dataset.statsEndpoint || "";
    const activityEndpoint = dashboard.dataset.activityEndpoint || "";

    if (statsEndpoint !== "") {
        loadDashboardStats(statsEndpoint);
    }

    if (activityEndpoint !== "") {
        loadRecentActivity(activityEndpoint);
    }
}

async function loadDashboardStats(endpoint) {
    const feedback = document.querySelector("[data-stats-feedback]");

    try {
        const responseData = await fetchAdminJson(endpoint);
        renderDashboardStats(responseData.data || {});
        setAdminFeedback(feedback, "", "info", true);
    } catch (error) {
        renderDashboardStats({});
        setAdminFeedback(
            feedback,
            error.message || "No fue posible cargar las metricas del dashboard.",
            "error",
            false
        );
    }
}

async function loadRecentActivity(endpoint) {
    const feedback = document.querySelector("[data-activity-feedback]");

    try {
        const responseData = await fetchAdminJson(endpoint);
        const data = responseData.data || {};

        renderActivityFeed(data.activity_feed || []);
        renderPaymentsList(data.recent_payments || []);
        renderDeliveriesList(data.recent_deliveries || []);
        renderOrdersTable(data.recent_orders || []);

        setAdminFeedback(feedback, "", "info", true);
    } catch (error) {
        renderActivityFeed([]);
        renderPaymentsList([]);
        renderDeliveriesList([]);
        renderOrdersTable([]);

        setAdminFeedback(
            feedback,
            error.message || "No fue posible cargar la actividad reciente.",
            "error",
            false
        );
    }
}

async function fetchAdminJson(endpoint) {
    const response = await fetch(endpoint, {
        method: "GET",
        headers: {
            Accept: "application/json",
        },
        credentials: "same-origin",
    });

    let responseData = null;

    try {
        responseData = await response.json();
    } catch (error) {
        throw new Error("La API devolvio una respuesta invalida.");
    }

    if (!response.ok || responseData.success !== true) {
        throw new Error(responseData.message || "No fue posible obtener la informacion.");
    }

    return responseData;
}

function renderDashboardStats(stats) {
    const statKeys = [
        "total_orders",
        "pending_orders",
        "processing_orders",
        "completed_orders",
        "pending_payments",
    ];

    statKeys.forEach((key) => {
        const valueElement = document.querySelector(`[data-kpi-value="${key}"]`);

        if (!(valueElement instanceof HTMLElement)) {
            return;
        }

        if (!Object.prototype.hasOwnProperty.call(stats, key)) {
            valueElement.textContent = "--";
            return;
        }

        const numericValue = Number(stats[key] || 0);
        valueElement.textContent = formatAdminNumber(numericValue);
    });
}

function renderActivityFeed(items) {
    const container = document.querySelector("[data-activity-feed]");

    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = createEmptyState(
            "No hay actividad reciente",
            "Cuando existan movimientos administrativos apareceran aqui."
        );
        return;
    }

    container.innerHTML = items.map((item) => {
        const moduleLabel = getActivityModuleLabel(item.modulo);
        const actionLabel = item.accion ? escapeHtml(String(item.accion)) : "Registro";
        const description = item.descripcion ? escapeHtml(String(item.descripcion)) : "Movimiento registrado.";

        return `
            <li class="admin-activity-feed__item">
                <div class="admin-list__item-header">
                    <strong class="admin-activity-feed__title">${moduleLabel}</strong>
                    <span class="admin-status admin-status--neutral">${actionLabel}</span>
                </div>
                <p>${description}</p>
                <span class="admin-activity-feed__meta">${formatAdminDate(item.created_at)}</span>
            </li>
        `;
    }).join("");
}

function renderPaymentsList(items) {
    const container = document.querySelector("[data-payments-list]");

    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = createEmptyState(
            "No existen pagos todavia",
            "Los pagos recientes apareceran en esta seccion."
        );
        return;
    }

    container.innerHTML = items.map((item) => {
        const amount = formatAdminCurrency(item.monto_pago);
        const status = createStatusBadge(item.estado_pago, "payment");
        const reference = item.referencia_pago ? escapeHtml(String(item.referencia_pago)) : "Sin referencia";

        return `
            <li class="admin-list__item">
                <div class="admin-list__item-header">
                    <strong class="admin-list__title">${escapeHtml(item.numero_pedido || "Pedido sin numero")}</strong>
                    ${status}
                </div>
                <p>${escapeHtml(item.cliente_nombre || "Cliente sin nombre")} | ${amount}</p>
                <span class="admin-list__meta">${reference} | ${formatAdminDate(item.fecha_referencia)}</span>
            </li>
        `;
    }).join("");
}

function renderDeliveriesList(items) {
    const container = document.querySelector("[data-deliveries-list]");

    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = createEmptyState(
            "No existen entregas todavia",
            "Las entregas finales se mostraran en esta lista."
        );
        return;
    }

    container.innerHTML = items.map((item) => {
        const fileName = getAdminFileName(item.archivo_final || "");

        return `
            <li class="admin-list__item">
                <div class="admin-list__item-header">
                    <strong class="admin-list__title">${escapeHtml(item.numero_pedido || "Pedido sin numero")}</strong>
                    <span class="admin-status admin-status--success">Entregado</span>
                </div>
                <p>${escapeHtml(item.cliente_nombre || "Cliente sin nombre")} | ${escapeHtml(item.formato_entrega || "Digital")}</p>
                <span class="admin-list__meta">${escapeHtml(fileName)} | ${formatAdminDate(item.fecha_entrega)}</span>
            </li>
        `;
    }).join("");
}

function renderOrdersTable(items) {
    const body = document.querySelector("[data-orders-table-body]");

    if (!(body instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        body.innerHTML = `
            <tr>
                <td colspan="4">
                    <div class="admin-empty-state">
                        <strong>No existen pedidos todavia</strong>
                        <p>Cuando entren nuevas solicitudes apareceran en esta tabla.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    body.innerHTML = items.map((item) => `
        <tr>
            <td>
                <a class="admin-table__link" href="./orders/details.php?id=${encodeURIComponent(String(item.id || ""))}">
                    ${escapeHtml(item.numero_pedido || "Sin numero")}
                </a>
            </td>
            <td>${escapeHtml(item.cliente_nombre || "Cliente sin nombre")}</td>
            <td>${createStatusBadge(item.estado_pedido, "order")}</td>
            <td>${formatAdminDate(item.created_at)}</td>
        </tr>
    `).join("");
}

function createStatusBadge(status, type) {
    const normalizedStatus = String(status || "").toLowerCase();
    const statusTone = getStatusTone(normalizedStatus, type);
    const statusLabel = getStatusLabel(normalizedStatus, type);

    return `<span class="admin-status admin-status--${statusTone}">${escapeHtml(statusLabel)}</span>`;
}

function getStatusTone(status, type) {
    if (type === "payment") {
        if (status === "confirmado") {
            return "success";
        }

        if (status === "rechazado" || status === "reembolsado") {
            return "danger";
        }

        return "pending";
    }

    const orderTones = {
        pendiente: "order-pending",
        pago_confirmado: "order-paid",
        en_proceso: "order-processing",
        terminado: "order-finished",
        entregado: "order-delivered",
        cancelado: "order-cancelled",
    };

    return orderTones[status] || "neutral";
}

function getStatusLabel(status, type) {
    if (type === "payment") {
        const paymentLabels = {
            pendiente: "Pendiente",
            confirmado: "Confirmado",
            rechazado: "Rechazado",
            reembolsado: "Reembolsado",
        };

        return paymentLabels[status] || "Sin estado";
    }

    const orderLabels = {
        pendiente: "Pendiente",
        pago_confirmado: "Pago confirmado",
        en_proceso: "En proceso",
        terminado: "Terminado",
        entregado: "Entregado",
        cancelado: "Cancelado",
    };

    return orderLabels[status] || "Sin estado";
}

function getActivityModuleLabel(moduleName) {
    const labels = {
        auth: "Acceso admin",
        orders: "Pedidos",
        services: "Servicios",
        payments: "Pagos",
        deliveries: "Entregas",
        messages: "Mensajes",
    };

    return labels[String(moduleName || "").toLowerCase()] || "Actividad";
}

function createEmptyState(title, copy) {
    return `
        <li class="admin-empty-state">
            <strong>${escapeHtml(title)}</strong>
            <p>${escapeHtml(copy)}</p>
        </li>
    `;
}

function formatAdminNumber(value) {
    const numericValue = Number(value || 0);

    return new Intl.NumberFormat("es-MX", {
        maximumFractionDigits: 0,
    }).format(numericValue);
}

function formatAdminCurrency(value) {
    const numericValue = Number(value || 0);

    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        maximumFractionDigits: 2,
    }).format(numericValue);
}

function formatAdminDate(value) {
    if (typeof value !== "string" || value.trim() === "") {
        return "Sin fecha";
    }

    const normalizedValue = value.replace(" ", "T");
    const parsedDate = new Date(normalizedValue);

    if (Number.isNaN(parsedDate.getTime())) {
        return escapeHtml(value);
    }

    return new Intl.DateTimeFormat("es-MX", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(parsedDate);
}

function getAdminFileName(path) {
    const normalizedPath = String(path || "");
    const pathSegments = normalizedPath.split("/");

    return pathSegments[pathSegments.length - 1] || "archivo-final";
}

function setAdminFeedback(element, message, state, hidden) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    element.hidden = hidden;
    element.textContent = message;
    element.classList.remove("is-info");

    if (!hidden && state === "info") {
        element.classList.add("is-info");
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
