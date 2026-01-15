/**
 * personal.js
 * Módulo para gestión de Personal
 *
 * @description
 * Maneja la lógica de CRUD para personal
 * Incluye búsqueda, edición, eliminación y renderizado de tablas
 *
 * @author Refactorización 2025-10-28
 */

import personalApi from '../api/personal-api.js';
import { showToast } from './ui/notifications.js';

let mainContent;
let personalData = [];
let personalModalInstance = null;
let currentPage = 1;
let recordsPerPage = 50;

/**
 * Inicializa el módulo de personal
 * Debe llamarse una sola vez con el elemento principal del contenido
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal (main)
 * @returns {void}
 */
export function initPersonalModule(contentElement) {
    mainContent = contentElement;
    setupPersonalModal();
    setupEventListeners();
    loadPersonalData();
}

/**
 * Configura el modal de personal
 * @private
 */
function setupPersonalModal() {
    const modalEl = document.getElementById('personal-modal');
    if (modalEl && !personalModalInstance) {
        modalEl.innerHTML = getPersonalModalTemplate();
        personalModalInstance = new bootstrap.Modal(modalEl);
        const form = modalEl.querySelector('#personal-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                handlePersonalFormSubmit(e, personalModalInstance);
            });
        }
    }
}

/**
 * Configura los event listeners del módulo
 * @private
 */
function setupEventListeners() {
    const addPersonalBtn = document.getElementById('add-personal-btn');
    const searchPersonalInput = document.getElementById('search-personal-tabla');
    const importPersonalBtn = document.getElementById('import-personal-btn');
    const filterEstadoSelect = document.getElementById('filter-estado');
    const filterUnidadSelect = document.getElementById('filter-unidad');
    const filterGradoSelect = document.getElementById('filter-grado');
    const resetFiltersBtn = document.getElementById('reset-filters-btn');
    const recordsPerPageSelect = document.getElementById('records-per-page');

    if (addPersonalBtn) {
        addPersonalBtn.addEventListener('click', () => openPersonalModal());
    }
    if (searchPersonalInput) {
        searchPersonalInput.addEventListener('input', applyFilters);
    }
    if (importPersonalBtn) {
        importPersonalBtn.addEventListener('click', openImportModal);
    }

    // Listeners para filtros
    if (filterEstadoSelect) {
        filterEstadoSelect.addEventListener('change', applyFilters);
    }
    if (filterUnidadSelect) {
        filterUnidadSelect.addEventListener('change', applyFilters);
    }
    if (filterGradoSelect) {
        filterGradoSelect.addEventListener('change', applyFilters);
    }
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }
    if (recordsPerPageSelect) {
        recordsPerPageSelect.addEventListener('change', (e) => {
            recordsPerPage = parseInt(e.target.value) || 50;
            currentPage = 1;
            applyFilters();
        });
    }

    // Llenar opciones de filtros
    populateFilterSelects();

    // Event delegation para botones de editar/eliminar
    mainContent.addEventListener('click', (e) => {
        const editPersonalBtn = e.target.closest('.edit-personal-btn');
        const deletePersonalBtn = e.target.closest('.delete-personal-btn');

        if (editPersonalBtn) {
            openPersonalModal(editPersonalBtn.dataset.id);
        } else if (deletePersonalBtn) {
            deletePersonal(deletePersonalBtn.dataset.id);
        }
    });
}

/**
 * Carga datos de personal
 * @private
 */
