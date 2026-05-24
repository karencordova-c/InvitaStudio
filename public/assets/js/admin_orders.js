const ORDER_STATUS_LABELS = {
    pendiente: "Pendiente",
    pago_confirmado: "Pago confirmado",
    en_proceso: "En proceso",
    terminado: "Terminado",
    entregado: "Entregado",
    cancelado: "Cancelado",
};

const PAYMENT_RESULT_LABELS = {
    pendiente: "Pendiente",
    aprobado: "Aprobado",
    rechazado: "Rechazado",
    saldo_insuficiente: "Saldo insuficiente",
    tarjeta_invalida: "Tarjeta invalida",
    tarjeta_inactiva: "Tarjeta inactiva",
    error: "Error",
};

document.addEventListener("DOMContentLoaded", () => {
    initAdminOrdersPage();
    initAdminOrderDetailsPage();
});

function initAdminOrdersPage() {
    const page = document.querySelector("[data-admin-orders-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const form = page.querySelector("[data-orders-filter-form]");
    const tableBody = page.querySelector("[data-orders-table-body]");
    const mobileList = page.querySelector("[data-orders-mobile-list]");
    const feedback = page.querySelector("[data-orders-feedback]");
    const summaryChip = page.querySelector("[data-orders-summary-chip]");
    const summaryText = page.querySelector("[data-orders-summary-text]");
    const filtersText = page.querySelector("[data-orders-filters-text]");
    const prevButton = page.querySelector("[data-pagination-prev]");
    const nextButton = page.querySelector("[data-pagination-next]");
    const paginationLabel = page.querySelector("[data-pagination-label]");
    const clearButton = page.querySelector("[data-clear-filters]");

    if (
        !(form instanceof HTMLFormElement)
        || !(tableBody instanceof HTMLElement)
        || !(mobileList instanceof HTMLElement)
        || !(summaryChip instanceof HTMLElement)
        || !(summaryText instanceof HTMLElement)
        || !(filtersText instanceof HTMLElement)
        || !(prevButton instanceof HTMLButtonElement)
        || !(nextButton instanceof HTMLButtonElement)
        || !(paginationLabel instanceof HTMLElement)
    ) {
        return;
    }

    const searchParams = new URLSearchParams(window.location.search);
    const state = {
        page: getPositiveInteger(searchParams.get("page"), 1),
        search: getFormValue(form, "search"),
        status: getFormValue(form, "status"),
        sort: normalizeSort(getFormValue(form, "sort")),
        perPage: normalizePerPage(getFormValue(form, "per_page")),
    };

    let pagination = {
        current_page: 1,
        total_pages: 0,
        has_prev: false,
        has_next: false,
        total_items: 0,
        from_item: 0,
        to_item: 0,
    };
    let isLoading = false;

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        if (isLoading) {
            return;
        }

        state.page = 1;
        syncStateFromForm();
        loadOrders();
    });

    ["status", "sort", "per_page"].forEach((fieldName) => {
        const field = form.elements.namedItem(fieldName);

        if (!(field instanceof HTMLSelectElement)) {
            return;
        }

        field.addEventListener("change", () => {
            if (isLoading) {
                return;
            }

            state.page = 1;
            syncStateFromForm();
            loadOrders();
        });
    });

    if (clearButton instanceof HTMLButtonElement) {
        clearButton.addEventListener("click", () => {
            if (isLoading) {
                return;
            }

            form.reset();
            setFormValue(form, "search", "");
            setFormValue(form, "status", "");
            setFormValue(form, "sort", "recent");
            setFormValue(form, "per_page", "10");
            state.page = 1;
            syncStateFromForm();
            loadOrders();
        });
    }

    prevButton.addEventListener("click", () => {
        if (isLoading || pagination.has_prev !== true) {
            return;
        }

        state.page = Math.max(1, state.page - 1);
        loadOrders();
    });

    nextButton.addEventListener("click", () => {
        if (isLoading || pagination.has_next !== true) {
            return;
        }

        state.page += 1;
        loadOrders();
    });

    loadOrders();

    function syncStateFromForm() {
        state.search = getFormValue(form, "search");
        state.status = getFormValue(form, "status");
        state.sort = normalizeSort(getFormValue(form, "sort"));
        state.perPage = normalizePerPage(getFormValue(form, "per_page"));
    }

    async function loadOrders() {
        const endpoint = page.dataset.ordersEndpoint || "";

        if (endpoint === "") {
            renderOrdersEmptyState(tableBody, mobileList, "No existe endpoint configurado para pedidos.");
            return;
        }

        isLoading = true;
        renderOrdersLoading(tableBody, mobileList);
        setOrdersFeedback(feedback, "", "info", true);
        updatePaginationControls(prevButton, nextButton, paginationLabel, true, true, "Cargando pedidos...");
        updateSummary(summaryChip, summaryText, filtersText, "Consultando...", "Cargando pedidos...", "");
        syncOrdersUrl(state);

        try {
            const responseData = await fetchAdminJson(buildOrdersEndpoint(endpoint, state));
            const data = responseData.data || {};

            pagination = data.pagination || pagination;
            state.page = Number(pagination.current_page || 1);

            renderOrdersTable(tableBody, mobileList, data.orders || [], page.dataset.detailsPage || "./details.php");
            updateSummary(summaryChip, summaryText, filtersText, buildOrdersSummaryChip(pagination), buildOrdersSummaryText(pagination), buildOrdersFiltersText(state));
            updatePaginationControls(
                prevButton,
                nextButton,
                paginationLabel,
                pagination.has_prev !== true,
                pagination.has_next !== true,
                buildPaginationLabel(pagination)
            );
        } catch (error) {
            renderOrdersEmptyState(
                tableBody,
                mobileList,
                error.message || "No fue posible cargar los pedidos."
            );
            updateSummary(summaryChip, summaryText, filtersText, "Sin datos", "No fue posible obtener pedidos.", buildOrdersFiltersText(state));
            updatePaginationControls(prevButton, nextButton, paginationLabel, true, true, "Sin resultados");
            setOrdersFeedback(feedback, error.message || "No fue posible cargar los pedidos.", "error", false);
        } finally {
            isLoading = false;
        }
    }
}

