/**
 * dashboard.js
 * Módulo para el dashboard/inicio de la aplicación
 *
 * @description
 * Maneja la lógica del dashboard mostrando contadores de:
 * - Personal (adentro, trabajando, residiendo, otras actividades, comisión)
 * - Vehículos (fiscal, funcionario, residente, visita, empresa)
 * - Visitas
 * - Alertas
 *
 * @author Refactorización 2025-10-25
 */

import dashboardApi from '../api/dashboard-api.js';
import { showToast } from './ui/notifications.js';

let mainContent;
let dashboardRefreshInterval = null;
let dashboardDetailModalInstance = null;

/**
 * Inicializa el módulo de dashboard
 * Carga datos y configura auto-refresh
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal
 * @returns {void}
 */
export function initDashboardModule(contentElement) {
    mainContent = contentElement;

    // Rellenar y configurar el modal que ya existe en el HTML (vacío)
    setupDashboardDetailModal();

    loadDashboardData();
    setupDashboardControls();
}

/**
 * Exporta la función loadDashboardData para que pueda ser llamada desde otros módulos
 * Útil para refrescar el dashboard cuando se registra un acceso en el pórtico
 * @returns {Promise<void>}
 */
export async function refreshDashboardData() {
    await loadDashboardData();
}

/**
 * Rellena el contenido del modal de detalles del dashboard
 * El modal ya existe en el HTML como contenedor vacío
 * @private
 */
function setupDashboardDetailModal() {
    const modalEl = document.getElementById('dashboard-detail-modal');
    if (!modalEl) {
        console.warn('Modal dashboard-detail-modal no encontrado en el HTML');
        return;
    }

    // Rellenar el contenido del modal (que estaba vacío)
    const modalContent = `
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dashboard-detail-modal-title">Detalles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead id="dashboard-detail-table-head">
                                <tr><th>Nombre</th><th>RUT</th><th>Tipo</th><th>Hora</th></tr>
                            </thead>
                            <tbody id="dashboard-detail-table-body">
                                <tr><td colspan="4" class="text-center">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Inyectar contenido en el modal
    modalEl.innerHTML = modalContent;

    // Crear instancia de Bootstrap Modal DESPUÉS de inyectar contenido
    // Usar setTimeout y try-catch para asegurar que el DOM está listo
    setTimeout(() => {
        try {
            dashboardDetailModalInstance = new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: true
            });
        } catch (error) {
            console.error('Error al crear instancia de Bootstrap Modal:', error);
        }
    }, 50); // Pequeño delay para asegurar que el DOM está listo
}

/**
 * Carga los datos del dashboard y actualiza la UI
 * @private
 */
async function loadDashboardData() {
    try {
        const dashboardData = await dashboardApi.getData();
        updateDashboardUI(dashboardData);

        // Configurar eventos de tarjetas clickeables
        setupDashboardCardEvents();
    } catch (error) {
        console.error('❌ Error al cargar dashboard:', error);
        showToast('Error al cargar el dashboard: ' + error.message, 'error');
    }
}

/**
 * Actualiza la UI del dashboard con los datos
 * @private
 */
function updateDashboardUI(data) {
    if (!data) return;

    // Alertas (con visibilidad condicional)
    const alertaAtrasadoContainer = mainContent.querySelector('#alerta-atrasado-container');
    const alertaNoAutorizadoContainer = mainContent.querySelector('#alerta-no-autorizado-container');
    const alertasTitle = mainContent.querySelector('#alertas-title');

    const atrasadoCount = parseInt(data.alerta_atrasado_count ?? 0, 10);
    const noAutorizadoCount = parseInt(data.alerta_no_autorizado_count ?? 0, 10);

    if (alertaAtrasadoContainer) {
        const countEl = alertaAtrasadoContainer.querySelector('#alerta-atrasado-count');
        if (countEl) countEl.textContent = atrasadoCount;
        alertaAtrasadoContainer.classList.toggle('d-none', atrasadoCount === 0);
    }

    if (alertaNoAutorizadoContainer) {
        const countEl = alertaNoAutorizadoContainer.querySelector('#alerta-no-autorizado-count');
        if (countEl) countEl.textContent = noAutorizadoCount;
        alertaNoAutorizadoContainer.classList.toggle('d-none', noAutorizadoCount === 0);
    }

    if (alertasTitle) {
        alertasTitle.classList.toggle('d-none', atrasadoCount === 0 && noAutorizadoCount === 0);
    }

    // Contadores de Personal
    updateCounterElement('personal-general-adentro-count', data.personal_general_adentro);
    updateCounterElement('personal-trabajando-count', data.personal_trabajando);
    updateCounterElement('personal-residiendo-count', data.personal_residiendo);
    updateCounterElement('personal-otras-actividades-count', data.personal_otras_actividades);
    updateCounterElement('personal-en-comision-count', data.personal_en_comision);

    // Contadores de Visitas y Empresas
    updateCounterElement('empresas-adentro-count', data.empresas_adentro);
    updateCounterElement('visitas-adentro-count', data.visitas_adentro);

    // Contadores de Vehículos
    updateCounterElement('vehiculos-funcionario-adentro-count', data.vehiculos_funcionario_adentro);
    updateCounterElement('vehiculos-residente-adentro-count', data.vehiculos_residente_adentro);
    updateCounterElement('vehiculos-visita-adentro-count', data.vehiculos_visita_adentro);
    updateCounterElement('vehiculos-proveedor-adentro-count', data.vehiculos_proveedor_adentro);
    updateCounterElement('vehiculos-fiscal-adentro-count', data.vehiculos_fiscal_adentro);
}

/**
 * Actualiza un elemento contador con un valor
 * @private
 */
function updateCounterElement(elementId, value) {
    const element = mainContent.querySelector(`#${elementId}`);
    if (element) {
        element.textContent = value ?? '0';
    }
}

