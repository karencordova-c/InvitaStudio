document.addEventListener("DOMContentLoaded", () => {
    initPublicServicesPage();
    initServicesGalleryCarousel();
});

function initPublicServicesPage() {
    const page = document.querySelector("[data-services-page]");

    if (!(page instanceof HTMLElement)) {
        return;
    }

    const endpoint = page.dataset.servicesEndpoint || "";
    const feedback = page.querySelector("[data-services-feedback]");
    const grid = page.querySelector("[data-services-grid]");

    if (endpoint === "" || !(grid instanceof HTMLElement)) {
        return;
    }

    loadPublicServices(endpoint, grid, feedback);
}

async function loadPublicServices(endpoint, grid, feedback) {
    try {
        const responseData = await fetchPublicServicesJson(endpoint);
        const services = Array.isArray(responseData.data?.services) ? responseData.data.services : [];

        renderPublicServicesGrid(grid, services);
        renderServiceCategoryChips(services);
        renderServiceStats(services);
        setPublicServicesFeedback(feedback, "", true, false);
    } catch (error) {
        renderPublicServicesGrid(grid, []);
        renderServiceCategoryChips([]);
        renderServiceStats([]);
        setPublicServicesFeedback(feedback, error.message || "No fue posible cargar el catalogo de servicios.", false, true);
    }
}

async function fetchPublicServicesJson(endpoint) {
    const response = await fetch(endpoint, {
        method: "GET",
        headers: {
            Accept: "application/json",
        },
    });

    let responseData = null;

    try {
        responseData = await response.json();
    } catch (error) {
        throw new Error("La API devolvio una respuesta invalida.");
    }

    if (!response.ok || responseData.success !== true) {
        throw new Error(responseData.message || "No fue posible obtener los servicios.");
    }

    return responseData;
}

function renderPublicServicesGrid(container, services) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!Array.isArray(services) || services.length === 0) {
        container.innerHTML = `
            <article class="service-card service-card--empty">
                <span class="pricing-label">Sin catalogo</span>
                <h2>Por ahora no hay servicios activos.</h2>
                <p>El administrador puede activar servicios desde el panel para publicarlos en esta seccion.</p>
                <a class="button button-secondary" href="contact.html">Contactar a InvitaStudio</a>
            </article>
        `;
        return;
    }

    container.innerHTML = services.map((service, index) => {
        const isFeatured = index === 0 || index === 1;
        const imagePath = resolvePublicServiceImage(service.imagen_referencia || "");
        const priceLabel = formatPublicServiceCurrency(service.precio);
        const formatLabel = escapePublicServiceHtml(service.formato_entrega_label || "Sin formato");
        const category = escapePublicServiceHtml(normalizeServiceCategoryLabel(service.categoria || "Evento general"));
        const name = escapePublicServiceHtml(normalizeServiceText(service.nombre || "Servicio"));
        const description = escapePublicServiceHtml(normalizeServiceText(service.descripcion || "Sin descripcion disponible."));
        const timeLabel = escapePublicServiceHtml(normalizeServiceText(service.tiempo_entrega || "Por definir"));
        const imageAlt = `Referencia visual de ${name}`;

        return `
            <article class="service-card ${isFeatured ? "service-card--featured" : ""}">
                <div class="service-visual ${imagePath === "" ? "service-visual--placeholder" : ""}">
                    ${imagePath !== ""
                        ? `<div class="service-visual__media">
                                <img class="service-visual__bg" src="${escapePublicServiceAttribute(imagePath)}" alt="" loading="lazy" aria-hidden="true">
                                <img class="service-visual__img" src="${escapePublicServiceAttribute(imagePath)}" alt="${escapePublicServiceAttribute(imageAlt)}" loading="lazy">
                           </div>`
                        : `<div class="service-visual__placeholder">${escapePublicServiceHtml(getServiceInitials(service.nombre || service.categoria || "IS"))}</div>`
                    }
                </div>
                <span class="pricing-label">${category}</span>
                <h2>${name}</h2>
                <p class="service-price">${priceLabel}</p>
                <p>${description}</p>
                <div class="service-meta-stack">
                    <span><strong>Formato:</strong> ${formatLabel}</span>
                    <span><strong>Tiempo estimado:</strong> ${timeLabel}</span>
                </div>
                <div class="hero-actions">
                    <a class="button button-primary" href="request.html">Solicitar servicio</a>
                    <a class="button button-outline" href="contact.html">Pedir orientacion</a>
                </div>
            </article>
        `;
    }).join("");
}

function renderServiceCategoryChips(services) {
    const container = document.querySelector("[data-service-category-chips]");

    if (!(container instanceof HTMLElement)) {
        return;
    }

    const categories = getUniqueServiceCategories(services);

    if (categories.length === 0) {
        container.innerHTML = '<span class="chip">Sin categorias activas</span>';
        return;
    }

    container.innerHTML = categories
        .map((category) => `<span class="chip">${escapePublicServiceHtml(normalizeServiceCategoryLabel(category))}</span>`)
        .join("");
}

