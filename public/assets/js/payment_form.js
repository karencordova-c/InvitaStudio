document.addEventListener("DOMContentLoaded", () => {
    renderCanonicalPaymentView();
    initPaymentForm();
});

const paymentRuntimeApiBaseUrl = window.InvitaStudio && window.InvitaStudio.config
    ? window.InvitaStudio.config.apiBaseUrl
    : `${window.location.origin}/api`;

const paymentFieldRules = {
    numero_tarjeta: {
        required: true,
        validator: validateCardNumberField,
    },
    titular: {
        required: true,
        minLength: 3,
        maxLength: 150,
        message: "Ingresa el titular de la tarjeta de prueba.",
    },
    fecha_expiracion: {
        required: true,
        validator: validateExpirationField,
    },
    cvv: {
        required: true,
        validator: validateCvvField,
    },
};

function renderCanonicalPaymentView() {
    if (document.body.dataset.page !== "payment") {
        return;
    }

    const mainElement = document.getElementById("main-content");

    if (!(mainElement instanceof HTMLElement)) {
        return;
    }

    mainElement.innerHTML = `
        <section class="content-section">
            <div class="payment-layout">
                <form
                    class="card-surface form-panel"
                    action="${resolvePaymentApiUrl("payments/process.php")}"
                    method="post"
                    novalidate
                    data-payment-form
                    data-api-endpoint="${resolvePaymentApiUrl("payments/process.php")}"
                    data-lookup-endpoint="${resolvePaymentApiUrl("status/lookup.php")}"
                >
                    <div class="form-panel__header">
                        <p class="eyebrow">Pago simulado</p>
                        <h2>Captura los datos de pago</h2>
                        <p>Selecciona una tarjeta de prueba o escribe los datos manualmente y despues presiona el boton de pagar.</p>
                    </div>

                    <section class="request-form__section" data-payment-capture aria-labelledby="section-payment-card">
                        <div class="request-form__meta">
                            <p class="eyebrow">Datos de tarjeta</p>
                            <h3 id="section-payment-card">Tarjeta de prueba</h3>
                            <p>Selecciona una tarjeta de prueba o captura los datos manualmente.</p>
                        </div>

                        <div class="payment-card-picker" aria-label="Tarjetas de prueba disponibles">
                            <button
                                class="payment-card-picker__option"
                                type="button"
                                data-payment-card-option
                                data-card-number="4111111111111111"
                                data-card-holder="Tarjeta Ficticia Uno"
                                data-card-expiration="12/30"
                                data-card-cvv="123"
                            >
                                <span class="payment-card-picker__brand">Visa prueba</span>
                                <strong>4111 1111 1111 1111</strong>
                                <small>Tarjeta Ficticia Uno | 12/30 | CVV 123</small>
                            </button>

                            <button
                                class="payment-card-picker__option"
                                type="button"
                                data-payment-card-option
                                data-card-number="4000000000000002"
                                data-card-holder="Tarjeta Saldo Bajo"
                                data-card-expiration="12/30"
                                data-card-cvv="222"
                            >
                                <span class="payment-card-picker__brand">Prueba saldo bajo</span>
                                <strong>4000 0000 0000 0002</strong>
                                <small>Respuesta esperada: saldo insuficiente</small>
                            </button>

                            <button
                                class="payment-card-picker__option"
                                type="button"
                                data-payment-card-option
                                data-card-number="4000000000000003"
                                data-card-holder="Tarjeta Inactiva"
                                data-card-expiration="12/30"
                                data-card-cvv="333"
                            >
                                <span class="payment-card-picker__brand">Prueba inactiva</span>
                                <strong>4000 0000 0000 0003</strong>
                                <small>Respuesta esperada: tarjeta inactiva</small>
                            </button>
                        </div>

                        <div class="form-grid">
                            <div class="form-field form-field--full" data-field-container="numero_tarjeta">
                                <label for="numero_tarjeta">Numero de tarjeta</label>
                                <input id="numero_tarjeta" name="numero_tarjeta" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="4111111111111111" required>
                                <p class="field-error" data-error-for="numero_tarjeta" aria-live="polite"></p>
                            </div>

                            <div class="form-field form-field--full" data-field-container="titular">
                                <label for="titular">Titular</label>
                                <input id="titular" name="titular" type="text" autocomplete="cc-name" placeholder="Tarjeta Ficticia Uno" required>
                                <p class="field-error" data-error-for="titular" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="fecha_expiracion">
                                <label for="fecha_expiracion">Expiracion</label>
                                <input id="fecha_expiracion" name="fecha_expiracion" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5" required>
                                <p class="field-error" data-error-for="fecha_expiracion" aria-live="polite"></p>
                            </div>

                            <div class="form-field" data-field-container="cvv">
                                <label for="cvv">CVV</label>
                                <input id="cvv" name="cvv" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" required>
                                <p class="field-error" data-error-for="cvv" aria-live="polite"></p>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions" data-payment-actions>
                        <button class="button button-primary" type="submit" data-submit-button data-default-label="Pagar ahora" data-loading-label="Procesando pago...">
                            Pagar ahora
                        </button>
                        <a class="button button-outline" href="request.html">Crear otro pedido</a>
                    </div>

                    <p class="form-feedback" data-form-feedback data-payment-feedback aria-live="polite"></p>

                    <article class="payment-summary-panel" aria-live="polite">
                        <div class="payment-summary-grid">
                            <div class="payment-summary-item">
                                <span>Numero de pedido</span>
                                <strong data-order-number>Consultando...</strong>
                            </div>
                            <div class="payment-summary-item">
                                <span>Total</span>
                                <strong class="payment-amount" data-order-total>$0.00 MXN</strong>
                            </div>
                            <div class="payment-summary-item payment-summary-item--full">
                                <span>Detalle</span>
                                <strong data-order-message>Preparando resumen seguro del pedido.</strong>
                            </div>
                            <div class="payment-summary-item">
                                <span>Fecha</span>
                                <strong data-order-date>Sin datos</strong>
                            </div>
                            <div class="payment-summary-item">
                                <span>Formato de entrega</span>
                                <strong data-order-delivery-format>Sin datos</strong>
                            </div>
                        </div>

                        <div class="payment-state-stack">
                            <div class="payment-state-chip">
                                <span>Estado pedido</span>
                                <strong data-order-status>Pendiente de carga</strong>
                            </div>
                            <div class="payment-state-chip">
                                <span>Estado pago</span>
                                <strong data-payment-status>Pendiente de carga</strong>
                            </div>
                        </div>

                        <p class="payment-card-note" data-payment-summary-note>
                            El resumen se habilitara cuando el pedido sea encontrado.
                        </p>
                    </article>

                    <article class="card-surface payment-result-card is-info" data-payment-result>
                        <span class="chip">Resultado</span>
                        <h3>Esperando procesamiento</h3>
                        <p>Cuando el pedido este cargado y envies la tarjeta de prueba, aqui veras el resultado de la transaccion.</p>
                    </article>
                </form>
            </div>
        </section>
    `;
}

