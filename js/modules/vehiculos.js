/**
 * @fileoverview Módulo de gestión de vehículos
 * @description Maneja todas las operaciones relacionadas con vehículos:
 * - CRUD de vehículos (crear, leer, actualizar, eliminar)
 * - Búsqueda y filtrado avanzado
 * - Validación de patentes chilenas
 * - Asociación con personal
 * - Generación de códigos QR
 * - Importación masiva desde Excel/CSV
 * - Historial de cambios
 * @module modules/vehiculos
 */

import vehiculosApi from '../api/vehiculos-api.js';
import personalApi from '../api/personal-api.js';
import { showToast } from './ui/notifications.js';

// Variables del módulo
let mainContent;
let vehiculosData = [];
let personalData = [];
let vehiculoModalInstance = null;

/**
 * Inicializa el módulo de vehículos
 * @param {HTMLElement} contentElement - Elemento contenedor principal
 * @public
 */
export function initVehiculosModule(contentElement) {
    mainContent = contentElement;
    initVehiculoModule();
}

/**
 * Inicializa el módulo de vehículos con todos sus event listeners
 * @private
 */
async function initVehiculoModule() {
    const modalEl = document.querySelector('#vehiculo-modal');
    if (!modalEl) {
        console.error('Modal element #vehiculo-modal not found in document');
        return;
    }

    if (modalEl && !vehiculoModalInstance) {
        console.log('Iniciando modal de vehículos...');
        modalEl.innerHTML = getVehiculoModalTemplate();
        vehiculoModalInstance = new bootstrap.Modal(modalEl);
        console.log('Modal de vehículos inicializado:', vehiculoModalInstance);

        const form = modalEl.querySelector('#vehiculo-form');

        // Validar que el formulario exista
        if (!form) {
            console.error('Formulario de vehículo no encontrado en el modal');
            return;
        }
        console.log('Formulario de vehículos encontrado');

        // --- VALIDACIÓN DE PATENTE CHILENA ---
        const patenteInput = form.querySelector('#patente');
        const patenteValidationMessage = form.querySelector('#patente-validation-message');

        if (!patenteInput || !patenteValidationMessage) {
            console.error('Elementos de patente no encontrados en el formulario');
            return;
        }

        patenteInput.addEventListener('input', function(e) {
            // Convertir automáticamente a mayúsculas
            this.value = this.value.toUpperCase();

            // Validar formato
            const patente = this.value.trim();
            const esValida = validarPatenteChilena(patente);

            // Feedback visual
            if (patente && !esValida) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');

                // Determinar el mensaje de error específico
                if (patente.length < 5 || patente.length > 6) {
                    patenteValidationMessage.textContent = 'La patente debe tener 5 o 6 caracteres';
                } else if (/[^A-Za-z0-9]/.test(patente)) {
                    patenteValidationMessage.textContent = 'La patente solo puede contener letras y números';
                } else {
                    patenteValidationMessage.textContent = 'Formato inválido. Formatos válidos: AA1234, BCDF12, BCD12, AB123, ABC123';
                }
            } else if (patente) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                patenteValidationMessage.textContent = ''; // Limpiar mensaje cuando es válida
            } else {
                this.classList.remove('is-valid', 'is-invalid');
                patenteValidationMessage.textContent = ''; // Limpiar mensaje cuando está vacía
            }
        });

        // --- LÓGICA PARA LA BÚSQUEDA DE PERSONAL ---
        const searchBtn = form.querySelector('#search-personal-btn');
        const personalRutInput = form.querySelector('#personalRut');
        const nombreAsociadoDisplay = form.querySelector('#vehiculo-nombre-asociado');
        const searchResultsContainer = form.querySelector('#personal-search-results');

        window.selectedPersonalId = null; // Variable para almacenar el ID del personal seleccionado
        let debounceTimer;

        // Event listener para el botón de búsqueda
        searchBtn.addEventListener('click', () => {
            const query = personalRutInput.value.trim();
            if (query) {
                searchPersonal(query, searchResultsContainer, nombreAsociadoDisplay, personalRutInput);
            }
        });

        // Event listener para la tecla Enter en el input
        personalRutInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevenir envío de formulario
                const query = personalRutInput.value.trim();
                if (query) {
                    searchPersonal(query, searchResultsContainer, nombreAsociadoDisplay, personalRutInput);
                }
            }
        });

        // Busqueda automática mientras escribe (con debounce)
        personalRutInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            // Limpiar el timer anterior
            clearTimeout(debounceTimer);

            // Si se borró el contenido, limpiar todo
            if (!query) {
                nombreAsociadoDisplay.textContent = '';
                searchResultsContainer.style.display = 'none';
                window.selectedPersonalId = null;
                personalRutInput.classList.remove('is-valid', 'is-invalid');
                return;
            }

            // Configurar un nuevo timer
            debounceTimer = setTimeout(() => {
                if (query.length >= 3) {
                    searchPersonal(query, searchResultsContainer, nombreAsociadoDisplay, personalRutInput);
                }
            }, 500); // Esperar 500ms después de que el usuario deje de escribir
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (searchResultsContainer.style.display === 'block' &&
                !searchResultsContainer.contains(e.target) &&
                e.target !== personalRutInput &&
                e.target !== searchBtn) {
                searchResultsContainer.style.display = 'none';
            }
        });
        // --- FIN DE LA LÓGICA DE BÚSQUEDA DE PERSONAL ---

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            handleVehiculoFormSubmit(e, vehiculoModalInstance);
        });

        const accesoPermanenteCheckbox = form.querySelector('#acceso_permanente');
        const fechaExpiracionInput = form.querySelector('#fecha_expiracion');
        const fechaExpiracionRequired = form.querySelector('#vehiculo_fecha_expiracion_required');

        // Función para actualizar el estado del campo fecha_expiracion
        const actualizarFechaExpiracion = () => {
            if (accesoPermanenteCheckbox.checked) {
                fechaExpiracionInput.disabled = true;
                fechaExpiracionInput.removeAttribute('required');
                fechaExpiracionInput.value = '';
                if (fechaExpiracionRequired) fechaExpiracionRequired.style.display = 'none';
            } else {
                fechaExpiracionInput.disabled = false;
                fechaExpiracionInput.setAttribute('required', 'required');
                if (fechaExpiracionRequired) fechaExpiracionRequired.style.display = 'inline';
            }
        };
        // Aplicar el estado inicial
        actualizarFechaExpiracion();
        // Añadir el evento para cambios futuros
        accesoPermanenteCheckbox.addEventListener('change', actualizarFechaExpiracion);
    }

    mainContent.querySelector('#add-vehiculo-btn').addEventListener('click', () => openVehiculoModal());
    mainContent.querySelector('#search-vehiculo-tabla').addEventListener('input', handleVehiculoTableSearch);
    mainContent.querySelector('#import-vehiculos-btn').addEventListener('click', openImportVehiculosModal);

    // Configuración de filtros avanzados
    const toggleAdvancedSearchBtn = mainContent.querySelector('#toggle-advanced-search');
    const advancedSearchFilters = mainContent.querySelector('#advanced-search-filters');
    const applyFiltersBtn = mainContent.querySelector('#apply-advanced-filters');
    const resetFiltersBtn = mainContent.querySelector('#reset-advanced-filters');
    const advancedFilterInputs = mainContent.querySelectorAll('.advanced-filter');

    // Mostrar/ocultar filtros avanzados
    toggleAdvancedSearchBtn.addEventListener('click', () => {
        const isVisible = advancedSearchFilters.style.display !== 'none';
        advancedSearchFilters.style.display = isVisible ? 'none' : 'block';
        toggleAdvancedSearchBtn.innerHTML = isVisible ?
            '<i class="bi bi-funnel"></i> Filtros Avanzados' :
            '<i class="bi bi-funnel-fill"></i> Ocultar Filtros';
    });

    // Aplicar filtros avanzados
    applyFiltersBtn.addEventListener('click', () => {
        applyFilters();
    });

    // Resetear filtros avanzados
    resetFiltersBtn.addEventListener('click', () => {
        // Limpiar todos los campos de filtro
        advancedFilterInputs.forEach(input => {
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });

        // Aplicar filtros (sin filtros = mostrar todos)
        applyFilters();
    });

    // Permitir aplicar filtros avanzados con Enter en cualquier campo de filtro
    advancedFilterInputs.forEach(input => {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });
    });

    // Event delegation for edit, delete, QR, and history buttons in vehiculo table
    mainContent.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-vehiculo-btn');
        const deleteBtn = e.target.closest('.delete-vehiculo-btn');
        const qrBtn = e.target.closest('.generate-qr-btn');
        const historialBtn = e.target.closest('.historial-vehiculo-btn');

        if (editBtn) {
            openVehiculoModal(editBtn.dataset.id);
        } else if (deleteBtn) {
            deleteVehiculo(deleteBtn.dataset.id);
        } else if (qrBtn) {
            generateAndShowQrCode(qrBtn.dataset.patente, qrBtn.dataset.id);
        } else if (historialBtn) {
            showVehiculoHistorial(historialBtn.dataset.id, historialBtn.dataset.patente);
        }
    });

    try {
        // Cargar los datos de vehículos y personal
        [vehiculosData, personalData] = await Promise.all([vehiculosApi.getAll(), personalApi.getAll()]);
        renderVehiculoTable(vehiculosData);
    } catch (error) {
        showToast(error.message, 'error');

        // Mostrar mensaje de diagnóstico más detallado
        console.error("Error en initVehiculoModule:", error);

        // Mensaje de error más descriptivo para el usuario
        mainContent.querySelector('#vehiculo-table-body').innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-danger p-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error al cargar datos de vehículos. Detalles: ${error.message}
                </td>
            </tr>
        `;
    }
}

/**
 * Función para validar patente chilena
 * @param {string} patente - Patente a validar
 * @returns {boolean} - True si la patente es válida
 * @private
 */
function validarPatenteChilena(patente) {
    // Formato antiguo: dos letras y cuatro dígitos (AA1234)
    const formatoAntiguo = /^[A-Z]{2}[0-9]{4}$/;

    // Formato nuevo: cuatro letras y dos dígitos (BCDF12)
    const formatoNuevo = /^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/;

    // Formato de motos nuevo: tres letras y dos números (ABC12)
    const formatoMotoNuevo = /^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/;

    // Formato de motos antiguo: dos letras y tres números (AB123)
    const formatoMotoAntiguo = /^[A-Z]{2}[0-9]{3}$/;

    // Formato de remolques: tres letras y tres números (ABC123)
    const formatoRemolque = /^[A-Z]{3}[0-9]{3}$/;

    return formatoAntiguo.test(patente) ||
           formatoNuevo.test(patente) ||
           formatoMotoNuevo.test(patente) ||
           formatoMotoAntiguo.test(patente) ||
           formatoRemolque.test(patente);
}

/**
 * Función para manejar el cambio de tipo de acceso
 * @param {string} selectedType - Tipo de acceso seleccionado
 * @global
 */
window.handleTipoAccesoChange = (selectedType) => {
    const personalRutInput = document.getElementById('personalRut');
    const nombreAsociadoDisplay = document.getElementById('vehiculo-nombre-asociado');
    const searchBtn = document.getElementById('search-personal-btn');

    // Limpiar búsqueda actual
    personalRutInput.value = '';
    nombreAsociadoDisplay.textContent = '';
    window.selectedPersonalId = null;

    // Actualizar placeholder según el tipo seleccionado
    switch(selectedType) {
        case 'FISCAL':
        case 'FUNCIONARIO':
            personalRutInput.placeholder = 'Buscar personal militar o civil';
            nombreAsociadoDisplay.textContent = 'Ingrese RUT o nombre del funcionario';
            break;
        case 'RESIDENTE':
            personalRutInput.placeholder = 'Buscar residente';
            nombreAsociadoDisplay.textContent = 'Ingrese RUT o nombre del residente';
            break;
        case 'VISITA':
            personalRutInput.placeholder = 'Buscar visita';
            nombreAsociadoDisplay.textContent = 'Ingrese RUT o nombre del visitante';
            break;
        case 'EMPRESA':
            personalRutInput.placeholder = 'Buscar personal de empresa';
            nombreAsociadoDisplay.textContent = 'Ingrese RUT o nombre del empleado';
            break;
    }
};

/**
 * Función para buscar personal
 * @param {string} query - Texto de búsqueda
 * @param {HTMLElement} searchResultsContainer - Contenedor de resultados
 * @param {HTMLElement} nombreAsociadoDisplay - Display del nombre asociado
 * @param {HTMLElement} personalRutInput - Input del RUT
 * @private
 */
const searchPersonal = async (query, searchResultsContainer, nombreAsociadoDisplay, personalRutInput) => {
    if (!query || query.length < 3) {
        searchResultsContainer.style.display = 'none';
        return;
    }

    nombreAsociadoDisplay.textContent = 'Buscando...';
    nombreAsociadoDisplay.className = 'form-text text-muted mt-1 d-block';

    try {
        // Obtener el tipo de acceso seleccionado (buscar en el modal o en mainContent)
        const tipoSelectModal = document.querySelector('#vehiculo-modal #tipo');
        const tipoSelectMain = mainContent.querySelector('#tipo');
        const tipoAcceso = (tipoSelectModal || tipoSelectMain)?.value || 'FUNCIONARIO';

        const results = await personalApi.search(query, tipoAcceso);

        if (!results || results.length === 0) {
            nombreAsociadoDisplay.textContent = 'No se encontraron resultados.';
            nombreAsociadoDisplay.className = 'form-text text-danger mt-1 d-block';
            searchResultsContainer.style.display = 'none';
            return;
        }

        // Mostrar resultados
        searchResultsContainer.innerHTML = results.map(persona => {
            const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres || ''} ${persona.Paterno || ''} ${persona.Materno || ''}`.trim();
            return `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center personal-result"
                        data-id="${persona.id}" data-rut="${persona.NrRut}">
                    <div>
                        <strong>${nombreCompleto}</strong>
                        <small class="d-block text-muted">RUT: ${persona.NrRut}</small>
                    </div>
                    <small class="text-muted">${persona.Unidad || ''}</small>
                </button>
            `;
        }).join('');

        searchResultsContainer.style.display = 'block';

        // Añadir event listeners a los resultados
        searchResultsContainer.querySelectorAll('.personal-result').forEach(item => {
            item.addEventListener('click', (e) => {
                const personalId = e.currentTarget.dataset.id;
                const rut = e.currentTarget.dataset.rut;
                const nombre = e.currentTarget.querySelector('strong').textContent;

                // Guardar el ID del personal seleccionado
                window.selectedPersonalId = personalId;

                // Actualizar el input y el display
                personalRutInput.value = rut;
                nombreAsociadoDisplay.textContent = `Seleccionado: ${nombre}`;
                nombreAsociadoDisplay.className = 'form-text text-success mt-1 d-block';
                personalRutInput.classList.add('is-valid');
                personalRutInput.classList.remove('is-invalid');

                // Ocultar los resultados
                searchResultsContainer.style.display = 'none';
            });
        });

        nombreAsociadoDisplay.textContent = `${results.length} resultados encontrados`;
        nombreAsociadoDisplay.className = 'form-text text-muted mt-1 d-block';

    } catch (error) {
        console.error("Error en búsqueda de personal:", error);

        // Obtener el tipo de acceso seleccionado para mensaje personalizado
        const tipoSelectModal = document.querySelector('#vehiculo-modal #tipo');
        const tipoSelectMain = mainContent.querySelector('#tipo');
        const tipoAcceso = (tipoSelectModal || tipoSelectMain)?.value || 'FUNCIONARIO';

        // Mapeo de tipos de acceso a nombres amigables
        const tiposAmigables = {
            'FUNCIONARIO': 'funcionario',
            'FISCAL': 'fiscal',
            'RESIDENTE': 'residente',
            'VISITA': 'visitante',
            'EMPRESA': 'empleado de empresa'
        };

        const nombreTipo = tiposAmigables[tipoAcceso] || tipoAcceso.toLowerCase();
        const mensajeError = `No se encontró ningún ${nombreTipo} con ese RUT o nombre.`;

        nombreAsociadoDisplay.textContent = mensajeError;
        nombreAsociadoDisplay.className = 'form-text text-danger mt-1 d-block';
        searchResultsContainer.style.display = 'none';
    }
};

