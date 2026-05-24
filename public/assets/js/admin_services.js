document.addEventListener("DOMContentLoaded", () => {
    initAdminServicesPage();
    initAdminServiceFormPage();
});

const adminServiceFormRules = {
    nombre: { required: true, minLength: 3, maxLength: 150, message: "Ingresa el nombre del servicio." },
    descripcion: { required: true, minLength: 10, maxLength: 1000, message: "Ingresa una descripcion clara del servicio." },
    categoria: { required: true, minLength: 3, maxLength: 100, message: "Ingresa una categoria valida." },
    precio: { required: true, validator: validateAdminServicePrice },
    formato_entrega: { required: true, allowedValues: ["imagen", "pdf", "video"], message: "Selecciona un formato valido." },
    tiempo_entrega: { required: true, minLength: 3, maxLength: 100, message: "Ingresa el tiempo estimado de entrega." },
    imagen_referencia: { required: false, minLength: 3, maxLength: 255, optionalMessage: "Si agregas una imagen, usa al menos 3 caracteres." },
    activo: { required: true, allowedValues: ["1", "0"], message: "Selecciona un estado valido." },
};

function initAdminServicesPage() {
    const page = document.querySelector("[data-admin-services-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const form = page.querySelector("[data-services-filter-form]");
    const feedback = page.querySelector("[data-services-feedback]");
    const endpoint = page.dataset.servicesEndpoint || "";
    const toggleEndpoint = page.dataset.toggleEndpoint || "";

    if (!(form instanceof HTMLFormElement) || endpoint === "" || toggleEndpoint === "") {
        return;
    }

    const filters = {
        search: getAdminServiceFieldValue(form, "search"),
        activo: getAdminServiceFieldValue(form, "activo"),
    };

    const load = async () => {
        await loadAdminServicesList(page, endpoint, filters, feedback);
    };

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        filters.search = getAdminServiceFieldValue(form, "search");
        filters.activo = getAdminServiceFieldValue(form, "activo");
        syncAdminServicesUrl(filters);
        await load();
    });

    const clearButton = page.querySelector("[data-clear-service-filters]");

    if (clearButton instanceof HTMLButtonElement) {
        clearButton.addEventListener("click", async () => {
            form.reset();
            filters.search = "";
            filters.activo = "";
            syncAdminServicesUrl(filters);
            await load();
        });
    }

    page.addEventListener("click", async (event) => {
        const toggleButton = event.target instanceof HTMLElement ? event.target.closest("[data-toggle-service]") : null;

        if (!(toggleButton instanceof HTMLButtonElement)) {
            return;
        }

        const serviceId = Number(toggleButton.dataset.serviceId || 0);
        const nextActive = toggleButton.dataset.nextActive || "0";

        if (!Number.isInteger(serviceId) || serviceId <= 0) {
            return;
        }

        await toggleAdminService(toggleButton, toggleEndpoint, serviceId, nextActive, feedback, load);
    });

    load();
}

async function loadAdminServicesList(page, endpoint, filters, feedback) {
    const url = new URL(endpoint, window.location.href);

    if (filters.search) {
        url.searchParams.set("search", filters.search);
    } else {
        url.searchParams.delete("search");
    }

    if (filters.activo) {
        url.searchParams.set("activo", filters.activo);
    } else {
        url.searchParams.delete("activo");
    }

    try {
        const responseData = await fetchAdminServicesJson(url.toString(), "GET");
        const data = responseData.data || {};
        const services = Array.isArray(data.services) ? data.services : [];
        const summary = data.summary || {};

        renderAdminServicesTable(page, services);
        renderAdminServicesMobile(page, services);
        renderAdminServicesSummary(page, summary, filters);
        setAdminServiceFeedback(feedback, "", "info", true);
    } catch (error) {
        renderAdminServicesTable(page, []);
        renderAdminServicesMobile(page, []);
        renderAdminServicesSummary(page, { total: 0, active: 0, inactive: 0 }, filters);
        setAdminServiceFeedback(feedback, error.message || "No fue posible cargar el catalogo de servicios.", "error", false);
    }
}