async function initPaymentForm() {
    const form = document.querySelector("[data-payment-form]");

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    ensurePaymentCaptureUi(form);

    const submitButton = form.querySelector("[data-submit-button]");
    const feedbackElement = form.querySelector("[data-form-feedback]");
    const resultElement = document.querySelector("[data-payment-result]");
    const summaryNoteElement = document.querySelector("[data-payment-summary-note]");

    bindPaymentFieldEvents(form);
    bindPaymentCardPicker(form);
    setSubmitState(submitButton, true, "Validando pedido...");
    showPaymentFeedback(feedbackElement, "Validando el pedido con numero y correo...", "loading");

    try {
        const summaryData = await fetchOrderSummary(form);
        renderOrderSummary(summaryData);
        renderResultCard(resultElement, "info", "Pedido validado", buildSummaryMessage(summaryData));

        showPaymentFeedback(
            feedbackElement,
            "Pedido validado. Ya puedes capturar o seleccionar una tarjeta de prueba.",
            "info"
        );
        setSubmitState(submitButton, false);
        setFormFieldsDisabled(form, false);

        if (summaryNoteElement instanceof HTMLElement) {
            summaryNoteElement.textContent = summaryData.can_process_payment
                ? "El pedido fue validado y aun admite un pago simulado."
                : "El formulario permanece habilitado para pruebas aunque este pedido no cumpla el criterio normal de pago.";
        }
    } catch (error) {
        const message = error instanceof Error
            ? error.message
            : "No fue posible validar el pedido.";

        showPaymentFeedback(feedbackElement, message, "error");
        renderResultCard(resultElement, "error", "Pedido no disponible", message);
        setSubmitState(submitButton, true, "Sin pedido");
        setFormFieldsDisabled(form, true);
        return;
    }

    let isSubmitting = false;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        clearPaymentFeedback(feedbackElement);

        if (!validatePaymentForm(form)) {
            showPaymentFeedback(feedbackElement, "Revisa los campos marcados antes de enviar.", "error");
            focusFirstInvalidField(form);
            return;
        }

        const payload = buildPaymentPayload(form);
        isSubmitting = true;
        setSubmitState(submitButton, true);
        showPaymentFeedback(feedbackElement, "Procesando pago simulado...", "loading");

        try {
            const response = await fetch(form.dataset.apiEndpoint || form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const responseData = await parsePaymentJsonResponse(response);

            if (responseData.success === true) {
                clearAllPaymentFieldErrors(form);
                applyTransactionSummary(responseData.data || {});
                showPaymentFeedback(feedbackElement, responseData.message || "Pago procesado correctamente.", "success");
                renderResultCard(
                    resultElement,
                    "success",
                    "Pago aprobado",
                    buildTransactionMessage(responseData.data || {}, true)
                );
                form.dataset.canProcessPayment = "false";
                setSubmitState(submitButton, true, "Pago confirmado");
                setFormFieldsDisabled(form, true);
                return;
            }

            if (responseData.data && responseData.data.simulacion_local === true) {
                clearAllPaymentFieldErrors(form);
                applyTransactionSummary(responseData.data || {});
                showPaymentFeedback(feedbackElement, responseData.message || "El pago fue rechazado.", "error");
                renderResultCard(
                    resultElement,
                    "error",
                    "Pago rechazado",
                    buildTransactionMessage(responseData.data || {}, false)
                );
                setSubmitState(submitButton, false);
                setFormFieldsDisabled(form, false);
                return;
            }

            applyServerErrors(form, responseData.errors || {});
            showPaymentFeedback(
                feedbackElement,
                responseData.message || "No fue posible procesar el pago simulado.",
                "error"
            );
            focusFirstInvalidField(form);
        } catch (error) {
            showPaymentFeedback(
                feedbackElement,
                "Ocurrio un problema al conectar con la API. Intenta nuevamente.",
                "error"
            );
        } finally {
            isSubmitting = false;

            if (form.dataset.canProcessPayment === "true") {
                setSubmitState(submitButton, false);
            }
        }
    });
}