/**
 * Renderiza la tabla de vehículos
 * @param {Array} data - Datos de vehículos a renderizar
 * @private
 */
function renderVehiculoTable(data) {
    const tableBody = mainContent.querySelector('#vehiculo-table-body');
    if (!tableBody) return;
    tableBody.innerHTML = data.length === 0 ? '<tr><td colspan="10" class="text-center text-muted p-4">No se encontraron resultados.</td></tr>' : data.map(v => {
        // Usar directamente el nombre del asociado que viene del backend
        const asociadoNombre = v.asociado_nombre || '-';
        const fechaInicio = v.fecha_inicio || '-';
        return `
            <tr>
                <td class="text-center">${v.patente}</td>
                <td class="text-center">${v.marca || '-'}</td>
                <td class="text-center">${asociadoNombre}</td>
                <td class="text-center">${v.tipo || '-'}</td>
                <td class="text-center">${fechaInicio}</td>
                <td class="text-center"><span class="badge ${v.status === 'autorizado' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'}">${v.status}</span></td>
                <td class="text-center">${v.acceso_permanente ? '-' : (v.fecha_expiracion || 'Sin fecha')}</td>
                <td class="text-center"><span class="badge ${v.acceso_permanente ? 'bg-primary-subtle text-primary-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'}">${v.acceso_permanente ? 'Sí' : 'No'}</span></td>
                <td class="text-center"><button class="btn btn-sm btn-outline-info generate-qr-btn" data-patente="${v.patente}" data-id="${v.id}" title="Generar QR"><i class="bi bi-qr-code"></i></button></td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary edit-vehiculo-btn" data-id="${v.id}" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-secondary historial-vehiculo-btn" data-id="${v.id}" data-patente="${v.patente}" title="Ver Historial"><i class="bi bi-clock-history"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-vehiculo-btn" data-id="${v.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

}

/**
 * Maneja la búsqueda en la tabla de vehículos
 * @param {Event} e - Evento de input
 * @private
 */
function handleVehiculoTableSearch(e) {
    // Para búsqueda rápida (simple), aplicamos el filtro inmediatamente
    const query = e.target.value.toLowerCase().trim();
    applyFilters(query);
}

/**
 * Función unificada para aplicar todos los filtros
 * @param {string} quickSearchQuery - Query de búsqueda rápida
 * @private
 */
function applyFilters(quickSearchQuery = '') {
    // Obtener valores de filtros avanzados
    const filterPatente = mainContent.querySelector('#filter-patente')?.value.toLowerCase().trim() || '';
    const filterMarca = mainContent.querySelector('#filter-marca')?.value.toLowerCase().trim() || '';
    const filterModelo = mainContent.querySelector('#filter-modelo')?.value.toLowerCase().trim() || '';
    const filterTipo = mainContent.querySelector('#filter-tipo')?.value || '';
    const filterStatus = mainContent.querySelector('#filter-status')?.value || '';
    const filterPermanente = mainContent.querySelector('#filter-permanente')?.value || '';
    const filterAsociado = mainContent.querySelector('#filter-asociado')?.value.toLowerCase().trim() || '';

    // Determinar si estamos en modo de búsqueda rápida o filtros avanzados
    const isAdvancedSearch = mainContent.querySelector('#advanced-search-filters')?.style.display !== 'none';

    const filteredVehiculos = vehiculosData.filter(v => {
        // Obtener información del asociado
        const asociadoNombre = v.asociado_nombre ? v.asociado_nombre.toLowerCase() : '';
        const rutAsociado = v.rut_asociado ? v.rut_asociado.toLowerCase().replace(/[.-]/g, '') : '';

        // Si estamos en búsqueda rápida, aplicamos la búsqueda general
        if (!isAdvancedSearch && quickSearchQuery) {
            return (
                (v.patente || '').toLowerCase().includes(quickSearchQuery) ||
                (v.marca || '').toLowerCase().includes(quickSearchQuery) ||
                (v.modelo || '').toLowerCase().includes(quickSearchQuery) ||
                asociadoNombre.includes(quickSearchQuery) ||
                rutAsociado.includes(quickSearchQuery) ||
                (v.tipo || '').toLowerCase().includes(quickSearchQuery)
            );
        }

        // Si estamos en filtros avanzados, aplicamos todos los filtros activos
        let matchesFilters = true;

        // Filtrar por patente
        if (filterPatente && !(v.patente || '').toLowerCase().includes(filterPatente)) {
            matchesFilters = false;
        }

        // Filtrar por marca
        if (filterMarca && !(v.marca || '').toLowerCase().includes(filterMarca)) {
            matchesFilters = false;
        }

        // Filtrar por modelo
        if (filterModelo && !(v.modelo || '').toLowerCase().includes(filterModelo)) {
            matchesFilters = false;
        }

        // Filtrar por tipo
        if (filterTipo && (v.tipo || '') !== filterTipo) {
            matchesFilters = false;
        }

        // Filtrar por estado de autorización
        if (filterStatus && (v.status || '') !== filterStatus) {
            matchesFilters = false;
        }

        // Filtrar por acceso permanente
        if (filterPermanente) {
            const isPermanente = v.acceso_permanente ? '1' : '0';
            if (isPermanente !== filterPermanente) {
                matchesFilters = false;
            }
        }

        // Filtrar por asociado (nombre o rut)
        if (filterAsociado && !asociadoNombre.includes(filterAsociado) && !rutAsociado.includes(filterAsociado)) {
            matchesFilters = false;
        }

        return matchesFilters;
    });

    renderVehiculoTable(filteredVehiculos);
}

/**
 * Abre el modal de importación de vehículos
 * @private
 */
function openImportVehiculosModal() {
    // Crear el modal si no existe
    let importModalEl = document.getElementById('import-vehiculos-modal');
    if (!importModalEl) {
        importModalEl = document.createElement('div');
        importModalEl.id = 'import-vehiculos-modal';
        importModalEl.className = 'modal fade';
        importModalEl.tabIndex = '-1';
        document.body.appendChild(importModalEl);
    }

    // Establecer el contenido y inicializar
    importModalEl.innerHTML = getImportVehiculosModalTemplate();
    const importModal = new bootstrap.Modal(importModalEl);

    // Resetear el contenido del modal
    const resetModalContent = () => {
        const fileInput = document.getElementById('vehiculos-excel-file');
        if (fileInput) {
            fileInput.value = '';
        }
        const progressContainer = document.getElementById('import-progress-container');
        if (progressContainer) {
            progressContainer.classList.add('d-none');
        }
        const progressBar = document.getElementById('import-progress-bar');
        if (progressBar) {
            progressBar.style.width = '0%';
        }
        const resultsContainer = document.getElementById('import-results');
        if (resultsContainer) {
            resultsContainer.classList.add('d-none');
        }
        const startBtn = document.getElementById('start-import-btn');
        if (startBtn) {
            startBtn.disabled = false;
        }
    };

    // Event listener para el botón de iniciar importación
    const startImportBtn = document.getElementById('start-import-btn');
    if (startImportBtn) {
        startImportBtn.addEventListener('click', () => {
            handleImportVehiculos();
        });
    }

    // Agregar eventos para descargar plantillas
    // Buscar dentro del modal usando getElementById primero
    const descargarExcelBtn = importModalEl.querySelector('a[href="templates/plantilla_vehiculos.xlsx"]');
    const descargarCsvBtn = importModalEl.querySelector('a[href="templates/plantilla_vehiculos.csv"]');

    if (descargarExcelBtn) {
        descargarExcelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            descargarPlantillaExcel();
        });
    } else {
        console.warn('No se encontró el botón de descargar Excel');
    }

    if (descargarCsvBtn) {
        descargarCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            descargarPlantillaCsv();
        });
    } else {
        console.warn('No se encontró el botón de descargar CSV');
    }

    // Mostrar el modal
    importModal.show();

    // Resetear cuando se oculta
    importModalEl.addEventListener('hidden.bs.modal', resetModalContent);
}

/**
 * Descarga una plantilla Excel para importación de vehículos
 * @private
 */
function descargarPlantillaExcel() {
    try {
        // Crear datos de ejemplo
        const headers = ["patente", "marca", "modelo", "tipo", "tipo_vehiculo", "personalNrRut", "fecha_inicio", "acceso_permanente", "fecha_expiracion"];
        const ejemploData = [
            ["SD4115", "TOYOTA", "SANTA FE", "VISITA", "FURGON", "15234567", "2025-01-15", "0", "2025-12-31"],
            ["AB1234", "HONDA", "CIVIC", "FUNCIONARIO", "AUTO", "19345678", "2025-02-01", "1", ""],
            ["XY5678", "FORD", "FIESTA", "EMPRESA", "AUTO", "16456789", "2025-01-20", "0", "2025-11-15"]
        ];

        // Crear hoja de cálculo
        if (typeof XLSX !== 'undefined') {
            const ws = XLSX.utils.aoa_to_sheet([headers, ...ejemploData]);

            // Ajustar ancho de columnas
            ws['!cols'] = [
                {wch: 12}, // patente
                {wch: 15}, // marca
                {wch: 15}, // modelo
                {wch: 15}, // tipo
                {wch: 15}, // tipo_vehiculo
                {wch: 15}, // personalNrRut
                {wch: 18}, // fecha_inicio
                {wch: 18}, // acceso_permanente
                {wch: 18}  // fecha_expiracion
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Vehículos");

            XLSX.writeFile(wb, "plantilla_vehiculos.xlsx");
            showToast('Plantilla descargada correctamente', 'success');
        } else {
            showToast('Error: SheetJS no está disponible', 'error');
        }
    } catch (error) {
        console.error('Error al descargar plantilla Excel:', error);
        showToast('Error al descargar la plantilla', 'error');
    }
}

/**
 * Descarga una plantilla CSV para importación de vehículos
 * @private
 */
function descargarPlantillaCsv() {
    try {
        const headers = "patente,marca,modelo,tipo,tipo_vehiculo,personalNrRut,fecha_inicio,acceso_permanente,fecha_expiracion";
        const ejemploData = [
            "SD4115,TOYOTA,SANTA FE,VISITA,FURGON,15234567,2025-01-15,0,2025-12-31",
            "AB1234,HONDA,CIVIC,FUNCIONARIO,AUTO,19345678,2025-02-01,1,",
            "XY5678,FORD,FIESTA,EMPRESA,AUTO,16456789,2025-01-20,0,2025-11-15"
        ];

        const csvContent = [headers, ...ejemploData].join('\n');

        // Crear blob y descargar
        const blob = new Blob([csvContent], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', 'plantilla_vehiculos.csv');
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showToast('Plantilla descargada correctamente', 'success');
    } catch (error) {
        console.error('Error al descargar plantilla CSV:', error);
        showToast('Error al descargar la plantilla', 'error');
    }
}

/**
 * Maneja la importación de vehículos desde archivo
 * @private
 */
async function handleImportVehiculos() {
    const fileInput = document.getElementById('vehiculos-excel-file');
    const file = fileInput.files[0];

    // Validar que hay un archivo seleccionado
    if (!file) {
        showToast('Por favor, seleccione un archivo para importar', 'warning');
        return;
    }

    // Identificar el tipo de archivo
    const isExcel = file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ||
                    file.type === 'application/vnd.ms-excel' ||
                    file.name.endsWith('.xlsx') ||
                    file.name.endsWith('.xls');

    const isCsv = file.type === 'text/csv' ||
                 file.name.endsWith('.csv');

    if (!isExcel && !isCsv) {
        showToast('El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv)', 'warning');
        return;
    }

    // Mostrar progreso y deshabilitar botón
    document.getElementById('start-import-btn').disabled = true;
    document.getElementById('import-progress-container').classList.remove('d-none');
    document.getElementById('import-results').classList.add('d-none');
    document.getElementById('import-status').textContent = 'Procesando archivo...';

    try {
        let vehiculosDataImport;

        // Determinar cómo procesar el archivo según su tipo
        const isExcelFile = file.name.endsWith('.xlsx') || file.name.endsWith('.xls');

        if (isExcelFile) {
            // Para archivos Excel
            // Verificar si la biblioteca xlsx está disponible
            if (typeof XLSX === 'undefined') {
                // Cargar la biblioteca xlsx.js dinámicamente si no está disponible
                await loadExcelLibrary();
            }

            // Leer el archivo Excel
            vehiculosDataImport = await readExcelFile(file);

            // Validar la estructura del archivo
            if (!validateExcelStructure(vehiculosDataImport)) {
                showToast('El archivo Excel no tiene la estructura correcta', 'error');
                document.getElementById('import-status').textContent = 'Error: El archivo Excel no tiene la estructura correcta.';
                return;
            }
        } else {
            // Para archivos CSV
            vehiculosDataImport = await readCSVFile(file);

            // Validar la estructura del archivo
            if (!validateCSVStructure(vehiculosDataImport)) {
                showToast('El archivo CSV no tiene la estructura correcta', 'error');
                document.getElementById('import-status').textContent = 'Error: El archivo CSV no tiene la estructura correcta.';
                return;
            }
        }

        // Iniciar el proceso de importación
        await processVehiculosImport(vehiculosDataImport);

    } catch (error) {
        console.error('Error en la importación de vehículos:', error);
        showToast('Error en la importación: ' + error.message, 'error');
        document.getElementById('import-status').textContent = 'Error durante la importación: ' + error.message;
    }
}

/**
 * Carga dinámicamente la biblioteca xlsx.js
 * @returns {Promise}
 * @private
 */
function loadExcelLibrary() {
    return new Promise((resolve, reject) => {
        if (typeof XLSX !== 'undefined') {
            resolve();
            return;
        }

        console.log('Cargando biblioteca SheetJS para procesar Excel...');
        const script = document.createElement('script');
        script.src = 'js/xlsx.full.min.js';
        script.onload = () => {
            console.log('Biblioteca SheetJS cargada correctamente');
            resolve();
        };
        script.onerror = () => {
            console.error('Error al cargar la biblioteca SheetJS');
            reject(new Error('No se pudo cargar la biblioteca para procesar archivos Excel. Por favor, intente nuevamente o contacte al administrador.'));
        };
        document.head.appendChild(script);
    });
}

/**
 * Lee un archivo Excel
 * @param {File} file - Archivo a leer
 * @returns {Promise<Array>}
 * @private
 */
function readExcelFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            try {
                // Leer el archivo Excel con la biblioteca SheetJS
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });

                // Obtener la primera hoja
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];

                // Convertir a JSON
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { raw: false });

                // Verificar los campos requeridos
                const requiredFields = ['patente', 'marca', 'modelo', 'tipo', 'personalNrRut', 'acceso_permanente', 'fecha_expiracion'];

                if (jsonData.length === 0) {
                    reject(new Error('El archivo Excel está vacío o no tiene datos válidos'));
                    return;
                }

                // Verificar que todos los campos requeridos están presentes
                const firstRow = jsonData[0];
                const missingFields = requiredFields.filter(field => !(field in firstRow));
                if (missingFields.length > 0) {
                    reject(new Error(`Faltan columnas requeridas: ${missingFields.join(', ')}`));
                    return;
                }

                resolve(jsonData);

            } catch (error) {
                reject(new Error('Error al procesar el archivo Excel: ' + error.message));
            }
        };

        reader.onerror = () => {
            reject(new Error('Error al leer el archivo'));
        };

        reader.readAsArrayBuffer(file);
    });
}

/**
 * Valida la estructura del archivo Excel
 * @param {Array} vehiculosDataImport - Datos importados
 * @returns {boolean}
 * @private
 */
function validateExcelStructure(vehiculosDataImport) {
    // Si no hay datos, no es válido
    if (!vehiculosDataImport || vehiculosDataImport.length === 0) {
        return false;
    }

    // Verificar que todos los registros tienen los campos necesarios
    const requiredFields = ['patente', 'marca', 'modelo', 'tipo', 'personalNrRut', 'acceso_permanente'];

    for (const vehiculo of vehiculosDataImport) {
        for (const field of requiredFields) {
            if (!(field in vehiculo)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Valida que el archivo tenga la estructura correcta para importación
 * Ahora incluye fecha_inicio como campo esperado
 * @param {Array} vehiculosDataImport - Datos importados
 * @returns {boolean}
 * @private
 */
function validateImportStructure(vehiculosDataImport) {
    if (!vehiculosDataImport || vehiculosDataImport.length === 0) {
        return false;
    }

    // Campos que deben estar presentes
    const requiredFields = ['patente', 'marca', 'modelo', 'tipo', 'personalNrRut', 'acceso_permanente'];

    // Campos que pueden estar presentes
    const optionalFields = ['tipo_vehiculo', 'fecha_inicio', 'fecha_expiracion'];

    for (const vehiculo of vehiculosDataImport) {
        // Verificar campos requeridos
        for (const field of requiredFields) {
            if (!(field in vehiculo)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Lee un archivo CSV
 * @param {File} file - Archivo a leer
 * @returns {Promise<Array>}
 * @private
 */
function readCSVFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            try {
                const csv = event.target.result;
                const lines = csv.split('\\n');
                const headers = lines[0].split(',').map(header => header.trim());

                const requiredFields = ['patente', 'marca', 'modelo', 'tipo', 'personalNrRut', 'acceso_permanente', 'fecha_expiracion'];

                // Verificar que todos los campos requeridos están presentes
                const missingFields = requiredFields.filter(field => !headers.includes(field));
                if (missingFields.length > 0) {
                    reject(new Error(`Faltan columnas requeridas: ${missingFields.join(', ')}`));
                    return;
                }

                const vehiculos = [];

                // Procesar cada línea del CSV
                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue; // Saltar líneas vacías

                    const values = lines[i].split(',').map(value => value.trim());

                    // Si el número de valores no coincide con el número de columnas, saltamos
                    if (values.length !== headers.length) continue;

                    const vehiculo = {};
                    headers.forEach((header, index) => {
                        vehiculo[header] = values[index];
                    });

                    vehiculos.push(vehiculo);
                }

                resolve(vehiculos);

            } catch (error) {
                reject(new Error('Error al procesar el archivo CSV: ' + error.message));
            }
        };

        reader.onerror = () => {
            reject(new Error('Error al leer el archivo'));
        };

        reader.readAsText(file);
    });
}

/**
 * Valida la estructura del archivo CSV
 * @param {Array} vehiculosDataImport - Datos importados
 * @returns {boolean}
 * @private
 */
function validateCSVStructure(vehiculosDataImport) {
    return validateExcelStructure(vehiculosDataImport); // Usa la misma lógica de validación
}

/**
 * Procesa la importación de vehículos
 * @param {Array} vehiculosDataImport - Datos a importar
 * @private
 */
async function processVehiculosImport(vehiculosDataImport) {
    const totalCount = vehiculosDataImport.length;
    let successCount = 0;
    let errorCount = 0;
    const errors = [];

    // Configurar la barra de progreso
    const progressBar = document.getElementById('import-progress-bar');
    const statusText = document.getElementById('import-status');

    // Procesar cada vehículo
    for (let i = 0; i < totalCount; i++) {
        const vehiculo = vehiculosDataImport[i];

        try {
            // Actualizar progreso
            const progress = Math.round(((i + 1) / totalCount) * 100);
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            statusText.textContent = `Procesando ${i + 1} de ${totalCount}...`;

            // Validar la patente (formato chileno)
            const patente = vehiculo.patente.toUpperCase();
            if (!validarPatenteChilena(patente)) {
                throw new Error(`Formato de patente inválido: ${patente}`);
            }

            // Preparar el objeto para enviar al API
            // Convertir acceso_permanente correctamente: "1" -> true, "0" -> false, etc
            const accesoPermanenteValue = String(vehiculo.acceso_permanente).trim().toLowerCase();
            const accesoPermanente = accesoPermanenteValue === '1' || accesoPermanenteValue === 'true' || accesoPermanenteValue === 'si';

            const vehiculoData = {
                patente: patente,
                marca: vehiculo.marca,
                modelo: vehiculo.modelo,
                tipo: vehiculo.tipo.toUpperCase(),
                tipo_vehiculo: vehiculo.tipo_vehiculo ? vehiculo.tipo_vehiculo.toUpperCase() : 'AUTO',
                personalNrRut: vehiculo.personalNrRut || null,
                fecha_inicio: vehiculo.fecha_inicio || null,
                acceso_permanente: accesoPermanente,
                fecha_expiracion: accesoPermanente ? null : (vehiculo.fecha_expiracion || null)
            };

            // Intentar actualizar si la patente ya existe, si no, crear
            let vehiculoExistente = null;
            try {
                const vehiculosExistentes = await vehiculosApi.getAll();
                vehiculoExistente = vehiculosExistentes.find(v => v.patente === patente);
            } catch (e) {
                // Si hay error al obtener la lista, intentar crear de todas formas
                console.warn('No se pudo verificar vehículos existentes');
            }

            if (vehiculoExistente) {
                // Actualizar si existe
                vehiculoData.id = vehiculoExistente.id;
                await vehiculosApi.update(vehiculoData);
                console.log(`Vehículo ${patente} actualizado`);
            } else {
                // Crear si no existe
                await vehiculosApi.create(vehiculoData);
                console.log(`Vehículo ${patente} creado`);
            }

            successCount++;

        } catch (error) {
            console.error(`Error al procesar vehículo ${i + 1}:`, error);
            errorCount++;
            errors.push(`Fila ${i + 1} - Patente ${vehiculo.patente}: ${error.message}`);
        }

        // Esperar un poco entre solicitudes para no sobrecargar el servidor
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Mostrar resultados finales
    document.getElementById('import-results').classList.remove('d-none');
    document.getElementById('import-total-count').textContent = totalCount;
    document.getElementById('import-success-count').textContent = successCount;
    document.getElementById('import-error-count').textContent = errorCount;

    // Mostrar errores si los hay
    if (errors.length > 0) {
        document.getElementById('import-errors-container').classList.remove('d-none');
        document.getElementById('import-errors-list').innerHTML = errors.map(error =>
            `<div class="text-danger small mb-1">${error}</div>`
        ).join('');
    } else {
        document.getElementById('import-errors-container').classList.add('d-none');
    }

    // Actualizar la tabla de vehículos si hubo éxito
    if (successCount > 0) {
        try {
            vehiculosData = await vehiculosApi.getAll();
            renderVehiculoTable(vehiculosData);
        } catch (error) {
            console.error("Error al actualizar la tabla de vehículos:", error);
        }
    }

    // Mensaje de finalización
    statusText.textContent = `Importación finalizada. ${successCount} vehículos importados con éxito.`;

    // Notificación
    if (errorCount === 0) {
        showToast(`Importación completada. ${successCount} vehículos importados.`, 'success');
    } else {
        showToast(`Importación completada con ${errorCount} errores. ${successCount} vehículos importados.`, 'warning');
    }
}

/**
 * Abre el modal de vehículo para crear o editar
 * @param {string|null} id - ID del vehículo a editar (null para crear nuevo)
 * @private
 */
function openVehiculoModal(id = null) {
    if (!vehiculoModalInstance) {
        console.error('Modal de vehículo no inicializado');
        return;
    }

    const modalEl = document.querySelector('#vehiculo-modal');
    if (!modalEl) {
        console.error('Elemento modal no encontrado');
        return;
    }

    const form = modalEl.querySelector('#vehiculo-form');
    if (!form) {
        console.error('Formulario del modal no encontrado');
        return;
    }

    const modalTitle = modalEl.querySelector('#vehiculo-modal-title');
    const nombreDisplay = form.querySelector('#vehiculo-nombre-asociado');
    const personalRutInput = form.querySelector('#personalRut');
    const searchResults = form.querySelector('#personal-search-results');

    if (!modalTitle || !nombreDisplay || !personalRutInput || !searchResults) {
        console.error('Algunos elementos del formulario no fueron encontrados');
        return;
    }

    // 1. Limpiar estado anterior
    form.reset();
    form.classList.remove('was-validated');
    form.elements.id.value = '';
    window.selectedPersonalId = null; // Limpia el ID global al abrir
    nombreDisplay.textContent = 'Ingrese RUT o nombre para buscar';
    personalRutInput.classList.remove('is-valid', 'is-invalid');
    searchResults.style.display = 'none';

    if (id) {
        modalTitle.textContent = 'Editar Vehículo';
        const vehiculo = vehiculosData.find(v => v.id == id);
        if (vehiculo) {
            // Llenar todos los campos del formulario
            form.elements.id.value = vehiculo.id;
            form.elements.patente.value = vehiculo.patente || '';
            form.elements.marca.value = vehiculo.marca || '';
            form.elements.modelo.value = vehiculo.modelo || '';
            form.elements.tipo.value = vehiculo.tipo || 'FUNCIONARIO';
            form.elements.tipo_vehiculo.value = vehiculo.tipo_vehiculo || 'AUTO';
            form.elements.fecha_inicio.value = vehiculo.fecha_inicio || '';
            form.elements.fecha_expiracion.value = vehiculo.fecha_expiracion || '';
            form.elements.acceso_permanente.checked = !!vehiculo.acceso_permanente;

            // 2. Cargar datos del asociado existente
            if (vehiculo.asociado_id) {
                window.selectedPersonalId = vehiculo.asociado_id; // Carga el ID existente
                personalRutInput.value = vehiculo.rut_asociado || '';
                nombreDisplay.textContent = `Asociado actual: ${vehiculo.asociado_nombre}`;
                nombreDisplay.className = 'form-text text-success mt-1 d-block';
            }

            // 3. Actualizar placeholder según el tipo (sin limpiar los datos)
            const tipoSelect = form.elements.tipo;
            if (tipoSelect) {
                const selectedType = tipoSelect.value;
                switch(selectedType) {
                    case 'FISCAL':
                    case 'FUNCIONARIO':
                        personalRutInput.placeholder = 'Buscar personal militar o civil';
                        break;
                    case 'RESIDENTE':
                        personalRutInput.placeholder = 'Buscar residente';
                        break;
                    case 'VISITA':
                        personalRutInput.placeholder = 'Buscar visita';
                        break;
                    case 'EMPRESA':
                        personalRutInput.placeholder = 'Buscar personal de empresa';
                        break;
                }
            }
        }
    } else {
        modalTitle.textContent = 'Agregar Vehículo';
        // 3. Simular un cambio para asegurar que la UI del placeholder esté correcta
        window.handleTipoAccesoChange(form.elements.tipo.value);
    }

    // Actualizar estado del campo fecha_expiracion
    form.querySelector('#acceso_permanente').dispatchEvent(new Event('change'));

    vehiculoModalInstance.show();
}

/**
 * Maneja el envío del formulario de vehículo
 * @param {Event} e - Evento de submit
 * @param {bootstrap.Modal} modal - Instancia del modal
 * @private
 */
async function handleVehiculoFormSubmit(e, modal) {
    const form = e.target;
    const patenteInput = form.querySelector('#patente');
    const patente = patenteInput.value.trim().toUpperCase();

    // Validación de formato de patente (debe coincidir con la validación del backend)
    const formatoAntiguo = /^[A-Z]{2}[0-9]{4}$/;
    const formatoNuevo = /^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/;
    const formatoMotoNuevo = /^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/;
    const formatoMotoAntiguo = /^[A-Z]{2}[0-9]{3}$/;
    const formatoRemolque = /^[A-Z]{3}[0-9]{3}$/;

    const patenteValida = formatoAntiguo.test(patente) ||
                         formatoNuevo.test(patente) ||
                         formatoMotoNuevo.test(patente) ||
                         formatoMotoAntiguo.test(patente) ||
                         formatoRemolque.test(patente);

    if (!patenteValida) {
        patenteInput.classList.add('is-invalid');
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }

    // Conversión final a mayúsculas
    patenteInput.value = patente;

    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }

    const id = form.elements.id.value;
    const data = {};

    // Campos que NO deben convertirse a mayúsculas
    const excludeFromUpperCase = ['id', 'patente', 'fecha_inicio', 'fecha_expiracion', 'acceso_permanente'];

    // Recopilar datos del formulario
    for (const element of form.elements) {
        if (element.name) {
            if (element.type === 'checkbox') {
                data[element.name] = element.checked ? 1 : 0;
            } else if (element.type === 'date' || element.name === 'fecha_inicio' || element.name === 'fecha_expiracion') {
                const val = element.value ? element.value.trim() : '';
                data[element.name] = (val === '' || val === 'null') ? null : val;
            } else if (element.type === 'select-one' && (element.name === 'tipo' || element.name === 'tipo_vehiculo')) {
                // Convertir valores de selects a mayúsculas
                data[element.name] = element.value.toUpperCase();
            } else if (element.type === 'text' && !excludeFromUpperCase.includes(element.name) && element.value) {
                // Convertir campos de texto a mayúsculas
                data[element.name] = element.value.toUpperCase();
            } else {
                data[element.name] = element.value;
            }
        }
    }

    // Si es acceso permanente, forzar fecha_expiracion a null
    if (data.acceso_permanente == 1) {
        data.fecha_expiracion = null;
    }

    // Verificar fecha_inicio
    if (!data.fecha_inicio) {
        data.fecha_inicio = new Date().toISOString().split('T')[0];
    }

    // Obtener información del asociado
    if (window.selectedPersonalId) {
        data.asociado_id = window.selectedPersonalId;
        data.asociado_tipo = data.tipo; // Usar el tipo de acceso seleccionado como tipo de asociado
    } else if (id) {
        // Si estamos editando y no se ha seleccionado un nuevo asociado, mantenemos el anterior
        const vehiculo = vehiculosData.find(v => v.id == id);
        if (vehiculo) {
            data.asociado_id = vehiculo.asociado_id;
            data.asociado_tipo = vehiculo.asociado_tipo;
        }
    } else {
        data.asociado_id = null;
        data.asociado_tipo = data.tipo;
    }

    // Forzar mayúsculas en campos de texto del vehículo antes de enviar
    if (data.patente) data.patente = String(data.patente).toUpperCase();
    if (data.marca) data.marca = String(data.marca).toUpperCase();
    if (data.modelo) data.modelo = String(data.modelo).toUpperCase();
    if (data.tipo) data.tipo = String(data.tipo).toUpperCase();
    if (data.tipo_vehiculo) data.tipo_vehiculo = String(data.tipo_vehiculo).toUpperCase();

    try {
        if (id) {
            await vehiculosApi.update(data);
            showToast('Vehículo actualizado correctamente.', 'success');
            vehiculosData = await vehiculosApi.getAll();
        } else {
            await vehiculosApi.create(data);
            showToast('Vehículo creado correctamente.', 'success');
            [vehiculosData, personalData] = await Promise.all([vehiculosApi.getAll(), personalApi.getAll()]);
        }
        modal.hide();
        renderVehiculoTable(vehiculosData);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Elimina un vehículo
 * @param {string} id - ID del vehículo a eliminar
 * @private
 */
async function deleteVehiculo(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este vehículo?')) {
        try {
            await vehiculosApi.deleteVehiculo(id);
            showToast('Vehículo eliminado correctamente.', 'success');
            [vehiculosData, personalData] = await Promise.all([vehiculosApi.getAll(), personalApi.getAll()]);
            renderVehiculoTable(vehiculosData);
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Genera y muestra el código QR del vehículo
 * @param {string} patente - Patente del vehículo
 * @param {string} vehiculoId - ID del vehículo
 * @private
 */
function generateAndShowQrCode(patente, vehiculoId) {
    const qrModal = new bootstrap.Modal(document.getElementById('qr-modal'));
    const qrcodeContainer = document.getElementById('qrcode');
    qrcodeContainer.innerHTML = '';
    new QRCode(qrcodeContainer, {
        text: vehiculoId.toString(),
        width: 200,
        height: 200,
    });
    document.getElementById('qr-modal-title').textContent = `Código QR del Vehículo: ${patente}`;
    document.getElementById('qr-patente-text').textContent = patente;
    document.getElementById('print-qr-btn').onclick = () => {
        const printContents = document.getElementById('printable-qr-area').innerHTML;
        const originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    };
    qrModal.show();
}

/**
 * Muestra el historial de un vehículo
 * @param {string} id - ID del vehículo
 * @param {string} patente - Patente del vehículo
 * @private
 */
async function showVehiculoHistorial(id, patente) {
    console.log('Abriendo historial para vehículo ID:', id, 'Patente:', patente);

    // Preparar el modal
    const modalEl = document.getElementById('historial-vehiculo-modal');
    if (!modalEl) {
        console.error('Modal historial no encontrado');
        return;
    }

    modalEl.innerHTML = getVehiculoHistorialModalTemplate();
    const historialModal = new bootstrap.Modal(modalEl);

    // Establecer la patente en el encabezado
    const patenteEl = document.getElementById('historial-patente');
    if (patenteEl) {
        patenteEl.textContent = patente;
    }

    try {
        // Cargar los datos del historial
        console.log('Cargando historial...');
        const historialData = await vehiculosApi.getHistorial(id);
        console.log('Datos del historial recibidos:', historialData);

        // Si no hay datos, mostrar mensaje
        if (!historialData || !historialData.historial || historialData.historial.length === 0) {
            document.getElementById('historial-table-body').innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        No hay registros de cambios para este vehículo
                    </td>
                </tr>
            `;
            document.getElementById('historial-total-cambios').textContent = '0';
            document.getElementById('historial-propietario-actual').textContent = 'Sin datos';

            // Deshabilitar botones de filtro y exportación
            document.getElementById('historial-filtro-btn').disabled = true;
            document.getElementById('exportar-historial-btn').disabled = true;
            document.getElementById('historial-buscar').disabled = true;
        } else {
            // Mostrar datos del vehículo
            const vehiculo = historialData.vehiculo || {};
            console.log('Datos del vehículo en historial:', vehiculo);
            console.log('propietario_actual_nombre:', vehiculo.propietario_actual_nombre);

            // El nombre del propietario está en propietario_actual_nombre
            const propietarioNombre = vehiculo.propietario_actual_nombre || 'No asignado';

            const propietarioEl = document.getElementById('historial-propietario-actual');
            if (propietarioEl) {
                propietarioEl.textContent = propietarioNombre;
                console.log('Propietario actualizado a:', propietarioNombre);
            } else {
                console.error('Elemento historial-propietario-actual no encontrado');
            }

            const cambiosEl = document.getElementById('historial-total-cambios');
            if (cambiosEl) {
                cambiosEl.textContent = historialData.historial.length.toString();
            }

            // Guardar datos completos para filtrado posterior
            modalEl.dataset.historialCompleto = JSON.stringify(historialData.historial);

            // Renderizar tabla de historial
            renderizarHistorialTabla(historialData.historial);

            // Configurar filtro de tipo de cambio
            configurarFiltrosHistorial(modalEl);

            // Configurar búsqueda en historial
            configurarBusquedaHistorial(modalEl);

            // Configurar exportación
            document.getElementById('exportar-historial-btn').addEventListener('click', () => {
                exportarHistorialExcel(historialData, patente);
            });
        }

        // Mostrar el modal
        historialModal.show();
    } catch (error) {
        showToast(error.message || 'Error al cargar el historial del vehículo', 'error');
    }
}

