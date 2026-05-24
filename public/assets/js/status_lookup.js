document.addEventListener("DOMContentLoaded", () => {
    initStatusLookupForm();
});

const statusFieldRules = {
    numero_pedido: {
        required: true,
        validator: validateOrderNumberField,
    },
    correo: {
        required: true,
        validator: validateEmailField,
    },
};

function initStatusLookupForm() {
    const form = document.querySelector("[data-status-form]");

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const submitButton = form.querySelector("[data-submit-button]");
    const feedbackElement = form.querySelector("[data-form-feedback]");
    const resultElement = document.querySelector("[data-status-result]");

    bindStatusFieldEvents(form);
    applyLookupQueryParams(form);

    let isSubmitting = false;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        clearStatusFeedback(feedbackElement);

        if (!validateStatusForm(form)) {
            showStatusFeedback(feedbackElement, "Revisa los campos marcados antes de consultar.", "error");
            focusFirstInvalidField(form);
            return;
        }

        const payload = buildStatusPayload(form);
        isSubmitting = true;
        setStatusSubmitState(submitButton, true);
        showStatusFeedback(feedbackElement, "Consultando estado del pedido...", "loading");
        renderStatusLoadingState(resultElement);

        try {
            const response = await fetch(form.dataset.apiEndpoint || form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const responseData = await parseStatusJsonResponse(response);

            if (!response.ok || responseData.success !== true || !responseData.data) {
                applyServerErrors(form, responseData.errors || {});
                showStatusFeedback(
                    feedbackElement,
                    responseData.message || "No fue posible consultar el pedido.",
                    "error"
                );
                renderStatusEmptyState(
                    resultElement,
                    responseData.message || "No se encontro informacion para la consulta realizada."
                );
                dispatchStatusLookupEvent("status:cleared", null);
                focusFirstInvalidField(form);
                return;
            }

            form.dataset.orderNumber = payload.numero_pedido;
            form.dataset.orderEmail = payload.correo;
            clearAllFieldErrors(form);
            showStatusFeedback(
                feedbackElement,
                responseData.message || "Consulta realizada correctamente.",
                "success"
            );
            renderStatusResult(resultElement, responseData.data, form);
            dispatchStatusLookupEvent("status:loaded", {
                numero_pedido: payload.numero_pedido,
                correo: payload.correo,
                status: responseData.data,
            });
            resultElement.scrollIntoView({ behavior: "smooth", block: "nearest" });
        } catch (error) {
            showStatusFeedback(
                feedbackElement,
                "Ocurrio un problema al conectar con la API. Intenta nuevamente.",
                "error"
            );
            renderStatusEmptyState(resultElement, "No fue posible obtener el estado del pedido.");
            dispatchStatusLookupEvent("status:cleared", null);
        } finally {
            isSubmitting = false;
            setStatusSubmitState(submitButton, false);
        }
    });

    const queryNumber = form.querySelector('[name="numero_pedido"]');
    const queryEmail = form.querySelector('[name="correo"]');

    if (
        queryNumber instanceof HTMLInputElement
        && queryEmail instanceof HTMLInputElement
        && queryNumber.value.trim() !== ""
        && queryEmail.value.trim() !== ""
    ) {
        form.requestSubmit();
    }
}

function bindStatusFieldEvents(form) {
    Object.keys(statusFieldRules).forEach((fieldName) => {
        const fieldElement = form.querySelector(`[name="${fieldName}"]`);

        if (!(fieldElement instanceof HTMLInputElement)) {
            return;
        }

        fieldElement.addEventListener("blur", () => {
            validateStatusField(form, fieldName);
        });

        fieldElement.addEventListener("input", () => {
            if (fieldName === "numero_pedido") {
                fieldElement.value = sanitizeOrderNumber(fieldElement.value).toUpperCase();
            }

            if (fieldName === "correo") {
                fieldElement.value = sanitizeEmailValue(fieldElement.value);
            }

            clearFieldError(form, fieldName);
        });
    });
}