function ensurePaymentCaptureUi(form) {
    const captureSection = form.querySelector("[data-payment-capture]");
    const formActions = form.querySelector("[data-payment-actions]");
    const feedbackElement = form.querySelector("[data-payment-feedback]");

    if (
        captureSection instanceof HTMLElement
        && formActions instanceof HTMLElement
        && feedbackElement instanceof HTMLElement
    ) {
        return;
    }

    const summaryPanel = form.querySelector(".payment-summary-panel");

    if (!(summaryPanel instanceof HTMLElement)) {
        return;
    }

    const captureMarkup = document.createElement("div");
    captureMarkup.innerHTML = `
        <section class="request-form__section" data-payment-capture aria-labelledby="section-payment-card">
            <div class="request-form__meta">
                <p class="eyebrow">Datos de tarjeta</p>
                <h3 id="section-payment-card">Tarjeta de prueba</h3>
                <p>Selecciona una tarjeta de prueba o captura los datos manualmente.</p>
            </div>

            <div class="payment-card-picker" aria-label="Tarjetas de prueba disponibles">
                <button
                    class="payment-card-picker__option"
                    type="button"
                    data-payment-card-option
                    data-card-number="4111111111111111"
                    data-card-holder="Tarjeta Ficticia Uno"
                    data-card-expiration="12/30"
                    data-card-cvv="123"
                >
                    <span class="payment-card-picker__brand">Visa prueba</span>
                    <strong>4111 1111 1111 1111</strong>
                    <small>Tarjeta Ficticia Uno | 12/30 | CVV 123</small>
                </button>

                <button
                    class="payment-card-picker__option"
                    type="button"
                    data-payment-card-option
                    data-card-number="4000000000000002"
                    data-card-holder="Tarjeta Saldo Bajo"
                    data-card-expiration="12/30"
                    data-card-cvv="222"
                >
                    <span class="payment-card-picker__brand">Prueba saldo bajo</span>
                    <strong>4000 0000 0000 0002</strong>
                    <small>Respuesta esperada: saldo insuficiente</small>
                </button>

                <button
                    class="payment-card-picker__option"
                    type="button"
                    data-payment-card-option
                    data-card-number="4000000000000003"
                    data-card-holder="Tarjeta Inactiva"
                    data-card-expiration="12/30"
                    data-card-cvv="333"
                >
                    <span class="payment-card-picker__brand">Prueba inactiva</span>
                    <strong>4000 0000 0000 0003</strong>
                    <small>Respuesta esperada: tarjeta inactiva</small>
                </button>
            </div>

            <div class="form-grid">
                <div class="form-field form-field--full" data-field-container="numero_tarjeta">
                    <label for="numero_tarjeta">Numero de tarjeta</label>
                    <input id="numero_tarjeta" name="numero_tarjeta" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="4111111111111111" required>
                    <p class="field-error" data-error-for="numero_tarjeta" aria-live="polite"></p>
                </div>

                <div class="form-field form-field--full" data-field-container="titular">
                    <label for="titular">Titular</label>
                    <input id="titular" name="titular" type="text" autocomplete="cc-name" placeholder="Tarjeta Ficticia Uno" required>
                    <p class="field-error" data-error-for="titular" aria-live="polite"></p>
                </div>

                <div class="form-field" data-field-container="fecha_expiracion">
                    <label for="fecha_expiracion">Expiracion</label>
                    <input id="fecha_expiracion" name="fecha_expiracion" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5" required>
                    <p class="field-error" data-error-for="fecha_expiracion" aria-live="polite"></p>
                </div>

                <div class="form-field" data-field-container="cvv">
                    <label for="cvv">CVV</label>
                    <input id="cvv" name="cvv" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" required>
                    <p class="field-error" data-error-for="cvv" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <div class="form-actions" data-payment-actions>
            <button class="button button-primary" type="submit" data-submit-button data-default-label="Procesar pago simulado" data-loading-label="Procesando pago...">
                Pagar ahora
            </button>
            <a class="button button-outline" href="request.html">Crear otro pedido</a>
        </div>

        <p class="form-feedback" data-form-feedback data-payment-feedback aria-live="polite"></p>
    `;

    const headerElement = form.querySelector(".form-panel__header");
    const insertionReference = headerElement instanceof HTMLElement ? headerElement.nextSibling : summaryPanel;

    form.insertBefore(captureMarkup, insertionReference);
}

