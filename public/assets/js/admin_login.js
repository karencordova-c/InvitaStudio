document.addEventListener("DOMContentLoaded", () => {
    initAdminLoginForm();
});

function initAdminLoginForm() {
    const form = document.querySelector("[data-admin-login-form]");

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const submitButton = form.querySelector("[data-submit-button]");
    const feedbackElement = form.querySelector("[data-form-feedback]");
    const fieldNames = ["correo", "password"];

    fieldNames.forEach((fieldName) => {
        const field = form.querySelector(`[name="${fieldName}"]`);

        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        field.addEventListener("blur", () => {
            validateLoginField(form, fieldName);
        });

        field.addEventListener("input", () => {
            clearLoginFieldError(form, fieldName);
        });
    });

    let isSubmitting = false;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        clearLoginFeedback(feedbackElement);

        if (!validateLoginForm(form)) {
            showLoginFeedback(feedbackElement, "Revisa los datos antes de continuar.", "error");
            focusFirstInvalidLoginField(form);
            return;
        }

        isSubmitting = true;
        setLoginSubmitState(submitButton, true);
        showLoginFeedback(feedbackElement, "Validando acceso...", "loading");

        try {
            const response = await fetch(form.dataset.apiEndpoint || form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                },
                body: JSON.stringify({
                    correo: sanitizeLoginEmail(getLoginFieldValue(form, "correo")),
                    password: getLoginFieldValue(form, "password"),
                }),
            });

            const responseData = await parseLoginJsonResponse(response);

            if (!response.ok || responseData.success !== true) {
                applyLoginServerErrors(form, responseData.errors || {});
                showLoginFeedback(
                    feedbackElement,
                    responseData.message || "No fue posible iniciar sesion.",
                    "error"
                );
                focusFirstInvalidLoginField(form);
                return;
            }

            showLoginFeedback(feedbackElement, responseData.message || "Login exitoso.", "success");

            const redirectUrl = responseData.data && responseData.data.redirect_url
                ? responseData.data.redirect_url
                : form.dataset.dashboardUrl || "./index.php";

            window.location.href = redirectUrl;
        } catch (error) {
            showLoginFeedback(
                feedbackElement,
                "Ocurrio un problema al conectar con la API.",
                "error"
            );
        } finally {
            isSubmitting = false;
            setLoginSubmitState(submitButton, false);
        }
    });
}

function validateLoginForm(form) {
    let isValid = true;

    ["correo", "password"].forEach((fieldName) => {
        if (!validateLoginField(form, fieldName)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateLoginField(form, fieldName) {
    const fieldValue = getLoginFieldValue(form, fieldName);

    if (fieldName === "correo") {
        const emailValue = sanitizeLoginEmail(fieldValue);

        if (emailValue === "") {
            setLoginFieldError(form, fieldName, "Ingresa un correo.");
            return false;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(emailValue)) {
            setLoginFieldError(form, fieldName, "Ingresa un correo electronico valido.");
            return false;
        }
    }

    if (fieldName === "password") {
        if (String(fieldValue).trim() === "") {
            setLoginFieldError(form, fieldName, "Ingresa tu contrasena.");
            return false;
        }
    }

    clearLoginFieldError(form, fieldName);
    return true;
}

function getLoginFieldValue(form, fieldName) {
    const field = form.querySelector(`[name="${fieldName}"]`);

    if (!(field instanceof HTMLInputElement)) {
        return "";
    }

    return field.value;
}

function sanitizeLoginEmail(value) {
    return String(value || "")
        .replace(/[<>]/g, "")
        .trim()
        .toLowerCase()
        .slice(0, 150);
}

function setLoginFieldError(form, fieldName, message) {
    const fieldContainer = form.querySelector(`[data-field-container="${fieldName}"]`);
    const errorElement = form.querySelector(`[data-error-for="${fieldName}"]`);
    const field = form.querySelector(`[name="${fieldName}"]`);

    if (fieldContainer instanceof HTMLElement) {
        fieldContainer.classList.add("is-invalid");
    }

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = message;
    }

    if (field instanceof HTMLInputElement) {
        field.setAttribute("aria-invalid", "true");
    }
}

function clearLoginFieldError(form, fieldName) {
    const fieldContainer = form.querySelector(`[data-field-container="${fieldName}"]`);
    const errorElement = form.querySelector(`[data-error-for="${fieldName}"]`);
    const field = form.querySelector(`[name="${fieldName}"]`);

    if (fieldContainer instanceof HTMLElement) {
        fieldContainer.classList.remove("is-invalid");
    }

    if (errorElement instanceof HTMLElement) {
        errorElement.textContent = "";
    }

    if (field instanceof HTMLInputElement) {
        field.removeAttribute("aria-invalid");
    }
}

function applyLoginServerErrors(form, errors) {
    ["correo", "password"].forEach((fieldName) => {
        clearLoginFieldError(form, fieldName);
    });

    Object.entries(errors).forEach(([fieldName, messages]) => {
        const firstMessage = Array.isArray(messages) ? messages[0] : messages;

        if (typeof firstMessage === "string" && firstMessage.trim() !== "") {
            setLoginFieldError(form, fieldName, firstMessage);
        }
    });
}

function focusFirstInvalidLoginField(form) {
    const invalidField = form.querySelector("[aria-invalid='true']");

    if (invalidField instanceof HTMLElement) {
        invalidField.focus();
    }
}

function setLoginSubmitState(submitButton, isLoading) {
    if (!(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    submitButton.disabled = isLoading;
    submitButton.textContent = isLoading
        ? submitButton.dataset.loadingLabel || "Procesando..."
        : submitButton.dataset.defaultLabel || "Continuar";
}

function showLoginFeedback(feedbackElement, message, state) {
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
}

function clearLoginFeedback(feedbackElement) {
    if (!(feedbackElement instanceof HTMLElement)) {
        return;
    }

    feedbackElement.textContent = "";
    feedbackElement.classList.remove("is-error", "is-success", "is-loading", "is-info");
}

async function parseLoginJsonResponse(response) {
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