/**
 * Renderiza la tabla de historial
 * @param {Array} historialData - Datos del historial
 * @private
 */
function renderizarHistorialTabla(historialData) {
    document.getElementById('historial-table-body').innerHTML = historialData.map(h => {
        // Asegurarnos de obtener un tipo de cambio válido para mostrar
        const tipoCambioMostrar = h.tipo_cambio_texto || getTipoCambioTexto(h.tipo_cambio);

        return `
            <tr data-tipo="${h.tipo_cambio}" class="historial-row">
                <td>${h.fecha_cambio_formateada || 'Desconocido'}</td>
                <td>
                    <span class="badge ${getBadgeClassByTipoChange(h.tipo_cambio)}">
                        ${tipoCambioMostrar}
                    </span>
                </td>
                <td>${h.propietario_anterior_nombre || 'N/A'}</td>
                <td>${h.propietario_nuevo_nombre || 'N/A'}</td>
                <td>${h.usuario_nombre || 'Sistema'}</td>
            </tr>
        `;
    }).join('');
}

/**
 * Configura los filtros del historial
 * @param {HTMLElement} modalEl - Elemento del modal
 * @private
 */
function configurarFiltrosHistorial(modalEl) {
    const filtroLinks = modalEl.querySelectorAll('#filtro-tipo-cambio .dropdown-item');
    const historialCompleto = JSON.parse(modalEl.dataset.historialCompleto);
    const infoText = modalEl.querySelector('#historial-info-text');

    filtroLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();

            // Actualizar estado activo
            filtroLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            const filtro = link.dataset.filter;
            let historialFiltrado;

            if (filtro === 'todos') {
                historialFiltrado = historialCompleto;
                infoText.textContent = 'Mostrando todos los registros';
            } else {
                historialFiltrado = historialCompleto.filter(h => h.tipo_cambio === filtro);
                infoText.textContent = `Mostrando ${historialFiltrado.length} registro(s) de tipo "${getTipoCambioTexto(filtro)}"`;
            }

            renderizarHistorialTabla(historialFiltrado);
        });
    });
}

