/**
 * control.js
 * Módulo para control de acceso mediante escaneo
 *
 * @description
 * Centraliza toda la lógica de escaneo para:
 * - Pórtico (acceso general)
 * - Personal
 * - Vehículos
 * - Visitas
 *
 * @author Refactorización 2025-10-25
 */

import accessLogsApi from '../api/access-logs-api.js';
import controlPersonalStatusApi from '../api/control-personal-status-api.js';
import { showToast } from './ui/notifications.js';

let mainContent;
let porticoAllLogs = [];
let personalLogs = [];
let porticoRefreshInterval = null;
let controlPersonalCheckInterval = null;

// Inicializar objeto global para gestionar temporizadores de feedback
if (!window.feedbackTimers) {
    window.feedbackTimers = {};
}

/**
 * Inicializa el módulo de control
 * Debe llamarse una sola vez con el elemento principal del contenido
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal (main)
 * @param {Function} onPorticoScan - Callback después de scan en pórtico
 * @returns {void}
 */
export async function initControlModule(contentElement, onPorticoScan = null) {
    mainContent = contentElement;

    // Limpiar cualquier intervalo anterior
    if (porticoRefreshInterval) {
        clearInterval(porticoRefreshInterval);
        porticoRefreshInterval = null;
    }
    if (controlPersonalCheckInterval) {
        clearInterval(controlPersonalCheckInterval);
        controlPersonalCheckInterval = null;
    }

    // Detectar si es módulo de Personal o Pórtico
    const isPersonalModule = mainContent.querySelector('#personal-log-table') !== null;

    if (isPersonalModule) {
        // Módulo de Control de Unidades
        try {
            // Obtener estado del servidor
            const isControlPersonalEnabled = await controlPersonalStatusApi.getStatus();

            if (!isControlPersonalEnabled) {
                // Mostrar modal de bloqueo
                showControlPersonalLockedModal();
                // Deshabilitar todos los inputs
                disableControlPersonalInputs();
                // Iniciar intervalo de verificación cada 3 segundos
                startControlPersonalStatusCheck();
            } else {
                // Módulo habilitado - permitir funcionalidad normal
                enableControlPersonalInputs();
                setupScanListeners(onPorticoScan);
                setupPersonalSearchListeners();
                loadAndRenderPersonalLogs();
                startPersonalAutoRefresh();
            }
        } catch (error) {
            console.error('Error al obtener estado de Control de Unidades:', error);
            // Si hay error, asumir que está deshabilitado
            showControlPersonalLockedModal();
            disableControlPersonalInputs();
            startControlPersonalStatusCheck();
        }
    } else {
        // Módulo de Pórtico
        setupScanListeners(onPorticoScan);
        setupSearchListeners();
        setupControlPersonalToggle();
        startPorticoAutoRefresh();
    }
}


/**
 * Configura los listeners para la búsqueda de logs
 * @private
 */
function setupSearchListeners() {
    const searchInput = mainContent.querySelector('#search-portico-log');
    const clearSearchBtn = mainContent.querySelector('#clear-portico-search');

    if (!searchInput) return;

    // Listener para filtrado en tiempo real
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();

        // Mostrar u ocultar el botón de limpiar
        if (clearSearchBtn) {
            clearSearchBtn.style.display = searchTerm ? 'block' : 'none';
        }

        // Filtrar los logs
        const filteredLogs = porticoAllLogs.filter(log => {
            const name = (log.name || log.nombre || log.patente || '').toLowerCase();
            const rut = (log.rut || '').toLowerCase();
            const id = (log.target_id || '').toString().toLowerCase();
            const tipo = (log.target_type || log.type || '').toLowerCase();
            const empresa = (log.empresa_nombre || log.empresa || '').toLowerCase();

            return name.includes(searchTerm) ||
                   rut.includes(searchTerm) ||
                   id.includes(searchTerm) ||
                   tipo.includes(searchTerm) ||
                   empresa.includes(searchTerm);
        });

        // Renderizar tabla filtrada
        renderPorticoLogTable(filteredLogs);

        // Mostrar contador de resultados
        const resultCount = mainContent.querySelector('#portico-search-result-count');
        if (resultCount) {
            resultCount.textContent = `${filteredLogs.length} resultado(s) encontrado(s)`;
            resultCount.style.display = searchTerm ? 'block' : 'none';
        }
    });

    // Listener para botón de limpiar búsqueda
    if (clearSearchBtn) {
        clearSearchBtn.style.display = 'none'; // Inicialmente oculto
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            renderPorticoLogTable(porticoAllLogs);

            // Ocultar contador de resultados
            const resultCount = mainContent.querySelector('#portico-search-result-count');
            if (resultCount) resultCount.style.display = 'none';

            // Ocultar botón de limpiar
            clearSearchBtn.style.display = 'none';

            // Enfocar el campo de escaneo
            const scanInput = mainContent.querySelector('#scan-portico-input');
            if (scanInput) scanInput.focus();
        });
    }
}

/**
 * Configura el toggle para habilitar/deshabilitar Control de Unidades
 * @private
 */
function setupControlPersonalToggle() {
    const toggleBtn = mainContent.querySelector('#toggle-control-personal-btn');
    if (!toggleBtn) return;

    // Obtener estado desde el servidor
    controlPersonalStatusApi.getStatus().then(isEnabled => {
        updateToggleButtonState(toggleBtn, isEnabled);
    }).catch(error => {
        console.error('Error al obtener estado:', error);
    });

    // Agregar listener para cambiar estado
    toggleBtn.addEventListener('click', async () => {
        try {
            // Obtener estado actual del servidor
            const currentState = await controlPersonalStatusApi.getStatus();
            const newState = !currentState;

            // Actualizar en el servidor
            await controlPersonalStatusApi.setStatus(newState);

            // Actualizar botón
            updateToggleButtonState(toggleBtn, newState);

            // Mostrar notificación
            const status = newState ? 'habilitado' : 'deshabilitado';
            showToast(`Control de Unidades ${status}`, newState ? 'success' : 'info');

            // Si el módulo Control de Unidades está abierto, actualizar su estado
            if (mainContent.querySelector('#personal-log-table')) {
                // Cerrar modal si está abierto
                const lockedModal = document.getElementById('control-personal-locked-modal');
                if (lockedModal) {
                    const bsModal = bootstrap.Modal.getInstance(lockedModal);
                    if (bsModal) bsModal.hide();
                }

                // Habilitar/Deshabilitar según el nuevo estado
                if (newState) {
                    // Habilitar módulo
                    enableControlPersonalInputs();
                    setupScanListeners();
                    setupPersonalSearchListeners();
                    loadAndRenderPersonalLogs();
                    startPersonalAutoRefresh();
                } else {
                    // Deshabilitar módulo
                    disableControlPersonalInputs();
                    showControlPersonalLockedModal();
                }
            }
        } catch (error) {
            console.error('Error al cambiar estado:', error);
            showToast('Error al actualizar Control de Unidades', 'error');
        }
    });
}

/**
 * Actualiza el estado visual del botón de toggle
 * @private
 */
function updateToggleButtonState(button, isEnabled) {
    if (isEnabled) {
        button.classList.remove('btn-warning');
        button.classList.add('btn-success');
        button.innerHTML = '<i class="bi bi-toggle-on me-1"></i> Deshabilitar Control de Unidades';
    } else {
        button.classList.remove('btn-success');
        button.classList.add('btn-warning');
        button.innerHTML = '<i class="bi bi-toggle-off me-1"></i> Habilitar Control de Unidades';
    }
}

/**
 * Configura los listeners para todos los formularios de escaneo
 * @private
 */