/**
 * Configura los controles del dashboard (botón de actualización, auto-refresh, etc)
 * @private
 */
function setupDashboardControls() {
    const refreshButton = mainContent.querySelector('#refresh-dashboard');

    if (refreshButton) {
        refreshButton.addEventListener('click', async () => {
            try {
                refreshButton.disabled = true;
                refreshButton.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Actualizando...';
                await loadDashboardData();
                refreshButton.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Actualizar';
                showToast('Dashboard actualizado correctamente', 'success');
            } catch (error) {
                showToast('Error al actualizar el dashboard', 'error');
            } finally {
                refreshButton.disabled = false;
            }
        });
    }

    // Configurar auto-refresh cada 1 minuto
    if (dashboardRefreshInterval) {
        clearInterval(dashboardRefreshInterval);
    }
    dashboardRefreshInterval = setInterval(() => {
        loadDashboardData();
    }, 60000); // 1 minuto
}

/**
 * Configura eventos click en las tarjetas del dashboard
 * @private
 */
function setupDashboardCardEvents() {
    const cards = mainContent.querySelectorAll('[data-category]');

    cards.forEach(card => {
        const category = card.dataset.category;

        // Skip modal para 'personal-general-adentro'
        if (category === 'personal-general-adentro') {
            card.style.cursor = 'default';
            return;
        }

        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
            const titleEl = card.querySelector('.text-muted, .text-danger, .text-warning');
            const title = titleEl ? titleEl.textContent : category;
            openDashboardDetailModal(category, title);
        });
    });
}

/**
 * Abre el modal con detalles de una categoría
 * @private
 */