/**
 * Configura la búsqueda en el historial
 * @param {HTMLElement} modalEl - Elemento del modal
 * @private
 */
function configurarBusquedaHistorial(modalEl) {
    const searchInput = modalEl.querySelector('#historial-buscar');
    const historialCompleto = JSON.parse(modalEl.dataset.historialCompleto);
    const infoText = modalEl.querySelector('#historial-info-text');

    searchInput.addEventListener('input', (e) => {
        const busqueda = e.target.value.toLowerCase().trim();

        if (!busqueda) {
            renderizarHistorialTabla(historialCompleto);
            infoText.textContent = 'Mostrando todos los registros';
            return;
        }

        const historialFiltrado = historialCompleto.filter(h => {
            return (
                (h.propietario_anterior_nombre && h.propietario_anterior_nombre.toLowerCase().includes(busqueda)) ||
                (h.propietario_nuevo_nombre && h.propietario_nuevo_nombre.toLowerCase().includes(busqueda)) ||
                (h.fecha_cambio_formateada && h.fecha_cambio_formateada.toLowerCase().includes(busqueda)) ||
                (h.usuario_nombre && h.usuario_nombre.toLowerCase().includes(busqueda)) ||
                (h.tipo_cambio_texto && h.tipo_cambio_texto.toLowerCase().includes(busqueda)) ||
                (h.tipo_cambio && h.tipo_cambio.toLowerCase().includes(busqueda))
            );
        });

        renderizarHistorialTabla(historialFiltrado);
        infoText.textContent = `Mostrando ${historialFiltrado.length} resultado(s) para "${busqueda}"`;
    });
}

