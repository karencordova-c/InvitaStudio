const invitaStudioRuntimeConfig = Object.freeze({
    backendBaseUrl: "https://cintiaparral.com/invita",
    apiBaseUrl: "https://cintiaparral.com/invita/api",
});

window.InvitaStudio = Object.freeze({
    config: invitaStudioRuntimeConfig,
    resolveApiUrl,
    resolveBackendUrl,
});

document.addEventListener("DOMContentLoaded", () => {
    applyRuntimeApiEndpoints();
    initNavigation();
    initCurrentYear();
    initPlaceholderForms();
    initHeaderState();
});

function applyRuntimeApiEndpoints() {
    document.querySelectorAll("*").forEach((element) => {
        Array.from(element.attributes).forEach((attribute) => {
            if (!attribute.name.startsWith("data-") || !attribute.name.endsWith("-endpoint")) {
                return;
            }

            const resolvedValue = resolveEndpointReference(attribute.value);

            if (resolvedValue !== attribute.value) {
                element.setAttribute(attribute.name, resolvedValue);
            }
        });

        if (!(element instanceof HTMLFormElement)) {
            return;
        }

        const actionValue = element.getAttribute("action") || "";
        const resolvedAction = resolveEndpointReference(actionValue);

        if (resolvedAction !== "" && resolvedAction !== actionValue) {
            element.setAttribute("action", resolvedAction);
        }
    });
}

function resolveEndpointReference(value) {
    const normalizedValue = String(value || "").trim();

    if (normalizedValue === "" || isAbsoluteUrl(normalizedValue)) {
        return normalizedValue;
    }

    const normalizedPath = normalizedValue.replace(/\\/g, "/");
    const apiIndex = normalizedPath.lastIndexOf("/api/");

    if (apiIndex !== -1) {
        return resolveApiUrl(normalizedPath.slice(apiIndex + 5));
    }

    if (normalizedPath.startsWith("api/")) {
        return resolveApiUrl(normalizedPath.slice(4));
    }

    if (normalizedPath.startsWith("./api/")) {
        return resolveApiUrl(normalizedPath.slice(6));
    }

    if (normalizedPath.startsWith("../api/")) {
        return resolveApiUrl(normalizedPath.slice(7));
    }

    return normalizedValue;
}

function resolveBackendUrl(path = "") {
    const normalizedPath = String(path || "").trim().replace(/^\/+/, "");

    if (normalizedPath === "") {
        return invitaStudioRuntimeConfig.backendBaseUrl;
    }

    return `${invitaStudioRuntimeConfig.backendBaseUrl}/${normalizedPath}`;
}

function resolveApiUrl(path = "") {
    const normalizedPath = String(path || "").trim().replace(/^\/+/, "");

    if (normalizedPath === "") {
        return invitaStudioRuntimeConfig.apiBaseUrl;
    }

    return `${invitaStudioRuntimeConfig.apiBaseUrl}/${normalizedPath}`;
}

function isAbsoluteUrl(value) {
    return /^(?:[a-z]+:)?\/\//i.test(value)
        || value.startsWith("data:")
        || value.startsWith("mailto:")
        || value.startsWith("tel:");
}

function initNavigation() {
    const navToggle = document.querySelector(".nav-toggle");
    const siteNav = document.getElementById("site-nav");
    const currentPage = document.body.dataset.page;

    if (siteNav instanceof HTMLElement && currentPage) {
        siteNav.querySelectorAll("[data-nav]").forEach((link) => {
            if (link.dataset.nav === currentPage) {
                link.classList.add("is-current");
                link.setAttribute("aria-current", "page");
            }
        });
    }

    if (!(navToggle instanceof HTMLButtonElement) || !(siteNav instanceof HTMLElement)) {
        return;
    }

    navToggle.addEventListener("click", () => {
        const isOpen = siteNav.classList.toggle("is-open");
        navToggle.setAttribute("aria-expanded", String(isOpen));
        navToggle.setAttribute("aria-label", isOpen ? "Cerrar menu" : "Abrir menu");
    });

    siteNav.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            if (window.innerWidth >= 1024) {
                return;
            }

            siteNav.classList.remove("is-open");
            navToggle.setAttribute("aria-expanded", "false");
            navToggle.setAttribute("aria-label", "Abrir menu");
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        siteNav.classList.remove("is-open");
        navToggle.setAttribute("aria-expanded", "false");
        navToggle.setAttribute("aria-label", "Abrir menu");
    });
}

function initCurrentYear() {
    const currentYear = String(new Date().getFullYear());

    document.querySelectorAll("[data-current-year]").forEach((element) => {
        element.textContent = currentYear;
    });
}

function initPlaceholderForms() {
    document.querySelectorAll("[data-placeholder-form]").forEach((form) => {
        const feedbackElement = form.querySelector(".form-feedback");

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            if (!(feedbackElement instanceof HTMLElement)) {
                return;
            }

            feedbackElement.textContent = form.dataset.placeholderForm || "Accion visual ejecutada.";
        });
    });
}

function initHeaderState() {
    const siteHeader = document.querySelector("[data-site-header]");

    if (!(siteHeader instanceof HTMLElement)) {
        return;
    }

    const syncHeaderState = () => {
        siteHeader.classList.toggle("is-scrolled", window.scrollY > 12);
    };

    syncHeaderState();
    window.addEventListener("scroll", syncHeaderState, { passive: true });
}