function initAdminOrderDetailsPage() {
    const page = document.querySelector("[data-admin-order-details-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const orderId = Number(page.dataset.orderId || 0);
    const heading = document.querySelector("[data-order-heading]");
    const numberElement = page.querySelector("[data-order-number]");
    const subtitleElement = page.querySelector("[data-order-subtitle]");
    const badgesElement = page.querySelector("[data-order-badges]");
    const metaListElement = page.querySelector("[data-order-meta-list]");
    const detailsFeedback = page.querySelector("[data-order-details-feedback]");
    const statusFeedback = page.querySelector("[data-order-status-feedback]");
    const statusForm = page.querySelector("[data-order-status-form]");
    const statusSelect = page.querySelector("#order-status-select");
    const statusSubmit = page.querySelector("[data-status-submit]");
    const customerContainer = page.querySelector("[data-detail-customer]");
    const eventContainer = page.querySelector("[data-detail-event]");
    const paymentContainer = page.querySelector("[data-detail-payment]");
    const deliveryContainer = page.querySelector("[data-detail-delivery]");
    const notesContainer = page.querySelector("[data-detail-notes]");
    const activityContainer = page.querySelector("[data-order-activity]");

    if (
        !(numberElement instanceof HTMLElement)
        || !(subtitleElement instanceof HTMLElement)
        || !(badgesElement instanceof HTMLElement)
        || !(metaListElement instanceof HTMLElement)
        || !(statusForm instanceof HTMLFormElement)
        || !(statusSelect instanceof HTMLSelectElement)
        || !(statusSubmit instanceof HTMLButtonElement)
        || !(customerContainer instanceof HTMLElement)
        || !(eventContainer instanceof HTMLElement)
        || !(paymentContainer instanceof HTMLElement)
        || !(deliveryContainer instanceof HTMLElement)
        || !(notesContainer instanceof HTMLElement)
        || !(activityContainer instanceof HTMLElement)
    ) {
        return;
    }

    let currentOrder = null;
    let isSubmitting = false;

    if (orderId <= 0) {
        if (heading instanceof HTMLElement) {
            heading.textContent = "Detalle de pedido invalido";
        }

        numberElement.textContent = "Pedido no disponible";
        subtitleElement.textContent = "No se recibio un identificador de pedido valido.";
        badgesElement.innerHTML = createInlineMessage("Selecciona un pedido valido desde el listado.");
        metaListElement.innerHTML = "";
        customerContainer.innerHTML = createDefinitionEmptyState("No hay pedido seleccionado.");
        eventContainer.innerHTML = createDefinitionEmptyState("No hay informacion del evento.");
        paymentContainer.innerHTML = createDefinitionEmptyState("No hay informacion de pago.");
        deliveryContainer.innerHTML = createDefinitionEmptyState("No hay informacion de entrega.");
        notesContainer.innerHTML = '<p class="admin-empty-state__copy">Selecciona un pedido desde el listado para continuar.</p>';
        activityContainer.innerHTML = createActivityEmptyState("No hay actividad disponible.");
        statusSelect.disabled = true;
        statusSubmit.disabled = true;
        setOrdersFeedback(detailsFeedback, "Pedido invalido.", "error", false);
        return;
    }

    statusForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (isSubmitting || currentOrder === null) {
            return;
        }

        const nextStatus = String(statusSelect.value || "");
        const currentStatus = String(currentOrder.order.estado_pedido || "");

        if (nextStatus === currentStatus) {
            setOrdersFeedback(statusFeedback, "El pedido ya tiene ese estado.", "info", false);
            return;
        }

        const updateEndpoint = page.dataset.updateEndpoint || "";

        if (updateEndpoint === "") {
            setOrdersFeedback(statusFeedback, "No existe endpoint configurado para actualizar el estado.", "error", false);
            return;
        }

        isSubmitting = true;
        setOrdersFeedback(statusFeedback, "Actualizando estado...", "info", false);
        setStatusSubmitState(statusSubmit, true);

        try {
            const responseData = await fetchAdminJsonWithOptions(updateEndpoint, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    order_id: orderId,
                    estado_pedido: nextStatus,
                }),
            });

            setOrdersFeedback(
                statusFeedback,
                responseData.message || "Estado actualizado correctamente.",
                "info",
                false
            );

            await loadDetails();
        } catch (error) {
            setOrdersFeedback(
                statusFeedback,
                error.message || "No fue posible actualizar el estado del pedido.",
                "error",
                false
            );
        } finally {
            isSubmitting = false;
            setStatusSubmitState(statusSubmit, false);
        }
    });

    loadDetails();

    async function loadDetails() {
        const detailsEndpoint = page.dataset.detailsEndpoint || "";

        if (detailsEndpoint === "") {
            setOrdersFeedback(detailsFeedback, "No existe endpoint configurado para detalle.", "error", false);
            return;
        }

        renderOrderDetailsLoading(
            numberElement,
            subtitleElement,
            badgesElement,
            metaListElement,
            customerContainer,
            eventContainer,
            paymentContainer,
            deliveryContainer,
            notesContainer,
            activityContainer
        );
        setOrdersFeedback(detailsFeedback, "", "info", true);
        statusSelect.disabled = true;
        statusSubmit.disabled = true;

        try {
            const responseData = await fetchAdminJson(buildOrderDetailsEndpoint(detailsEndpoint, orderId));
            currentOrder = responseData.data || null;

            if (currentOrder === null) {
                throw new Error("La API no devolvio datos del pedido.");
            }

            renderOrderDetails(
                currentOrder,
                heading,
                numberElement,
                subtitleElement,
                badgesElement,
                metaListElement,
                statusSelect,
                statusSubmit,
                customerContainer,
                eventContainer,
                paymentContainer,
                deliveryContainer,
                notesContainer,
                activityContainer
            );
        } catch (error) {
            currentOrder = null;
            setOrdersFeedback(detailsFeedback, error.message || "No fue posible cargar el detalle del pedido.", "error", false);
            statusSelect.disabled = true;
            statusSubmit.disabled = true;
        }
    }
}