function setupScanListeners(onPorticoScan) {
    const scanPorticoForm = mainContent.querySelector('#scan-portico-form');
    const scanPersonalForm = mainContent.querySelector('#scan-personal-form');
    const scanVehiculoForm = mainContent.querySelector('#scan-vehiculo-form');
    const scanVisitaForm = mainContent.querySelector('#scan-visita-form');

    if (scanPorticoForm) {
        scanPorticoForm.addEventListener('submit', (e) => handleScanPorticoSubmit(e, onPorticoScan));
    }

    if (scanPersonalForm) {
        scanPersonalForm.addEventListener('submit', handleScanPersonalSubmit);
    }

    if (scanVehiculoForm) {
        scanVehiculoForm.addEventListener('submit', handleScanVehiculoSubmit);
    }

    if (scanVisitaForm) {
        scanVisitaForm.addEventListener('submit', handleScanVisitaSubmit);
    }
}

/**
 * Maneja el escaneo en el pórtico
 * @private
 */
async function handleScanPorticoSubmit(e, onPorticoScan = null) {
    e.preventDefault();
    const scanInput = mainContent.querySelector('#scan-portico-input');
    const targetId = scanInput.value.trim();
    if (!targetId) return;

    try {
        // Establecemos un bloqueo temporal para evitar múltiples escaneos
        if (window.scanInProgress) {
            return;
        }
        window.scanInProgress = true;

        // Limpiamos cualquier feedback anterior
        const feedbackEl = mainContent.querySelector('#portico-scan-feedback');
        if (feedbackEl) feedbackEl.innerHTML = '';

        // Limpiar TODOS los temporizadores pendientes
        if (window.feedbackTimers) {
            Object.keys(window.feedbackTimers).forEach(timerId => {
                clearTimeout(window.feedbackTimers[timerId]);
                delete window.feedbackTimers[timerId];
            });
        }

        const result = await accessLogsApi.logPortico(targetId);

        // Reproducir sonido de éxito
        playScanSound('success');

        if (result.action === 'clarification_required') {
            showClarificationModal(result.person_details, async () => {
                await loadAndRenderPorticoLogs();
                // Mostrar feedback después de cerrar el modal de clarificación
                // El resultado aquí tendrá el tipo 'personal' basado en los datos de clarification
                const feedbackData = {
                    ...result.person_details,
                    action: 'entrada',
                    type: 'personal',
                    name: result.person_details.name
                };
                renderPorticoScanFeedback(feedbackData, 'success');
            });
        } else {
            showToast(result.message || 'Acceso registrado con éxito.', 'success');
            await loadAndRenderPorticoLogs();
            renderPorticoScanFeedback(result, 'success');
        }

        // Llamar callback si existe
        if (onPorticoScan) {
            onPorticoScan();
        }

    } catch (error) {
        playScanSound('error');
        showToast(error.message, 'error');
        renderPorticoScanFeedback({ message: error.message }, 'error');
    } finally {
        window.scanInProgress = false;
        scanInput.value = '';
        scanInput.focus();
    }
}

/**
 * Maneja el escaneo manual de personal
 * @private
 */
async function handleScanPersonalSubmit(e) {
    e.preventDefault();
    const scanInput = mainContent.querySelector('#scan-personal-input');
    let targetId = scanInput.value.trim();

    // Filtrar solo números
    targetId = targetId.replace(/[^0-9]/g, '');

    if (!targetId) {
        showToast('Ingrese un RUT válido (solo números)', 'warning');
        return;
    }

    // Validar que tenga 7 u 8 dígitos
    if (targetId.length < 7 || targetId.length > 8) {
        showToast('El RUT debe tener 7 u 8 dígitos (sin dígito verificador)', 'warning');
        return;
    }

    try {
        const result = await accessLogsApi.logManual(targetId, 'personal', 'control_unidades');
        playScanSound('success');
        showToast(result.message || 'Acceso registrado.', 'success');
        scanInput.value = '';
        renderPersonalScanFeedback(result, 'success');
        // Reload logs after successful scan
        await loadAndRenderPersonalLogs();
    } catch (error) {
        playScanSound('error');
        console.error('Error registrando acceso de personal:', error);

        // Mejorar mensaje de error según el tipo
        let errorMessage = error.message || 'Error al registrar acceso';

        if (errorMessage.includes('404') || errorMessage.includes('no encontrada')) {
            errorMessage = 'Persona no encontrada. Verifique el RUT ingresado.';
        } else if (errorMessage.includes('403') || errorMessage.includes('Acceso denegado')) {
            errorMessage = error.message;
        } else if (errorMessage.includes('500')) {
            errorMessage = 'Error en el servidor. Intente más tarde.';
        }

        showToast(errorMessage, 'error');
        renderPersonalScanFeedback({ message: errorMessage }, 'error');
    }
}

/**
 * Maneja el escaneo manual de vehículos
 * @private
 */
async function handleScanVehiculoSubmit(e) {
    e.preventDefault();
    const scanInput = mainContent.querySelector('#scan-vehiculo-input');
    const targetId = scanInput.value.trim();
    if (!targetId) return;

    try {
        const result = await accessLogsApi.logManual(targetId, 'vehiculo');
        showToast(result.message || 'Acceso registrado.', 'success');
        scanInput.value = '';
        renderVehiculoScanFeedback(result, 'success');
    } catch (error) {
        showToast(error.message, 'error');
        renderVehiculoScanFeedback({ message: error.message }, 'error');
    }
}

/**
 * Maneja el escaneo manual de visitas
 * @private
 */
async function handleScanVisitaSubmit(e) {
    e.preventDefault();
    const scanInput = mainContent.querySelector('#scan-visita-input');
    const targetId = scanInput.value.trim();
    if (!targetId) return;

    try {
        const result = await accessLogsApi.logManual(targetId, 'visita');
        showToast(result.message || 'Acceso registrado.', 'success');
        scanInput.value = '';
        renderVisitaScanFeedback(result, 'success');
    } catch (error) {
        showToast(error.message, 'error');
        renderVisitaScanFeedback({ message: error.message }, 'error');
    }
}

/**
 * Carga y renderiza los logs del pórtico
 * @private
 */
async function loadAndRenderPorticoLogs() {
    try {
        const allLogs = await accessLogsApi.getAllTypes();

        // Combinar logs asignando el tipo_target correcto
        porticoAllLogs = [
            ...allLogs.personal.map(log => ({ ...log, target_type: 'personal' })),
            ...allLogs.vehiculo.map(log => ({ ...log, target_type: 'vehiculo' })),
            ...allLogs.visita.map(log => ({ ...log, target_type: 'visita' })),
            ...allLogs.personal_comision.map(log => ({ ...log, target_type: 'personal_comision' })),
            ...allLogs.empresa_empleado.map(log => ({ ...log, target_type: 'empresa_empleado' }))
        ];
        porticoAllLogs.sort((a, b) => new Date(b.log_time) - new Date(a.log_time));
        renderPorticoLogTable(porticoAllLogs);
    } catch (error) {
        console.error('Error al cargar logs del pórtico:', error);
    }
}

/**
 * Renderiza el feedback visual del escaneo del pórtico
 * @private
 */