function applyLookupQueryParams(form) {
    const urlParameters = new URLSearchParams(window.location.search);
    const numeroPedidoField = form.querySelector('[name="numero_pedido"]');
    const correoField = form.querySelector('[name="correo"]');

    if (numeroPedidoField instanceof HTMLInputElement) {
        numeroPedidoField.value = sanitizeOrderNumber(urlParameters.get("numero_pedido")).toUpperCase();
    }

    if (correoField instanceof HTMLInputElement) {
        correoField.value = sanitizeEmailValue(urlParameters.get("correo"));
    }
}

function buildStatusPayload(form) {
    const formData = new FormData(form);
    const rawValues = Object.fromEntries(formData.entries());

    return {
        numero_pedido: sanitizeOrderNumber(rawValues.numero_pedido).toUpperCase(),
        correo: sanitizeEmailValue(rawValues.correo),
    };
}

function validateStatusForm(form) {
    let isValid = true;

    Object.keys(statusFieldRules).forEach((fieldName) => {
        const fieldIsValid = validateStatusField(form, fieldName);

        if (!fieldIsValid) {
            isValid = false;
        }
    });

    return isValid;
}

function validateStatusField(form, fieldName) {
    const fieldElement = form.querySelector(`[name="${fieldName}"]`);
    const rule = statusFieldRules[fieldName];

    if (!(fieldElement instanceof HTMLInputElement) || !rule) {
        return true;
    }

    const value = fieldName === "correo"
        ? sanitizeEmailValue(fieldElement.value)
        : sanitizeOrderNumber(fieldElement.value).toUpperCase();

    if (rule.required && value === "") {
        setFieldError(form, fieldName, rule.validator === validateEmailField
            ? "Ingresa el correo asociado."
            : "Ingresa tu numero de pedido."
        );
        return false;
    }

    if (typeof rule.validator === "function") {
        const validationMessage = rule.validator(value);

        if (validationMessage) {
            setFieldError(form, fieldName, validationMessage);
            return false;
        }
    }

    clearFieldError(form, fieldName);
    return true;
}

function validateOrderNumberField(value) {
    if (value.length < 5) {
        return "Ingresa un numero de pedido valido.";
    }

    return /^[A-Z0-9-]+$/.test(value)
        ? ""
        : "El numero de pedido solo admite letras, numeros y guiones.";
}

function validateEmailField(value) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailPattern.test(value) ? "" : "Ingresa un correo electronico valido.";
}

function renderStatusLoadingState(container) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.classList.remove("is-idle");
    container.innerHTML = `
        <span class="chip">Seguimiento</span>
        <div class="status-loading-state">
            <span class="status-spinner" aria-hidden="true"></span>
            <div>
                <h3>Consultando pedido...</h3>
                <p>Validando numero de pedido y correo asociado.</p>
            </div>
        </div>
        <div class="status-loading-grid" aria-hidden="true">
            <span class="status-skeleton status-skeleton--line"></span>
            <span class="status-skeleton status-skeleton--line"></span>
            <span class="status-skeleton status-skeleton--line status-skeleton--short"></span>
            <span class="status-skeleton status-skeleton--card"></span>
        </div>
    `;
}

function renderStatusEmptyState(container, message) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.classList.add("is-idle");
    container.innerHTML = `
        <span class="chip">Seguimiento</span>
        <h3>Sin resultados</h3>
        <p>${escapeHtml(message || "No se encontro informacion para la consulta realizada.")}</p>
    `;
}