async function openDashboardDetailModal(category, title) {
    const modalEl = document.getElementById('dashboard-detail-modal');
    if (!modalEl) {
        showToast('Modal no disponible', 'warning');
        console.error('Modal dashboard-detail-modal no encontrado en el DOM');
        return;
    }

    // Verificar que la instancia de Bootstrap Modal existe
    if (!dashboardDetailModalInstance) {
        showToast('El modal no está completamente inicializado', 'warning');
        console.error('Instancia de Bootstrap Modal no disponible');
        return;
    }

    const modalTitle = modalEl.querySelector('#dashboard-detail-modal-title');
    const modalBody = modalEl.querySelector('.modal-body');

    if (modalTitle) modalTitle.textContent = title;
    if (modalBody) modalBody.innerHTML = '<div class="text-center text-muted">Cargando...</div>';

    try {
        // Mostrar el modal usando la instancia existente
        dashboardDetailModalInstance.show();

        // Si es personal-trabajando o alguna alerta, mostrar tarjetas por unidad
        if (category === 'personal-trabajando' || category === 'alerta-atrasado' || category === 'alerta-no-autorizado') {
            await renderUnidadesCards(modalBody, category);
        } else {
            // Para otras categorías, mostrar tabla como antes
            const tableHead = modalEl.querySelector('#dashboard-detail-table-head');
            const tableBody = modalEl.querySelector('#dashboard-detail-table-body');

            // Restaurar estructura de tabla si no existe
            if (!tableHead || !tableBody) {
                modalBody.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead id="dashboard-detail-table-head">
                                <tr><th>Nombre</th><th>RUT</th><th>Tipo</th><th>Hora</th></tr>
                            </thead>
                            <tbody id="dashboard-detail-table-body">
                                <tr><td colspan="4" class="text-center">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                `;
            }

            // Cargar datos del API
            const data = await dashboardApi.getDetails(category);

            // Renderizar contenido en la tabla
            renderDashboardDetailModal(category, data, modalEl.querySelector('#dashboard-detail-table-head'), modalEl.querySelector('#dashboard-detail-table-body'));
        }
    } catch (error) {
        if (modalBody) {
            modalBody.innerHTML = `<div class="text-center text-danger">Error al cargar detalles: ${error.message}</div>`;
        }
        console.error('Error al cargar detalles del dashboard:', error);
        showToast('Error al cargar detalles del dashboard', 'error');
    }
}

/**
 * Renderiza el contenido del modal de detalles
 * @private
 */
function renderDashboardDetailModal(category, data, tableHead, tableBody) {
    // Validar que tenemos elementos de tabla
    if (!tableHead || !tableBody) {
        console.error('Elementos de tabla no encontrados');
        return;
    }

    // Validar que data es un array
    if (!Array.isArray(data) || data.length === 0) {
        console.warn(`No hay datos disponibles para la categoría: ${category}`);
        console.warn('Data recibida:', data);
        let mensaje = 'No hay datos disponibles';

        // Mensaje más específico según la categoría
        if (category && category.includes('vehiculos')) {
            mensaje = 'No hay vehículos registrados actualmente en la base de datos con entrada activa';
        } else if (category && category.includes('personal')) {
            mensaje = 'No hay personal registrado en esta categoría';
        }

        tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">${mensaje}</td></tr>`;
        return;
    }

    let headers = '';
    let rows = '';

    // Definir estructura según categoría
    if (category === 'personal-otras-actividades') {
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Móvil</th><th class="text-center">Hora de Entrada</th><th class="text-center">Motivo</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                    <td class="text-center">${item.motivo || 'No especificado'}</td>
                </tr>
            `;
        }).join('');
    } else if (category === 'alerta-atrasado') {
        // Personal que se quedó después de horario
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Móvil</th><th class="text-center">Unidad</th><th class="text-center">Hora de Entrada</th><th class="text-center">Hora de Salida Autorizada</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            const horaSalidaAutorizada = item.fecha_hora_termino ? new Date(item.fecha_hora_termino).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${item.Unidad || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                    <td class="text-center">${horaSalidaAutorizada}</td>
                </tr>
            `;
        }).join('');
    } else if (category === 'alerta-no-autorizado') {
        // Personal fuera de horario autorizado
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Móvil</th><th class="text-center">Unidad</th><th class="text-center">Hora de Entrada</th><th class="text-center">Hora de Salida Autorizada</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            const horaSalidaAutorizada = 'No autorizado';
            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${item.Unidad || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                    <td class="text-center">${horaSalidaAutorizada}</td>
                </tr>
            `;
        }).join('');
    } else if (category === 'personal-en-comision') {
        // Estructura especial para personal en comisión
        headers = `<tr><th class="text-center">Nombre Completo</th><th class="text-center">Unidad Origen</th><th class="text-center">POC</th><th class="text-center">Unidad POC</th><th class="text-center">Hora de Entrada</th></tr>`;
        rows = data.map(item => {
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';

            // Formatear POC con ícono si existe
            const pocNombre = item.poc_nombre && item.poc_nombre.trim() !== ''
                ? `<span class="text-primary"><i class="bi bi-person-badge me-1"></i>${item.poc_nombre}</span>`
                : 'N/A';

            const unidadPoc = item.unidad_poc && item.unidad_poc.trim() !== ''
                ? `<span class="text-muted"><i class="bi bi-building me-1"></i>${item.unidad_poc}</span>`
                : 'N/A';

            return `
                <tr>
                    <td class="text-center">${item.nombre_completo || 'N/A'}</td>
                    <td class="text-center">${item.unidad_origen || 'N/A'}</td>
                    <td class="text-center">${pocNombre}</td>
                    <td class="text-center">${unidadPoc}</td>
                    <td class="text-center">${horaEntrada}</td>
                </tr>
            `;
        }).join('');
    } else if (category === 'personal-trabajando') {
        // Estructura específica para personal trabajando CON salida posterior
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Móvil</th><th class="text-center">Unidad</th><th class="text-center">Hora de Entrada</th><th class="text-center">Salida Posterior</th><th class="text-center">Hora Salida</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const tieneSalidaPosterior = item.fecha_hora_termino !== null && item.fecha_hora_termino !== 'N/A';
            const salidaPosteriorTexto = tieneSalidaPosterior ? 'Sí' : 'No';
            const horaSalida = tieneSalidaPosterior ? new Date(item.fecha_hora_termino).toLocaleString('es-CL', { hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';

            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${item.Unidad || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                    <td class="text-center"><span class="badge ${tieneSalidaPosterior ? 'bg-success' : 'bg-secondary'}">${salidaPosteriorTexto}</span></td>
                    <td class="text-center">${horaSalida}</td>
                </tr>
            `;
        }).join('');
    } else if (category === 'personal-residiendo') {
        // Estructura para personal residiendo
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Móvil</th><th class="text-center">Hora de Entrada</th><th class="text-center">Estado</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                    <td class="text-center"><span class="badge bg-info">Residiendo</span></td>
                </tr>
            `;
        }).join('');
    } else if (category === 'empresas-adentro') {
        // Estructura especial para empleados de empresas
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Empresa</th><th class="text-center">Hora de Entrada</th><th class="text-center">Tipo</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.nombre} ${item.paterno} ${item.materno}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.empresa_nombre || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                    <td class="text-center">Empresa</td>
                </tr>
            `;
        }).join('');
    } else if (category === 'visitas-adentro') {
        // Estructura especial para visitas
        headers = `<tr><th class="text-center">Nombre Completo</th><th class="text-center">Tipo de Visita</th><th class="text-center">POC / Familiar de</th><th class="text-center">Hora de Entrada</th></tr>`;
        rows = data.map(item => {
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';

            // Determinar si mostrar POC o Familiar
            let pocFamiliar = 'N/A';
            if (item.poc_nombre && item.poc_nombre.trim() !== '') {
                pocFamiliar = `<span class="text-primary"><i class="bi bi-person-badge me-1"></i>${item.poc_nombre}</span>`;
            } else if (item.familiar_nombre && item.familiar_nombre.trim() !== '') {
                pocFamiliar = `<span class="text-success"><i class="bi bi-people me-1"></i>${item.familiar_nombre}</span>`;
            }

            return `
                <tr>
                    <td class="text-center">${item.nombre_completo || 'N/A'}</td>
                    <td class="text-center">${item.tipo || 'N/A'}</td>
                    <td class="text-center">${pocFamiliar}</td>
                    <td class="text-center">${horaEntrada}</td>
                </tr>
            `;
        }).join('');
    } else if (category.includes('vehiculos')) {
        // Estructura para vehículos
        headers = `<tr><th class="text-center">Patente</th><th class="text-center">Marca / Modelo</th><th class="text-center">Asociado</th><th class="text-center">Hora Entrada</th></tr>`;
        rows = data.map(item => {
            const modelo = `${item.marca || ''} ${item.modelo || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            return `
                <tr>
                    <td class="text-center">${item.patente || 'N/A'}</td>
                    <td class="text-center">${modelo || 'N/A'}</td>
                    <td class="text-center">${item.asociado_nombre || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                </tr>
            `;
        }).join('');
    } else {
        // Por defecto: otras categorías de personal
        headers = `<tr><th class="text-center">Nombre</th><th class="text-center">Móvil</th><th class="text-center">Tipo</th><th class="text-center">Hora Entrada</th></tr>`;
        rows = data.map(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';
            return `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${item.tipo || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>
                </tr>
            `;
        }).join('');
    }

    tableHead.innerHTML = headers;
    tableBody.innerHTML = rows;
}

/**
 * Renderiza tarjetas agrupadas por unidad para Personal Trabajando o Alertas
 * @private
 */
/**
 * Obtiene la ruta del logo de una unidad
 * @param {string} unidadNombre - Nombre de la unidad
 * @returns {string} Ruta del logo o null si no existe
 */
function getUnidadLogo(unidadNombre) {
    if (!unidadNombre || unidadNombre === 'Sin Unidad') {
        return null;
    }

    // Mapeo de nombres de unidades a archivos de logo
    // Busca el archivo con el nombre exacto (case-insensitive) con extensiones .png, .jpg, .jpeg
    const logosDisponibles = {
        'BA': 'BA.png',
        'CI': 'ci.png',
        'G1': 'G1.png',
        'G2': 'g2.png',
        'G24': 'G24.jpg',
        'G3': 'G3.png',
        'G34': 'G34.png',
        'G44': 'G44.png',
        'GA': 'GA.jpg',
        'GBS': 'GBS.png',
        'GOB': 'GOB.jpg'
    };

    // Buscar coincidencia exacta (case-insensitive)
    const unidadUpper = unidadNombre.toUpperCase();
    const logoFile = logosDisponibles[unidadUpper];

    if (logoFile) {
        return `./assets/imagenes/logo_unidades/${logoFile}`;
    }

    // Si no se encuentra, retornar null para usar icono por defecto
    return null;
}

async function renderUnidadesCards(modalBody, category) {
    try {
        // Determinar endpoint según categoría
        let endpoint = '';
        let emptyMessage = '';
        let cardLabel = '';
        let cardColor = 'text-primary';
        let icon = 'bi-building-fill';

        switch(category) {
            case 'personal-trabajando':
                endpoint = 'personal-trabajando-por-unidad';
                emptyMessage = 'No hay personal trabajando actualmente';
                cardLabel = 'Personal trabajando';
                cardColor = 'text-primary';
                icon = 'bi-building-fill';
                break;
            case 'alerta-atrasado':
                endpoint = 'alerta-atrasado-por-unidad';
                emptyMessage = 'No hay personal atrasado actualmente';
                cardLabel = 'Personal atrasado';
                cardColor = 'text-warning';
                icon = 'bi-exclamation-triangle-fill';
                break;
            case 'alerta-no-autorizado':
                endpoint = 'alerta-no-autorizado-por-unidad';
                emptyMessage = 'No hay personal sin autorización actualmente';
                cardLabel = 'Personal sin autorización';
                cardColor = 'text-danger';
                icon = 'bi-exclamation-circle-fill';
                break;
        }

        // Obtener datos agrupados por unidad
        const response = await fetch(`./api/dashboard.php?details=${endpoint}`);
        const unidades = await response.json();

        if (!Array.isArray(unidades) || unidades.length === 0) {
            modalBody.innerHTML = `<div class="text-center text-muted">${emptyMessage}</div>`;
            return;
        }

        // Crear tarjetas por unidad
        let cardsHtml = '<div class="row g-3">';
        unidades.forEach(unidad => {
            const unidadNombre = unidad.Unidad || 'Sin Unidad';
            const cantidad = unidad.cantidad || 0;
            const logoPath = getUnidadLogo(unidadNombre);

            // Generar el contenido del logo o icono
            let logoHtml;
            if (logoPath) {
                logoHtml = `<img src="${logoPath}" alt="${unidadNombre}" class="mb-3" style="max-width: 80px; max-height: 80px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                           <i class="bi ${icon} display-4 ${cardColor}" style="display: none;"></i>`;
            } else {
                logoHtml = `<i class="bi ${icon} display-4 ${cardColor}"></i>`;
            }

            cardsHtml += `
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 shadow-sm unidad-card" data-unidad="${unidadNombre}" data-category="${category}" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body text-center">
                            ${logoHtml}
                            <h5 class="card-title ${cardColor} mt-2">
                                ${unidadNombre}
                            </h5>
                            <p class="card-text display-6 fw-bold">${cantidad}</p>
                            <small class="text-muted">${cardLabel}</small>
                        </div>
                    </div>
                </div>
            `;
        });
        cardsHtml += '</div>';

        modalBody.innerHTML = cardsHtml;

        // Agregar eventos de hover y click
        const unidadCards = modalBody.querySelectorAll('.unidad-card');
        unidadCards.forEach(card => {
            // Hover effect
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'scale(1.05)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
            });

            // Click event
            card.addEventListener('click', async () => {
                const unidadNombre = card.dataset.unidad;
                const cardCategory = card.dataset.category;
                await renderUnidadDetalle(modalBody, unidadNombre, cardCategory);
            });
        });

    } catch (error) {
        console.error('Error al cargar unidades:', error);
        modalBody.innerHTML = '<div class="text-center text-danger">Error al cargar las unidades</div>';
    }
}