async function loadPersonalData() {
    try {
        personalData = await personalApi.getAll();
        renderPersonalTable(personalData);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Renderiza la tabla de personal con paginación
 * @private
 */
function renderPersonalTable(data) {
    const tableBody = mainContent.querySelector('#personal-table-body');
    const currentRecordsSpan = mainContent.querySelector('#current-records');
    const totalRecordsSpan = mainContent.querySelector('#total-records');

    if (!tableBody) return;

    // Actualizar información de registros
    if (currentRecordsSpan && totalRecordsSpan) {
        totalRecordsSpan.textContent = data.length;
    }

    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-4">No se encontraron resultados.</td></tr>';
        if (currentRecordsSpan) currentRecordsSpan.textContent = 0;
        return;
    }

    // Aplicar paginación si recordsPerPage no es 0 (mostrar todos)
    let dataToShow = data;
    if (recordsPerPage > 0) {
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        dataToShow = data.slice(startIndex, endIndex);
    }

    if (currentRecordsSpan) currentRecordsSpan.textContent = dataToShow.length;

    tableBody.innerHTML = dataToShow.map(p => `
        <tr>
            <td><img src="${p.foto ? '../foto-emple/' + p.foto : 'assets/imagenes/placeholder-avatar.png'}" alt="Foto de ${p.Nombres}" class="rounded-circle" width="40" height="40" style="object-fit: cover;"></td>
            <td>${(p.Grado || '') + ' ' + p.Nombres + ' ' + p.Paterno + ' ' + (p.Materno || '')}</td>
            <td>${p.NrRut}</td>
            <td>${p.Unidad || 'N/A'}</td>
            <td><span class="badge ${p.Estado == 1 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${p.Estado == 1 ? 'Activo' : 'Inactivo'}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary edit-personal-btn" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-personal-btn" data-id="${p.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

/**
 * Llena los selects de filtros con opciones únicas
 * @private
 */
function populateFilterSelects() {
    const unidades = [...new Set(personalData.map(p => p.Unidad).filter(u => u))].sort();
    const grados = [...new Set(personalData.map(p => p.Grado).filter(g => g))].sort();

    const filterUnidadSelect = document.getElementById('filter-unidad');
    const filterGradoSelect = document.getElementById('filter-grado');

    if (filterUnidadSelect) {
        const currentValue = filterUnidadSelect.value;
        filterUnidadSelect.innerHTML = '<option value="">Todas</option>';
        unidades.forEach(unidad => {
            filterUnidadSelect.innerHTML += `<option value="${unidad}">${unidad}</option>`;
        });
        filterUnidadSelect.value = currentValue;
    }

    if (filterGradoSelect) {
        const currentValue = filterGradoSelect.value;
        filterGradoSelect.innerHTML = '<option value="">Todos</option>';
        grados.forEach(grado => {
            filterGradoSelect.innerHTML += `<option value="${grado}">${grado}</option>`;
        });
        filterGradoSelect.value = currentValue;
    }
}

/**
 * Aplica todos los filtros activos
 * @private
 */
function applyFilters() {
    const searchInput = document.getElementById('search-personal-tabla');
    const filterEstado = document.getElementById('filter-estado');
    const filterUnidad = document.getElementById('filter-unidad');
    const filterGrado = document.getElementById('filter-grado');

    const query = (searchInput?.value || '').toLowerCase().trim();
    const estado = filterEstado?.value || '';
    const unidad = filterUnidad?.value || '';
    const grado = filterGrado?.value || '';

    const filteredPersonal = personalData.filter(p => {
        // Filtro por búsqueda (nombre o RUT)
        if (query) {
            const nombreCompleto = `${p.Grado || ''} ${p.Nombres} ${p.Paterno} ${p.Materno || ''}`.toLowerCase();
            const rut = (p.NrRut || '').toLowerCase().replace(/[.-]/g, '');
            const noCoincide = !nombreCompleto.includes(query) && !rut.includes(query);
            if (noCoincide) return false;
        }

        // Filtro por Estado
        if (estado !== '') {
            if (p.Estado != estado) return false;
        }

        // Filtro por Unidad
        if (unidad !== '') {
            if (p.Unidad !== unidad) return false;
        }

        // Filtro por Grado
        if (grado !== '') {
            if (p.Grado !== grado) return false;
        }

        return true;
    });

    // Resetear a página 1 cuando se aplican filtros
    currentPage = 1;
    renderPersonalTable(filteredPersonal);
}

/**
 * Limpia todos los filtros
 * @private
 */
function resetFilters() {
    const searchInput = document.getElementById('search-personal-tabla');
    const filterEstado = document.getElementById('filter-estado');
    const filterUnidad = document.getElementById('filter-unidad');
    const filterGrado = document.getElementById('filter-grado');

    if (searchInput) searchInput.value = '';
    if (filterEstado) filterEstado.value = '';
    if (filterUnidad) filterUnidad.value = '';
    if (filterGrado) filterGrado.value = '';

    currentPage = 1;
    renderPersonalTable(personalData);
}

/**
 * Abre el modal de personal
 * @private
 */
function openPersonalModal(id = null) {
    if (!personalModalInstance) return;

    const modalEl = document.getElementById('personal-modal');
    if (!modalEl) {
        console.error('Modal personal no encontrado');
        return;
    }

    const form = modalEl.querySelector('#personal-form');
    const modalTitle = modalEl.querySelector('#personal-modal-title');

    if (!form || !modalTitle) {
        console.error('Formulario o título del modal no encontrado');
        return;
    }

    form.reset();
    form.classList.remove('was-validated');
    form.elements.id.value = '';

    if (id) {
        modalTitle.textContent = 'Editar Personal';
        const person = personalData.find(p => p.id == id);
        if (person) {
            populatePersonalForm(form, person);
        }
    } else {
        modalTitle.textContent = 'Agregar Personal';
    }
    personalModalInstance.show();
}

/**
 * Rellena el formulario de personal
 * @private
 */
function populatePersonalForm(form, person) {
    const fields = [
        'id', 'Grado', 'Nombres', 'Paterno', 'Materno', 'NrRut', 'fechaNacimiento',
        'sexo', 'estadoCivil', 'nrEmpleado', 'puesto', 'especialidadPrimaria',
        'fechaIngreso', 'fechaPresentacion', 'Unidad', 'unidadEspecifica',
        'categoria', 'escalafon', 'trabajoExterno', 'calle', 'numeroDepto',
        'poblacionVilla', 'telefonoFijo', 'movil1', 'movil2', 'email1', 'email2',
        'anexo', 'foto', 'prevision', 'sistemaSalud', 'regimenMatrimonial',
        'religion', 'tipoVivienda', 'nombreConyuge', 'profesionConyuge',
        'nombreContactoEmergencia', 'direccionEmergencia', 'movilEmergencia',
        'Estado', 'fechaExpiracion'
    ];

    fields.forEach(field => {
        if (form.elements[field]) {
            if (form.elements[field].type === 'checkbox') {
                form.elements[field].checked = person[field] == 1 || !!person[field];
            } else {
                form.elements[field].value = person[field] || '';
            }
        }
    });
}

/**
 * Maneja el envío del formulario de personal
 * @private
 */
async function handlePersonalFormSubmit(e, modal) {
    const form = e.target;
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }

    const id = form.elements.id.value;
    const data = {};

    // Campos que NO deben convertirse a mayúsculas
    const excludeFromUpperCase = ['id', 'fechaNacimiento', 'fechaIngreso', 'fechaPresentacion',
                                  'fechaExpiracion', 'email1', 'email2', 'foto', 'sexo',
                                  'es_residente', 'Estado'];

    for (const element of form.elements) {
        if (element.name) {
            let value = element.type === 'checkbox' ? element.checked : element.value;

            // Convertir a mayúsculas solo si es un campo de texto y no está excluido
            if (element.type === 'text' && !excludeFromUpperCase.includes(element.name) && value) {
                value = value.toUpperCase();
            }

            data[element.name] = value;
        }
    }

    try {
        if (id) {
            await personalApi.update(data);
            showToast('Personal actualizado correctamente.', 'success');
        } else {
            await personalApi.create(data);
            showToast('Personal creado correctamente.', 'success');
        }
        modal.hide();
        await loadPersonalData();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Elimina un personal
 * @private
 */
async function deletePersonal(id) {
    if (confirm('¿Estás seguro de que quieres eliminar a esta persona?')) {
        try {
            await personalApi.delete(id);
            showToast('Personal eliminado correctamente.', 'success');
            await loadPersonalData();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Abre el modal de importación masiva
 * @private
 */
function openImportModal() {
    let importModalInstance = null;
    const importModalEl = document.getElementById('import-personal-modal');

    if (!importModalEl) {
        console.error('Modal de importación no encontrado');
        return;
    }

    importModalEl.innerHTML = getImportPersonalModalTemplate();
    importModalInstance = new bootstrap.Modal(importModalEl);

    // Event listeners del modal
    const fileInput = importModalEl.querySelector('#import-file-input');
    const importBtn = importModalEl.querySelector('#confirm-import-btn');
    const downloadTemplateBtn = importModalEl.querySelector('#download-template-btn');

    if (downloadTemplateBtn) {
        downloadTemplateBtn.addEventListener('click', downloadImportTemplate);
    }

    if (importBtn) {
        importBtn.addEventListener('click', () => handleImportFile(importModalInstance));
    }

    importModalInstance.show();
}

/**
 * Maneja la importación de archivo
 * @private
 */
async function handleImportFile(modalInstance) {
    const fileInput = document.querySelector('#import-file-input');
    const file = fileInput?.files[0];

    if (!file) {
        showToast('Por favor selecciona un archivo', 'warning');
        return;
    }

    // Validar tipo de archivo
    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                       'application/vnd.ms-excel',
                       'text/csv'];
    if (!validTypes.includes(file.type)) {
        showToast('Solo se permiten archivos Excel (.xlsx, .xls) o CSV', 'error');
        return;
    }

    try {
        const data = await readFileAsArray(file);
        if (!data || data.length === 0) {
            showToast('El archivo está vacío o no se pudo leer', 'error');
            return;
        }

        // Mostrar modal de progreso
        modalInstance.hide();
        showImportProgressModal(data);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Lee archivo Excel o CSV y retorna array de objetos
 * @private
 */
async function readFileAsArray(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = async (e) => {
            try {
                let data = [];

                if (file.type === 'text/csv') {
                    data = parseCSV(e.target.result);
                } else {
                    // Excel: cargar librería XLSX si no está disponible
                    if (typeof XLSX === 'undefined') {
                        // Cargar dinamicamente
                        const script = document.createElement('script');
                        script.src = 'js/xlsx.full.min.js';
                        document.head.appendChild(script);

                        await new Promise(resolve => {
                            script.onload = resolve;
                        });
                    }

                    const arrayBuffer = e.target.result;
                    const workbook = XLSX.read(arrayBuffer, { type: 'array' });
                    const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                    data = XLSX.utils.sheet_to_json(worksheet);
                }

                resolve(data);
            } catch (error) {
                reject(new Error('Error al procesar el archivo: ' + error.message));
            }
        };

        reader.onerror = () => reject(new Error('Error al leer el archivo'));

        if (file.type === 'text/csv') {
            reader.readAsText(file);
        } else {
            reader.readAsArrayBuffer(file);
        }
    });
}

/**
 * Parsea contenido CSV (soporta coma y punto y coma como separadores)
 * @private
 */
function parseCSV(csvContent) {
    const lines = csvContent.trim().split('\n');
    if (lines.length < 2) return [];

    // Detectar separador automáticamente (coma o punto y coma)
    let separator = ',';
    if (lines[0].includes(';')) {
        separator = ';';
    }

    const headers = lines[0]
        .split(separator)
        .map(h => h.trim().replace(/^\uFEFF/, '')) // Elimina BOM si existe
        .filter(h => h !== ''); // Elimina encabezados vacíos

    const data = [];

    for (let i = 1; i < lines.length; i++) {
        if (!lines[i].trim()) continue; // Saltar líneas vacías

        const values = lines[i].split(separator).map(v => v.trim());
        const obj = {};

        headers.forEach((header, index) => {
            obj[header] = values[index] || '';
        });

        data.push(obj);
    }

    return data;
}

/**
 * Muestra modal de progreso e importa datos
 * @private
 */
async function showImportProgressModal(personalData) {
    const progressModalEl = document.getElementById('import-progress-modal');

    if (!progressModalEl) {
        console.error('Modal de progreso no encontrado');
        return;
    }

    progressModalEl.innerHTML = getImportProgressTemplate();
    const progressModal = new bootstrap.Modal(progressModalEl);
    progressModal.show();

    const progressBar = progressModalEl.querySelector('#import-progress-bar');
    const statusList = progressModalEl.querySelector('#import-status-list');
    const totalSpan = progressModalEl.querySelector('#import-total');
    const processedSpan = progressModalEl.querySelector('#import-processed');
    const createdSpan = progressModalEl.querySelector('#import-created');
    const updatedSpan = progressModalEl.querySelector('#import-updated');
    const errorsSpan = progressModalEl.querySelector('#import-errors');

    totalSpan.textContent = personalData.length;

    try {
        const result = await personalApi.importMasivo(personalData);

        // Actualizar contadores
        processedSpan.textContent = result.processed || 0;
        createdSpan.textContent = result.created || 0;
        updatedSpan.textContent = result.updated || 0;
        errorsSpan.textContent = result.errors?.length || 0;

        // Mostrar resultados
        let statusHTML = '';

        if (result.success && result.success.length > 0) {
            statusHTML += '<div class="alert alert-success">Importaciones exitosas:</div>';
            result.success.forEach(item => {
                statusHTML += `<div class="mb-2"><span class="badge bg-success">${item.action}</span> Fila ${item.row}: ${item.rut}</div>`;
            });
        }

        if (result.errors && result.errors.length > 0) {
            statusHTML += '<div class="alert alert-danger mt-3">Errores encontrados:</div>';
            result.errors.forEach(error => {
                statusHTML += `<div class="mb-2"><span class="badge bg-danger">Error</span> Fila ${error.row}: ${error.message}</div>`;
            });
        }

        if (statusHTML) {
            statusList.innerHTML = statusHTML;
        }

        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');

        // Recargar datos después de 2 segundos
        setTimeout(async () => {
            await loadPersonalData();
            progressModal.hide();
            showToast(`Importación completada: ${result.created} creados, ${result.updated} actualizados, ${result.errors?.length || 0} errores`, 'success');
        }, 2000);

    } catch (error) {
        statusList.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        progressBar.classList.add('bg-danger');
    }
}

/**
 * Descarga plantilla de importación
 * @private
 */
function downloadImportTemplate() {
    const templateData = [
        {
            'Grado': 'Ej: Sargento',
            'Nombres': 'Juan',
            'Paterno': 'Gonzalez',
            'Materno': 'Lopez',
            'NrRut': '12345678-9',
            'Unidad': 'Ej: A1',
            'Estado': '1',
            'es_residente': '0'
        }
    ];

    // Crear libro de trabajo
    const worksheet = XLSX ? XLSX.utils.json_to_sheet(templateData) : null;

    if (!worksheet) {
        // Si XLSX no está disponible, crear CSV
        const csvContent = 'Grado,Nombres,Paterno,Materno,NrRut,Unidad,Estado,es_residente\n'
            + 'Sargento,Juan,Gonzalez,Lopez,12345678-9,A1,1,0';
        downloadCSV(csvContent, 'plantilla_personal.csv');
        showToast('Se descargó la plantilla en formato CSV', 'success');
        return;
    }

    // Crear y descargar Excel
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Personal');
    XLSX.writeFile(workbook, 'plantilla_personal.xlsx');
    showToast('Plantilla descargada exitosamente', 'success');
}

/**
 * Descarga archivo CSV
 * @private
 */
function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Template para modal de importación
 * @private
 */
function getImportPersonalModalTemplate() {
    return `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar Personal Masivamente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import-file-input" class="form-label">Selecciona archivo Excel o CSV:</label>
                        <input type="file" class="form-control" id="import-file-input"
                               accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted d-block mt-2">
                            Formatos soportados: .xlsx, .xls, .csv<br>
                            Campos requeridos: Nombres, Paterno, NrRut
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <strong>Instrucciones:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Descarga la plantilla de referencia</li>
                            <li>Completa los datos del personal</li>
                            <li>Carga el archivo para procesar</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="download-template-btn">
                        <i class="bi bi-download"></i> Descargar Plantilla
                    </button>
                    <button type="button" class="btn btn-primary" id="confirm-import-btn">
                        <i class="bi bi-upload"></i> Importar
                    </button>
                </div>
            </div>
        </div>
    `;
}

/**
 * Template para modal de progreso
 * @private
 */
function getImportProgressTemplate() {
    return `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Progreso de Importación</h5>
                </div>
                <div class="modal-body">
                    <div class="progress mb-3">
                        <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                             style="width: 100%"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">Total: <strong id="import-total">0</strong></small><br>
                            <small class="text-muted">Procesados: <strong id="import-processed">0</strong></small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-success">Creados: <strong id="import-created">0</strong></small><br>
                            <small class="text-success">Actualizados: <strong id="import-updated">0</strong></small>
                        </div>
                    </div>
                    <div class="alert alert-warning">
                        <small>Errores: <strong id="import-errors">0</strong></small>
                    </div>
                    <div id="import-status-list" class="mt-3" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    `;
}