function renderOrdersLoading(tableBody, mobileList) {
    if (tableBody instanceof HTMLElement) {
        tableBody.innerHTML = Array.from({ length: 5 }).map(() => `
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
        `).join("");
    }

    if (mobileList instanceof HTMLElement) {
        mobileList.innerHTML = Array.from({ length: 3 }).map(() => `
            <article class="admin-order-card admin-order-card--loading">
                <span class="admin-skeleton admin-skeleton--line"></span>
                <span class="admin-skeleton admin-skeleton--pill"></span>
                <span class="admin-skeleton admin-skeleton--line"></span>
                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
            </article>
        `).join("");
    }
}

function renderOrdersTable(tableBody, mobileList, orders, detailsPage) {
    if (!Array.isArray(orders) || orders.length === 0) {
        renderOrdersEmptyState(tableBody, mobileList, "No hay pedidos registrados");
        return;
    }

    if (tableBody instanceof HTMLElement) {
        tableBody.innerHTML = orders.map((order) => `
            <tr>
                <td>
                    <strong>${escapeHtml(order.numero_pedido || "Sin numero")}</strong>
                </td>
                <td>
                    <div class="admin-table__cell-stack">
                        <strong>${escapeHtml(order.cliente_nombre || "Cliente sin nombre")}</strong>
                        <span>${escapeHtml(order.cliente_correo || "Sin correo")}</span>
                    </div>
                </td>
                <td>
                    <div class="admin-table__cell-stack">
                        <strong>${escapeHtml(order.nombre_evento || "Evento sin nombre")}</strong>
                        <span>${escapeHtml(order.tipo_evento || "Sin tipo")}</span>
                    </div>
                </td>
                <td>${formatAdminDateOnly(order.fecha_evento)}</td>
                <td>${createStatusBadge(order.estado_pedido, "order")}</td>
                <td>${createStatusBadge(order.estado_pago, "payment")}</td>
                <td>${formatAdminDate(order.created_at)}</td>
                <td>
                    <a class="button button-secondary admin-button-compact" href="${buildDetailsUrl(detailsPage, order.id)}">Ver detalle</a>
                </td>
            </tr>
        `).join("");
    }

    if (mobileList instanceof HTMLElement) {
        mobileList.innerHTML = orders.map((order) => `
            <article class="admin-order-card">
                <div class="admin-order-card__header">
                    <div>
                        <p class="admin-order-card__label">Pedido</p>
                        <h3>${escapeHtml(order.numero_pedido || "Sin numero")}</h3>
                    </div>
                    ${createStatusBadge(order.estado_pedido, "order")}
                </div>
                <p class="admin-order-card__title">${escapeHtml(order.nombre_evento || "Evento sin nombre")}</p>
                <p class="admin-order-card__copy">${escapeHtml(order.cliente_nombre || "Cliente sin nombre")} | ${escapeHtml(order.cliente_correo || "Sin correo")}</p>
                <div class="admin-order-card__meta">
                    <span>Evento: ${formatAdminDateOnly(order.fecha_evento)}</span>
                    <span>Pago: ${getPaymentStatusLabel(order.estado_pago)}</span>
                </div>
                <a class="button button-secondary admin-order-card__action" href="${buildDetailsUrl(detailsPage, order.id)}">Abrir detalle</a>
            </article>
        `).join("");
    }
}

