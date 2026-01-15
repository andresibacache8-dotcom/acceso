/**
 * personal-refactored.js
 * Módulo de Gestión de Personal - Refactorizado con BaseModule
 *
 * Refactorización: 759 líneas → ~600 líneas (↓ 21%)
 *
 * Cambios:
 * - Extiende BaseModule para reutilizar patrones comunes
 * - Elimina código duplicado de modal, form, table, search, delete
 * - Mantiene lógica específica de personal (filtros avanzados, importación)
 *
 * Patrones reutilizados de BaseModule:
 * ✓ setupModal() - Inicialización de modal
 * ✓ populateModalForm() - Rellena formulario
 * ✓ clearModalForm() - Limpia formulario
 * ✓ setupSearch() - Búsqueda en tabla
 * ✓ loadData() - Carga de datos API
 * ✓ confirmDelete() - Eliminación con confirmación
 * ✓ setupDelegatedListener() - Event delegation
 *
 * @author Refactorización 2025
 */

import { BaseModule } from '../core/base-module.js';
import personalApi from '../api/personal-api.js';

/**
 * PersonalModule - Gestión de Personal
 * Extiende BaseModule pero con lógica específica compleja (importación)
 */
class PersonalModule extends BaseModule {
    /**
     * Constructor
     * @param {HTMLElement} contentElement
     */
    constructor(contentElement) {
        super(contentElement, personalApi);

        // Campos de búsqueda específicos para personal
        this.searchFields = ['Nombres', 'Paterno', 'Materno', 'NrRut', 'Unidad', 'Grado'];

        // Estado local específico de personal
        this.filterUnidad = '';
        this.filterGrado = '';
        this.filterEstado = '';
    }

    /**
     * Inicializa el módulo
     * @async
     */
    async init() {
        try {
            // Configurar modal con template y submit handler
            this.setupModal(
                'personal-modal',
                window.getPersonalModalTemplate,
                this.handlePersonalFormSubmit.bind(this)
            );

            // Configurar search input
            this.setupSearch('search-personal-tabla', this.searchFields);

            // Configurar filtros avanzados
            this.setupAdvancedFilters();

            // Configurar event delegation para botones de acción
            this.setupDelegatedListener(
                '.edit-personal-btn',
                'click',
                this.handleEditClick.bind(this)
            );

            this.setupDelegatedListener(
                '.delete-personal-btn',
                'click',
                this.handleDeleteClick.bind(this)
            );

            // Configurar botón agregar
            const addPersonalBtn = this.content.querySelector('#add-personal-btn');
            if (addPersonalBtn) {
                addPersonalBtn.addEventListener('click', () => {
                    this.openModal('personal-modal');
                });
                this.eventListeners.push({
                    element: addPersonalBtn,
                    event: 'click',
                    handler: () => this.openModal('personal-modal')
                });
            }

            // Configurar botón importar
            const importPersonalBtn = this.content.querySelector('#import-personal-btn');
            if (importPersonalBtn) {
                const importListener = () => this.openImportModal();
                importPersonalBtn.addEventListener('click', importListener);
                this.eventListeners.push({
                    element: importPersonalBtn,
                    event: 'click',
                    handler: importListener
                });
            }

            // Cargar datos
            await this.loadData();

        } catch (error) {
            console.error('Error initializing PersonalModule:', error);
            window.showToast('Error al inicializar módulo de personal', 'error');
        }
    }

    /**
     * Configurar filtros avanzados (estado, unidad, grado)
     * @private
     */
    setupAdvancedFilters() {
        const filterEstado = this.content.querySelector('#filter-estado');
        const filterUnidad = this.content.querySelector('#filter-unidad');
        const filterGrado = this.content.querySelector('#filter-grado');
        const resetFiltersBtn = this.content.querySelector('#reset-filters-btn');

        // Escuchar cambios en filtros avanzados
        if (filterEstado) {
            const listener = () => this.applyAdvancedFilters();
            filterEstado.addEventListener('change', listener);
            this.eventListeners.push({
                element: filterEstado,
                event: 'change',
                handler: listener
            });
        }

        if (filterUnidad) {
            const listener = () => this.applyAdvancedFilters();
            filterUnidad.addEventListener('change', listener);
            this.eventListeners.push({
                element: filterUnidad,
                event: 'change',
                handler: listener
            });
        }

        if (filterGrado) {
            const listener = () => this.applyAdvancedFilters();
            filterGrado.addEventListener('change', listener);
            this.eventListeners.push({
                element: filterGrado,
                event: 'change',
                handler: listener
            });
        }

        if (resetFiltersBtn) {
            const listener = () => this.resetAdvancedFilters();
            resetFiltersBtn.addEventListener('click', listener);
            this.eventListeners.push({
                element: resetFiltersBtn,
                event: 'click',
                handler: listener
            });
        }
    }