function renderAdminServicesTable(page, services) {
    const tableBody = page.querySelector("[data-services-table-body]");
    const editPage = page.dataset.editPage || "./edit.php";

    if (!(tableBody instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(services) || services.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="admin-empty-state">
                        <strong>No hay servicios registrados</strong>
                        <p>Crea el primer servicio o ajusta los filtros del listado.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = services.map((service) => `
        <tr>
            <td>
                <div class="admin-table__cell-stack">
                    <strong>${escapeAdminServiceHtml(service.nombre || "Servicio")}</strong>
                    <span>${escapeAdminServiceHtml(service.tiempo_entrega || "Tiempo no definido")}</span>
                </div>
            </td>
            <td>${escapeAdminServiceHtml(service.categoria || "Sin categoria")}</td>
            <td>${formatAdminServiceCurrency(service.precio)}</td>
            <td>${escapeAdminServiceHtml(service.formato_entrega_label || "Sin formato")}</td>
            <td>${createAdminServiceStatusBadge(Boolean(service.activo))}</td>
            <td>
                <div class="admin-actions-row">
                    <a class="button button-secondary admin-button-compact" href="${buildAdminServiceEditUrl(editPage, service.id)}">Editar</a>
                    <button
                        type="button"
                        class="button button-outline admin-button-compact"
                        data-toggle-service
                        data-service-id="${Number(service.id || 0)}"
                        data-next-active="${service.activo ? "0" : "1"}"
                    >
                        ${service.activo ? "Ocultar" : "Activar"}
                    </button>
                </div>
            </td>
        </tr>
    `).join("");
}

function renderAdminServicesMobile(page, services) {
    const container = page.querySelector("[data-services-mobile-list]");
    const editPage = page.dataset.editPage || "./edit.php";

    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(services) || services.length === 0) {
        container.innerHTML = `
            <article class="admin-empty-state">
                <strong>No hay servicios registrados</strong>
                <p>El listado se llenara conforme registres servicios activos u ocultos.</p>
            </article>
        `;
        return;
    }

    container.innerHTML = services.map((service) => `
        <article class="admin-service-card">
            <div class="admin-service-card__header">
                <div>
                    <p class="admin-order-card__label">${escapeAdminServiceHtml(service.categoria || "Categoria")}</p>
                    <p class="admin-order-card__title">${escapeAdminServiceHtml(service.nombre || "Servicio")}</p>
                </div>
                ${createAdminServiceStatusBadge(Boolean(service.activo))}
            </div>
            <p class="admin-order-card__copy">${escapeAdminServiceHtml(service.descripcion || "Sin descripcion disponible.")}</p>
            <div class="admin-order-card__meta">
                <span>${formatAdminServiceCurrency(service.precio)}</span>
                <span>${escapeAdminServiceHtml(service.formato_entrega_label || "Sin formato")}</span>
                <span>${escapeAdminServiceHtml(service.tiempo_entrega || "Tiempo sin definir")}</span>
            </div>
            <div class="admin-actions-row">
                <a class="button button-secondary admin-button-compact" href="${buildAdminServiceEditUrl(editPage, service.id)}">Editar</a>
                <button
                    type="button"
                    class="button button-outline admin-button-compact"
                    data-toggle-service
                    data-service-id="${Number(service.id || 0)}"
                    data-next-active="${service.activo ? "0" : "1"}"
                >
                    ${service.activo ? "Ocultar" : "Activar"}
                </button>
            </div>
        </article>
    `).join("");
}

function renderAdminServicesSummary(page, summary, filters) {
    const chip = page.querySelector("[data-services-summary-chip]");
    const summaryText = page.querySelector("[data-services-summary-text]");
    const filtersText = page.querySelector("[data-services-filters-text]");
    const total = Number(summary.total || 0);
    const active = Number(summary.active || 0);
    const inactive = Number(summary.inactive || 0);

    if (chip instanceof HTMLElement) {
        chip.textContent = `${active} activos / ${inactive} ocultos`;
    }

    if (summaryText instanceof HTMLElement) {
        summaryText.textContent = total === 0 ? "No hay servicios para mostrar con los filtros actuales." : `Mostrando ${total} servicio(s) del catalogo administrativo.`;
    }

    if (filtersText instanceof HTMLElement) {
        const appliedFilters = [];

        if (filters.search) {
            appliedFilters.push(`Busqueda: ${filters.search}`);
        }

        if (filters.activo === "1") {
            appliedFilters.push("Visibilidad: Activos");
        }

        if (filters.activo === "0") {
            appliedFilters.push("Visibilidad: Ocultos");
        }

        filtersText.textContent = appliedFilters.length > 0 ? appliedFilters.join(" | ") : "Sin filtros aplicados.";
    }
}

async function toggleAdminService(button, endpoint, serviceId, nextActive, feedback, reload) {
    button.disabled = true;

    try {
        const responseData = await fetchAdminServicesJson(endpoint, "PUT", { id: serviceId, activo: nextActive });
        setAdminServiceFeedback(feedback, responseData.message || "Estado actualizado.", "success", false);
        await reload();
    } catch (error) {
        setAdminServiceFeedback(feedback, error.message || "No fue posible actualizar el estado del servicio.", "error", false);
    } finally {
        button.disabled = false;
    }
}

function initAdminServiceFormPage() {
    const page = document.querySelector("[data-admin-service-form-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const form = page.querySelector("[data-admin-service-form]");
    const feedback = page.querySelector("[data-service-form-feedback]");

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const mode = page.dataset.mode || "create";
    const serviceId = Number(page.dataset.serviceId || form.querySelector('[name="id"]')?.value || 0);
    const detailsEndpoint = page.dataset.detailsEndpoint || "";
    const createEndpoint = page.dataset.createEndpoint || "";
    const updateEndpoint = page.dataset.updateEndpoint || "";
    const editPageUrl = page.dataset.editPageUrl || "./edit.php";

    Object.keys(adminServiceFormRules).forEach((fieldName) => {
        form.querySelectorAll(`[name="${fieldName}"]`).forEach((fieldElement) => {
            fieldElement.addEventListener("input", () => {
                clearAdminServiceFieldError(form, fieldName);
                syncAdminServicePreview(form, page);
            });

            fieldElement.addEventListener("change", () => {
                validateAdminServiceField(form, fieldName);
                syncAdminServicePreview(form, page);
            });
        });
    });

    if (mode === "edit" && serviceId > 0 && detailsEndpoint !== "") {
        loadAdminServiceDetails(form, page, feedback, detailsEndpoint, serviceId);
    } else {
        syncAdminServicePreview(form, page);
    }

    let isSubmitting = false;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        clearAllAdminServiceErrors(form);

        if (!validateAdminServiceForm(form)) {
            setAdminServiceFeedback(feedback, "Revisa los campos marcados antes de guardar.", "error", false);
            focusFirstAdminServiceInvalidField(form);
            return;
        }

        const payload = buildAdminServicePayload(form, mode, serviceId);
        const endpoint = mode === "edit" ? updateEndpoint : createEndpoint;
        const method = mode === "edit" ? "PUT" : "POST";
        const submitButton = form.querySelector("[data-service-submit-button]");

        isSubmitting = true;
        setAdminServiceSubmitState(submitButton, true);
        setAdminServiceFeedback(feedback, "Guardando informacion del servicio...", "info", false);

        try {
            const responseData = await fetchAdminServicesJson(endpoint, method, payload);
            const service = responseData.data?.service || null;

            if (service) {
                fillAdminServiceForm(form, service);
                syncAdminServicePreview(form, page);
            }

            if (mode === "create" && service && Number(service.id || 0) > 0) {
                window.location.href = `${buildAdminServiceEditUrl(editPageUrl, service.id)}&created=1`;
                return;
            }

            setAdminServiceFeedback(feedback, responseData.message || "Servicio actualizado correctamente.", "success", false);
        } catch (error) {
            if (error && typeof error === "object" && error.validationErrors) {
                applyAdminServiceServerErrors(form, error.validationErrors);
                focusFirstAdminServiceInvalidField(form);
            }

            setAdminServiceFeedback(feedback, error.message || "No fue posible guardar el servicio.", "error", false);
        } finally {
            isSubmitting = false;
            setAdminServiceSubmitState(submitButton, false);
        }
    });
}

