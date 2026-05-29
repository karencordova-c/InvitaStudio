document.addEventListener("DOMContentLoaded", () => {
    initRequestForm();
});

const requestServiceCatalog = {
    clasica_esencial: {
        title: "Invitacion digital clasica",
        category: "Social",
        priceLabel: "Desde $850 MXN",
    },
    interactiva_recomendada: {
        title: "Invitacion interactiva",
        category: "Experiencia web",
        priceLabel: "Desde $1,450 MXN",
    },
    concepto_medida: {
        title: "Concepto a medida",
        category: "Direccion creativa",
        priceLabel: "Cotizacion segun briefing",
    },
};

const requestFieldRules = {
    nombre: {
        required: true,
        minLength: 3,
        maxLength: 150,
        message: "Ingresa tu nombre completo.",
    },
    correo: {
        required: true,
        validator: validateEmailField,
    },
    telefono: {
        required: true,
        validator: validatePhoneField,
    },
    medio_contacto: {
        required: true,
        allowedValues: ["whatsapp", "correo", "llamada"],
        message: "Selecciona un medio de contacto.",
    },
    tipo_evento: {
        required: true,
        minLength: 3,
        maxLength: 100,
        message: "Selecciona el tipo de evento.",
    },
    nombre_evento: {
        required: false,
        minLength: 3,
        maxLength: 150,
        optionalMessage: "Si lo completas, usa al menos 3 caracteres.",
    },
    fecha_evento: {
        required: true,
        validator: validateDateField,
    },
    hora_evento: {
        required: true,
        validator: validateTimeField,
    },
    ubicacion_evento: {
        required: true,
        minLength: 5,
        maxLength: 255,
        message: "Ingresa la ubicacion del evento.",
    },
    tematica: {
        required: false,
        minLength: 3,
        maxLength: 120,
        optionalMessage: "Si agregas tematica, usa al menos 3 caracteres.",
    },
    colores: {
        required: false,
        minLength: 3,
        maxLength: 255,
        optionalMessage: "Si agregas colores, usa al menos 3 caracteres.",
    },
    estilo_diseno: {
        required: true,
        minLength: 3,
        maxLength: 120,
        message: "Selecciona un estilo visual.",
    },
    informacion_adicional: {
        required: false,
        minLength: 10,
        maxLength: 1500,
        optionalMessage: "Si agregas detalles, usa al menos 10 caracteres.",
    },
    formato_entrega: {
        required: true,
        allowedValues: ["imagen", "pdf", "video"],
        message: "Selecciona el formato de entrega.",
    },
    servicio_id: {
        required: true,
        allowedValues: Object.keys(requestServiceCatalog),
        message: "Selecciona un servicio.",
    },
};

function initRequestForm() {
    const form = document.querySelector("[data-request-form]");

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const submitButton = form.querySelector("[data-submit-button]");
    const feedbackElement = form.querySelector("[data-form-feedback]");
    const resultActionsElement = form.querySelector("[data-form-result-actions]");
    const serviceTitle = document.querySelector("[data-service-title]");
    const serviceMeta = document.querySelector("[data-service-meta]");

    applyServiceQueryParam(form);
    updateServiceSummary(form, serviceTitle, serviceMeta);

    Object.keys(requestFieldRules).forEach((fieldName) => {
        form.querySelectorAll(`[name="${fieldName}"]`).forEach((fieldElement) => {
            fieldElement.addEventListener("blur", () => {
                validateField(form, fieldName);
            });

            fieldElement.addEventListener("input", () => {
                clearFieldError(form, fieldName);
            });

            fieldElement.addEventListener("change", () => {
                validateField(form, fieldName);

                if (fieldName === "servicio_id") {
                    updateServiceSummary(form, serviceTitle, serviceMeta);
                }
            });
        });
    });

    let isSubmitting = false;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        clearFormFeedback(feedbackElement);
        clearResultActions(resultActionsElement);

        if (!validateForm(form)) {
            showFormFeedback(feedbackElement, "Revisa los campos marcados antes de enviar.", "error");
            focusFirstInvalidField(form);
            return;
        }

        const payload = buildRequestPayload(form);
        isSubmitting = true;
        setSubmitState(submitButton, true);
        showFormFeedback(feedbackElement, "Enviando solicitud...", "loading");

        try {
            const response = await fetch(form.dataset.apiEndpoint || form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const responseData = await parseJsonResponse(response);

            if (!response.ok || responseData.success !== true) {
                applyServerErrors(form, responseData.errors || {});
                showFormFeedback(
                    feedbackElement,
                    responseData.message || "No fue posible registrar la solicitud.",
                    "error"
                );
                clearResultActions(resultActionsElement);
                focusFirstInvalidField(form);
                return;
            }

            const orderNumber = responseData.data && responseData.data.numero_pedido
                ? ` Folio: ${responseData.data.numero_pedido}.`
                : "";

            const paymentUrl = responseData.data && typeof responseData.data.payment_url === "string"
                ? responseData.data.payment_url.trim()
                : "";

            if (paymentUrl) {
                window.location.href = paymentUrl;
                return;
            }

            form.reset();
            updateServiceSummary(form, serviceTitle, serviceMeta);
            clearAllFieldErrors(form);
            showFormFeedback(feedbackElement, `${responseData.message || "Solicitud registrada."}${orderNumber}`, "success");
            renderResultActions(resultActionsElement, responseData.data || {});
            form.scrollIntoView({ behavior: "smooth", block: "start" });
        } catch (error) {
            showFormFeedback(
                feedbackElement,
                "Ocurrio un problema al enviar la solicitud. Intenta nuevamente.",
                "error"
            );
            clearResultActions(resultActionsElement);
        } finally {
            isSubmitting = false;
            setSubmitState(submitButton, false);
        }
    });
}