function renderOrdersEmptyState(tableBody, mobileList, message) {
    const normalizedMessage = escapeHtml(message || "No hay pedidos registrados");

    if (tableBody instanceof HTMLElement) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8">
                    <div class="admin-empty-state">
                        <strong>No hay pedidos registrados</strong>
                        <p>${normalizedMessage}</p>
                    </div>
                </td>
            </tr>
        `;
    }

    if (mobileList instanceof HTMLElement) {
        mobileList.innerHTML = `
            <div class="admin-empty-state">
                <strong>No hay pedidos registrados</strong>
                <p>${normalizedMessage}</p>
            </div>
        `;
    }
}

function updateSummary(chipElement, summaryElement, filtersElement, chipText, summaryText, filtersText) {
    if (chipElement instanceof HTMLElement) {
        chipElement.textContent = chipText;
    }

    if (summaryElement instanceof HTMLElement) {
        summaryElement.textContent = summaryText;
    }

    if (filtersElement instanceof HTMLElement) {
        filtersElement.textContent = filtersText;
    }
}

function updatePaginationControls(prevButton, nextButton, labelElement, disablePrev, disableNext, label) {
    if (prevButton instanceof HTMLButtonElement) {
        prevButton.disabled = disablePrev;
    }

    if (nextButton instanceof HTMLButtonElement) {
        nextButton.disabled = disableNext;
    }

    if (labelElement instanceof HTMLElement) {
        labelElement.textContent = label;
    }
}

function renderOrderDetailsLoading(
    numberElement,
    subtitleElement,
    badgesElement,
    metaListElement,
    customerContainer,
    eventContainer,
    paymentContainer,
    deliveryContainer,
    notesContainer,
    activityContainer
) {
    numberElement.textContent = "Consultando pedido...";
    subtitleElement.textContent = "Cargando informacion principal del pedido.";
    badgesElement.innerHTML = `
        <span class="admin-skeleton admin-skeleton--pill"></span>
        <span class="admin-skeleton admin-skeleton--pill"></span>
    `;
    metaListElement.innerHTML = `
        <span class="admin-skeleton admin-skeleton--line"></span>
        <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
    `;
    customerContainer.innerHTML = createLoadingDefinitionRows();
    eventContainer.innerHTML = createLoadingDefinitionRows();
    paymentContainer.innerHTML = createLoadingDefinitionRows();
    deliveryContainer.innerHTML = createLoadingDefinitionRows();
    notesContainer.innerHTML = `
        <span class="admin-skeleton admin-skeleton--line"></span>
        <span class="admin-skeleton admin-skeleton--line"></span>
    `;
    activityContainer.innerHTML = `
        <li class="admin-activity-feed__item">
            <span class="admin-skeleton admin-skeleton--line"></span>
            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
        </li>
    `;
}

function renderOrderDetails(
    data,
    heading,
    numberElement,
    subtitleElement,
    badgesElement,
    metaListElement,
    statusSelect,
    statusSubmit,
    customerContainer,
    eventContainer,
    paymentContainer,
    deliveryContainer,
    notesContainer,
    activityContainer
) {
    const order = data.order || {};
    const customer = data.customer || {};
    const payment = data.payment || {};
    const delivery = data.delivery || {};
    const activity = Array.isArray(data.activity) ? data.activity : [];

    if (heading instanceof HTMLElement) {
        heading.textContent = `Detalle ${order.numero_pedido || "del pedido"}`;
    }

    numberElement.textContent = order.numero_pedido || "Pedido sin numero";
    subtitleElement.textContent = `${order.nombre_evento || "Evento sin nombre"} | ${customer.nombre || "Cliente sin nombre"}`;
    badgesElement.innerHTML = `
        ${createStatusBadge(order.estado_pedido, "order")}
        ${createStatusBadge(payment.estado_pago, "payment")}
    `;
    metaListElement.innerHTML = `
        <span>Creado: ${formatAdminDate(order.created_at)}</span>
        <span>Actualizado: ${formatAdminDate(order.updated_at)}</span>
        <span>Evento: ${formatAdminDateOnly(order.fecha_evento)}</span>
    `;

    statusSelect.value = String(order.estado_pedido || "pendiente");
    statusSelect.disabled = false;
    statusSubmit.disabled = false;

    customerContainer.innerHTML = createDefinitionGrid([
        ["Nombre", customer.nombre],
        ["Correo", customer.correo],
        ["Telefono", customer.telefono],
        ["Medio contacto", formatContactMethod(customer.medio_contacto)],
    ]);

    eventContainer.innerHTML = createDefinitionGrid([
        ["Tipo evento", order.tipo_evento],
        ["Nombre evento", order.nombre_evento],
        ["Fecha", formatAdminDateOnly(order.fecha_evento)],
        ["Hora", formatAdminTimeOnly(order.hora_evento)],
        ["Ubicacion", order.ubicacion_evento],
        ["Tematica", order.tematica],
        ["Colores", order.colores],
        ["Estilo", order.estilo_diseno],
    ]);

    paymentContainer.innerHTML = createDefinitionGrid([
        ["Estado pago", getPaymentStatusLabel(payment.estado_pago)],
        ["Resultado transaccion", getPaymentResultLabel(payment.resultado_transaccion)],
        ["Metodo pago", payment.metodo_pago || "Sin metodo"],
        ["Monto", formatAdminCurrency(payment.monto_pago || 0)],
        ["Referencia", payment.referencia_pago || "Sin referencia"],
        ["Fecha pago", payment.fecha_pago ? formatAdminDate(payment.fecha_pago) : formatAdminDate(payment.created_at)],
    ]);

    deliveryContainer.innerHTML = createDefinitionGrid([
        ["Formato solicitado", delivery.requested_format || "Sin formato solicitado"],
        ["Formato entrega", delivery.formato_entrega || "Sin entrega registrada"],
        ["Archivo final", delivery.archivo_final || "Sin archivo final"],
        ["Fecha entrega", delivery.fecha_entrega ? formatAdminDate(delivery.fecha_entrega) : "Sin entrega registrada"],
        ["Estado pedido", getOrderStatusLabel(order.estado_pedido)],
    ]);

    notesContainer.innerHTML = order.informacion_adicional
        ? `<p>${escapeHtml(order.informacion_adicional).replace(/\n/g, "<br>")}</p>`
        : '<p class="admin-empty-state__copy">No hay informacion adicional registrada.</p>';

    if (activity.length === 0) {
        activityContainer.innerHTML = createActivityEmptyState("No hay actividad registrada para este pedido.");
        return;
    }

    activityContainer.innerHTML = activity.map((activityItem) => `
        <li class="admin-activity-feed__item">
            <div class="admin-list__item-header">
                <strong class="admin-activity-feed__title">${escapeHtml(getModuleLabel(activityItem.modulo))}</strong>
                <span class="admin-status admin-status--neutral">${escapeHtml(activityItem.accion || "registro")}</span>
            </div>
            <p>${escapeHtml(activityItem.descripcion || "Movimiento registrado.")}</p>
            <span class="admin-activity-feed__meta">${formatAdminDate(activityItem.created_at)}</span>
        </li>
    `).join("");
}

function createDefinitionGrid(items) {
    const rows = items.filter((item) => Array.isArray(item) && item.length === 2);

    if (rows.length === 0) {
        return createDefinitionEmptyState("No hay informacion disponible.");
    }

    return rows.map(([label, value]) => `
        <div class="admin-key-value-item">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(normalizeDetailValue(value))}</strong>
        </div>
    `).join("");
}

function createDefinitionEmptyState(message) {
    return `<p class="admin-empty-state__copy">${escapeHtml(message || "No hay informacion disponible.")}</p>`;
}

function createActivityEmptyState(message) {
    return `
        <li class="admin-empty-state">
            <strong>Sin actividad</strong>
            <p>${escapeHtml(message || "No hay actividad registrada.")}</p>
        </li>
    `;
}

function createInlineMessage(message) {
    return `<p class="admin-empty-state__copy">${escapeHtml(message || "")}</p>`;
}

function createLoadingDefinitionRows() {
    return `
        <span class="admin-skeleton admin-skeleton--line"></span>
        <span class="admin-skeleton admin-skeleton--line"></span>
        <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
    `;
}

function buildOrdersEndpoint(endpoint, state) {
    const searchParams = new URLSearchParams();

    if (state.search !== "") {
        searchParams.set("search", state.search);
    }

    if (state.status !== "") {
        searchParams.set("status", state.status);
    }

    searchParams.set("sort", state.sort);
    searchParams.set("page", String(state.page));
    searchParams.set("per_page", String(state.perPage));

    return `${endpoint}?${searchParams.toString()}`;
}

function buildOrderDetailsEndpoint(endpoint, orderId) {
    const searchParams = new URLSearchParams({ id: String(orderId) });

    return `${endpoint}?${searchParams.toString()}`;
}

function buildDetailsUrl(detailsPage, orderId) {
    return `${detailsPage}?id=${encodeURIComponent(String(orderId || ""))}`;
}

function syncOrdersUrl(state) {
    const searchParams = new URLSearchParams();

    if (state.search !== "") {
        searchParams.set("search", state.search);
    }

    if (state.status !== "") {
        searchParams.set("status", state.status);
    }

    if (state.sort !== "recent") {
        searchParams.set("sort", state.sort);
    }

    if (state.perPage !== 10) {
        searchParams.set("per_page", String(state.perPage));
    }

    if (state.page > 1) {
        searchParams.set("page", String(state.page));
    }

    const nextUrl = searchParams.toString() === ""
        ? window.location.pathname
        : `${window.location.pathname}?${searchParams.toString()}`;

    window.history.replaceState({}, "", nextUrl);
}

async function fetchAdminJsonWithOptions(endpoint, options) {
    const response = await fetch(endpoint, options);
    let responseData = null;

    try {
        responseData = await response.json();
    } catch (error) {
        throw new Error("La API devolvio una respuesta invalida.");
    }

    if (!response.ok || responseData.success !== true) {
        throw new Error(responseData.message || "No fue posible completar la solicitud.");
    }

    return responseData;
}

function setOrdersFeedback(element, message, state, hidden) {
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

function setStatusSubmitState(button, isLoading) {
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    button.disabled = isLoading;
    button.textContent = isLoading
        ? button.dataset.loadingLabel || "Actualizando..."
        : button.dataset.defaultLabel || "Guardar estado";
}

function buildOrdersSummaryChip(pagination) {
    const totalItems = Number(pagination.total_items || 0);

    if (totalItems === 0) {
        return "0 pedidos";
    }

    return `${totalItems} pedidos`;
}

function buildOrdersSummaryText(pagination) {
    const totalItems = Number(pagination.total_items || 0);
    const fromItem = Number(pagination.from_item || 0);
    const toItem = Number(pagination.to_item || 0);

    if (totalItems === 0) {
        return "No hay pedidos registrados para los filtros actuales.";
    }

    return `Mostrando ${fromItem}-${toItem} de ${totalItems} pedidos.`;
}

function buildOrdersFiltersText(state) {
    const filters = [];

    if (state.search !== "") {
        filters.push(`Busqueda: ${state.search}`);
    }

    if (state.status !== "") {
        filters.push(`Estado: ${getOrderStatusLabel(state.status)}`);
    }

    if (state.sort !== "") {
        filters.push(`Orden: ${getSortLabel(state.sort)}`);
    }

    filters.push(`Pagina: ${state.page}`);
    filters.push(`Por pagina: ${state.perPage}`);

    return filters.join(" | ");
}

function buildPaginationLabel(pagination) {
    const totalPages = Number(pagination.total_pages || 0);
    const currentPage = Number(pagination.current_page || 1);

    if (totalPages === 0) {
        return "Sin resultados";
    }

    return `Pagina ${currentPage} de ${totalPages}`;
}

function getPaymentStatusLabel(status) {
    const normalizedStatus = String(status || "").toLowerCase();
    const labels = {
        pendiente: "Pendiente",
        confirmado: "Confirmado",
        rechazado: "Rechazado",
        reembolsado: "Reembolsado",
    };

    return labels[normalizedStatus] || "Sin estado";
}

function getPaymentResultLabel(status) {
    const normalizedStatus = String(status || "").toLowerCase();

    return PAYMENT_RESULT_LABELS[normalizedStatus] || "Sin resultado";
}

function getOrderStatusLabel(status) {
    const normalizedStatus = String(status || "").toLowerCase();

    return ORDER_STATUS_LABELS[normalizedStatus] || "Sin estado";
}

function getSortLabel(sort) {
    const normalizedSort = String(sort || "").toLowerCase();
    const labels = {
        recent: "Mas recientes",
        event_date: "Fecha evento",
        status: "Estado",
    };

    return labels[normalizedSort] || "Mas recientes";
}

function formatContactMethod(value) {
    const normalizedValue = String(value || "").toLowerCase();
    const labels = {
        whatsapp: "WhatsApp",
        correo: "Correo",
        llamada: "Llamada",
    };

    return labels[normalizedValue] || "Sin definir";
}

function formatAdminDateOnly(value) {
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
    }).format(parsedDate);
}

function formatAdminTimeOnly(value) {
    if (typeof value !== "string" || value.trim() === "") {
        return "Sin hora";
    }

    const normalizedValue = value.length === 5 ? `${value}:00` : value;
    const parsedDate = new Date(`1970-01-01T${normalizedValue}`);

    if (Number.isNaN(parsedDate.getTime())) {
        return escapeHtml(value);
    }

    return new Intl.DateTimeFormat("es-MX", {
        timeStyle: "short",
    }).format(parsedDate);
}

function getPositiveInteger(value, fallbackValue) {
    const numericValue = Number.parseInt(String(value || ""), 10);

    if (!Number.isInteger(numericValue) || numericValue <= 0) {
        return fallbackValue;
    }

    return numericValue;
}

function normalizePerPage(value) {
    const perPage = Number.parseInt(String(value || ""), 10);

    return perPage === 20 ? 20 : 10;
}

function normalizeSort(value) {
    const normalizedValue = String(value || "").toLowerCase();

    if (normalizedValue === "event_date" || normalizedValue === "status") {
        return normalizedValue;
    }

    return "recent";
}

function getFormValue(form, fieldName) {
    const field = form.elements.namedItem(fieldName);

    if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLSelectElement)) {
        return "";
    }

    return String(field.value || "").trim();
}

function setFormValue(form, fieldName, value) {
    const field = form.elements.namedItem(fieldName);

    if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLSelectElement)) {
        return;
    }

    field.value = value;
}

function normalizeDetailValue(value) {
    if (typeof value === "number") {
        return String(value);
    }

    if (typeof value !== "string" || value.trim() === "") {
        return "Sin dato";
    }

    return value;
}

function getModuleLabel(moduleName) {
    const normalizedModuleName = String(moduleName || "").toLowerCase();
    const labels = {
        orders: "Pedidos",
        deliveries: "Entregas",
        messages: "Mensajes",
    };

    return labels[normalizedModuleName] || "Actividad";
}