async function loadAdminServiceDetails(form, page, feedback, endpoint, serviceId) {
    try {
        const url = new URL(endpoint, window.location.href);
        url.searchParams.set("id", String(serviceId));

        const responseData = await fetchAdminServicesJson(url.toString(), "GET");
        const service = responseData.data?.service || null;

        if (!service) {
            throw new Error("No fue posible cargar el servicio solicitado.");
        }

        fillAdminServiceForm(form, service);
        syncAdminServicePreview(form, page);
        setAdminServiceFeedback(feedback, "", "info", true);
    } catch (error) {
        setAdminServiceFeedback(feedback, error.message || "No fue posible cargar la informacion del servicio.", "error", false);
    }
}

function fillAdminServiceForm(form, service) {
    const fieldMap = {
        id: service.id,
        nombre: service.nombre,
        descripcion: service.descripcion,
        categoria: service.categoria,
        precio: service.precio,
        formato_entrega: service.formato_entrega,
        tiempo_entrega: service.tiempo_entrega,
        imagen_referencia: service.imagen_referencia,
        activo: service.activo ? "1" : "0",
    };

    Object.entries(fieldMap).forEach(([fieldName, value]) => {
        const field = form.querySelector(`[name="${fieldName}"]`);

        if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
            field.value = value === null || value === undefined ? "" : String(value);
        }
    });
}