function applyServiceQueryParam(form) {
    const urlParameters = new URLSearchParams(window.location.search);
    const requestedServiceId = urlParameters.get("service");

    if (!requestedServiceId || !requestServiceCatalog[requestedServiceId]) {
        return;
    }

    const serviceInput = form.querySelector(`[name="servicio_id"][value="${requestedServiceId}"]`);

    if (serviceInput instanceof HTMLInputElement) {
        serviceInput.checked = true;
    }
}

function buildRequestPayload(form) {
    const formData = new FormData(form);
    const rawValues = Object.fromEntries(formData.entries());

    return {
        nombre: sanitizeTextValue(rawValues.nombre, 150),
        correo: sanitizeEmailValue(rawValues.correo),
        telefono: sanitizePhoneValue(rawValues.telefono),
        medio_contacto: sanitizeChoiceValue(rawValues.medio_contacto),
        tipo_evento: sanitizeTextValue(rawValues.tipo_evento, 100),
        nombre_evento: sanitizeTextValue(rawValues.nombre_evento, 150),
        fecha_evento: sanitizeDateValue(rawValues.fecha_evento),
        hora_evento: sanitizeTimeValue(rawValues.hora_evento),
        ubicacion_evento: sanitizeTextValue(rawValues.ubicacion_evento, 255),
        tematica: sanitizeTextValue(rawValues.tematica, 120),
        colores: sanitizeTextValue(rawValues.colores, 255),
        estilo_diseno: sanitizeTextValue(rawValues.estilo_diseno, 120),
        informacion_adicional: sanitizeTextValue(rawValues.informacion_adicional, 1500),
        formato_entrega: sanitizeChoiceValue(rawValues.formato_entrega),
        servicio_id: sanitizeChoiceValue(rawValues.servicio_id),
    };
}