function renderStatusResult(container, data, form) {
    if (!(container instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
        return;
    }

    const orderStatusClass = getStatusBadgeClass(data.estado_pedido);
    const paymentStatusClass = getPaymentBadgeClass(data.estado_pago);
    const timelineMarkup = buildTimelineMarkup(data.timeline || []);
    const deliveryMarkup = buildDeliveryMarkup(data.entrega || {}, form);
    const paymentActionMarkup = buildPaymentActionMarkup(data);

    container.classList.remove("is-idle");
    container.innerHTML = `
        <span class="chip">Pedido validado</span>
        <div class="status-result-header">
            <div>
                <h3>${escapeHtml(data.numero_pedido || "Pedido sin numero")}</h3>
                <p>${escapeHtml(data.mensaje_estado || "Consulta realizada correctamente.")}</p>
            </div>
            <span class="status-badge ${orderStatusClass}">
                ${escapeHtml(getOrderStatusLabel(data.estado_pedido))}
            </span>
        </div>

        <div class="status-summary-grid">
            <div class="status-summary-item">
                <span>Estado pedido</span>
                <strong>${escapeHtml(getOrderStatusLabel(data.estado_pedido))}</strong>
            </div>
            <div class="status-summary-item">
                <span>Estado pago</span>
                <strong class="status-payment-badge ${paymentStatusClass}">
                    ${escapeHtml(getPaymentStatusLabel(data.estado_pago))}
                </strong>
            </div>
            <div class="status-summary-item">
                <span>Fecha evento</span>
                <strong>${escapeHtml(formatStatusDate(data.fecha_evento))}</strong>
            </div>
            <div class="status-summary-item">
                <span>Formato entrega</span>
                <strong>${escapeHtml(getDeliveryFormatLabel(data.formato_entrega))}</strong>
            </div>
        </div>

        <div class="status-detail-grid">
            <article class="status-detail-card">
                <span>Tiempo estimado</span>
                <strong>${escapeHtml(data.tiempo_estimado || "Sin estimacion")}</strong>
            </article>
            <article class="status-detail-card">
                <span>Ultima actualizacion</span>
                <strong>${escapeHtml(formatStatusDateTime(data.ultima_actualizacion))}</strong>
            </article>
        </div>

        ${paymentActionMarkup}

        <section class="status-timeline-panel" aria-label="Progreso del pedido">
            <h4>Timeline de avance</h4>
            <ul class="status-timeline status-timeline--rich">
                ${timelineMarkup}
            </ul>
        </section>

        ${deliveryMarkup}
    `;

    const downloadButton = container.querySelector("[data-download-button]");

    if (downloadButton instanceof HTMLButtonElement) {
        downloadButton.addEventListener("click", async () => {
            await handleDeliveryDownload(downloadButton, form);
        });
    }
}

function buildPaymentActionMarkup(data) {
    const canProcessPayment = data && data.can_process_payment === true;
    const paymentUrl = typeof data?.payment_url === "string" ? data.payment_url.trim() : "";

    if (!canProcessPayment || paymentUrl === "") {
        return "";
    }

    return `
        <section class="status-delivery-card">
            <div>
                <h4>Pago pendiente</h4>
                <p>Tu pedido aun admite pago simulado. Puedes continuar desde aqui sin volver a buscar el folio.</p>
            </div>
            <a class="button button-primary" href="${escapeAttribute(paymentUrl)}">Pagar ahora</a>
        </section>
    `;
}

function buildTimelineMarkup(timeline) {
    if (!Array.isArray(timeline) || timeline.length === 0) {
        return "";
    }

    return timeline.map((step) => {
        const stepState = typeof step.state === "string" ? step.state : "upcoming";
        const itemClass = stepState === "complete"
            ? "is-complete"
            : stepState === "current"
                ? "is-current"
                : "";

        return `
            <li class="${itemClass}">
                <strong>${escapeHtml(step.label || "Paso")}</strong>
                <span>${escapeHtml(step.detail || "")}</span>
            </li>
        `;
    }).join("");
}

function buildDeliveryMarkup(delivery, form) {
    const deliveryAvailable = delivery && delivery.disponible === true;

    if (!deliveryAvailable) {
        return `
            <section class="status-delivery-card">
                <div>
                    <h4>Entrega final</h4>
                    <p>${escapeHtml(delivery.confirmacion || "La entrega final aun no esta disponible.")}</p>
                </div>
            </section>
        `;
    }

    return `
        <section class="status-delivery-card">
            <div>
                <h4>Entrega final disponible</h4>
                <p>${escapeHtml(delivery.confirmacion || "La entrega final esta lista para descarga.")}</p>
                <p class="status-inline-note">
                    Formato: ${escapeHtml(getDeliveryFormatLabel(delivery.formato_entrega))}
                    | Fecha: ${escapeHtml(formatStatusDateTime(delivery.fecha_entrega))}
                </p>
            </div>
            <button
                class="button button-secondary"
                type="button"
                data-download-button
                data-default-label="Descargar archivo"
                data-loading-label="Preparando descarga..."
            >
                Descargar archivo
            </button>
        </section>
    `;
}

async function handleDeliveryDownload(button, form) {
    const downloadEndpoint = form.dataset.downloadEndpoint;
    const feedbackElement = form.querySelector("[data-form-feedback]");
    const payload = {
        numero_pedido: String(form.dataset.orderNumber || ""),
        correo: String(form.dataset.orderEmail || ""),
    };

    if (!(button instanceof HTMLButtonElement) || !downloadEndpoint || !payload.numero_pedido || !payload.correo) {
        return;
    }

    button.disabled = true;
    button.textContent = button.dataset.loadingLabel || "Preparando descarga...";
    showStatusFeedback(feedbackElement, "Preparando descarga de la entrega final...", "loading");

    try {
        const response = await fetch(downloadEndpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/octet-stream,application/json",
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorData = await parseStatusJsonResponse(response);
            throw new Error(errorData.message || "No fue posible descargar el archivo.");
        }

        const fileBlob = await response.blob();
        const objectUrl = URL.createObjectURL(fileBlob);
        const downloadLink = document.createElement("a");
        const fileName = resolveDownloadFileName(response.headers.get("Content-Disposition"));

        downloadLink.href = objectUrl;
        downloadLink.download = fileName || `${payload.numero_pedido}.pdf`;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        downloadLink.remove();
        URL.revokeObjectURL(objectUrl);

        showStatusFeedback(feedbackElement, "La descarga se preparo correctamente.", "success");
    } catch (error) {
        showStatusFeedback(
            feedbackElement,
            error instanceof Error ? error.message : "No fue posible descargar el archivo.",
            "error"
        );
    } finally {
        button.disabled = false;
        button.textContent = button.dataset.defaultLabel || "Descargar archivo";
    }
}

function resolveDownloadFileName(contentDisposition) {
    if (!contentDisposition) {
        return "";
    }

    const utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);

    if (utfMatch && utfMatch[1]) {
        return decodeURIComponent(utfMatch[1]);
    }

    const simpleMatch = contentDisposition.match(/filename="([^"]+)"/i);

    if (simpleMatch && simpleMatch[1]) {
        return simpleMatch[1];
    }

    return "";
}