function bindPaymentFieldEvents(form) {
    Object.keys(paymentFieldRules).forEach((fieldName) => {
        const fieldElement = form.querySelector(`[name="${fieldName}"]`);

        if (!(fieldElement instanceof HTMLInputElement)) {
            return;
        }

        fieldElement.addEventListener("blur", () => {
            validatePaymentField(form, fieldName);
        });

        fieldElement.addEventListener("input", () => {
            if (fieldName === "numero_tarjeta" || fieldName === "cvv") {
                fieldElement.value = fieldElement.value.replace(/\D+/g, "");
            }

            if (fieldName === "fecha_expiracion") {
                fieldElement.value = normalizeExpirationInput(fieldElement.value);
            }

            clearPaymentFieldError(form, fieldName);
        });
    });
}

function bindPaymentCardPicker(form) {
    const cardOptions = Array.from(form.querySelectorAll("[data-payment-card-option]"));

    cardOptions.forEach((optionElement) => {
        if (!(optionElement instanceof HTMLButtonElement)) {
            return;
        }

        optionElement.addEventListener("click", () => {
            fillPaymentCardFields(form, optionElement.dataset);
            updateSelectedPaymentCard(cardOptions, optionElement);
            clearAllPaymentFieldErrors(form);
        });
    });
}