function renderPorticoScanFeedback(data, type) {
    const feedbackEl = mainContent.querySelector('#portico-scan-feedback');
    if (!feedbackEl) return;

    // Generar un ID único para esta tarjeta de feedback
    const cardId = `feedback-card-${Date.now()}`;

    // Limpiar cualquier tarjeta existente
    feedbackEl.innerHTML = '';

    // Limpiar TODOS los temporizadores pendientes
    if (!window.feedbackTimers) {
        window.feedbackTimers = {};
    }
    Object.keys(window.feedbackTimers).forEach(timerId => {
        clearTimeout(window.feedbackTimers[timerId]);
        delete window.feedbackTimers[timerId];
    });

    // Crear un nuevo elemento de tarjeta con ID único
    const cardEl = document.createElement('div');
    cardEl.id = cardId;
    cardEl.className = 'card shadow-sm border-0 mt-4';

    // Aplicar animación de entrada usando transiciones CSS en lugar de clases animate
    cardEl.style.opacity = '0';
    cardEl.style.transform = 'translateY(-10px)';
    cardEl.style.transition = 'opacity 0.5s ease-in, transform 0.5s ease-in';

    // Aplicar un pequeño retraso para que la transición se active
    setTimeout(() => {
        cardEl.style.opacity = '1';
        cardEl.style.transform = 'translateY(0)';
    }, 10);

    if (type === 'success') {
        const isEntrada = data.action === 'entrada';
        const bgColorClass = isEntrada ? 'bg-success-subtle' : 'bg-danger-subtle';
        const textColorClass = isEntrada ? 'text-success-emphasis' : 'text-danger-emphasis';
        const iconClass = isEntrada ? 'bi bi-box-arrow-in-right' : 'bi bi-box-arrow-right';
        const actionText = isEntrada ? 'ENTRADA' : 'SALIDA';

        const currentTime = new Date().toLocaleTimeString();
        const currentDate = new Date().toLocaleDateString('es-CL');

        // Determinar el tipo de entidad y su icono
        let entityIcon = '';
        let entityTypeText = '';
        let photoHtml = '';

        switch(data.type) {
            case 'personal':
                entityIcon = 'bi bi-person-badge';
                entityTypeText = 'Personal';
                if (data.photoUrl) {
                    photoHtml = `<img src="../foto-emple/${data.photoUrl}" class="rounded-circle mx-auto mb-3 img-thumbnail" width="96" height="96" style="object-fit: cover;" alt="Foto de ${data.name}">`;
                }
                break;
            case 'empresa_empleado':
                entityIcon = 'bi bi-building';
                entityTypeText = 'Empresa';
                break;
            case 'vehiculo':
                entityIcon = 'bi bi-car-front';
                entityTypeText = 'Vehículo';
                break;
            case 'visita':
                entityIcon = 'bi bi-person-vcard';
                entityTypeText = 'Visita';
                break;
            case 'personal_comision':
                entityIcon = 'bi bi-briefcase';
                entityTypeText = 'Personal en Comisión';
                break;
            default:
                entityIcon = 'bi bi-person';
                entityTypeText = data.type || 'Desconocido';
        }

        // Construir detalles adicionales según el tipo de entidad
        let detailsHtml = '';

        if (data.type === 'empresa_empleado') {
            detailsHtml = `<div class="d-flex flex-column align-items-center mt-3">
                <hr class="w-50 my-2" />
                <div class="d-flex flex-column gap-3">
                    <div class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-${isEntrada ? 'success' : 'danger'}-subtle rounded-3 px-4 py-2 shadow-sm">
                            <i class="bi bi-building-fill me-2 text-${isEntrada ? 'success' : 'danger'}-emphasis fs-5"></i>
                            <span class="fw-medium text-${isEntrada ? 'success' : 'danger'}-emphasis">
                                ${data.empresa_nombre || 'Empresa no especificada'}
                            </span>
                        </div>
                    </div>
                    ${data.cargo ? `
                    <div class="text-center mt-1">
                        <div class="badge bg-secondary-subtle text-secondary px-3 py-2">
                            <i class="bi bi-briefcase me-1"></i> Cargo: ${data.cargo}
                        </div>
                    </div>` : ''}
                    ${data.rut ? `
                    <div class="text-center mt-1">
                        <div class="badge bg-dark-subtle text-dark px-3 py-2">
                            <i class="bi bi-person-vcard me-1"></i> RUT: ${data.rut}
                        </div>
                    </div>` : ''}
                </div>
            </div>`;
        } else if (data.type === 'vehiculo') {
            // Para vehículos, mostramos la información del vehículo de forma más integrada
            detailsHtml = `<div class="d-flex flex-column align-items-center mt-3">
                <hr class="w-50 my-2" />
                <div class="d-flex flex-column gap-3 w-100">
                    ${data.tipo ? `
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="badge bg-${isEntrada ? 'success' : 'danger'}-subtle text-${isEntrada ? 'success' : 'danger'} px-3 py-2">
                            <i class="bi bi-tag-fill me-1"></i> ${data.tipo}
                        </div>
                    </div>` : ''}
                    ${data.personalName ? `
                    <div class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-${isEntrada ? 'success' : 'danger'}-subtle text-${isEntrada ? 'success' : 'danger'}-emphasis rounded-pill px-3 py-1">
                            <i class="bi bi-person-circle me-2"></i>
                            <span>Propietario: <strong>${data.personalName}</strong></span>
                        </div>
                    </div>` : ''}
                </div>
            </div>`;
        } else if (data.type === 'personal') {
            const rutText = data.personalRut ? `<small class="d-block text-muted"><i class="bi bi-person-vcard-fill text-primary me-1"></i>RUT: ${data.personalRut}</small>` : '';
            const unidadText = data.personalUnidad ? `<small class="d-block text-muted mt-1"><i class="bi bi-building-fill text-success me-1"></i>${data.personalUnidad}</small>` : '';
            detailsHtml = `<div class="mt-1">${rutText}${unidadText}</div>`;
        }

        cardEl.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center ${bgColorClass}">
                <span class="fw-bold ${textColorClass}">
                    <i class="${iconClass} me-1"></i> Registro Exitoso
                </span>
                <div class="text-end">
                    <small class="text-muted d-block">${currentTime}</small>
                    <small class="text-muted">${currentDate}</small>
                </div>
            </div>
            <div class="card-body text-center p-4 rounded-bottom ${bgColorClass}">
                ${photoHtml}
                ${data.type === 'vehiculo' ?
                    `<div class="mb-3 mt-1">
                        <div class="d-flex flex-column align-items-center">
                            <div class="mb-1">
                                <i class="bi bi-car-front fs-1 text-${isEntrada ? 'success' : 'danger'}-emphasis"></i>
                            </div>
                            <div class="bg-dark text-white px-4 py-2 rounded-3 border border-2 border-${isEntrada ? 'success' : 'danger'} shadow-sm" style="letter-spacing: 1.5px; font-family: 'Consolas', monospace; font-size: 1.5rem;">
                                <strong>${data.patente || data.name || 'Patente no disponible'}</strong>
                            </div>
                        </div>
                    </div>` :
                    data.type === 'empresa_empleado' ?
                    `<div class="mb-2 mt-1">
                        <div class="d-flex flex-column align-items-center">
                            <div class="mb-2">
                                <i class="bi bi-person-badge fs-1 text-${isEntrada ? 'success' : 'danger'}-emphasis"></i>
                            </div>
                            <div class="badge bg-${isEntrada ? 'success' : 'danger'}-subtle text-${isEntrada ? 'success' : 'danger'} mb-1">
                                <i class="bi bi-buildings me-1"></i> Empleado
                            </div>
                            <h3 class="h4 fw-bold mb-0 border-bottom border-${isEntrada ? 'success' : 'danger'} pb-1">${data.name || 'Desconocido'}</h3>
                        </div>
                    </div>` :
                    `<h3 class="h5 fw-bold mb-1">${data.name || 'Desconocido'}</h3>`
                }
                <div class="badge ${isEntrada ? 'bg-success' : 'bg-danger'} text-white mb-2">
                    <i class="${entityIcon} me-1"></i> ${entityTypeText}
                </div>
                ${detailsHtml}
                ${data.type === 'vehiculo' || data.type === 'empresa_empleado' ? `
                <div class="d-flex justify-content-center align-items-center mt-3">
                    <span class="badge bg-${isEntrada ? 'success' : 'danger'} px-4 py-2 fs-5">
                        <i class="${iconClass} me-2"></i>${actionText}
                    </span>
                </div>
                ` : `
                <div class="d-flex justify-content-center align-items-center mt-3 mb-2">
                    <div class="border rounded-circle p-2 ${isEntrada ? 'border-success' : 'border-danger'} me-2">
                        <i class="${iconClass} fs-4 ${textColorClass}"></i>
                    </div>
                    <h4 class="m-0 ${textColorClass}">${actionText}</h4>
                </div>
                `}
                ${data.message ? `<div class="alert alert-${isEntrada ? 'success' : 'danger'} mt-3 mb-0 py-2 ${data.type === 'vehiculo' ? 'w-75 mx-auto' : ''} small">${data.message}</div>` : ''}
            </div>`;
    } else {
        // Caso de error
        const currentTime = new Date().toLocaleTimeString();
        const currentDate = new Date().toLocaleDateString('es-CL');

        cardEl.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center bg-danger-subtle">
                <span class="fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Error
                </span>
                <div class="text-end">
                    <small class="text-muted d-block">${currentTime}</small>
                    <small class="text-muted">${currentDate}</small>
                </div>
            </div>
            <div class="card-body text-center p-4 rounded-bottom bg-danger-subtle">
                <div class="d-flex justify-content-center mb-3">
                    <div class="border border-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px">
                        <i class="bi bi-x-lg text-danger fs-1"></i>
                    </div>
                </div>
                <h3 class="h5 fw-bold text-danger mb-3">Acceso Denegado</h3>
                <p class="alert alert-danger py-2">${data.message || 'Ha ocurrido un error al procesar su solicitud.'}</p>
                <p class="text-muted small mt-2">Verifique la información e intente nuevamente o contacte al administrador del sistema.</p>
            </div>`;
    }

    // Agregar la tarjeta al contenedor
    feedbackEl.appendChild(cardEl);

    // Enfocar el campo de escaneo después de mostrar la respuesta
    const scanInput = mainContent.querySelector('#scan-portico-input');
    if (scanInput) scanInput.focus();

    // Configuramos un nuevo temporizador para ocultar la tarjeta después de un tiempo
    window.feedbackTimers[`${cardId}-fade`] = setTimeout(() => {
        // Buscamos la tarjeta específica por su ID único
        const currentCard = document.getElementById(cardId);

        // Solo procedemos si la tarjeta específica aún existe en el DOM
        if (currentCard && feedbackEl && feedbackEl.contains(currentCard)) {
            // Aplicar una transición CSS suave en lugar de la clase animate
            currentCard.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            currentCard.style.opacity = '0';
            currentCard.style.transform = 'translateY(-10px)';

            // Esperamos a que termine la transición CSS antes de eliminar
            window.feedbackTimers[`${cardId}-cleanup`] = setTimeout(() => {
                // Buscamos nuevamente la tarjeta por su ID para asegurarnos de que aún existe
                const cardToRemove = document.getElementById(cardId);
                if (cardToRemove && feedbackEl && feedbackEl.contains(cardToRemove)) {
                    cardToRemove.remove();
                }
                delete window.feedbackTimers[`${cardId}-cleanup`];
            }, 500);
        }
        delete window.feedbackTimers[`${cardId}-fade`];
    }, 5000);
}