function validateAdminServiceForm(form) {
    let isValid = true;

    Object.keys(adminServiceFormRules).forEach((fieldName) => {
        const fieldIsValid = validateAdminServiceField(form, fieldName);

        if (!fieldIsValid) {
            isValid = false;
        }
    });

    return isValid;
}

function validateAdminServiceField(form, fieldName) {
    const rule = adminServiceFormRules[fieldName];

    if (!rule) {
        return true;
    }

    const value = normalizeAdminServiceFieldValue(fieldName, getAdminServiceFieldValue(form, fieldName));

    if (rule.required && value === "") {
        setAdminServiceFieldError(form, fieldName, rule.message || "Este campo es obligatorio.");
        return false;
    }

    if (!rule.required && value === "") {
        clearAdminServiceFieldError(form, fieldName);
        return true;
    }

    if (Array.isArray(rule.allowedValues) && !rule.allowedValues.includes(value)) {
        setAdminServiceFieldError(form, fieldName, rule.message || "Selecciona una opcion valida.");
        return false;
    }

    if (typeof rule.minLength === "number" && value.length < rule.minLength) {
        setAdminServiceFieldError(form, fieldName, rule.optionalMessage || rule.message || "El valor es demasiado corto.");
        return false;
    }

    if (typeof rule.maxLength === "number" && value.length > rule.maxLength) {
        setAdminServiceFieldError(form, fieldName, `El campo no debe superar ${rule.maxLength} caracteres.`);
        return false;
    }

    if (typeof rule.validator === "function") {
        const validationMessage = rule.validator(value);

        if (validationMessage) {
            setAdminServiceFieldError(form, fieldName, validationMessage);
            return false;
        }
    }

    clearAdminServiceFieldError(form, fieldName);
    return true;
}

function validateAdminServicePrice(value) {
    if (value === "" || Number.isNaN(Number(value))) {
        return "Ingresa un precio numerico valido.";
    }

    if (Number(value) < 0) {
        return "El precio no puede ser menor a 0.";
    }

    return "";
}

function buildAdminServicePayload(form, mode, serviceId) {
    const payload = {
        nombre: sanitizeAdminServiceText(getAdminServiceFieldValue(form, "nombre"), 150),
        descripcion: sanitizeAdminServiceText(getAdminServiceFieldValue(form, "descripcion"), 1000),
        categoria: sanitizeAdminServiceText(getAdminServiceFieldValue(form, "categoria"), 100),
        precio: sanitizeAdminServiceNumeric(getAdminServiceFieldValue(form, "precio")),
        formato_entrega: sanitizeAdminServiceChoice(getAdminServiceFieldValue(form, "formato_entrega")),
        tiempo_entrega: sanitizeAdminServiceText(getAdminServiceFieldValue(form, "tiempo_entrega"), 100),
        imagen_referencia: sanitizeAdminServiceText(getAdminServiceFieldValue(form, "imagen_referencia"), 255),
        activo: sanitizeAdminServiceChoice(getAdminServiceFieldValue(form, "activo")),
    };

    if (mode === "edit") {
        payload.id = serviceId;
    }

    return payload;
}