function validateForm(form) {
    let isValid = true;

    Object.keys(requestFieldRules).forEach((fieldName) => {
        const fieldIsValid = validateField(form, fieldName);

        if (!fieldIsValid) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(form, fieldName) {
    const rule = requestFieldRules[fieldName];

    if (!rule) {
        return true;
    }

    const rawValue = getFieldValue(form, fieldName);
    const value = getNormalizedValue(fieldName, rawValue);

    if (rule.required && value === "") {
        setFieldError(form, fieldName, rule.message || "Este campo es obligatorio.");
        return false;
    }

    if (!rule.required && value === "") {
        clearFieldError(form, fieldName);
        return true;
    }

    if (Array.isArray(rule.allowedValues) && !rule.allowedValues.includes(value)) {
        setFieldError(form, fieldName, rule.message || "Selecciona una opcion valida.");
        return false;
    }

    if (typeof rule.minLength === "number" && value.length < rule.minLength) {
        setFieldError(form, fieldName, rule.optionalMessage || rule.message || "El valor es demasiado corto.");
        return false;
    }

    if (typeof rule.maxLength === "number" && value.length > rule.maxLength) {
        setFieldError(form, fieldName, `El campo no debe superar ${rule.maxLength} caracteres.`);
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

function validateEmailField(value) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailPattern.test(value) ? "" : "Ingresa un correo electronico valido.";
}

function validatePhoneField(value) {
    const digitsOnly = value.replace(/\D+/g, "");

    if (digitsOnly.length < 8) {
        return "Ingresa un telefono con al menos 8 digitos.";
    }

    if (digitsOnly.length > 20) {
        return "El telefono no debe superar 20 digitos.";
    }

    return "";
}

function validateDateField(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return "Selecciona una fecha valida.";
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return "Selecciona una fecha valida.";
    }

    const [year, month, day] = value.split("-").map(Number);

    if (
        date.getFullYear() !== year ||
        date.getMonth() + 1 !== month ||
        date.getDate() !== day
    ) {
        return "Selecciona una fecha valida.";
    }

    return "";
}

function validateTimeField(value) {
    return /^([01]\d|2[0-3]):([0-5]\d)$/.test(value)
        ? ""
        : "Selecciona una hora valida.";
}

function getFieldValue(form, fieldName) {
    const fieldElements = form.querySelectorAll(`[name="${fieldName}"]`);

    if (fieldElements.length === 0) {
        return "";
    }

    const firstField = fieldElements[0];

    if (firstField instanceof HTMLInputElement && firstField.type === "radio") {
        const selectedField = Array.from(fieldElements).find((fieldElement) => {
            return fieldElement instanceof HTMLInputElement && fieldElement.checked;
        });

        return selectedField instanceof HTMLInputElement ? selectedField.value : "";
    }

    return firstField instanceof HTMLInputElement ||
        firstField instanceof HTMLSelectElement ||
        firstField instanceof HTMLTextAreaElement
        ? firstField.value
        : "";
}

function getNormalizedValue(fieldName, rawValue) {
    switch (fieldName) {
        case "correo":
            return sanitizeEmailValue(rawValue);
        case "telefono":
            return sanitizePhoneValue(rawValue);
        case "fecha_evento":
            return sanitizeDateValue(rawValue);
        case "hora_evento":
            return sanitizeTimeValue(rawValue);
        case "medio_contacto":
        case "formato_entrega":
        case "servicio_id":
            return sanitizeChoiceValue(rawValue);
        default:
            return sanitizeTextValue(rawValue);
    }
}

function sanitizeTextValue(value, maxLength = Infinity) {
    const normalizedValue = String(value || "")
        .replace(/[<>]/g, "")
        .replace(/\s+/g, " ")
        .trim();

    return normalizedValue.slice(0, maxLength);
}

function sanitizeEmailValue(value) {
    return sanitizeTextValue(value, 150).toLowerCase();
}

function sanitizePhoneValue(value) {
    return String(value || "")
        .replace(/[^\d+\-\s()]/g, "")
        .replace(/\s+/g, " ")
        .trim()
        .slice(0, 30);
}

function sanitizeDateValue(value) {
    return String(value || "").trim().slice(0, 10);
}

function sanitizeTimeValue(value) {
    return String(value || "").trim().slice(0, 5);
}

function sanitizeChoiceValue(value) {
    return String(value || "")
        .replace(/[^\w-]/g, "")
        .trim();
}

function updateServiceSummary(form, titleElement, metaElement) {
    if (!(titleElement instanceof HTMLElement) || !(metaElement instanceof HTMLElement)) {
        return;
    }

    const selectedServiceId = getFieldValue(form, "servicio_id");
    const selectedService = requestServiceCatalog[selectedServiceId];

    if (!selectedService) {
        titleElement.textContent = "Selecciona una opcion para ver el resumen.";
        metaElement.textContent = "Categoria y precio apareceran aqui.";
        return;
    }

    titleElement.textContent = selectedService.title;
    metaElement.textContent = `Categoria: ${selectedService.category} | Precio: ${selectedService.priceLabel}`;
}

function setFieldError(form, fieldName, message) {
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

function clearFieldError(form, fieldName) {
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

function clearAllFieldErrors(form) {
    Object.keys(requestFieldRules).forEach((fieldName) => {
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

function setSubmitState(submitButton, isLoading) {
    if (!(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    submitButton.disabled = isLoading;
    submitButton.textContent = isLoading
        ? submitButton.dataset.loadingLabel || "Procesando..."
        : submitButton.dataset.defaultLabel || "Enviar";
}

function showFormFeedback(feedbackElement, message, state) {
    if (!(feedbackElement instanceof HTMLElement)) {
        return;
    }

    feedbackElement.textContent = message;
    feedbackElement.classList.remove("is-error", "is-success", "is-loading");

    if (state === "error") {
        feedbackElement.classList.add("is-error");
    }

    if (state === "success") {
        feedbackElement.classList.add("is-success");
    }

    if (state === "loading") {
        feedbackElement.classList.add("is-loading");
    }
}

function clearFormFeedback(feedbackElement) {
    if (!(feedbackElement instanceof HTMLElement)) {
        return;
    }

    feedbackElement.textContent = "";
    feedbackElement.classList.remove("is-error", "is-success", "is-loading");
}

function renderResultActions(container, data) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    const paymentUrl = typeof data.payment_url === "string" ? data.payment_url.trim() : "";
    const amount = Number(data.monto_pago || 0);

    if (!paymentUrl) {
        clearResultActions(container);
        return;
    }

    const summary = amount > 0
        ? `<p>Total estimado: ${formatRequestCurrency(amount)}.</p>`
        : "";

    container.innerHTML = `
        ${summary}
        <a class="button button-secondary" href="${escapeAttribute(paymentUrl)}">Continuar al pago</a>
    `;
    container.hidden = false;
}

function clearResultActions(container) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.hidden = true;
    container.innerHTML = "";
}

async function parseJsonResponse(response) {
    try {
        return await response.json();
    } catch (error) {
        return {
            success: false,
            message: "No fue posible leer la respuesta. Intenta nuevamente.",
            errors: {},
        };
    }
}

function formatRequestCurrency(amount) {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        minimumFractionDigits: 2,
    }).format(amount);
}

function escapeAttribute(value) {
    return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}