function setFieldError(form, fieldName, message) {
    const fieldContainer = form.querySelector(`[data-field-container="${fieldName}"]`);
    const errorElement = form.querySelector(`[data-error-for="${fieldName}"]`);
    const fieldElement = form.querySelector(`[name="${fieldName}"]`);

    if (fieldContainer instanceof HTMLElement) {
        fieldContainer.classList.add("is-invalid");
    }

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = message;
    }

    if (fieldElement instanceof HTMLElement) {
        fieldElement.setAttribute("aria-invalid", "true");
    }
}

function clearFieldError(form, fieldName) {
    const fieldContainer = form.querySelector(`[data-field-container="${fieldName}"]`);
    const errorElement = form.querySelector(`[data-error-for="${fieldName}"]`);
    const fieldElement = form.querySelector(`[name="${fieldName}"]`);

    if (fieldContainer instanceof HTMLElement) {
        fieldContainer.classList.remove("is-invalid");
    }

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = "";
    }

    if (fieldElement instanceof HTMLElement) {
        fieldElement.removeAttribute("aria-invalid");
    }
}

function clearAllFieldErrors(form) {
    Object.keys(statusFieldRules).forEach((fieldName) => {
        clearFieldError(form, fieldName);
    });
}

function applyServerErrors(form, errors) {
    clearAllFieldErrors(form);

    Object.entries(errors).forEach(([fieldName, fieldErrors]) => {
        const firstErrorMessage = Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors;

        if (typeof firstErrorMessage === "string" && firstErrorMessage.trim() !== "") {
            setFieldError(form, fieldName, firstErrorMessage);
        }
    });
}