function fillPaymentCardFields(form, dataset) {
    setInputValue(form, "numero_tarjeta", sanitizeCardNumber(dataset.cardNumber || ""));
    setInputValue(form, "titular", sanitizeTextValue(dataset.cardHolder || "", 150));
    setInputValue(form, "fecha_expiracion", normalizeExpirationInput(dataset.cardExpiration || ""));
    setInputValue(form, "cvv", sanitizeCvv(dataset.cardCvv || ""));
}

function setInputValue(form, fieldName, value) {
    const fieldElement = form.querySelector(`[name="${fieldName}"]`);

    if (fieldElement instanceof HTMLInputElement) {
        fieldElement.value = value;
    }
}

function updateSelectedPaymentCard(cardOptions, selectedOption) {
    cardOptions.forEach((optionElement) => {
        if (optionElement instanceof HTMLElement) {
            optionElement.classList.toggle("is-selected", optionElement === selectedOption);
        }
    });
}

async function fetchOrderSummary(form) {
    const lookupEndpoint = form.dataset.lookupEndpoint || resolvePaymentApiUrl("status/lookup.php");
    const lookupPayload = buildPaymentLookupPayload();

    if (!lookupPayload) {
        throw new Error("Faltan numero de pedido y correo en el enlace de pago.");
    }

    const response = await fetch(lookupEndpoint, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
        body: JSON.stringify(lookupPayload),
    });
    const responseData = await parsePaymentJsonResponse(response);

    if (!response.ok || responseData.success !== true || !responseData.data) {
        throw new Error(responseData.message || "No fue posible obtener el resumen del pedido.");
    }

    form.dataset.orderNumber = lookupPayload.numero_pedido;
    form.dataset.orderEmail = lookupPayload.correo;
    form.dataset.canProcessPayment = responseData.data.can_process_payment ? "true" : "false";

    return responseData.data;
}

function resolvePaymentApiUrl(path) {
    const normalizedPath = String(path || "").trim().replace(/^\/+/, "");

    if (normalizedPath === "") {
        return paymentRuntimeApiBaseUrl;
    }

    return `${paymentRuntimeApiBaseUrl}/${normalizedPath}`;
}

function buildPaymentLookupPayload() {
    const urlParameters = new URLSearchParams(window.location.search);
    const numeroPedido = sanitizeLookupValue(urlParameters.get("numero_pedido")).toUpperCase();
    const correo = sanitizeLookupEmail(urlParameters.get("correo"));

    if (!numeroPedido || !correo) {
        return null;
    }

    return {
        numero_pedido: numeroPedido,
        correo,
    };
}

function renderOrderSummary(summaryData) {
    setTextContent("[data-order-number]", summaryData.numero_pedido || "Sin numero");
    setTextContent("[data-order-total]", formatPaymentCurrency(summaryData.monto_pago || 0));
    setTextContent("[data-order-message]", summaryData.mensaje_estado || "Sin detalles");
    setTextContent("[data-order-date]", formatOrderDate(summaryData.fecha_evento));
    setTextContent(
        "[data-order-delivery-format]",
        getDeliveryFormatLabel(summaryData.formato_entrega)
    );
    setTextContent("[data-order-status]", getOrderStatusLabel(summaryData.estado_pedido));
    setTextContent("[data-payment-status]", getPaymentStatusLabel(summaryData.estado_pago));
}

