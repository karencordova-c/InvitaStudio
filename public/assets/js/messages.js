document.addEventListener("DOMContentLoaded", () => {
    initPublicMessagesPanel();
    initAdminMessagesIndexPage();
    initAdminMessageDetailsPage();
});

function initPublicMessagesPanel() {
    const panel = document.querySelector("[data-public-messages-panel]");

    if (!(panel instanceof HTMLElement)) {
        return;
    }

    const form = panel.querySelector("[data-public-message-form]");
    const thread = panel.querySelector("[data-public-message-thread]");
    const feedback = panel.querySelector("[data-public-message-feedback]");
    const errorElement = panel.querySelector("[data-public-message-error]");
    const submitButton = panel.querySelector("[data-public-message-submit]");

    if (
        !(form instanceof HTMLFormElement)
        || !(thread instanceof HTMLElement)
        || !(feedback instanceof HTMLElement)
        || !(errorElement instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const state = {
        numeroPedido: "",
        correo: "",
        isSubmitting: false,
        pollTimer: 0,
    };

    document.addEventListener("status:loaded", async (event) => {
        const detail = event.detail || {};

        state.numeroPedido = String(detail.numero_pedido || "");
        state.correo = String(detail.correo || "");

        if (state.numeroPedido === "" || state.correo === "") {
            return;
        }

        panel.hidden = false;
        await loadPublicConversation();
        startPublicPolling();
    });

    document.addEventListener("status:cleared", () => {
        state.numeroPedido = "";
        state.correo = "";
        stopPublicPolling();
        panel.hidden = true;
        clearMessageForm(form, errorElement, feedback);
        renderConversationEmptyState(thread, "Cuando la consulta se valide cargaremos aqui el historial asociado al pedido.");
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (state.isSubmitting || state.numeroPedido === "" || state.correo === "") {
            return;
        }

        const validationMessage = validateMessageText(getMessageTextValue(form));

        if (validationMessage !== "") {
            showInlineError(errorElement, validationMessage);
            showMessageFeedback(feedback, "Revisa el mensaje antes de enviarlo.", "error");
            return;
        }

        state.isSubmitting = true;
        showInlineError(errorElement, "");
        setMessageSubmitState(submitButton, true);
        showMessageFeedback(feedback, "Enviando mensaje...", "loading");

        try {
            const payload = new FormData(form);
            payload.set("numero_pedido", state.numeroPedido);
            payload.set("correo", state.correo);

            const responseData = await fetchJsonWithOptions(
                form.dataset.createEndpoint || "",
                {
                    method: "POST",
                    body: payload,
                }
            );

            showMessageFeedback(feedback, responseData.message || "Mensaje enviado.", "success");
            form.reset();
            await loadPublicConversation();
        } catch (error) {
            showMessageFeedback(feedback, error.message || "No fue posible enviar el mensaje.", "error");
        } finally {
            state.isSubmitting = false;
            setMessageSubmitState(submitButton, false);
        }
    });

    async function loadPublicConversation() {
        const listEndpoint = form.dataset.listEndpoint || "";

        if (listEndpoint === "") {
            return;
        }

        renderConversationLoadingState(thread, "Cargando historial del pedido...");

        try {
            const responseData = await fetchJson(
                `${listEndpoint}?${new URLSearchParams({
                    numero_pedido: state.numeroPedido,
                    correo: state.correo,
                }).toString()}`
            );

            renderConversationThread(
                thread,
                responseData.data && Array.isArray(responseData.data.messages) ? responseData.data.messages : [],
                "Aun no hay mensajes en esta conversacion."
            );
        } catch (error) {
            renderConversationEmptyState(thread, error.message || "No fue posible cargar la conversacion.");
            showMessageFeedback(feedback, error.message || "No fue posible obtener los mensajes.", "error");
        }
    }

    function startPublicPolling() {
        stopPublicPolling();
        state.pollTimer = window.setInterval(async () => {
            if (state.numeroPedido !== "" && state.correo !== "" && state.isSubmitting === false) {
                await loadPublicConversation();
            }
        }, 20000);
    }

    function stopPublicPolling() {
        if (state.pollTimer > 0) {
            window.clearInterval(state.pollTimer);
            state.pollTimer = 0;
        }
    }
}

function initAdminMessagesIndexPage() {
    const page = document.querySelector("[data-admin-messages-index-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const tableBody = page.querySelector("[data-admin-messages-table-body]");
    const mobileList = page.querySelector("[data-admin-messages-mobile-list]");
    const feedback = page.querySelector("[data-admin-messages-feedback]");
    const refreshButton = page.querySelector("[data-admin-messages-refresh]");

    if (
        !(tableBody instanceof HTMLElement)
        || !(mobileList instanceof HTMLElement)
        || !(feedback instanceof HTMLElement)
        || !(refreshButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const state = {
        isLoading: false,
        pollTimer: 0,
    };

    refreshButton.addEventListener("click", async () => {
        if (state.isLoading) {
            return;
        }

        await loadConversations();
    });

    loadConversations();
    state.pollTimer = window.setInterval(async () => {
        if (state.isLoading === false) {
            await loadConversations(false);
        }
    }, 30000);

    async function loadConversations(showLoading = true) {
        const listEndpoint = page.dataset.listEndpoint || "";

        if (listEndpoint === "") {
            return;
        }

        state.isLoading = true;

        if (showLoading) {
            renderAdminConversationsLoading(tableBody, mobileList);
        }

        try {
            const responseData = await fetchJson(listEndpoint);
            const conversations = responseData.data && Array.isArray(responseData.data.conversations)
                ? responseData.data.conversations
                : [];

            renderAdminConversations(
                tableBody,
                mobileList,
                conversations,
                page.dataset.detailsPage || "./details.php"
            );
            showMessageFeedback(feedback, "", "");
            feedback.hidden = true;
        } catch (error) {
            renderAdminConversationEmpty(tableBody, mobileList, error.message || "No fue posible cargar las conversaciones.");
            showMessageFeedback(feedback, error.message || "No fue posible cargar las conversaciones.", "error");
            feedback.hidden = false;
        } finally {
            state.isLoading = false;
        }
    }
}

function initAdminMessageDetailsPage() {
    const page = document.querySelector("[data-admin-message-details-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const orderId = Number(page.dataset.orderId || 0);
    const heading = document.querySelector("[data-admin-message-heading]");
    const numberElement = page.querySelector("[data-admin-message-order-number]");
    const subtitleElement = page.querySelector("[data-admin-message-subtitle]");
    const badgesElement = page.querySelector("[data-admin-message-badges]");
    const metaListElement = page.querySelector("[data-admin-message-meta-list]");
    const detailsFeedback = page.querySelector("[data-admin-message-details-feedback]");
    const thread = page.querySelector("[data-admin-message-thread]");
    const refreshButton = page.querySelector("[data-admin-message-refresh]");
    const form = page.querySelector("[data-admin-message-form]");
    const feedback = page.querySelector("[data-admin-message-feedback]");
    const errorElement = page.querySelector("[data-admin-message-error]");
    const submitButton = page.querySelector("[data-admin-message-submit]");

    if (
        !(numberElement instanceof HTMLElement)
        || !(subtitleElement instanceof HTMLElement)
        || !(badgesElement instanceof HTMLElement)
        || !(metaListElement instanceof HTMLElement)
        || !(detailsFeedback instanceof HTMLElement)
        || !(thread instanceof HTMLElement)
        || !(refreshButton instanceof HTMLButtonElement)
        || !(form instanceof HTMLFormElement)
        || !(feedback instanceof HTMLElement)
        || !(errorElement instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const state = {
        isLoading: false,
        isSubmitting: false,
        pollTimer: 0,
    };

    if (orderId <= 0) {
        if (heading instanceof HTMLElement) {
            heading.textContent = "Conversacion invalida";
        }

        numberElement.textContent = "Pedido no disponible";
        subtitleElement.textContent = "No se recibio un pedido valido para consultar la conversacion.";
        badgesElement.innerHTML = "";
        metaListElement.innerHTML = "";
        renderConversationEmptyState(thread, "Selecciona un pedido valido desde el listado de conversaciones.");
        showMessageFeedback(detailsFeedback, "Pedido invalido.", "error");
        detailsFeedback.hidden = false;
        return;
    }

    refreshButton.addEventListener("click", async () => {
        if (state.isLoading || state.isSubmitting) {
            return;
        }

        await loadConversation();
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (state.isSubmitting || state.isLoading) {
            return;
        }

        const validationMessage = validateMessageText(getMessageTextValue(form));

        if (validationMessage !== "") {
            showInlineError(errorElement, validationMessage);
            showMessageFeedback(feedback, "Revisa el mensaje antes de enviarlo.", "error");
            return;
        }

        state.isSubmitting = true;
        showInlineError(errorElement, "");
        setMessageSubmitState(submitButton, true);
        showMessageFeedback(feedback, "Enviando mensaje...", "loading");

        try {
            const payload = new FormData(form);
            payload.set("order_id", String(orderId));

            const responseData = await fetchJsonWithOptions(
                page.dataset.createEndpoint || "",
                {
                    method: "POST",
                    body: payload,
                    credentials: "same-origin",
                }
            );

            showMessageFeedback(feedback, responseData.message || "Mensaje enviado.", "success");
            form.reset();
            await loadConversation();
        } catch (error) {
            showMessageFeedback(feedback, error.message || "No fue posible enviar el mensaje.", "error");
        } finally {
            state.isSubmitting = false;
            setMessageSubmitState(submitButton, false);
        }
    });

    loadConversation();
    state.pollTimer = window.setInterval(async () => {
        if (state.isLoading === false && state.isSubmitting === false) {
            await loadConversation(false);
        }
    }, 20000);

    async function loadConversation(showLoading = true) {
        const listEndpoint = page.dataset.listEndpoint || "";

        if (listEndpoint === "") {
            return;
        }

        state.isLoading = true;

        if (showLoading) {
            renderConversationLoadingState(thread, "Cargando historial cronologico...");
        }

        try {
            const responseData = await fetchJson(
                `${listEndpoint}?${new URLSearchParams({ order_id: String(orderId) }).toString()}`
            );

            const data = responseData.data || {};
            const conversation = data.conversation || {};
            const messages = Array.isArray(data.messages) ? data.messages : [];

            if (heading instanceof HTMLElement) {
                heading.textContent = `Conversacion ${conversation.numero_pedido || "del pedido"}`;
            }

            numberElement.textContent = conversation.numero_pedido || "Pedido sin numero";
            subtitleElement.textContent = `${conversation.cliente_nombre || "Cliente sin nombre"} | ${conversation.cliente_correo || "Sin correo"}`;
            badgesElement.innerHTML = `
                ${createConversationStatusBadge(conversation.estado_pedido, "order")}
                ${createConversationStatusBadge("admin", "author")}
            `;
            metaListElement.innerHTML = `
                <span>Total mensajes: ${escapeHtml(String(conversation.total_mensajes || 0))}</span>
                <span>Ultimo movimiento: ${escapeHtml(formatMessageDateTime(conversation.ultimo_mensaje_fecha))}</span>
            `;
            renderConversationThread(thread, messages, "Aun no hay mensajes asociados a este pedido.");
            detailsFeedback.hidden = true;
        } catch (error) {
            renderConversationEmptyState(thread, error.message || "No fue posible cargar la conversacion.");
            showMessageFeedback(detailsFeedback, error.message || "No fue posible cargar la conversacion.", "error");
            detailsFeedback.hidden = false;
        } finally {
            state.isLoading = false;
        }
    }
}

function renderAdminConversationsLoading(tableBody, mobileList) {
    tableBody.innerHTML = `
        <tr>
            <td colspan="7">
                <div class="admin-empty-state">
                    <strong>Cargando conversaciones</strong>
                    <p>Preparando historial asociado a pedidos.</p>
                </div>
            </td>
        </tr>
    `;
    mobileList.innerHTML = `
        <div class="admin-empty-state">
            <strong>Cargando conversaciones</strong>
            <p>Preparando historial asociado a pedidos.</p>
        </div>
    `;
}

function renderAdminConversations(tableBody, mobileList, conversations, detailsPage) {
    if (!Array.isArray(conversations) || conversations.length === 0) {
        renderAdminConversationEmpty(tableBody, mobileList, "Aun no existen conversaciones registradas.");
        return;
    }

    tableBody.innerHTML = conversations.map((item) => `
        <tr>
            <td><strong>${escapeHtml(item.numero_pedido || "Sin numero")}</strong></td>
            <td>
                <div class="admin-table__cell-stack">
                    <strong>${escapeHtml(item.cliente_nombre || "Cliente sin nombre")}</strong>
                    <span>${escapeHtml(item.cliente_correo || "Sin correo")}</span>
                </div>
            </td>
            <td>${createConversationStatusBadge(item.estado_pedido, "order")}</td>
            <td>
                <div class="admin-table__cell-stack">
                    <strong>${escapeHtml(getActorLabel(item.ultimo_mensaje_tipo))}</strong>
                    <span>${escapeHtml(truncateMessage(item.ultimo_mensaje || ""))}</span>
                </div>
            </td>
            <td>${escapeHtml(formatMessageDateTime(item.ultimo_mensaje_fecha))}</td>
            <td>${escapeHtml(String(item.total_mensajes || 0))}</td>
            <td>
                <a class="button button-secondary admin-button-compact" href="${buildConversationDetailsUrl(detailsPage, item.pedido_id)}">Abrir</a>
            </td>
        </tr>
    `).join("");

    mobileList.innerHTML = conversations.map((item) => `
        <article class="admin-order-card">
            <div class="admin-order-card__header">
                <div>
                    <p class="admin-order-card__label">Conversacion</p>
                    <h3>${escapeHtml(item.numero_pedido || "Sin numero")}</h3>
                </div>
                ${createConversationStatusBadge(item.estado_pedido, "order")}
            </div>
            <p class="admin-order-card__title">${escapeHtml(item.cliente_nombre || "Cliente sin nombre")}</p>
            <p class="admin-order-card__copy">${escapeHtml(truncateMessage(item.ultimo_mensaje || ""))}</p>
            <div class="admin-order-card__meta">
                <span>${escapeHtml(getActorLabel(item.ultimo_mensaje_tipo))}</span>
                <span>${escapeHtml(formatMessageDateTime(item.ultimo_mensaje_fecha))}</span>
                <span>Total: ${escapeHtml(String(item.total_mensajes || 0))}</span>
            </div>
            <a class="button button-secondary admin-order-card__action" href="${buildConversationDetailsUrl(detailsPage, item.pedido_id)}">Abrir conversacion</a>
        </article>
    `).join("");
}

function renderAdminConversationEmpty(tableBody, mobileList, message) {
    const safeMessage = escapeHtml(message || "No hay conversaciones registradas.");

    tableBody.innerHTML = `
        <tr>
            <td colspan="7">
                <div class="admin-empty-state">
                    <strong>Sin conversaciones</strong>
                    <p>${safeMessage}</p>
                </div>
            </td>
        </tr>
    `;
    mobileList.innerHTML = `
        <div class="admin-empty-state">
            <strong>Sin conversaciones</strong>
            <p>${safeMessage}</p>
        </div>
    `;
}

function renderConversationThread(container, messages, emptyMessage) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(messages) || messages.length === 0) {
        renderConversationEmptyState(container, emptyMessage);
        return;
    }

    container.innerHTML = messages.map((message) => {
        const actorType = String(message.tipo_usuario || "cliente");
        const attachment = message.attachment;
        const attachmentMarkup = attachment && attachment.download_url
            ? `
                <a class="button button-outline button-small conversation-attachment-link" href="${escapeAttribute(attachment.download_url)}" target="_blank" rel="noopener">
                    Descargar adjunto
                </a>
              `
            : "";

        return `
            <article class="conversation-item conversation-item--${escapeAttribute(actorType)}">
                <div class="conversation-bubble conversation-bubble--${escapeAttribute(actorType)}">
                    <div class="conversation-meta">
                        <strong>${escapeHtml(message.autor || getActorLabel(actorType))}</strong>
                        <span>${escapeHtml(formatMessageDateTime(message.created_at))}</span>
                    </div>
                    <p>${formatMessageBody(message.mensaje || "")}</p>
                    ${attachmentMarkup}
                </div>
            </article>
        `;
    }).join("");

    container.scrollTop = container.scrollHeight;
}

function renderConversationLoadingState(container, message) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.innerHTML = `
        <div class="conversation-empty">
            <span class="status-spinner" aria-hidden="true"></span>
            <strong>${escapeHtml(message || "Cargando mensajes...")}</strong>
            <p>Preparando historial asociado al pedido.</p>
        </div>
    `;
}

function renderConversationEmptyState(container, message) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.innerHTML = `
        <div class="conversation-empty">
            <strong>Sin mensajes</strong>
            <p>${escapeHtml(message || "No hay mensajes en esta conversacion.")}</p>
        </div>
    `;
}

function showMessageFeedback(element, message, state) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    element.textContent = message;
    element.classList.remove("is-error", "is-success", "is-loading", "is-info");

    if (!message) {
        return;
    }

    if (state === "error") {
        element.classList.add("is-error");
    } else if (state === "success") {
        element.classList.add("is-success");
    } else if (state === "loading") {
        element.classList.add("is-loading");
    } else {
        element.classList.add("is-info");
    }
}

function setMessageSubmitState(button, isLoading) {
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    button.disabled = isLoading;
    button.textContent = isLoading
        ? button.dataset.loadingLabel || "Enviando..."
        : button.dataset.defaultLabel || "Enviar mensaje";
}

function clearMessageForm(form, errorElement, feedbackElement) {
    if (form instanceof HTMLFormElement) {
        form.reset();
    }

    showInlineError(errorElement, "");
    showMessageFeedback(feedbackElement, "", "");
}

function showInlineError(element, message) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    element.textContent = message;
}

function getMessageTextValue(form) {
    if (!(form instanceof HTMLFormElement)) {
        return "";
    }

    const field = form.elements.namedItem("mensaje");

    if (!(field instanceof HTMLTextAreaElement)) {
        return "";
    }

    return String(field.value || "").trim();
}

function validateMessageText(value) {
    const normalizedValue = String(value || "").trim();

    if (normalizedValue.length < 5) {
        return "Escribe un mensaje de al menos 5 caracteres.";
    }

    if (normalizedValue.length > 2000) {
        return "El mensaje no puede superar 2000 caracteres.";
    }

    return "";
}

function createConversationStatusBadge(value, type) {
    const normalizedValue = String(value || "").toLowerCase();

    if (type === "author") {
        return `<span class="admin-status admin-status--neutral">${escapeHtml(getActorLabel(normalizedValue))}</span>`;
    }

    const classes = {
        pendiente: "admin-status--order-pending",
        pago_confirmado: "admin-status--order-paid",
        en_proceso: "admin-status--order-processing",
        terminado: "admin-status--order-finished",
        entregado: "admin-status--order-delivered",
        cancelado: "admin-status--order-cancelled",
    };

    return `
        <span class="admin-status ${classes[normalizedValue] || "admin-status--neutral"}">
            ${escapeHtml(getOrderStatusLabel(normalizedValue))}
        </span>
    `;
}

function buildConversationDetailsUrl(detailsPage, orderId) {
    return `${detailsPage}?order_id=${encodeURIComponent(String(orderId || ""))}`;
}

function getActorLabel(actorType) {
    return String(actorType || "").toLowerCase() === "admin" ? "Equipo" : "Cliente";
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

function truncateMessage(message) {
    const normalized = String(message || "").trim();

    return normalized.length > 84
        ? `${normalized.slice(0, 81)}...`
        : normalized;
}

function formatMessageDateTime(value) {
    if (!value) {
        return "Sin fecha";
    }

    const parsedDate = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(parsedDate.getTime())) {
        return "Sin fecha";
    }

    return new Intl.DateTimeFormat("es-MX", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(parsedDate);
}

function formatMessageBody(message) {
    return escapeHtml(String(message || "")).replace(/\n/g, "<br>");
}

async function fetchJson(endpoint) {
    return fetchJsonWithOptions(endpoint, {
        headers: {
            Accept: "application/json",
        },
        credentials: "same-origin",
    });
}

async function fetchJsonWithOptions(endpoint, options) {
    if (!endpoint) {
        throw new Error("No fue posible cargar los mensajes. Intenta nuevamente.");
    }

    const response = await fetch(endpoint, options);
    const responseData = await parseMessagesJsonResponse(response);

    if (!response.ok || responseData.success !== true) {
        throw new Error(responseData.message || "No fue posible completar la solicitud.");
    }

    return responseData;
}

async function parseMessagesJsonResponse(response) {
    try {
        return await response.json();
    } catch (error) {
        return {
            success: false,
            message: "No fue posible leer la respuesta. Intenta nuevamente.",
        };
    }
}

function escapeHtml(value) {
    return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, "&#96;");
}