/**
 * Exporta el historial a Excel
 * @param {Object} historialData - Datos del historial
 * @param {string} patente - Patente del vehículo
 * @private
 */
function exportarHistorialExcel(historialData, patente) {
    try {
        console.log('Exportando historial:', historialData);

        // Crear datos para el Excel
        const headers = ["Fecha", "Tipo de Cambio", "Propietario Anterior", "Propietario Nuevo", "Usuario"];

        // historialData ya es el array de historial (no un objeto con .historial)
        const historialArray = Array.isArray(historialData) ? historialData : (historialData.historial || []);

        const data = historialArray.map(h => {
            // Asegurarnos de obtener un tipo de cambio válido
            const tipoCambioMostrar = h.tipo_cambio_texto || getTipoCambioTexto(h.tipo_cambio);

            return [
                h.fecha_cambio_formateada || 'Desconocido',
                tipoCambioMostrar,
                h.propietario_anterior_nombre || 'N/A',
                h.propietario_nuevo_nombre || 'N/A',
                h.usuario_nombre || 'Sistema'
            ];
        });

        console.log('Datos a exportar:', data);

        // Usar SheetJS para crear el archivo Excel
        if (typeof XLSX !== 'undefined') {
            const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);

            // Ajustar ancho de columnas
            ws['!cols'] = [
                {wch: 20}, // Fecha
                {wch: 25}, // Tipo de Cambio
                {wch: 25}, // Propietario Anterior
                {wch: 25}, // Propietario Nuevo
                {wch: 15}  // Usuario
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Historial");

            const nombreArchivo = `Historial_Vehiculo_${patente}_${formatDate(new Date())}.xlsx`;
            console.log('Descargando archivo:', nombreArchivo);

            XLSX.writeFile(wb, nombreArchivo);
            showToast('Historial exportado correctamente', 'success');
        } else {
            console.error('SheetJS (XLSX) no está cargado');
            showToast('Error: No se puede exportar. SheetJS no está disponible', 'error');
        }
    } catch (error) {
        console.error('Error al exportar:', error);
        showToast('Error al exportar historial: ' + error.message, 'error');
    }
}