function applyTransactionSummary(data) {
    const orderStatus = data.estado_pedido || "pendiente";
    const paymentStatus = data.estado_pago || "rechazado";
    const paymentAmount = Number(data.monto_pago || 0);
    const summaryNoteElement = document.querySelector("[data-payment-summary-note]");

    setTextContent("[data-order-total]", formatPaymentCurrency(paymentAmount));
    setTextContent("[data-order-status]", getOrderStatusLabel(orderStatus));
    setTextContent("[data-payment-status]", getPaymentStatusLabel(paymentStatus));
    setTextContent(
        "[data-order-message]",
        data.mensaje_transaccion || "El estado del pago fue actualizado."
    );

    if (summaryNoteElement instanceof HTMLElement) {
        summaryNoteElement.textContent = data.mensaje_transaccion || "El estado del pago fue actualizado.";
    }
}

function buildPaymentPayload(form) {
    const formData = new FormData(form);
    const rawValues = Object.fromEntries(formData.entries());

    return {
        numero_pedido: String(form.dataset.orderNumber || ""),
        correo: String(form.dataset.orderEmail || ""),
        numero_tarjeta: sanitizeCardNumber(rawValues.numero_tarjeta),
        titular: sanitizeTextValue(rawValues.titular, 150),
        fecha_expiracion: normalizeExpirationInput(rawValues.fecha_expiracion),
        cvv: sanitizeCvv(rawValues.cvv),
    };
}

function validatePaymentForm(form) {
    let isValid = true;

    Object.keys(paymentFieldRules).forEach((fieldName) => {
        const fieldIsValid = validatePaymentField(form, fieldName);

        if (!fieldIsValid) {
            isValid = false;
        }
    });

    return isValid;
}

function validatePaymentField(form, fieldName) {
    const fieldElement = form.querySelector(`[name="${fieldName}"]`);
    const rule = paymentFieldRules[fieldName];

    if (!(fieldElement instanceof HTMLInputElement) || !rule) {
        return true;
    }

    const value = getNormalizedPaymentValue(fieldName, fieldElement.value);

    if (rule.required && value === "") {
        setPaymentFieldError(form, fieldName, rule.message || "Este campo es obligatorio.");
        return false;
    }

    if (typeof rule.minLength === "number" && value.length < rule.minLength) {
        setPaymentFieldError(form, fieldName, rule.message || "El valor es demasiado corto.");
        return false;
    }

    if (typeof rule.maxLength === "number" && value.length > rule.maxLength) {
        setPaymentFieldError(form, fieldName, `El campo no debe superar ${rule.maxLength} caracteres.`);
        return false;
    }

    if (typeof rule.validator === "function") {
        const validationMessage = rule.validator(value);

        if (validationMessage) {
            setPaymentFieldError(form, fieldName, validationMessage);
            return false;
        }
    }

    clearPaymentFieldError(form, fieldName);
    return true;
}

function validateCardNumberField(value) {
    return /^\d{13,19}$/.test(value)
        ? ""
        : "Ingresa una tarjeta de prueba usando solo numeros.";
}

function validateExpirationField(value) {
    return /^(0[1-9]|1[0-2])\/\d{2}$/.test(value)
        ? ""
        : "Usa el formato MM/YY.";
}

function validateCvvField(value) {
    return /^\d{3,4}$/.test(value)
        ? ""
        : "Ingresa un CVV de 3 o 4 digitos.";
}

function getNormalizedPaymentValue(fieldName, value) {
    switch (fieldName) {
        case "numero_tarjeta":
            return sanitizeCardNumber(value);
        case "fecha_expiracion":
            return normalizeExpirationInput(value);
        case "cvv":
            return sanitizeCvv(value);
        default:
            return sanitizeTextValue(value, 150);
    }
}

function sanitizeCardNumber(value) {
    return String(value || "")
        .replace(/\D+/g, "")
        .slice(0, 19);
}

function sanitizeCvv(value) {
    return String(value || "")
        .replace(/\D+/g, "")
        .slice(0, 4);
}

function normalizeExpirationInput(value) {
    const digitsOnly = String(value || "")
        .replace(/\D+/g, "")
        .slice(0, 4);

    if (digitsOnly.length <= 2) {
        return digitsOnly;
    }

    return `${digitsOnly.slice(0, 2)}/${digitsOnly.slice(2)}`;
}

function sanitizeTextValue(value, maxLength = Infinity) {
    return String(value || "")
        .replace(/[<>]/g, "")
        .replace(/\s+/g, " ")
        .trim()
        .slice(0, maxLength);
}