    /**
     * Aplicar filtros avanzados
     * @private
     */
    applyAdvancedFilters() {
        const filterEstado = this.content.querySelector('#filter-estado');
        const filterUnidad = this.content.querySelector('#filter-unidad');
        const filterGrado = this.content.querySelector('#filter-grado');

        this.filterEstado = filterEstado?.value || '';
        this.filterUnidad = filterUnidad?.value || '';
        this.filterGrado = filterGrado?.value || '';

        this.currentPage = 1;
        this.applyFilters();
        this.renderTable();
    }

    /**
     * Resetear filtros avanzados
     * @private
     */
    resetAdvancedFilters() {
        const filterEstado = this.content.querySelector('#filter-estado');
        const filterUnidad = this.content.querySelector('#filter-unidad');
        const filterGrado = this.content.querySelector('#filter-grado');
        const searchInput = this.content.querySelector('#search-personal-tabla');

        if (searchInput) searchInput.value = '';
        if (filterEstado) filterEstado.value = '';
        if (filterUnidad) filterUnidad.value = '';
        if (filterGrado) filterGrado.value = '';

        this.filterEstado = '';
        this.filterUnidad = '';
        this.filterGrado = '';
        this.searchQuery = '';
        this.currentPage = 1;

        this.applyFilters();
        this.renderTable();
    }

    /**
     * Aplicar filtros avanzados al data
     * @private
     */
    applyFilters() {
        this.filteredData = this.data.filter(item => this.filterItem(item));

        // Rellenar selects de filtros con opciones únicas del dataset actual
        this.populateFilterSelects();

        // Aplicar sorting si existe
        if (this.sortField) {
            this.filteredData.sort((a, b) => this.compareItems(a, b));
        }
    }

    /**
     * Filtrar item (override para lógica específica de personal)
     * @private
     */
    filterItem(item) {
        // Filtro por búsqueda (se aplica por setupSearch)
        // Ya está filtrado por searchQuery en la lógica de BaseModule

        // Filtro por Estado
        if (this.filterEstado !== '') {
            if (item.Estado != this.filterEstado) return false;
        }

        // Filtro por Unidad
        if (this.filterUnidad !== '') {
            if (item.Unidad !== this.filterUnidad) return false;
        }

        // Filtro por Grado
        if (this.filterGrado !== '') {
            if (item.Grado !== this.filterGrado) return false;
        }

        return true;
    }