function focusFirstInvalidField(form) {
    const firstInvalidField = form.querySelector("[aria-invalid='true']");

    if (firstInvalidField instanceof HTMLElement) {
        firstInvalidField.focus();
    }
}

function setStatusSubmitState(submitButton, isLoading) {
    if (!(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    submitButton.disabled = isLoading;
    submitButton.textContent = isLoading
        ? submitButton.dataset.loadingLabel || "Consultando..."
        : submitButton.dataset.defaultLabel || "Consultar estado";
}

function showStatusFeedback(feedbackElement, message, state) {
    if (!(feedbackElement instanceof HTMLElement)) {
        return;
    }

    feedbackElement.textContent = message;
    feedbackElement.classList.remove("is-error", "is-success", "is-loading", "is-info");

    if (state === "error") {
        feedbackElement.classList.add("is-error");
    }

    if (state === "success") {
        feedbackElement.classList.add("is-success");
    }

    if (state === "loading") {
        feedbackElement.classList.add("is-loading");
    }

    if (state === "info") {
        feedbackElement.classList.add("is-info");
    }
}

function clearStatusFeedback(feedbackElement) {
    if (!(feedbackElement instanceof HTMLElement)) {
        return;
    }

    feedbackElement.textContent = "";
    feedbackElement.classList.remove("is-error", "is-success", "is-loading", "is-info");
}

function sanitizeOrderNumber(value) {
    return String(value || "")
        .replace(/[^\w-]/g, "")
        .replace(/\s+/g, "")
        .trim()
        .slice(0, 40);
}

function sanitizeEmailValue(value) {
    return String(value || "")
        .replace(/[<>]/g, "")
        .replace(/\s+/g, "")
        .trim()
        .toLowerCase()
        .slice(0, 150);
}

function formatStatusDate(dateValue) {
    if (!dateValue) {
        return "Sin fecha";
    }

    const normalizedDate = String(dateValue).slice(0, 10);
    const date = new Date(`${normalizedDate}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return "Sin fecha";
    }

    return new Intl.DateTimeFormat("es-MX", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
}

function formatStatusDateTime(dateValue) {
    if (!dateValue) {
        return "Sin actualizacion";
    }

    const normalizedValue = String(dateValue).replace(" ", "T");
    const date = new Date(normalizedValue);

    if (Number.isNaN(date.getTime())) {
        return "Sin actualizacion";
    }

    return new Intl.DateTimeFormat("es-MX", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
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

    return labels[status] || "Sin estado";
}

function getPaymentStatusLabel(status) {
    const labels = {
        pendiente: "Pendiente",
        confirmado: "Confirmado",
        rechazado: "Rechazado",
        reembolsado: "Reembolsado",
    };

    return labels[status] || "Sin estado";
}

function getDeliveryFormatLabel(format) {
    const labels = {
        imagen: "Imagen",
        pdf: "PDF",
        video: "Video",
    };

    return labels[format] || "Sin definir";
}

function getStatusBadgeClass(status) {
    const classes = {
        pendiente: "status-badge--pending",
        pago_confirmado: "status-badge--paid",
        en_proceso: "status-badge--processing",
        terminado: "status-badge--finished",
        entregado: "status-badge--delivered",
        cancelado: "status-badge--cancelled",
    };

    return classes[status] || "status-badge--pending";
}

function getPaymentBadgeClass(status) {
    const classes = {
        pendiente: "status-payment-badge--pending",
        confirmado: "status-payment-badge--confirmed",
        rechazado: "status-payment-badge--rejected",
        reembolsado: "status-payment-badge--refunded",
    };

    return classes[status] || "status-payment-badge--pending";
}

async function parseStatusJsonResponse(response) {
    try {
        return await response.json();
    } catch (error) {
        return {
            success: false,
            message: "La API devolvio una respuesta invalida.",
            errors: {},
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
    return escapeHtml(value);
}

function dispatchStatusLookupEvent(eventName, detail) {
    document.dispatchEvent(new CustomEvent(eventName, { detail }));
}