function syncAdminServicePreview(form, page) {
    const name = sanitizeAdminServiceText(getAdminServiceFieldValue(form, "nombre"), 150);
    const description = sanitizeAdminServiceText(getAdminServiceFieldValue(form, "descripcion"), 1000);
    const category = sanitizeAdminServiceText(getAdminServiceFieldValue(form, "categoria"), 100);
    const format = sanitizeAdminServiceChoice(getAdminServiceFieldValue(form, "formato_entrega"));
    const price = sanitizeAdminServiceNumeric(getAdminServiceFieldValue(form, "precio"));
    const time = sanitizeAdminServiceText(getAdminServiceFieldValue(form, "tiempo_entrega"), 100);
    const imagePath = sanitizeAdminServiceText(getAdminServiceFieldValue(form, "imagen_referencia"), 255);
    const active = sanitizeAdminServiceChoice(getAdminServiceFieldValue(form, "activo")) !== "0";
    const resolvedImagePath = resolveAdminServicePreviewImage(imagePath);

    setTextContent(page.querySelector("[data-service-preview-name]"), name || "Nuevo servicio");
    setTextContent(page.querySelector("[data-service-preview-description]"), description || "La descripcion aparecera aqui conforme completes el formulario.");
    setTextContent(page.querySelector("[data-service-preview-price]"), formatAdminServiceCurrency(price || 0));
    setTextContent(page.querySelector("[data-service-preview-category]"), category || "Sin definir");
    setTextContent(page.querySelector("[data-service-preview-format]"), getAdminServiceFormatLabel(format));
    setTextContent(page.querySelector("[data-service-preview-time]"), time || "Sin definir");
    setTextContent(page.querySelector("[data-service-preview-image-text]"), imagePath ? "Ruta capturada" : "Placeholder");
    setTextContent(page.querySelector("[data-service-preview-status]"), active ? "Activo" : "Oculto");

    const imageElement = page.querySelector("[data-service-preview-image]");
    const placeholderElement = page.querySelector("[data-service-preview-placeholder]");

    if (imageElement instanceof HTMLImageElement) {
        if (resolvedImagePath) {
            imageElement.src = resolvedImagePath;
            imageElement.hidden = false;
        } else {
            imageElement.hidden = true;
            imageElement.removeAttribute("src");
        }
    }

    if (placeholderElement instanceof HTMLElement) {
        placeholderElement.hidden = resolvedImagePath !== "";
        placeholderElement.textContent = getAdminServiceInitials(name || category || "IS");
    }
}

function applyAdminServiceServerErrors(form, errors) {
    Object.entries(errors || {}).forEach(([fieldName, fieldErrors]) => {
        const firstError = Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors;

        if (typeof firstError === "string" && firstError.trim() !== "") {
            setAdminServiceFieldError(form, fieldName, firstError);
        }
    });
}

function setAdminServiceFieldError(form, fieldName, message) {
    const fieldContainer = form.querySelector(`[data-field-container="${fieldName}"]`);
    const errorElement = form.querySelector(`[data-error-for="${fieldName}"]`);
    const fieldElements = form.querySelectorAll(`[name="${fieldName}"]`);

    if (fieldContainer instanceof HTMLElement) {
        fieldContainer.classList.add("is-invalid");
    }

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = message;
    }

    fieldElements.forEach((fieldElement) => {
        fieldElement.setAttribute("aria-invalid", "true");
    });
}

function clearAdminServiceFieldError(form, fieldName) {
    const fieldContainer = form.querySelector(`[data-field-container="${fieldName}"]`);
    const errorElement = form.querySelector(`[data-error-for="${fieldName}"]`);
    const fieldElements = form.querySelectorAll(`[name="${fieldName}"]`);

    if (fieldContainer instanceof HTMLElement) {
        fieldContainer.classList.remove("is-invalid");
    }

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = "";
    }

    fieldElements.forEach((fieldElement) => {
        fieldElement.removeAttribute("aria-invalid");
    });
}

function clearAllAdminServiceErrors(form) {
    Object.keys(adminServiceFormRules).forEach((fieldName) => {
        clearAdminServiceFieldError(form, fieldName);
    });
}

function focusFirstAdminServiceInvalidField(form) {
    const firstInvalidField = form.querySelector("[aria-invalid='true']");

    if (firstInvalidField instanceof HTMLElement) {
        firstInvalidField.focus();
    }
}