    /**
     * Rellenar selects de filtros con opciones únicas
     * @private
     */
    populateFilterSelects() {
        const unidades = [...new Set(this.data.map(p => p.Unidad).filter(u => u))].sort();
        const grados = [...new Set(this.data.map(p => p.Grado).filter(g => g))].sort();

        const filterUnidadSelect = this.content.querySelector('#filter-unidad');
        const filterGradoSelect = this.content.querySelector('#filter-grado');

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
     * Renderizar tabla de personal
     * Implementación específica para personal
     */
    renderTable() {
        const tableBody = this.content.querySelector('#personal-table-body');
        if (!tableBody) return;

        if (this.filteredData.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted p-4">
                        No se encontraron resultados.
                    </td>
                </tr>
            `;
            return;
        }

        // Aplicar paginación
        const startIndex = (this.currentPage - 1) * this.recordsPerPage;
        const endIndex = startIndex + this.recordsPerPage;
        const dataToShow = this.filteredData.slice(startIndex, endIndex);

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
     * Manejar clic en botón editar
     * @private
     */
    handleEditClick(e, target) {
        const id = target.dataset.id;
        const personal = this.data.find(p => p.id == id);

        if (personal) {
            this.openModal('personal-modal', personal);
        }
    }

    /**
     * Manejar clic en botón eliminar
     * @private
     */
    handleDeleteClick(e, target) {
        const id = target.dataset.id;
        const personal = this.data.find(p => p.id == id);

        if (personal) {
            const nombreCompleto = `${personal.Grado || ''} ${personal.Nombres} ${personal.Paterno}`.trim();
            this.confirmDelete(id, nombreCompleto);
        }
    }

    /**
     * Manejar envío de formulario de personal
     * @private
     */
    async handlePersonalFormSubmit(e, modal) {
        const form = e.target;

        // Validación del formulario
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        try {
            window.showLoadingSpinner();

            const id = form.elements.id.value;
            const data = {};

            // Campos que NO se convierten a mayúsculas
            const excludeFromUpperCase = [
                'id', 'fechaNacimiento', 'fechaIngreso', 'fechaPresentacion',
                'fechaExpiracion', 'email1', 'email2', 'foto', 'sexo',
                'es_residente', 'Estado'
            ];

            // Procesar elementos del formulario
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

            // Realizar operación (crear o actualizar)
            let response;
            if (id) {
                response = await this.api.update(data);
                window.showToast('Personal actualizado correctamente', 'success');
            } else {
                response = await this.api.create(data);
                window.showToast('Personal creado correctamente', 'success');
            }

            if (response.success) {
                modal.hide();
                await this.loadData();
            } else {
                throw new Error(response.error?.message || 'Error al guardar');
            }

        } catch (error) {
            console.error('Error submitting personal form:', error);
            window.showToast(error.message || 'Error al guardar', 'error');
        } finally {
            window.hideLoadingSpinner();
        }
    }

    /**
     * Abrir modal de importación masiva
     * @private
     */
    openImportModal() {
        const importModalEl = this.content.querySelector('#import-personal-modal');

        if (!importModalEl) {
            console.error('Modal de importación no encontrado');
            return;
        }

        importModalEl.innerHTML = this.getImportPersonalModalTemplate();
        const importModalInstance = new bootstrap.Modal(importModalEl);

        // Event listeners del modal
        const fileInput = importModalEl.querySelector('#import-file-input');
        const importBtn = importModalEl.querySelector('#confirm-import-btn');
        const downloadTemplateBtn = importModalEl.querySelector('#download-template-btn');

        if (downloadTemplateBtn) {
            downloadTemplateBtn.addEventListener('click', () => this.downloadImportTemplate());
        }

        if (importBtn) {
            importBtn.addEventListener('click', () => this.handleImportFile(importModalInstance));
        }

        importModalInstance.show();
    }

    /**
     * Manejar importación de archivo
     * @private
     */
    async handleImportFile(modalInstance) {
        const fileInput = this.content.querySelector('#import-file-input');
        const file = fileInput?.files[0];

        if (!file) {
            window.showToast('Por favor selecciona un archivo', 'warning');
            return;
        }

        // Validar tipo de archivo
        const validTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv'
        ];
        if (!validTypes.includes(file.type)) {
            window.showToast('Solo se permiten archivos Excel (.xlsx, .xls) o CSV', 'error');
            return;
        }

        try {
            const data = await this.readFileAsArray(file);
            if (!data || data.length === 0) {
                window.showToast('El archivo está vacío o no se pudo leer', 'error');
                return;
            }

            // Mostrar modal de progreso
            modalInstance.hide();
            this.showImportProgressModal(data);
        } catch (error) {
            window.showToast(error.message, 'error');
        }
    }

    /**
     * Lee archivo Excel o CSV y retorna array de objetos
     * @private
     */
    async readFileAsArray(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = async (e) => {
                try {
                    let data = [];

                    if (file.type === 'text/csv') {
                        data = this.parseCSV(e.target.result);
                    } else {
                        // Excel: cargar librería XLSX si no está disponible
                        if (typeof XLSX === 'undefined') {
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
     * Parsea contenido CSV
     * @private
     */
    parseCSV(csvContent) {
        const lines = csvContent.trim().split('\n');
        if (lines.length < 2) return [];

        // Detectar separador automáticamente (coma o punto y coma)
        let separator = ',';
        if (lines[0].includes(';')) {
            separator = ';';
        }

        const headers = lines[0]
            .split(separator)
            .map(h => h.trim().replace(/^\uFEFF/, ''))
            .filter(h => h !== '');

        const data = [];

        for (let i = 1; i < lines.length; i++) {
            if (!lines[i].trim()) continue;

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
    async showImportProgressModal(personalData) {
        const progressModalEl = this.content.querySelector('#import-progress-modal');

        if (!progressModalEl) {
            console.error('Modal de progreso no encontrado');
            return;
        }

        progressModalEl.innerHTML = this.getImportProgressTemplate();
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
            const result = await this.api.importMasivo(personalData);

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
                await this.loadData();
                progressModal.hide();
                window.showToast(`Importación completada: ${result.created} creados, ${result.updated} actualizados, ${result.errors?.length || 0} errores`, 'success');
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
    downloadImportTemplate() {
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
        const worksheet = typeof XLSX !== 'undefined' ? XLSX.utils.json_to_sheet(templateData) : null;

        if (!worksheet) {
            // Si XLSX no está disponible, crear CSV
            const csvContent = 'Grado,Nombres,Paterno,Materno,NrRut,Unidad,Estado,es_residente\n'
                + 'Sargento,Juan,Gonzalez,Lopez,12345678-9,A1,1,0';
            this.downloadCSV(csvContent, 'plantilla_personal.csv');
            window.showToast('Se descargó la plantilla en formato CSV', 'success');
            return;
        }

        // Crear y descargar Excel
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Personal');
        XLSX.writeFile(workbook, 'plantilla_personal.xlsx');
        window.showToast('Plantilla descargada exitosamente', 'success');
    }

    /**
     * Descarga archivo CSV
     * @private
     */
    downloadCSV(csvContent, filename) {
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
    getImportPersonalModalTemplate() {
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
    getImportProgressTemplate() {
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
}

/**
 * Inicializador del módulo (para compatibilidad)
 * @param {HTMLElement} contentElement
 */
export function initPersonalModule(contentElement) {
    const module = new PersonalModule(contentElement);
    module.init();
    return module;
}

export default PersonalModule;