/**
 * Renderiza el feedback visual del escaneo de personal
 * @private
 */
function renderPersonalScanFeedback(data, type) {
    const feedbackEl = mainContent.querySelector('#personal-scan-feedback');
    if (!feedbackEl) return;

    // Generar un ID único para esta tarjeta de feedback
    const cardId = `feedback-card-${Date.now()}`;

    // Limpiar cualquier tarjeta existente
    feedbackEl.innerHTML = '';

    // Limpiar TODOS los temporizadores pendientes
    if (!window.feedbackTimers) {
        window.feedbackTimers = {};
    }
    Object.keys(window.feedbackTimers).forEach(timerId => {
        clearTimeout(window.feedbackTimers[timerId]);
        delete window.feedbackTimers[timerId];
    });

    // Crear un nuevo elemento de tarjeta con ID único
    const cardEl = document.createElement('div');
    cardEl.id = cardId;
    cardEl.className = 'card shadow-sm border-0 mt-4';

    // Aplicar animación de entrada usando transiciones CSS en lugar de clases animate
    cardEl.style.opacity = '0';
    cardEl.style.transform = 'translateY(-10px)';
    cardEl.style.transition = 'opacity 0.5s ease-in, transform 0.5s ease-in';

    // Aplicar un pequeño retraso para que la transición se active
    setTimeout(() => {
        cardEl.style.opacity = '1';
        cardEl.style.transform = 'translateY(0)';
    }, 10);

    if (type === 'success') {
        const isEntrada = data.action === 'entrada';
        const bgColorClass = isEntrada ? 'bg-success-subtle' : 'bg-danger-subtle';
        const textColorClass = isEntrada ? 'text-success-emphasis' : 'text-danger-emphasis';
        const iconClass = isEntrada ? 'bi bi-box-arrow-in-right' : 'bi bi-box-arrow-right';
        const actionText = isEntrada ? 'ENTRADA' : 'SALIDA';

        const currentTime = new Date().toLocaleTimeString();
        const currentDate = new Date().toLocaleDateString('es-CL');

        // Determinar el tipo de entidad y su icono
        let entityIcon = 'bi bi-person-badge';
        let entityTypeText = 'Personal';
        let photoHtml = '';

        if (data.personalPhotoUrl) {
            photoHtml = `<img src="../foto-emple/${data.personalPhotoUrl}" class="rounded-circle mx-auto mb-3 img-thumbnail" width="96" height="96" style="object-fit: cover;" alt="Foto de ${data.personalName}">`;
        }

        // Construir detalles adicionales
        let detailsHtml = '';
        if (data.personalRut || data.personalUnidad) {
            detailsHtml = `<div class="mt-2 d-flex flex-column gap-1">
                ${data.personalRut ? `<small class="d-block text-muted"><i class="bi bi-person-vcard me-1"></i>RUT: ${data.personalRut}</small>` : ''}
                ${data.personalUnidad ? `<small class="d-block text-muted"><i class="bi bi-building me-1"></i>Unidad: ${data.personalUnidad}</small>` : ''}
            </div>`;
        }

        cardEl.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center ${bgColorClass}">
                <span class="fw-bold ${textColorClass}">
                    <i class="${iconClass} me-1"></i> Registro Exitoso
                </span>
                <div class="text-end">
                    <small class="text-muted d-block">${currentTime}</small>
                    <small class="text-muted">${currentDate}</small>
                </div>
            </div>
            <div class="card-body text-center p-4 rounded-bottom ${bgColorClass}">
                ${photoHtml}
                <div class="mb-3 mt-1">
                    <div class="d-flex flex-column align-items-center">
                        <div class="mb-2">
                            <i class="bi bi-person-badge fs-1 text-${isEntrada ? 'success' : 'danger'}-emphasis"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-1">${data.personalName || 'Desconocido'}</h4>
                        ${detailsHtml}
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top border-${isEntrada ? 'success' : 'danger'}-subtle">
                    <h3 class="h3 fw-bold text-uppercase ${textColorClass} mb-0">
                        <i class="bi ${iconClass} me-2"></i>${actionText}
                    </h3>
                </div>
                <div class="mt-3">
                    <div class="badge bg-${isEntrada ? 'success' : 'danger'}-subtle text-${isEntrada ? 'success' : 'danger'}-emphasis px-3 py-2">
                        <i class="bi bi-check-circle-fill me-1"></i> Acceso Registrado
                    </div>
                </div>
            </div>`;
    } else {
        cardEl.innerHTML = `
            <div class="card-header bg-danger-subtle">
                <span class="fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> Error
                </span>
            </div>
            <div class="card-body text-center p-4 rounded-bottom bg-danger-subtle">
                <i class="bi bi-x-circle fs-1 text-danger mb-3"></i>
                <h4 class="h5 fw-bold text-danger mb-2">Error al registrar acceso</h4>
                <p class="text-danger mb-0">${data.message || 'Ha ocurrido un error.'}</p>
            </div>`;
    }

    feedbackEl.appendChild(cardEl);

    // Configuramos un nuevo temporizador para ocultar la tarjeta después de un tiempo
    window.feedbackTimers[`${cardId}-fade`] = setTimeout(() => {
        // Buscamos la tarjeta específica por su ID único
        const currentCard = document.getElementById(cardId);

        // Solo procedemos si la tarjeta específica aún existe en el DOM
        if (currentCard && feedbackEl && feedbackEl.contains(currentCard)) {
            // Aplicar una transición CSS suave en lugar de la clase animate
            currentCard.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            currentCard.style.opacity = '0';
            currentCard.style.transform = 'translateY(-10px)';

            // Esperamos a que termine la transición CSS antes de eliminar
            window.feedbackTimers[`${cardId}-cleanup`] = setTimeout(() => {
                // Buscamos nuevamente la tarjeta por su ID para asegurarnos de que aún existe
                const cardToRemove = document.getElementById(cardId);
                if (cardToRemove && feedbackEl && feedbackEl.contains(cardToRemove)) {
                    cardToRemove.remove();
                }
                delete window.feedbackTimers[`${cardId}-cleanup`];
            }, 500);
        }
        delete window.feedbackTimers[`${cardId}-fade`];
    }, 5000);
}

/**
 * Renderiza el feedback visual del escaneo de vehículos
 * @private
 */
function renderVehiculoScanFeedback(data, type) {
    const feedbackEl = mainContent.querySelector('#vehiculo-scan-feedback');
    if (!feedbackEl) return;

    // Generar un ID único para esta tarjeta de feedback
    const cardId = `feedback-card-${Date.now()}`;

    // Limpiar cualquier tarjeta existente
    feedbackEl.innerHTML = '';

    // Limpiar TODOS los temporizadores pendientes
    if (!window.feedbackTimers) {
        window.feedbackTimers = {};
    }
    Object.keys(window.feedbackTimers).forEach(timerId => {
        clearTimeout(window.feedbackTimers[timerId]);
        delete window.feedbackTimers[timerId];
    });

    // Crear un nuevo elemento de tarjeta con ID único
    const cardEl = document.createElement('div');
    cardEl.id = cardId;
    cardEl.className = 'card shadow-sm border-0 mt-4';

    // Aplicar animación de entrada usando transiciones CSS en lugar de clases animate
    cardEl.style.opacity = '0';
    cardEl.style.transform = 'translateY(-10px)';
    cardEl.style.transition = 'opacity 0.5s ease-in, transform 0.5s ease-in';

    // Aplicar un pequeño retraso para que la transición se active
    setTimeout(() => {
        cardEl.style.opacity = '1';
        cardEl.style.transform = 'translateY(0)';
    }, 10);

    let cardHtml = '';
    if (type === 'success') {
        const bgColorClass = data.action === 'entrada' ? 'bg-success-subtle' : 'bg-danger-subtle';
        const textColorClass = data.action === 'entrada' ? 'text-success-emphasis' : 'text-danger-emphasis';
        cardHtml = `
            <div class="card-body text-center p-4 rounded-3 ${bgColorClass}">
                <h3 class="h5 fw-bold">${data.patente || 'Desconocida'}</h3>
                <p class="text-muted">Asociado: ${data.personalName || 'N/A'}</p>
                <p class="mt-2 fw-bold fs-4 text-uppercase ${textColorClass}">${data.action || ''}</p>
            </div>`;
    } else {
        cardHtml = `
            <div class="card-body text-center p-4 rounded-3 bg-danger-subtle">
                <h3 class="h5 fw-bold text-danger">Error</h3>
                <p class="text-muted">${data.message || 'Ha ocurrido un error.'}</p>
            </div>`;
    }

    cardEl.innerHTML = cardHtml;
    feedbackEl.appendChild(cardEl);

    // Configuramos un nuevo temporizador para ocultar la tarjeta después de un tiempo
    window.feedbackTimers[`${cardId}-fade`] = setTimeout(() => {
        // Buscamos la tarjeta específica por su ID único
        const currentCard = document.getElementById(cardId);

        // Solo procedemos si la tarjeta específica aún existe en el DOM
        if (currentCard && feedbackEl && feedbackEl.contains(currentCard)) {
            // Aplicar una transición CSS suave en lugar de la clase animate
            currentCard.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            currentCard.style.opacity = '0';
            currentCard.style.transform = 'translateY(-10px)';

            // Esperamos a que termine la transición CSS antes de eliminar
            window.feedbackTimers[`${cardId}-cleanup`] = setTimeout(() => {
                // Buscamos nuevamente la tarjeta por su ID para asegurarnos de que aún existe
                const cardToRemove = document.getElementById(cardId);
                if (cardToRemove && feedbackEl && feedbackEl.contains(cardToRemove)) {
                    cardToRemove.remove();
                }
                delete window.feedbackTimers[`${cardId}-cleanup`];
            }, 500);
        }
        delete window.feedbackTimers[`${cardId}-fade`];
    }, 5000);
}

/**
 * Renderiza el feedback visual del escaneo de visitas
 * @private
 */
function renderVisitaScanFeedback(data, type) {
    const feedbackEl = mainContent.querySelector('#visita-scan-feedback');
    if (!feedbackEl) return;

    // Generar un ID único para esta tarjeta de feedback
    const cardId = `feedback-card-${Date.now()}`;

    // Limpiar cualquier tarjeta existente
    feedbackEl.innerHTML = '';

    // Limpiar TODOS los temporizadores pendientes
    if (!window.feedbackTimers) {
        window.feedbackTimers = {};
    }
    Object.keys(window.feedbackTimers).forEach(timerId => {
        clearTimeout(window.feedbackTimers[timerId]);
        delete window.feedbackTimers[timerId];
    });

    // Crear un nuevo elemento de tarjeta con ID único
    const cardEl = document.createElement('div');
    cardEl.id = cardId;
    cardEl.className = 'card shadow-sm border-0 mt-4';

    // Aplicar animación de entrada usando transiciones CSS en lugar de clases animate
    cardEl.style.opacity = '0';
    cardEl.style.transform = 'translateY(-10px)';
    cardEl.style.transition = 'opacity 0.5s ease-in, transform 0.5s ease-in';

    // Aplicar un pequeño retraso para que la transición se active
    setTimeout(() => {
        cardEl.style.opacity = '1';
        cardEl.style.transform = 'translateY(0)';
    }, 10);

    let cardHtml = '';
    if (type === 'success') {
        const bgColorClass = data.action === 'entrada' ? 'bg-success-subtle' : 'bg-danger-subtle';
        const textColorClass = data.action === 'entrada' ? 'text-success-emphasis' : 'text-danger-emphasis';
        cardHtml = `
            <div class="card-body text-center p-4 rounded-3 ${bgColorClass}">
                <h3 class="h5 fw-bold">${data.nombre || 'Desconocido'}</h3>
                <p class="text-muted">Empresa: ${data.empresa || 'N/A'}</p>
                <p class="mt-2 fw-bold fs-4 text-uppercase ${textColorClass}">${data.action || ''}</p>
            </div>`;
    } else {
        cardHtml = `
            <div class="card-body text-center p-4 rounded-3 bg-danger-subtle">
                <h3 class="h5 fw-bold text-danger">Error</h3>
                <p class="text-muted">${data.message || 'Ha ocurrido un error.'}</p>
            </div>`;
    }

    cardEl.innerHTML = cardHtml;
    feedbackEl.appendChild(cardEl);

    // Configuramos un nuevo temporizador para ocultar la tarjeta después de un tiempo
    window.feedbackTimers[`${cardId}-fade`] = setTimeout(() => {
        // Buscamos la tarjeta específica por su ID único
        const currentCard = document.getElementById(cardId);

        // Solo procedemos si la tarjeta específica aún existe en el DOM
        if (currentCard && feedbackEl && feedbackEl.contains(currentCard)) {
            // Aplicar una transición CSS suave en lugar de la clase animate
            currentCard.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            currentCard.style.opacity = '0';
            currentCard.style.transform = 'translateY(-10px)';

            // Esperamos a que termine la transición CSS antes de eliminar
            window.feedbackTimers[`${cardId}-cleanup`] = setTimeout(() => {
                // Buscamos nuevamente la tarjeta por su ID para asegurarnos de que aún existe
                const cardToRemove = document.getElementById(cardId);
                if (cardToRemove && feedbackEl && feedbackEl.contains(cardToRemove)) {
                    cardToRemove.remove();
                }
                delete window.feedbackTimers[`${cardId}-cleanup`];
            }, 500);
        }
        delete window.feedbackTimers[`${cardId}-fade`];
    }, 5000);
}

/**
 * Renderiza la tabla general de logs del pórtico
 * @private
 */
function renderPorticoLogTable(logs) {
    const tableBody = mainContent.querySelector('#portico-log-table');
    const logCount = mainContent.querySelector('#portico-log-count');

    if (!tableBody) return;

    if (logCount) {
        logCount.textContent = logs.length;
    }

    if (logs.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center p-4">
                    <div class="text-muted">
                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                        <span class="fw-medium">No se encontraron registros</span>
                        <small class="d-block mt-2">Los nuevos registros aparecerán aquí automáticamente</small>
                    </div>
                </td>
            </tr>`;
        return;
    }

    // Crear encabezado de la tabla con mejor estructura
    tableBody.innerHTML = `
        <tr class="table-light">
            <th style="width: 5%" class="text-center">#</th>
            <th style="width: 40%">Nombre/Identificación</th>
            <th style="width: 15%" class="text-center">Tipo</th>
            <th style="width: 15%" class="text-center">Acción</th>
            <th style="width: 25%" class="text-center">Fecha/Hora</th>
        </tr>
    `;

    // Agregar filas de datos
    logs.forEach((log, index) => {
        let nameCellHtml;
        let type;
        let typeClass;
        let iconHtml = '';

        if (log.target_type === 'empresa_empleado') {
            nameCellHtml = `
                <div class="d-flex flex-column">
                    <div class="fw-semibold">${log.name || 'Empleado'}</div>
                    <div class="d-flex align-items-center mt-1">
                        <i class="bi bi-building-fill text-info me-1"></i>
                        <small class="text-muted">${log.empresa_nombre || 'Empresa no especificada'}</small>
                    </div>
                </div>`;
            type = 'Empresa';
            typeClass = 'bg-info-subtle text-info-emphasis';
            iconHtml = '<i class="bi bi-building me-1"></i>';
        } else if (log.target_type === 'personal') {
            const unidadText = log.unidad ? `<div class="d-flex align-items-center mt-1">
                        <i class="bi bi-building-fill text-success me-1"></i>
                        <small class="text-muted">${log.unidad}</small>
                    </div>` : '';
            nameCellHtml = `
                <div class="d-flex flex-column">
                    <div class="fw-semibold">${log.name || 'Personal'}</div>
                    <div class="d-flex align-items-center mt-1">
                        <i class="bi bi-person-vcard-fill text-primary me-1"></i>
                        <small class="text-muted">RUT: ${log.rut || 'N/A'}</small>
                    </div>
                    ${unidadText}
                </div>`;
            type = 'Personal';
            typeClass = 'bg-primary-subtle text-primary-emphasis';
            iconHtml = '<i class="bi bi-person-badge me-1"></i>';
        } else if (log.target_type === 'visita') {
            const empresaText = log.empresa ? `<div class="d-flex align-items-center mt-1">
                        <i class="bi bi-briefcase-fill text-warning me-1"></i>
                        <small class="text-muted">${log.empresa}</small>
                    </div>` : '';
            nameCellHtml = `
                <div class="d-flex flex-column">
                    <div class="fw-semibold">${log.nombre || 'Visita'}</div>
                    ${empresaText}
                </div>`;
            type = log.tipo || 'Visita';
            typeClass = 'bg-warning-subtle text-warning-emphasis';
            iconHtml = '<i class="bi bi-person me-1"></i>';
        } else if (log.target_type === 'personal_comision') {
            nameCellHtml = `
                <div class="d-flex flex-column">
                    <div class="fw-semibold">${log.name || 'Personal en comisión'}</div>
                    <div class="d-flex align-items-center mt-1">
                        <i class="bi bi-briefcase-fill text-secondary me-1"></i>
                        <small class="text-muted">Comisión de servicio</small>
                    </div>
                </div>`;
            type = 'Comisión';
            typeClass = 'bg-secondary-subtle text-secondary-emphasis';
            iconHtml = '<i class="bi bi-briefcase me-1"></i>';
        } else if (log.target_type === 'vehiculo') {
            const personalText = log.personalName ? `<div class="d-flex align-items-center mt-1">
                        <i class="bi bi-person-fill text-success me-1"></i>
                        <small class="text-muted">${log.personalName}</small>
                    </div>` : '';
            nameCellHtml = `
                <div class="d-flex flex-column">
                    <div class="fw-semibold">${log.patente || 'Patente desconocida'}</div>
                    ${personalText}
                </div>`;
            type = 'Vehículo';
            typeClass = 'bg-success-subtle text-success-emphasis';
            iconHtml = '<i class="bi bi-car-front me-1"></i>';
        } else {
            nameCellHtml = `
                <div class="fw-semibold">${log.name || log.nombre || log.patente || log.target_id || 'Sin identificación'}</div>`;
            type = log.target_type || 'Desconocido';
            typeClass = 'bg-secondary-subtle text-secondary-emphasis';
        }

        // Formatear la fecha y hora de manera más legible
        const timestamp = log.timestamp || 'Sin fecha';
        let datePart = '';
        let timePart = '';

        if (timestamp && timestamp.includes(' ')) {
            const [date, time] = timestamp.split(' ');

            // Formatear fecha como DD/MM/YYYY
            if (date && date.includes('-')) {
                const [year, month, day] = date.split('-');
                datePart = `${day}/${month}/${year}`;
            } else {
                datePart = date;
            }

            timePart = time;
        }

        // Crear la fila de la tabla
        const row = document.createElement('tr');
        row.className = index % 2 === 0 ? 'table-row-even' : 'table-row-odd';
        row.innerHTML = `
            <td class="text-center align-middle">
                <span class="badge rounded-pill bg-light text-dark border">${index + 1}</span>
            </td>
            <td class="align-middle">${nameCellHtml}</td>
            <td class="text-center align-middle">
                <span class="badge ${typeClass} px-2 py-2">
                    ${iconHtml}${type}
                </span>
            </td>
            <td class="text-center align-middle">
                <span class="badge ${log.action === 'entrada' ? 'bg-success' : 'bg-danger'} px-2 py-2">
                    <i class="bi ${log.action === 'entrada' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'} me-1"></i>
                    ${log.action === 'entrada' ? 'Entrada' : 'Salida'}
                </span>
            </td>
            <td class="text-center align-middle">
                <div class="d-flex flex-column align-items-center">
                    <span class="fw-medium">${timePart || ''}</span>
                    <small class="text-muted">${datePart || ''}</small>
                </div>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

/**
 * Inicia la actualización automática de logs del pórtico
 * @private
 */
function startPorticoAutoRefresh() {
    loadAndRenderPorticoLogs();

    porticoRefreshInterval = setInterval(async () => {
        try {
            // Si hay un escaneo en progreso o hay tarjetas de feedback activas, no actualizamos
            if (window.scanInProgress || (window.feedbackTimers && Object.keys(window.feedbackTimers).length > 0)) {
                return;
            }

            const allLogs = await accessLogsApi.getAllTypes();
            porticoAllLogs = [
                ...allLogs.personal.map(log => ({ ...log, target_type: 'personal' })),
                ...allLogs.vehiculo.map(log => ({ ...log, target_type: 'vehiculo' })),
                ...allLogs.visita.map(log => ({ ...log, target_type: 'visita' })),
                ...allLogs.personal_comision.map(log => ({ ...log, target_type: 'personal_comision' })),
                ...allLogs.empresa_empleado.map(log => ({ ...log, target_type: 'empresa_empleado' }))
            ];
            porticoAllLogs.sort((a, b) => new Date(b.log_time) - new Date(a.log_time));

            // Re-aplicar búsqueda si existe un término de búsqueda activo
            const searchInput = mainContent.querySelector('#search-portico-log');
            if (searchInput && searchInput.value) {
                // Dispara el evento input para re-filtrar con el término de búsqueda actual
                searchInput.dispatchEvent(new Event('input'));
            } else {
                // Si no hay búsqueda activa, renderizar todos los logs
                renderPorticoLogTable(porticoAllLogs);
            }

            // Actualizar timestamp
            const lastUpdateEl = document.querySelector('#portico-last-update');
            if (lastUpdateEl) {
                const now = new Date();
                lastUpdateEl.textContent = `Última actualización: ${now.toLocaleTimeString()}`;
                lastUpdateEl.classList.remove('d-none');
            }

        } catch (error) {
            console.error('Error al actualizar logs:', error);
        }
    }, 5000); // Actualizar cada 5 segundos
}

/**
 * Detiene la actualización automática de logs del pórtico
 * @public
 */
export function stopPorticoAutoRefresh() {
    if (porticoRefreshInterval) {
        clearInterval(porticoRefreshInterval);
        porticoRefreshInterval = null;
    }
}

/**
 * Playea un sonido de escaneo
 * @private
 */
function playScanSound(type) {
    const audioElement = new Audio();
    if (type === 'success') {
        audioElement.src = './assets/sounds/scan-success.mp3';
    } else if (type === 'error') {
        audioElement.src = './assets/sounds/scan-error.mp3';
    }
    audioElement.play().catch(() => {
        console.log('No se pudo reproducir el sonido de escaneo');
    });
}

/**
 * Muestra el modal de clarificación para accesos ambiguos
 * @private
 */
function showClarificationModal(personDetails, onConfirm) {
    // Preparar datos del modal
    const photoUrl = personDetails.photoUrl ? `../foto-emple/${personDetails.photoUrl}` : 'assets/imagenes/placeholder-avatar.png';
    const rut = personDetails.rut || 'No disponible';
    const currentDate = new Date().toLocaleDateString('es-CL', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const currentTime = new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
    const unidad = personDetails.unidad || 'Sin unidad especificada';
    const grado = personDetails.grado || personDetails.Grado || '';
    const personName = personDetails.name || personDetails.Nombres || 'Desconocido';

    // Verificar si es residente
    const esResidente = personDetails.es_residente == 1;
    const badgeColor = esResidente ? 'bg-success' : 'bg-warning';
    const badgeIcon = esResidente ? 'bi-house-heart-fill' : 'bi-building';
    const badgeText = esResidente ? 'Residente' : 'No Residente';
    const alertType = esResidente ? 'alert-success' : 'alert-warning';
    const alertIcon = esResidente ? 'bi-house-check-fill' : 'bi-exclamation-triangle-fill';
    const alertText = esResidente
        ? 'Esta persona es residente. Por favor, confirme el motivo de su ingreso:'
        : 'Esta persona no es residente. Por favor, especifique el motivo del ingreso:';

    // Crear contenedor del modal
    const modalContainer = document.createElement('div');
    modalContainer.id = 'clarification-modal-container';
    modalContainer.classList.add('modal', 'fade');
    modalContainer.tabIndex = -1;

    // HTML del modal
    modalContainer.innerHTML = `
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Clarificación de Acceso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="badge ${badgeColor} text-white px-3 py-2 fs-6">
                        <i class="bi ${badgeIcon} me-1"></i>
                        Personal ${badgeText}
                    </div>
                    <div class="text-end">
                        <span class="d-block text-muted small">${currentDate}</span>
                        <span class="d-block text-muted small fw-bold">${currentTime}</span>
                    </div>
                </div>

                <div class="card border-0 bg-light shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3 text-center">
                                <img src="${photoUrl}" class="rounded-circle mb-2 img-thumbnail shadow-sm" width="100" height="100" style="object-fit: cover;" alt="Foto de ${personName}">
                                <div class="badge bg-primary text-white mt-1 d-block px-2 py-1">
                                    ${grado}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="mb-1 d-flex align-items-center">
                                    ${personName}
                                </h4>
                                <div class="d-flex flex-column gap-1 mt-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building text-primary me-2"></i>
                                        <span class="text-dark">${unidad}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-vcard text-primary me-2"></i>
                                        <span class="text-dark">RUT: ${rut}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-history text-primary me-2"></i>
                                        <span class="text-dark">Ingreso: ${currentTime}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert ${alertType} d-flex align-items-center py-3">
                    <i class="bi ${alertIcon} fs-5 me-3"></i>
                    <div>${alertText}</div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-3"><i class="bi bi-question-circle me-2"></i>Seleccione el motivo del ingreso:</h6>

                        <div class="d-flex flex-column gap-3">
                            ${esResidente ? `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="clarificationReason" id="reason-residencia" value="residencia" checked>
                                <label class="form-check-label fw-medium" for="reason-residencia">
                                    <span class="d-flex align-items-center">
                                        <span class="badge bg-success-subtle text-success me-2">
                                            <i class="bi bi-house-heart"></i>
                                        </span>
                                        Ir a residencia
                                    </span>
                                    <small class="d-block text-muted mt-1 ps-4">Se registrará como entrada a residencia</small>
                                </label>
                            </div>` : ''}

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="clarificationReason" id="reason-trabajo" value="trabajo" ${esResidente ? '' : 'checked'}>
                                <label class="form-check-label fw-medium" for="reason-trabajo">
                                    <span class="d-flex align-items-center">
                                        <span class="badge bg-primary-subtle text-primary me-2">
                                            <i class="bi bi-briefcase"></i>
                                        </span>
                                        Actividad laboral
                                    </span>
                                    <small class="d-block text-muted mt-1 ps-4">Se registrará como entrada por trabajo</small>
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="clarificationReason" id="reason-otros" value="otros">
                                <label class="form-check-label fw-medium" for="reason-otros">
                                    <span class="d-flex align-items-center">
                                        <span class="badge bg-secondary-subtle text-secondary me-2">
                                            <i class="bi bi-journal-text"></i>
                                        </span>
                                        Otro motivo
                                    </span>
                                    <small class="d-block text-muted mt-1 ps-4">Requiere especificar detalles adicionales</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" id="clarification-otros-details-container" style="display: none;">
                    <div class="card-body p-3 bg-light rounded">
                        <label for="clarification-otros-details" class="form-label">
                            <i class="bi bi-pencil-square me-1"></i>
                            Detalle el motivo del ingreso:
                        </label>
                        <textarea id="clarification-otros-details" class="form-control" rows="3"
                            placeholder="Por favor, especifique detalladamente el motivo de su ingreso..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="clarification-submit-btn" data-person-id="${personDetails.id}">
                    <i class="bi bi-check-circle me-1"></i> Confirmar Acceso
                </button>
            </div>
        </div>
    </div>`;

    // Remover modal anterior si existe
    const existingModal = document.getElementById('clarification-modal-container');
    if (existingModal) existingModal.remove();

    document.body.appendChild(modalContainer);
    const modal = new bootstrap.Modal(modalContainer);

    // Configurar listeners para radio buttons
    const radios = modalContainer.querySelectorAll('input[name="clarificationReason"]');
    const otrosDetailsContainer = modalContainer.querySelector('#clarification-otros-details-container');

    radios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            otrosDetailsContainer.style.display = e.target.value === 'otros' ? 'block' : 'none';
        });
    });

    // Listener para botón de envío
    const submitBtn = modalContainer.querySelector('#clarification-submit-btn');
    submitBtn.addEventListener('click', async () => {
        const reason = modalContainer.querySelector('input[name="clarificationReason"]:checked').value;
        const details = modalContainer.querySelector('#clarification-otros-details').value;
        const personId = personDetails.id || personDetails.person_id;

        if (reason === 'otros' && !details.trim()) {
            showToast('Por favor, especifique el motivo en el cuadro de texto.', 'warning');
            return;
        }

        try {
            await accessLogsApi.logClarified({
                person_id: personId,
                reason: reason,
                details: details
            });

            modal.hide();
            showToast('Acceso registrado con éxito.', 'success');
            playScanSound('success');
            if (onConfirm) onConfirm();
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
            playScanSound('error');
        }
    });

    // Limpiar el modal cuando se cierre
    modalContainer.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modalContainer);
    });

    modal.show();
}

/**
 * Configura los listeners para la búsqueda de logs de personal
 * @private
 */
function setupPersonalSearchListeners() {
    const searchInput = mainContent.querySelector('#search-personal-log');
    if (!searchInput) return;

    // Listener para filtrado en tiempo real
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();

        // Filtrar los logs
        const filteredLogs = personalLogs.filter(log => {
            const nombre = (log.nombre || log.name || '').toLowerCase();
            const rut = (log.rut || '').toLowerCase();
            const unidad = (log.unidad || '').toLowerCase();
            const accion = (log.accion || log.action || '').toLowerCase();

            return nombre.includes(searchTerm) ||
                   rut.includes(searchTerm) ||
                   unidad.includes(searchTerm) ||
                   accion.includes(searchTerm);
        });

        // Renderizar tabla filtrada
        renderPersonalLogTable(filteredLogs);
    });
}

/**
 * Carga y renderiza los logs de personal
 * @private
 */
async function loadAndRenderPersonalLogs() {
    try {
        const allLogs = await accessLogsApi.getAllTypes();
        personalLogs = allLogs.personal || [];
        personalLogs.sort((a, b) => new Date(b.log_time) - new Date(a.log_time));
        renderPersonalLogTable(personalLogs);
    } catch (error) {
        console.error('Error al cargar logs de personal:', error);
    }
}

/**
 * Renderiza la tabla de logs de personal
 * @private
 */
function renderPersonalLogTable(data) {
    const tableBody = mainContent.querySelector('#personal-log-table');
    if (!tableBody) return;

    tableBody.innerHTML = data.length === 0
        ? '<tr><td colspan="3" class="text-center text-muted p-4">No se encontraron registros de personal.</td></tr>'
        : data.map(log => `
            <tr>
                <td>
                    <strong>${log.nombre || log.name || 'N/A'}</strong>
                    <br><small class="text-muted">${log.rut || 'N/A'}</small>
                    ${log.unidad ? `<br><small class="text-muted"><i class="bi bi-building"></i> ${log.unidad}</small>` : ''}
                </td>
                <td>
                    <span class="badge ${log.accion === 'Entrada' || log.action === 'entrada' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">
                        <i class="bi ${log.accion === 'Entrada' || log.action === 'entrada' ? 'bi-box-arrow-in' : 'bi-box-arrow-out'} me-1"></i>${(log.accion || log.action || 'N/A').charAt(0).toUpperCase() + (log.accion || log.action || 'N/A').slice(1).toLowerCase()}
                    </span>
                </td>
                <td class="text-muted small">${new Date(log.log_time).toLocaleString('es-CL')}</td>
            </tr>
        `).join('');
}

/**
 * Inicia el refresco automático de logs de personal
 * @private
 */
function startPersonalAutoRefresh() {
    // Refrescar cada 10 segundos
    const tableElement = mainContent.querySelector('#personal-log-table');
    if (tableElement) {
        if (porticoRefreshInterval) clearInterval(porticoRefreshInterval);
        porticoRefreshInterval = setInterval(() => {
            // Verificar que el elemento aún existe en el DOM
            if (mainContent.querySelector('#personal-log-table')) {
                loadAndRenderPersonalLogs();
            } else {
                clearInterval(porticoRefreshInterval);
            }
        }, 10000);
    }
}

/**
 * Muestra el modal de bloqueo del módulo Control de Unidades
 * @private
 */
function showControlPersonalLockedModal() {
    const modal = document.getElementById('control-personal-locked-modal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * Deshabilita todos los inputs del módulo Control de Unidades
 * @private
 */
function disableControlPersonalInputs() {
    const scanForm = mainContent.querySelector('#scan-personal-form');
    const scanInput = mainContent.querySelector('#scan-personal-input');
    const searchInput = mainContent.querySelector('#search-personal-log');

    if (scanForm) scanForm.style.pointerEvents = 'none';
    if (scanInput) {
        scanInput.disabled = true;
        scanInput.style.opacity = '0.5';
    }
    if (searchInput) {
        searchInput.disabled = true;
        searchInput.style.opacity = '0.5';
    }

    // Agregar overlay semitransparente
    const mainDiv = mainContent.querySelector('h1');
    if (mainDiv) {
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.right = '0';
        overlay.style.bottom = '0';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.1)';
        overlay.style.zIndex = '1050';
        overlay.style.pointerEvents = 'none';
    }
}

/**
 * Habilita todos los inputs del módulo Control de Unidades
 * @private
 */
function enableControlPersonalInputs() {
    const scanForm = mainContent.querySelector('#scan-personal-form');
    const scanInput = mainContent.querySelector('#scan-personal-input');
    const searchInput = mainContent.querySelector('#search-personal-log');

    if (scanForm) scanForm.style.pointerEvents = 'auto';
    if (scanInput) {
        scanInput.disabled = false;
        scanInput.style.opacity = '1';
    }
    if (searchInput) {
        searchInput.disabled = false;
        searchInput.style.opacity = '1';
    }
}

/**
 * Inicia la verificación periódica del estado de Control de Unidades desde el servidor
 * Verifica cada 3 segundos si el módulo fue habilitado en otro navegador
 * @private
 */
function startControlPersonalStatusCheck() {
    // Verificar cada 3 segundos (3000 milisegundos) el estado desde el servidor
    controlPersonalCheckInterval = setInterval(async () => {
        // Verificar si el módulo sigue siendo Control de Unidades
        if (mainContent.querySelector('#personal-log-table')) {
            try {
                const isControlPersonalEnabled = await controlPersonalStatusApi.getStatus();

                if (isControlPersonalEnabled) {
                    // El módulo fue habilitado - actualizar la interfaz
                    clearInterval(controlPersonalCheckInterval);
                    controlPersonalCheckInterval = null;

                    // Cerrar modal de bloqueo si existe
                    const lockedModal = document.getElementById('control-personal-locked-modal');
                    if (lockedModal) {
                        const bsModal = bootstrap.Modal.getInstance(lockedModal);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    }

                    // Mostrar notificación
                    showToast('Control de Unidades habilitado', 'success');

                    // Habilitar los inputs
                    enableControlPersonalInputs();

                    // Configurar listeners y cargar datos
                    setupScanListeners();
                    setupPersonalSearchListeners();
                    loadAndRenderPersonalLogs();
                    startPersonalAutoRefresh();
                }
            } catch (error) {
                console.error('Error al verificar estado de Control de Unidades:', error);
            }
        } else {
            // El módulo no es Control de Unidades - detener verificación
            clearInterval(controlPersonalCheckInterval);
            controlPersonalCheckInterval = null;
        }
    }, 3000); // 3 segundos para detección rápida desde el servidor
}