async function fetchAdminServicesJson(endpoint, method, payload = null) {
    const options = {
        method,
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    };

    if (payload !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(payload);
    }

    const response = await fetch(endpoint, options);
    let responseData = null;

    try {
        responseData = await response.json();
    } catch (error) {
        throw new Error("La API devolvio una respuesta invalida.");
    }

    if (!response.ok || responseData.success !== true) {
        const requestError = new Error(responseData.message || "No fue posible completar la solicitud.");
        requestError.validationErrors = responseData.errors || {};
        throw requestError;
    }

    return responseData;
}

function setAdminServiceFeedback(element, message, state, hidden) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    element.hidden = hidden;
    element.textContent = message;
    element.classList.remove("is-info", "is-success");

    if (!hidden && state === "info") {
        element.classList.add("is-info");
    }

    if (!hidden && state === "success") {
        element.classList.add("is-success");
    }
}

function setAdminServiceSubmitState(button, isLoading) {
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    button.disabled = isLoading;
    button.textContent = isLoading ? button.dataset.loadingLabel || "Procesando..." : button.dataset.defaultLabel || "Guardar";
}

function createAdminServiceStatusBadge(isActive) {
    return `<span class="admin-status ${isActive ? "admin-status--success" : "admin-status--danger"}">${isActive ? "Activo" : "Oculto"}</span>`;
}

function buildAdminServiceEditUrl(basePath, serviceId) {
    return `${basePath}?id=${encodeURIComponent(String(serviceId || ""))}`;
}

function syncAdminServicesUrl(filters) {
    const url = new URL(window.location.href);

    if (filters.search) {
        url.searchParams.set("search", filters.search);
    } else {
        url.searchParams.delete("search");
    }

    if (filters.activo) {
        url.searchParams.set("activo", filters.activo);
    } else {
        url.searchParams.delete("activo");
    }

    window.history.replaceState({}, "", url.toString());
}

function getAdminServiceFieldValue(form, fieldName) {
    const field = form.querySelector(`[name="${fieldName}"]`);

    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
        return field.value;
    }

    return "";
}

function normalizeAdminServiceFieldValue(fieldName, value) {
    switch (fieldName) {
        case "precio":
            return sanitizeAdminServiceNumeric(value);
        case "formato_entrega":
        case "activo":
            return sanitizeAdminServiceChoice(value);
        default:
            return sanitizeAdminServiceText(value);
    }
}

function sanitizeAdminServiceText(value, maxLength = Infinity) {
    const normalizedValue = String(value || "").replace(/[<>]/g, "").replace(/\s+/g, " ").trim();
    return normalizedValue.slice(0, maxLength);
}

function sanitizeAdminServiceChoice(value) {
    return String(value || "").replace(/[^\w-]/g, "").trim().toLowerCase();
}

function sanitizeAdminServiceNumeric(value) {
    return String(value || "").replace(/[^0-9.]/g, "").trim();
}

function getAdminServiceFormatLabel(format) {
    const labels = { imagen: "Imagen", pdf: "PDF", video: "Video" };
    return labels[String(format || "").toLowerCase()] || "Sin definir";
}

function resolveAdminServicePreviewImage(path) {
    const normalizedPath = String(path || "").trim();

    if (normalizedPath === "") {
        return "";
    }

    if (/^(https?:|data:|\/)/i.test(normalizedPath) || normalizedPath.startsWith("../") || normalizedPath.startsWith("./")) {
        return normalizedPath;
    }

    if (normalizedPath.startsWith("assets/")) {
        return `../../public/${normalizedPath}`;
    }

    if (normalizedPath.startsWith("uploads/")) {
        return `../../${normalizedPath}`;
    }

    return normalizedPath;
}

function formatAdminServiceCurrency(value) {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
}

function getAdminServiceInitials(value) {
    const parts = String(value || "").trim().split(/\s+/).filter(Boolean).slice(0, 2);

    if (parts.length === 0) {
        return "IS";
    }

    return parts.map((part) => part.charAt(0).toUpperCase()).join("");
}

function setTextContent(element, value) {
    if (element instanceof HTMLElement) {
        element.textContent = value;
    }
}

function escapeAdminServiceHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