function sanitizeLookupValue(value) {
    return String(value || "")
        .replace(/[^\w-]/g, "")
        .trim()
        .slice(0, 40);
}

function sanitizeLookupEmail(value) {
    return sanitizeTextValue(value, 150).toLowerCase();
}

function setPaymentFieldError(form, fieldName, message) {
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

function clearPaymentFieldError(form, fieldName) {
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

function clearAllPaymentFieldErrors(form) {
    Object.keys(paymentFieldRules).forEach((fieldName) => {
        clearPaymentFieldError(form, fieldName);
    });
}

function applyServerErrors(form, errors) {
    clearAllPaymentFieldErrors(form);

    Object.entries(errors).forEach(([fieldName, fieldErrors]) => {
        const firstErrorMessage = Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors;

        if (typeof firstErrorMessage === "string" && firstErrorMessage.trim() !== "") {
            setPaymentFieldError(form, fieldName, firstErrorMessage);
        }
    });
}

function focusFirstInvalidField(form) {
    const firstInvalidField = form.querySelector("[aria-invalid='true']");

    if (firstInvalidField instanceof HTMLElement) {
        firstInvalidField.focus();
    }
}

function setFormFieldsDisabled(form, isDisabled) {
    form.querySelectorAll("input, button[data-payment-card-option]").forEach((fieldElement) => {
        fieldElement.disabled = isDisabled;
    });
}

function setSubmitState(submitButton, isDisabled, customLabel = "") {
    if (!(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    submitButton.disabled = isDisabled;
    submitButton.textContent = customLabel
        || (isDisabled ? submitButton.dataset.loadingLabel : "")
        || submitButton.dataset.defaultLabel
        || "Enviar";
}

function showPaymentFeedback(feedbackElement, message, state) {
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

function clearPaymentFeedback(feedbackElement) {
    if (!(feedbackElement instanceof HTMLElement)) {
        return;
    }

    feedbackElement.textContent = "";
    feedbackElement.classList.remove("is-error", "is-success", "is-loading", "is-info");
}

function renderResultCard(container, state, title, message) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    container.classList.remove("is-success", "is-error", "is-info");

    if (state === "success") {
        container.classList.add("is-success");
    } else if (state === "error") {
        container.classList.add("is-error");
    } else {
        container.classList.add("is-info");
    }

    container.innerHTML = `
        <span class="chip">Resultado</span>
        <h3>${escapeHtml(title)}</h3>
        <p>${escapeHtml(message)}</p>
    `;
}

function buildSummaryMessage(summaryData) {
    const amountText = formatPaymentCurrency(summaryData.monto_pago || 0);

    return `Pedido ${summaryData.numero_pedido || ""} validado. Monto base simulado: ${amountText}.`;
}

function buildBlockedPaymentMessage(summaryData) {
    if ((summaryData.estado_pago || "") === "confirmado" || (summaryData.estado_pedido || "") === "pago_confirmado") {
        return "Este pedido ya tiene un pago confirmado. No es necesario procesarlo de nuevo.";
    }

    if (Number(summaryData.monto_pago || 0) <= 0) {
        return "El pedido no tiene un monto simulado disponible para procesar.";
    }

    return "El pedido no esta disponible para un nuevo pago simulado en su estado actual.";
}

function buildTransactionMessage(data, isApproved) {
    const amountText = formatPaymentCurrency(data.monto_pago || 0);

    if (isApproved) {
        return `Pedido ${data.numero_pedido || ""} aprobado por ${amountText}. Referencia: ${data.referencia_pago || "sin referencia"}.`;
    }

    return `${data.mensaje_transaccion || "La transaccion fue rechazada."} Pedido ${data.numero_pedido || ""}.`;
}

function formatPaymentCurrency(amount) {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        minimumFractionDigits: 2,
    }).format(Number(amount || 0));
}

function formatOrderDate(dateValue) {
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

function setTextContent(selector, value) {
    const element = document.querySelector(selector);

    if (element instanceof HTMLElement) {
        element.textContent = value;
    }
}

async function parsePaymentJsonResponse(response) {
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
