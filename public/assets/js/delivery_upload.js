const DELIVERY_FORMAT_LABELS = {
    imagen: "Imagen",
    pdf: "PDF",
    video: "Video",
};

const DELIVERY_ALLOWED_EXTENSIONS = ["jpg", "jpeg", "png", "pdf", "mp4"];
const DELIVERY_MAX_FILE_SIZE_BYTES = 50 * 1024 * 1024;

document.addEventListener("DOMContentLoaded", () => {
    initAdminDeliveriesPage();
    initAdminDeliveryUploadPage();
});

function initAdminDeliveriesPage() {
    const page = document.querySelector("[data-admin-deliveries-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const ordersEndpoint = page.dataset.ordersEndpoint || "";
    const activityEndpoint = page.dataset.activityEndpoint || "";
    const uploadPage = page.dataset.uploadPage || "./upload.php";
    const readyOrdersBody = page.querySelector("[data-ready-orders-body]");
    const readyOrdersMobileList = page.querySelector("[data-ready-orders-mobile-list]");
    const readyOrdersFeedback = page.querySelector("[data-ready-orders-feedback]");
    const recentDeliveriesList = page.querySelector("[data-recent-deliveries-list]");
    const recentDeliveriesFeedback = page.querySelector("[data-recent-deliveries-feedback]");

    if (
        !(readyOrdersBody instanceof HTMLElement)
        || !(readyOrdersMobileList instanceof HTMLElement)
        || !(recentDeliveriesList instanceof HTMLElement)
    ) {
        return;
    }

    if (ordersEndpoint !== "") {
        loadReadyOrders();
    }

    if (activityEndpoint !== "") {
        loadRecentDeliveries();
    }

    async function loadReadyOrders() {
        readyOrdersBody.innerHTML = createReadyOrdersLoadingRows();
        readyOrdersMobileList.innerHTML = createReadyOrdersLoadingCards();
        setAdminFeedback(readyOrdersFeedback, "", "info", true);

        try {
            const endpoint = new URL(ordersEndpoint, window.location.href);
            endpoint.searchParams.set("status", "terminado");
            endpoint.searchParams.set("sort", "event_date");
            endpoint.searchParams.set("per_page", "10");
            const responseData = await fetchAdminJson(endpoint.toString());
            renderReadyOrders(responseData.data?.orders || [], uploadPage, readyOrdersBody, readyOrdersMobileList);
        } catch (error) {
            renderReadyOrders([], uploadPage, readyOrdersBody, readyOrdersMobileList);
            setAdminFeedback(
                readyOrdersFeedback,
                error.message || "No fue posible cargar los pedidos listos para entrega.",
                "error",
                false
            );
        }
    }

    async function loadRecentDeliveries() {
        recentDeliveriesList.innerHTML = `
            <li class="admin-list__item">
                <span class="admin-skeleton admin-skeleton--line"></span>
                <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
            </li>
        `;
        setAdminFeedback(recentDeliveriesFeedback, "", "info", true);

        try {
            const responseData = await fetchAdminJson(activityEndpoint);
            renderRecentDeliveryHistory(responseData.data?.recent_deliveries || [], recentDeliveriesList);
        } catch (error) {
            renderRecentDeliveryHistory([], recentDeliveriesList);
            setAdminFeedback(
                recentDeliveriesFeedback,
                error.message || "No fue posible cargar las entregas recientes.",
                "error",
                false
            );
        }
    }
}

function initAdminDeliveryUploadPage() {
    const page = document.querySelector("[data-admin-delivery-upload-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const ordersEndpoint = page.dataset.ordersEndpoint || "";
    const orderDetailsEndpoint = page.dataset.orderDetailsEndpoint || "";
    const deliveryDetailsEndpoint = page.dataset.deliveryDetailsEndpoint || "";
    const uploadEndpoint = page.dataset.uploadEndpoint || "";
    const initialOrderId = Number.parseInt(page.dataset.initialOrderId || "0", 10);
    const searchForm = page.querySelector("[data-order-search-form]");
    const searchField = page.querySelector("#delivery-order-search");
    const orderSelect = page.querySelector("[data-order-select]");
    const refreshOrdersButton = page.querySelector("[data-refresh-orders]");
    const uploadForm = page.querySelector("[data-delivery-upload-form]");
    const fileInput = page.querySelector("[data-delivery-file-input]");
    const formatSelect = page.querySelector("[data-delivery-format]");
    const fileNameElement = page.querySelector("[data-selected-file-name]");
    const orderSearchFeedback = page.querySelector("[data-order-search-feedback]");
    const orderSummary = page.querySelector("[data-order-summary]");
    const orderSummaryFeedback = page.querySelector("[data-order-summary-feedback]");
    const currentDelivery = page.querySelector("[data-current-delivery]");
    const currentDeliveryFeedback = page.querySelector("[data-current-delivery-feedback]");
    const formFeedback = page.querySelector("[data-delivery-form-feedback]");
    const progressWrap = page.querySelector("[data-upload-progress-wrap]");
    const progressBar = page.querySelector("[data-upload-progress-bar]");
    const progressLabel = page.querySelector("[data-upload-progress-label]");
    const submitButton = page.querySelector("[data-delivery-submit]");
    const notesField = page.querySelector("#delivery-notes");

    if (
        !(searchForm instanceof HTMLFormElement)
        || !(searchField instanceof HTMLInputElement)
        || !(orderSelect instanceof HTMLSelectElement)
        || !(uploadForm instanceof HTMLFormElement)
        || !(fileInput instanceof HTMLInputElement)
        || !(formatSelect instanceof HTMLSelectElement)
        || !(fileNameElement instanceof HTMLElement)
        || !(orderSummary instanceof HTMLElement)
        || !(currentDelivery instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const state = {
        search: "",
        currentOrder: null,
    };

    searchForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        state.search = searchField.value.trim();
        await loadOrderOptions(state.search, getSelectedOrderId());
    });

    if (refreshOrdersButton instanceof HTMLButtonElement) {
        refreshOrdersButton.addEventListener("click", async () => {
            await loadOrderOptions(state.search, getSelectedOrderId());
        });
    }

    orderSelect.addEventListener("change", async () => {
        const selectedOrderId = getSelectedOrderId();
        resetFeedback(formFeedback);

        if (selectedOrderId <= 0) {
            state.currentOrder = null;
            renderOrderSummaryEmpty(orderSummary);
            renderCurrentDeliveryEmpty(currentDelivery);
            return;
        }

        await loadSelectedOrder(selectedOrderId);
    });

    fileInput.addEventListener("change", () => {
        const file = fileInput.files?.[0] || null;
        fileNameElement.textContent = file === null
            ? "Ningun archivo seleccionado."
            : `${file.name} | ${formatFileSize(file.size)}`;
    });

    uploadForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (uploadEndpoint === "") {
            setAdminFeedback(formFeedback, "No existe endpoint configurado para uploads.", "error", false);
            return;
        }

        const selectedOrderId = getSelectedOrderId();
        const selectedFile = fileInput.files?.[0] || null;
        const clientValidationError = validateClientUpload(selectedOrderId, formatSelect.value, selectedFile);

        if (clientValidationError !== "") {
            setAdminFeedback(formFeedback, clientValidationError, "error", false);
            return;
        }

        const formData = new FormData(uploadForm);
        formData.set("order_id", String(selectedOrderId));

        resetFeedback(formFeedback);
        setSubmitLoadingState(submitButton, true);
        updateUploadProgress(progressWrap, progressBar, progressLabel, 0, true);

        try {
            const responseData = await uploadDeliveryFile(uploadEndpoint, formData, progressWrap, progressBar, progressLabel);
            setAdminFeedback(
                formFeedback,
                responseData.message || "Entrega registrada correctamente.",
                "info",
                false
            );

            fileInput.value = "";
            fileNameElement.textContent = "Ningun archivo seleccionado.";

            if (notesField instanceof HTMLTextAreaElement) {
                notesField.value = "";
            }

            if (state.currentOrder !== null) {
                formatSelect.value = inferDeliveryFormatValue(state.currentOrder.delivery?.requested_format || "") || "imagen";
            } else {
                formatSelect.value = "imagen";
            }

            await loadOrderOptions(state.search, selectedOrderId);
            await loadSelectedOrder(selectedOrderId);
        } catch (error) {
            setAdminFeedback(
                formFeedback,
                error.message || "No fue posible registrar la entrega.",
                "error",
                false
            );
        } finally {
            setSubmitLoadingState(submitButton, false);
            window.setTimeout(() => {
                updateUploadProgress(progressWrap, progressBar, progressLabel, 0, false);
            }, 400);
        }
    });

    initializePage();

    async function initializePage() {
        if (initialOrderId > 0) {
            await loadSelectedOrder(initialOrderId);
            await loadOrderOptions("", initialOrderId);
            appendPinnedOrderOption(orderSelect, state.currentOrder);
            orderSelect.value = String(initialOrderId);
            return;
        }

        await loadOrderOptions("", 0);
    }

    async function loadOrderOptions(search, selectedOrderId) {
        if (ordersEndpoint === "") {
            setAdminFeedback(orderSearchFeedback, "No existe endpoint configurado para pedidos.", "error", false);
            return;
        }

        setAdminFeedback(orderSearchFeedback, "Buscando pedidos...", "info", false);
        orderSelect.disabled = true;

        try {
            const endpoint = new URL(ordersEndpoint, window.location.href);
            endpoint.searchParams.set("sort", "recent");
            endpoint.searchParams.set("per_page", "20");

            if (search !== "") {
                endpoint.searchParams.set("search", search);
            }

            const responseData = await fetchAdminJson(endpoint.toString());
            const orders = Array.isArray(responseData.data?.orders) ? responseData.data.orders : [];

            renderOrderSelectOptions(orderSelect, orders, selectedOrderId);
            setAdminFeedback(orderSearchFeedback, "", "info", true);

            if (selectedOrderId > 0 && !orders.some((order) => Number(order.id || 0) === selectedOrderId) && state.currentOrder !== null) {
                appendPinnedOrderOption(orderSelect, state.currentOrder);
                orderSelect.value = String(selectedOrderId);
            }
        } catch (error) {
            renderOrderSelectOptions(orderSelect, [], 0);
            setAdminFeedback(
                orderSearchFeedback,
                error.message || "No fue posible cargar los pedidos.",
                "error",
                false
            );
        } finally {
            orderSelect.disabled = false;
        }
    }

    async function loadSelectedOrder(orderId) {
        if (orderDetailsEndpoint === "") {
            setAdminFeedback(orderSummaryFeedback, "No existe endpoint configurado para detalle de pedido.", "error", false);
            return;
        }

        renderOrderSummaryLoading(orderSummary);
        renderCurrentDeliveryLoading(currentDelivery);
        resetFeedback(orderSummaryFeedback);
        resetFeedback(currentDeliveryFeedback);

        try {
            const orderData = await fetchJsonPayload(buildEndpointWithQuery(orderDetailsEndpoint, { id: orderId }));
            state.currentOrder = orderData.data || null;
            renderOrderSummaryData(orderSummary, state.currentOrder);

            const inferredFormat = inferDeliveryFormatValue(state.currentOrder?.delivery?.requested_format || "");

            if (inferredFormat !== "") {
                formatSelect.value = inferredFormat;
            }
        } catch (error) {
            state.currentOrder = null;
            renderOrderSummaryEmpty(orderSummary);
            renderCurrentDeliveryEmpty(currentDelivery);
            setAdminFeedback(
                orderSummaryFeedback,
                error.message || "No fue posible cargar el pedido seleccionado.",
                "error",
                false
            );
            return;
        }

        if (deliveryDetailsEndpoint === "") {
            return;
        }

        try {
            const deliveryData = await fetchJsonPayload(buildEndpointWithQuery(deliveryDetailsEndpoint, { order_id: orderId }));
            renderCurrentDeliveryData(currentDelivery, deliveryData.data?.delivery || {});
        } catch (error) {
            renderCurrentDeliveryEmpty(currentDelivery);

            if (error.statusCode !== 404) {
                setAdminFeedback(
                    currentDeliveryFeedback,
                    error.message || "No fue posible consultar la entrega actual.",
                    "error",
                    false
                );
            }
        }
    }

    function getSelectedOrderId() {
        return Number.parseInt(orderSelect.value || "0", 10) || 0;
    }
}