/**
 * Renderiza el detalle de personal de una unidad específica
 * @private
 */
async function renderUnidadDetalle(modalBody, unidadNombre, category) {
    try {
        modalBody.innerHTML = '<div class="text-center text-muted">Cargando personal de ' + unidadNombre + '...</div>';

        // Determinar endpoint según categoría
        let endpoint = '';
        let emptyMessage = '';

        switch(category) {
            case 'personal-trabajando':
                endpoint = 'personal-trabajando-unidad-detalle';
                emptyMessage = 'No hay personal de ' + unidadNombre + ' trabajando actualmente';
                break;
            case 'alerta-atrasado':
                endpoint = 'alerta-atrasado-unidad-detalle';
                emptyMessage = 'No hay personal de ' + unidadNombre + ' atrasado actualmente';
                break;
            case 'alerta-no-autorizado':
                endpoint = 'alerta-no-autorizado-unidad-detalle';
                emptyMessage = 'No hay personal de ' + unidadNombre + ' sin autorización actualmente';
                break;
        }

        // Obtener detalle de la unidad
        const response = await fetch(`./api/dashboard.php?details=${endpoint}&unidad=${encodeURIComponent(unidadNombre)}`);
        const personal = await response.json();

        if (!Array.isArray(personal) || personal.length === 0) {
            modalBody.innerHTML = `
                <div class="text-center">
                    <button class="btn btn-secondary mb-3" id="btn-volver-unidades">
                        <i class="bi bi-arrow-left me-2"></i>Volver a Unidades
                    </button>
                    <p class="text-muted">${emptyMessage}</p>
                </div>
            `;

            // Event listener para volver
            document.getElementById('btn-volver-unidades')?.addEventListener('click', () => {
                renderUnidadesCards(modalBody, category);
            });
            return;
        }

        // Renderizar tabla con el personal - columnas según categoría
        let tableHtml = `
            <div class="mb-3">
                <button class="btn btn-secondary btn-sm" id="btn-volver-unidades">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Unidades
                </button>
                <span class="ms-3 text-muted">Personal de ${unidadNombre}: <strong>${personal.length}</strong></span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Nombre</th>
                            <th class="text-center">Móvil</th>
                            <th class="text-center">Hora de Entrada</th>`;

        // Agregar columnas específicas según categoría
        if (category === 'personal-trabajando' || category === 'alerta-atrasado') {
            tableHtml += `
                            <th class="text-center">Salida Posterior</th>
                            <th class="text-center">Hora Salida</th>`;
        }

        tableHtml += `
                        </tr>
                    </thead>
                    <tbody>
        `;

        personal.forEach(item => {
            const nombre = `${item.Grado || ''} ${item.Nombres} ${item.Paterno} ${item.Materno || ''}`.trim();
            const horaEntrada = item.entry_time ? new Date(item.entry_time).toLocaleString('es-CL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';

            tableHtml += `
                <tr>
                    <td class="text-center">${nombre}</td>
                    <td class="text-center">${item.movil1 || 'N/A'}</td>
                    <td class="text-center">${horaEntrada}</td>`;

            // Agregar columnas específicas según categoría
            if (category === 'personal-trabajando' || category === 'alerta-atrasado') {
                const tieneSalidaPosterior = item.fecha_hora_termino !== null && item.fecha_hora_termino !== 'N/A' && item.fecha_hora_termino !== undefined;
                const salidaPosteriorTexto = tieneSalidaPosterior ? 'Sí' : 'No';
                const horaSalida = tieneSalidaPosterior ? new Date(item.fecha_hora_termino).toLocaleString('es-CL', { hour: '2-digit', minute: '2-digit', hour12: false }) : 'N/A';

                tableHtml += `
                    <td class="text-center"><span class="badge ${tieneSalidaPosterior ? 'bg-success' : 'bg-secondary'}">${salidaPosteriorTexto}</span></td>
                    <td class="text-center">${horaSalida}</td>`;
            }

            tableHtml += `
                </tr>
            `;
        });

        tableHtml += `
                    </tbody>
                </table>
            </div>
        `;

        modalBody.innerHTML = tableHtml;

        // Event listener para volver
        document.getElementById('btn-volver-unidades')?.addEventListener('click', () => {
            renderUnidadesCards(modalBody, category);
        });

    } catch (error) {
        console.error('Error al cargar detalle de unidad:', error);
        modalBody.innerHTML = '<div class="text-center text-danger">Error al cargar el detalle de la unidad</div>';
    }
}

/**
 * Detiene el auto-refresh del dashboard
 * @public
 */
export function stopDashboardAutoRefresh() {
    if (dashboardRefreshInterval) {
        clearInterval(dashboardRefreshInterval);
        dashboardRefreshInterval = null;
    }
}