function renderServiceStats(services) {
    const totalElement = document.querySelector("[data-services-total]");
    const categoriesElement = document.querySelector("[data-services-categories]");
    const priceElement = document.querySelector("[data-services-starting-price]");

    if (totalElement instanceof HTMLElement) {
        totalElement.textContent = Array.isArray(services) ? String(services.length) : "0";
    }

    if (categoriesElement instanceof HTMLElement) {
        categoriesElement.textContent = String(getUniqueServiceCategories(services).length);
    }

    if (priceElement instanceof HTMLElement) {
        if (!Array.isArray(services) || services.length === 0) {
            priceElement.textContent = "--";
            return;
        }

        const prices = services
            .map((service) => Number(service.precio || 0))
            .filter((price) => Number.isFinite(price));

        priceElement.textContent = prices.length === 0 ? "--" : formatPublicServiceCurrency(Math.min(...prices));
    }
}

function getUniqueServiceCategories(services) {
    if (!Array.isArray(services)) {
        return [];
    }

    return Array.from(
        new Set(
            services
                .map((service) => String(service.categoria || "").trim())
                .filter((category) => category !== "")
        )
    );
}

function normalizeServiceText(value) {
    return String(value || "")
        .replace(/Cumplea\?\?os/gi, "Cumpleaños")
        .replace(/a\?\?os/gi, "años")
        .replace(/A\?\?os/g, "Años")
        .replace(/invitacion para xv a\?\?os/gi, "Invitacion para XV años")
        .replace(/xv a\?\?os/gi, "XV años");
}

function normalizeServiceCategoryLabel(value) {
    return normalizeServiceText(value).replace(/\banos\b/gi, "años");
}

function resolvePublicServiceImage(path) {
    const normalizedPath = String(path || "").trim();
    const backendResolver = window.InvitaStudio && typeof window.InvitaStudio.resolveBackendUrl === "function"
        ? window.InvitaStudio.resolveBackendUrl
        : null;

    if (normalizedPath === "") {
        return "";
    }

    if (/^(https?:|data:|\/)/i.test(normalizedPath) || normalizedPath.startsWith("./") || normalizedPath.startsWith("../")) {
        if (normalizedPath.startsWith("/uploads/") && backendResolver) {
            return backendResolver(normalizedPath.slice(1));
        }

        return normalizedPath;
    }

    if (normalizedPath.startsWith("uploads/") && backendResolver) {
        return backendResolver(normalizedPath);
    }

    if (normalizedPath.startsWith("uploads/")) {
        return `../${normalizedPath}`;
    }

    return normalizedPath;
}

function getServiceInitials(value) {
    const parts = String(value || "").trim().split(/\s+/).filter(Boolean).slice(0, 2);

    if (parts.length === 0) {
        return "IS";
    }

    return parts.map((part) => part.charAt(0).toUpperCase()).join("");
}

function formatPublicServiceCurrency(value) {
    const numericValue = Number(value || 0);

    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        minimumFractionDigits: 2,
    }).format(numericValue);
}

function setPublicServicesFeedback(element, message, hidden, isError) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    element.hidden = hidden;
    element.textContent = message;
    element.classList.remove("is-error", "is-info");

    if (!hidden) {
        element.classList.add(isError ? "is-error" : "is-info");
    }
}

function escapePublicServiceHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function escapePublicServiceAttribute(value) {
    return escapePublicServiceHtml(value);
}

function initServicesGalleryCarousel() {
    const carousel = document.querySelector("[data-gallery-carousel]");

    if (!(carousel instanceof HTMLElement)) {
        return;
    }

    const track = carousel.querySelector("[data-gallery-track]");
    const prevButton = carousel.querySelector("[data-gallery-prev]");
    const nextButton = carousel.querySelector("[data-gallery-next]");
    const status = carousel.querySelector("[data-gallery-status]");

    if (
        !(track instanceof HTMLElement) ||
        !(prevButton instanceof HTMLButtonElement) ||
        !(nextButton instanceof HTMLButtonElement) ||
        !(status instanceof HTMLElement)
    ) {
        return;
    }

    const pages = Array.from(track.children);
    let currentPage = 0;

    const syncGallery = () => {
        track.style.transform = `translateX(-${currentPage * 100}%)`;
        status.textContent = `Pagina ${currentPage + 1} de ${pages.length}`;
        prevButton.disabled = currentPage === 0;
        nextButton.disabled = currentPage === pages.length - 1;
    };

    prevButton.addEventListener("click", () => {
        currentPage = Math.max(0, currentPage - 1);
        syncGallery();
    });

    nextButton.addEventListener("click", () => {
        currentPage = Math.min(pages.length - 1, currentPage + 1);
        syncGallery();
    });

    syncGallery();
}