function renderReadyOrders(orders, uploadPage, tableBody, mobileList) {
    if (!Array.isArray(orders) || orders.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="admin-empty-state">
                        <strong>No hay pedidos listos para entrega</strong>
                        <p>Los pedidos con estado terminado apareceran aqui.</p>
                    </div>
                </td>
            </tr>
        `;
        mobileList.innerHTML = `
            <div class="admin-empty-state">
                <strong>No hay pedidos listos para entrega</strong>
                <p>Cuando un pedido quede terminado aparecera en esta lista.</p>
            </div>
        `;
        return;
    }

    tableBody.innerHTML = orders.map((order) => `
        <tr>
            <td><strong>${escapeHtml(order.numero_pedido || "Sin numero")}</strong></td>
            <td>
                <div class="admin-table__cell-stack">
                    <strong>${escapeHtml(order.cliente_nombre || "Cliente sin nombre")}</strong>
                    <span>${escapeHtml(order.cliente_correo || "Sin correo")}</span>
                </div>
            </td>
            <td>${escapeHtml(order.nombre_evento || "Evento sin nombre")}</td>
            <td>${formatAdminDate(order.fecha_evento)}</td>
            <td>
                <a class="button button-secondary admin-button-compact" href="${buildUploadPageUrl(uploadPage, order.id)}">Subir entrega</a>
            </td>
        </tr>
    `).join("");

    mobileList.innerHTML = orders.map((order) => `
        <article class="admin-order-card">
            <div class="admin-order-card__header">
                <div>
                    <p class="admin-order-card__label">Pedido listo</p>
                    <h3>${escapeHtml(order.numero_pedido || "Sin numero")}</h3>
                </div>
                ${createStatusBadge(order.estado_pedido, "order")}
            </div>
            <p class="admin-order-card__title">${escapeHtml(order.nombre_evento || "Evento sin nombre")}</p>
            <p class="admin-order-card__copy">${escapeHtml(order.cliente_nombre || "Cliente sin nombre")}</p>
            <div class="admin-order-card__meta">
                <span>${escapeHtml(order.cliente_correo || "Sin correo")}</span>
                <span>${formatAdminDate(order.fecha_evento)}</span>
            </div>
            <a class="button button-secondary admin-order-card__action" href="${buildUploadPageUrl(uploadPage, order.id)}">Subir entrega</a>
        </article>
    `).join("");
}

function renderRecentDeliveryHistory(items, container) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = `
            <li class="admin-empty-state">
                <strong>No hay entregas registradas</strong>
                <p>Las entregas finales recientes apareceran aqui.</p>
            </li>
        `;
        return;
    }

    container.innerHTML = items.map((item) => `
        <li class="admin-list__item">
            <div class="admin-list__item-header">
                <strong class="admin-list__title">${escapeHtml(item.numero_pedido || "Pedido sin numero")}</strong>
                <span class="admin-status admin-status--success">Entregado</span>
            </div>
            <p>${escapeHtml(item.cliente_nombre || "Cliente sin nombre")} | ${escapeHtml(getDeliveryFormatLabel(item.formato_entrega || ""))}</p>
            <span class="admin-list__meta">${escapeHtml(getAdminFileName(item.archivo_final || ""))} | ${formatAdminDate(item.fecha_entrega)}</span>
        </li>
    `).join("");
}

function renderOrderSelectOptions(selectElement, orders, selectedOrderId) {
    const baseOption = '<option value="">Selecciona un pedido</option>';

    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    if (!Array.isArray(orders) || orders.length === 0) {
        selectElement.innerHTML = `${baseOption}<option value="" disabled>No se encontraron pedidos</option>`;
        selectElement.value = "";
        return;
    }

    selectElement.innerHTML = baseOption + orders.map((order) => {
        const orderId = Number(order.id || 0);
        const label = [
            order.numero_pedido || "Sin numero",
            order.cliente_nombre || "Cliente sin nombre",
            getOrderStatusLabel(order.estado_pedido || ""),
        ].join(" | ");

        return `<option value="${orderId}" ${orderId === selectedOrderId ? "selected" : ""}>${escapeHtml(label)}</option>`;
    }).join("");

    if (selectedOrderId > 0) {
        selectElement.value = String(selectedOrderId);
    }
}

function appendPinnedOrderOption(selectElement, orderData) {
    if (!(selectElement instanceof HTMLSelectElement) || !orderData || !orderData.order) {
        return;
    }

    const orderId = Number(orderData.order.id || 0);

    if (orderId <= 0) {
        return;
    }

    const alreadyExists = Array.from(selectElement.options).some((option) => Number(option.value || 0) === orderId);

    if (alreadyExists) {
        return;
    }

    const option = document.createElement("option");
    option.value = String(orderId);
    option.selected = true;
    option.textContent = [
        orderData.order.numero_pedido || "Sin numero",
        orderData.customer?.nombre || "Cliente sin nombre",
        getOrderStatusLabel(orderData.order.estado_pedido || ""),
    ].join(" | ");
    selectElement.appendChild(option);
}

function renderOrderSummaryLoading(container) {
    container.innerHTML = createLoadingDefinitionGrid();
}

function renderCurrentDeliveryLoading(container) {
    container.innerHTML = createLoadingDefinitionGrid();
}

function renderOrderSummaryEmpty(container) {
    container.innerHTML = '<p class="admin-empty-state__copy">Selecciona un pedido para revisar su informacion.</p>';
}

function renderCurrentDeliveryEmpty(container) {
    container.innerHTML = '<p class="admin-empty-state__copy">Aun no existe una entrega registrada para este pedido.</p>';
}

function renderOrderSummaryData(container, data) {
    if (!data || !data.order) {
        renderOrderSummaryEmpty(container);
        return;
    }

    const order = data.order || {};
    const customer = data.customer || {};
    const payment = data.payment || {};
    const delivery = data.delivery || {};

    container.innerHTML = createDefinitionGrid([
        ["Numero pedido", order.numero_pedido || "Sin numero"],
        ["Estado pedido", getOrderStatusLabel(order.estado_pedido || "")],
        ["Cliente", customer.nombre || "Cliente sin nombre"],
        ["Correo", customer.correo || "Sin correo"],
        ["Evento", order.nombre_evento || "Evento sin nombre"],
        ["Fecha evento", order.fecha_evento ? formatAdminDate(order.fecha_evento) : "Sin fecha"],
        ["Estado pago", getPaymentStatusLabel(payment.estado_pago || "")],
        ["Formato solicitado", getDeliveryFormatLabel(delivery.requested_format || "") || "Sin formato solicitado"],
    ]);
}

function renderCurrentDeliveryData(container, delivery) {
    if (!delivery || Object.keys(delivery).length === 0) {
        renderCurrentDeliveryEmpty(container);
        return;
    }

    container.innerHTML = createDefinitionGrid([
        ["Formato entrega", getDeliveryFormatLabel(delivery.formato_entrega || "") || "Sin formato"],
        ["Archivo final", delivery.archivo_nombre || getAdminFileName(delivery.archivo_final || "") || "Sin archivo"],
        ["Ruta almacenada", delivery.archivo_final || "Sin ruta"],
        ["Fecha entrega", delivery.fecha_entrega ? formatAdminDate(delivery.fecha_entrega) : "Sin fecha"],
        ["Disponible", delivery.archivo_disponible === true ? "Si" : "No"],
        ["Notas", delivery.notas_entrega || "Sin notas"],
    ]);
}

function createDefinitionGrid(items) {
    const rows = items.filter((item) => Array.isArray(item) && item.length === 2);

    if (rows.length === 0) {
        return '<p class="admin-empty-state__copy">No hay informacion disponible.</p>';
    }

    return rows.map(([label, value]) => `
        <div class="admin-key-value-item">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(normalizeDefinitionValue(value))}</strong>
        </div>
    `).join("");
}

function createLoadingDefinitionGrid() {
    return `
        <span class="admin-skeleton admin-skeleton--line"></span>
        <span class="admin-skeleton admin-skeleton--line"></span>
        <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
        <span class="admin-skeleton admin-skeleton--line"></span>
    `;
}

function createReadyOrdersLoadingRows() {
    return Array.from({ length: 4 }).map(() => `
        <tr>
            <td><span class="admin-skeleton admin-skeleton--line"></span></td>
            <td><span class="admin-skeleton admin-skeleton--line"></span></td>
            <td><span class="admin-skeleton admin-skeleton--line"></span></td>
            <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
            <td><span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span></td>
        </tr>
    `).join("");
}

function createReadyOrdersLoadingCards() {
    return Array.from({ length: 3 }).map(() => `
        <article class="admin-order-card admin-order-card--loading">
            <span class="admin-skeleton admin-skeleton--line"></span>
            <span class="admin-skeleton admin-skeleton--line"></span>
            <span class="admin-skeleton admin-skeleton--line admin-skeleton--line-short"></span>
        </article>
    `).join("");
}

function buildUploadPageUrl(uploadPage, orderId) {
    return `${uploadPage}?order_id=${encodeURIComponent(String(orderId || ""))}`;
}

function buildEndpointWithQuery(endpoint, params) {
    const url = new URL(endpoint, window.location.href);

    Object.entries(params).forEach(([key, value]) => {
        if (value === null || value === undefined || value === "") {
            return;
        }

        url.searchParams.set(key, String(value));
    });

    return url.toString();
}

async function fetchJsonPayload(endpoint) {
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
        const parsingError = new Error("La API devolvio una respuesta invalida.");
        parsingError.statusCode = response.status;
        throw parsingError;
    }

    if (!response.ok || responseData.success !== true) {
        const requestError = new Error(responseData.message || "No fue posible completar la solicitud.");
        requestError.statusCode = response.status;
        throw requestError;
    }

    return responseData;
}

function validateClientUpload(orderId, selectedFormat, file) {
    if (!Number.isInteger(orderId) || orderId <= 0) {
        return "Selecciona un pedido antes de registrar la entrega.";
    }

    if (!(selectedFormat in DELIVERY_FORMAT_LABELS)) {
        return "Selecciona un formato de entrega valido.";
    }

    if (!(file instanceof File)) {
        return "Selecciona un archivo antes de continuar.";
    }

    if (file.size <= 0) {
        return "El archivo seleccionado esta vacio.";
    }

    if (file.size > DELIVERY_MAX_FILE_SIZE_BYTES) {
        return "Archivo demasiado grande.";
    }

    const extension = getFileExtension(file.name);

    if (!DELIVERY_ALLOWED_EXTENSIONS.includes(extension)) {
        return "Formato no permitido.";
    }

    return "";
}

function uploadDeliveryFile(endpoint, formData, progressWrap, progressBar, progressLabel) {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();
        request.open("POST", endpoint, true);
        request.withCredentials = true;
        request.setRequestHeader("Accept", "application/json");

        request.upload.addEventListener("progress", (event) => {
            if (!event.lengthComputable) {
                updateUploadProgress(progressWrap, progressBar, progressLabel, 0, true, "Subiendo archivo...");
                return;
            }

            const progressValue = Math.max(0, Math.min(100, Math.round((event.loaded / event.total) * 100)));
            updateUploadProgress(progressWrap, progressBar, progressLabel, progressValue, true);
        });

        request.addEventListener("load", () => {
            let responseData = null;

            try {
                responseData = JSON.parse(request.responseText || "{}");
            } catch (error) {
                reject(new Error("La API devolvio una respuesta invalida."));
                return;
            }

            if (request.status < 200 || request.status >= 300 || responseData.success !== true) {
                reject(new Error(responseData.message || "No fue posible registrar la entrega."));
                return;
            }

            updateUploadProgress(progressWrap, progressBar, progressLabel, 100, true, "Upload completado.");
            resolve(responseData);
        });

        request.addEventListener("error", () => {
            reject(new Error("No fue posible enviar el archivo al servidor."));
        });

        request.send(formData);
    });
}

function updateUploadProgress(progressWrap, progressBar, progressLabel, progressValue, visible, customText) {
    if (progressWrap instanceof HTMLElement) {
        progressWrap.hidden = !visible;
    }

    if (progressBar instanceof HTMLElement) {
        progressBar.style.width = `${progressValue}%`;
    }

    if (progressLabel instanceof HTMLElement) {
        progressLabel.textContent = customText || `${progressValue}%`;
    }
}

function setSubmitLoadingState(button, isLoading) {
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    button.disabled = isLoading;
    button.textContent = isLoading
        ? button.dataset.loadingLabel || "Subiendo..."
        : button.dataset.defaultLabel || "Registrar entrega";
}

function resetFeedback(element) {
    setAdminFeedback(element, "", "info", true);
}

function getDeliveryFormatLabel(value) {
    const normalizedValue = String(value || "").toLowerCase();

    return DELIVERY_FORMAT_LABELS[normalizedValue] || value || "";
}

function inferDeliveryFormatValue(value) {
    const normalizedValue = String(value || "").toLowerCase();

    if (normalizedValue.includes("video")) {
        return "video";
    }

    if (normalizedValue.includes("pdf")) {
        return "pdf";
    }

    if (normalizedValue.includes("imagen") || normalizedValue.includes("jpg") || normalizedValue.includes("png")) {
        return "imagen";
    }

    return "";
}

function getOrderStatusLabel(status) {
    const labels = {
        pendiente: "Pendiente",
        pago_confirmado: "Pago confirmado",
        en_proceso: "En proceso",
        terminado: "Terminado",
        entregado: "Entregado",
        cancelado: "Cancelado",
    };

    return labels[String(status || "").toLowerCase()] || "Sin estado";
}

function getPaymentStatusLabel(status) {
    const labels = {
        pendiente: "Pendiente",
        confirmado: "Confirmado",
        rechazado: "Rechazado",
        reembolsado: "Reembolsado",
    };

    return labels[String(status || "").toLowerCase()] || "Sin estado";
}

function getFileExtension(fileName) {
    const normalizedName = String(fileName || "");
    const segments = normalizedName.split(".");

    if (segments.length <= 1) {
        return "";
    }

    return String(segments.pop() || "").toLowerCase();
}

function formatFileSize(sizeInBytes) {
    const numericValue = Number(sizeInBytes || 0);

    if (numericValue < 1024) {
        return `${numericValue} B`;
    }

    if (numericValue < 1024 * 1024) {
        return `${(numericValue / 1024).toFixed(1)} KB`;
    }

    return `${(numericValue / (1024 * 1024)).toFixed(1)} MB`;
}

function normalizeDefinitionValue(value) {
    if (typeof value === "number") {
        return String(value);
    }

    if (typeof value !== "string" || value.trim() === "") {
        return "Sin dato";
    }

    return value;
}