/**
 * Formatea una fecha a YYYY-MM-DD
 * @param {Date} date - Fecha a formatear
 * @returns {string}
 * @private
 */
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Obtiene la clase de badge según el tipo de cambio
 * @param {string} tipo - Tipo de cambio
 * @returns {string}
 * @private
 */
function getBadgeClassByTipoChange(tipo) {
    switch (tipo) {
        case 'creacion':
            return 'bg-success-subtle text-success-emphasis';
        case 'actualizacion':
            return 'bg-info-subtle text-info-emphasis';
        case 'cambio_propietario':
            return 'bg-warning-subtle text-warning-emphasis';
        case 'eliminacion':
            return 'bg-danger-subtle text-danger-emphasis';
        default:
            return 'bg-secondary-subtle text-secondary-emphasis';
    }
}

/**
 * Obtiene el texto descriptivo del tipo de cambio
 * @param {string} tipo - Tipo de cambio
 * @returns {string}
 * @private
 */
function getTipoCambioTexto(tipo) {
    if (!tipo) return 'Tipo no especificado';

    switch (tipo.toLowerCase()) {
        case 'creacion':
            return 'Creación de vehículo';
        case 'actualizacion':
            return 'Actualización de datos';
        case 'cambio_propietario':
            return 'Cambio de propietario';
        case 'eliminacion':
            return 'Eliminación de vehículo';
        default:
            // Convertir primera letra a mayúscula para mejor presentación
            return tipo.charAt(0).toUpperCase() + tipo.slice(1);
    }
}
