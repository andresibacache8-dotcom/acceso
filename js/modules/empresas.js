/**
 * empresas.js
 * Módulo para gestión de empresas y sus empleados
 *
 * @description
 * Maneja la lógica de CRUD para empresas y sus empleados asociados
 * Incluye búsqueda, edición, eliminación y renderizado de tablas
 *
 * @author Refactorización 2025-10-25
 */

import empresasApi from '../api/empresas-api.js';
import personalApi from '../api/personal-api.js';
import { showToast } from './ui/notifications.js';

let mainContent;
let empresasData = [];
let empleadosData = [];
let selectedEmpresaId = null;
let empresaModalInstance = null;
let empleadoModalInstance = null;
let allPersonalData = []; // Cache de todo el personal para búsqueda

/**
 * Inicializa el módulo de empresas
 * Debe llamarse una sola vez con el elemento principal del contenido
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal (main)
 * @returns {void}
 */
export function initEmpresasModule(contentElement) {
    mainContent = contentElement;
    createModals();
    setupEmpresaModal();
    setupEmpleadoModal();
    setupEventListeners();
    loadAndRenderEmpresas();
}

/**
 * Crea los modales de empresa y empleado dinámicamente
 * @private
 */
function createModals() {
    // Crear modal de empresa si no existe
    if (!document.getElementById('empresa-modal')) {
        const empresaModalEl = document.createElement('div');
        empresaModalEl.className = 'modal fade';
        empresaModalEl.id = 'empresa-modal';
        empresaModalEl.innerHTML = window.getEmpresaModalTemplate();
        document.body.appendChild(empresaModalEl);
    }

    // Crear modal de empleado si no existe
    if (!document.getElementById('empleado-modal')) {
        const empleadoModalEl = document.createElement('div');
        empleadoModalEl.className = 'modal fade';
        empleadoModalEl.id = 'empleado-modal';
        empleadoModalEl.innerHTML = window.getEmpresaEmpleadoModalTemplate();
        document.body.appendChild(empleadoModalEl);
    }
}

/**
 * Configura el modal de empresa
 * @private
 */
function setupEmpresaModal() {
    const modalEl = document.getElementById('empresa-modal');
    if (modalEl && !empresaModalInstance) {
        empresaModalInstance = new bootstrap.Modal(modalEl);
        const form = modalEl.querySelector('#empresa-form');
        if (form) {
            form.addEventListener('submit', handleEmpresaFormSubmit);
        }

        // Evento para búsqueda de POC mientras se escribe
        const pocRutInput = modalEl.querySelector('#poc_rut');
        if (pocRutInput) {
            pocRutInput.addEventListener('focus', async () => {
                // Cargar personal la primera vez que el usuario hace focus en el campo
                if (allPersonalData.length === 0) {
                    await loadPersonalData();
                }
            });
            pocRutInput.addEventListener('input', handlePocSearch);
        }

        // Evento para seleccionar POC de la lista
        modalEl.addEventListener('click', (e) => {
            const pocItem = e.target.closest('.poc-search-item');
            if (pocItem) {
                selectPocFromSearch(pocItem);
            }
        });
    }
}

/**
 * Configura el modal de empleado
 * @private
 */
function setupEmpleadoModal() {
    const modalEl = document.getElementById('empleado-modal');
    if (modalEl && !empleadoModalInstance) {
        empleadoModalInstance = new bootstrap.Modal(modalEl);
        const form = modalEl.querySelector('#empleado-form');
        if (form) {
            form.addEventListener('submit', handleEmpleadoFormSubmit);
        }

        // Lógica de acceso permanente
        const accesoPermanenteCheckbox = modalEl.querySelector('#empleado_acceso_permanente');
        const fechaExpiracionInput = modalEl.querySelector('#empleado_fecha_expiracion');
        const fechaExpiracionRequired = modalEl.querySelector('#fecha_expiracion_required');

        if (accesoPermanenteCheckbox && fechaExpiracionInput) {
            accesoPermanenteCheckbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    // Si está activado: deshabilitar y remover required
                    fechaExpiracionInput.disabled = true;
                    fechaExpiracionInput.removeAttribute('required');
                    fechaExpiracionInput.value = '';
                    if (fechaExpiracionRequired) fechaExpiracionRequired.style.display = 'none';
                } else {
                    // Si está desactivado: habilitar y agregar required
                    fechaExpiracionInput.disabled = false;
                    fechaExpiracionInput.setAttribute('required', 'required');
                    if (fechaExpiracionRequired) fechaExpiracionRequired.style.display = 'inline';
                }
            });
        }
    }
}

/**
 * Configura los event listeners del módulo
 * @private
 */
function setupEventListeners() {
    const empresasList = mainContent.querySelector('#empresas-list');
    const empleadosTableBody = mainContent.querySelector('#empleados-table-body');
    const addEmpresaBtn = mainContent.querySelector('#add-empresa-btn');
    const addEmpleadoBtn = mainContent.querySelector('#add-empleado-btn');
    const importEmpleadosBtn = mainContent.querySelector('#import-empleados-btn');
    const searchEmpresaInput = mainContent.querySelector('#search-empresa');
    const searchEmpleadoInput = mainContent.querySelector('#search-empleado-input');

    if (addEmpresaBtn) {
        addEmpresaBtn.addEventListener('click', () => openEmpresaModal());
    }

    if (searchEmpresaInput) {
        searchEmpresaInput.addEventListener('input', handleEmpresaTableSearch);
    }

    if (addEmpleadoBtn) {
        addEmpleadoBtn.addEventListener('click', () => openEmpleadoModal());
    }

    if (importEmpleadosBtn) {
        importEmpleadosBtn.addEventListener('click', () => openImportEmpleadosModal());
    }

    if (searchEmpleadoInput) {
        searchEmpleadoInput.addEventListener('input', handleEmpleadoTableSearch);
    }

    // Event delegation para empresas
    if (empresasList) {
        empresasList.addEventListener('click', (e) => {
            const link = e.target.closest('a.list-group-item');
            const editBtn = e.target.closest('.edit-empresa-btn');
            const deleteBtn = e.target.closest('.delete-empresa-btn');

            if (editBtn) {
                e.preventDefault();
                openEmpresaModal(editBtn.dataset.id);
            } else if (deleteBtn) {
                e.preventDefault();
                deleteEmpresa(deleteBtn.dataset.id);
            } else if (link) {
                e.preventDefault();
                selectEmpresa(link.dataset.id);
            }
        });
    }

    // Event delegation para empleados
    if (empleadosTableBody) {
        empleadosTableBody.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-empleado-btn');
            const deleteBtn = e.target.closest('.delete-empleado-btn');

            if (editBtn) {
                openEmpleadoModal(editBtn.dataset.id);
            } else if (deleteBtn) {
                deleteEmpleado(deleteBtn.dataset.id);
            }
        });
    }
}

/**
 * Carga y renderiza las empresas
 * @private
 */
async function loadAndRenderEmpresas() {
    try {
        empresasData = await empresasApi.getAll();
        renderEmpresas(empresasData);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Renderiza la lista de empresas
 * @private
 */
function renderEmpresas(data) {
    const empresasList = mainContent.querySelector('#empresas-list');
    if (!empresasList) return;

    empresasList.innerHTML = data.length === 0
        ? '<li class="list-group-item text-center text-muted">No hay empresas.</li>'
        : data.map(empresa => `
            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-id="${empresa.id}">
                ${empresa.nombre}
                <div>
                    <button class="btn btn-sm btn-outline-primary edit-empresa-btn" data-id="${empresa.id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-empresa-btn" data-id="${empresa.id}"><i class="bi bi-trash"></i></button>
                </div>
            </a>
        `).join('');
}

/**
 * Carga y renderiza los empleados de una empresa
 * @private
 */
async function loadAndRenderEmpleados(empresaId) {
    try {
        empleadosData = await empresasApi.getEmpleados(empresaId);
        renderEmpleados(empleadosData);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Renderiza la tabla de empleados
 * @private
 */
function renderEmpleados(data) {
    const empleadosTableBody = mainContent.querySelector('#empleados-table-body');
    if (!empleadosTableBody) return;

    empleadosTableBody.innerHTML = data.length === 0
        ? '<tr><td colspan="5" class="text-center text-muted p-4">Esta empresa no tiene empleados registrados.</td></tr>'
        : data.map(emp => `
            <tr>
                <td>${emp.nombre} ${emp.paterno} ${emp.materno || ''}</td>
                <td>${emp.rut}</td>
                <td><span class="badge ${emp.status === 'autorizado' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'}">${emp.status}</span></td>
                <td>${emp.acceso_permanente ? 'Permanente' : (emp.fecha_expiracion || 'N/A')}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-empleado-btn" data-id="${emp.id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-empleado-btn" data-id="${emp.id}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `).join('');
}

/**
 * Busca en la lista de empresas
 * @private
 */
function handleEmpresaTableSearch(e) {
    const query = e.target.value.toLowerCase();
    const filtered = empresasData.filter(emp => emp.nombre.toLowerCase().includes(query));
    renderEmpresas(filtered);
}

/**
 * Busca en la tabla de empleados
 * @private
 */
function handleEmpleadoTableSearch(e) {
    const query = e.target.value.toLowerCase().trim();
    if (!empleadosData.length) return;

    const filtered = empleadosData.filter(emp => {
        const nombreCompleto = `${emp.nombre} ${emp.paterno} ${emp.materno || ''}`.toLowerCase();
        const rut = (emp.rut || '').toLowerCase();
        return nombreCompleto.includes(query) || rut.includes(query);
    });
    renderEmpleados(filtered);
}

/**
 * Abre el modal de empresa
 * @private
 */
function openEmpresaModal(id = null) {
    if (!empresaModalInstance) return;

    const modalEl = document.getElementById('empresa-modal');
    const form = modalEl.querySelector('#empresa-form');
    const modalTitle = modalEl.querySelector('#empresa-modal-title');
    const feedback = modalEl.querySelector('#poc-rut-feedback');

    form.reset();
    feedback.textContent = '';

    if (id) {
        modalTitle.textContent = 'Editar Empresa';
        const empresa = empresasData.find(e => e.id == id);
        if (empresa) {
            form.elements.id.value = empresa.id;
            form.elements.nombre.value = empresa.nombre;
            form.elements.unidad_poc.value = empresa.unidad_poc || '';
            form.elements.poc_rut.value = empresa.poc_rut || '';
            form.elements.poc_nombre.value = empresa.poc_nombre || '';
            form.elements.poc_anexo.value = empresa.poc_anexo || '';
            if(empresa.poc_nombre) feedback.textContent = `POC: ${empresa.poc_nombre}`;
        }
    } else {
        modalTitle.textContent = 'Agregar Empresa';
        form.elements.id.value = '';
    }

    empresaModalInstance.show();
}

/**
 * Abre el modal de empleado
 * @private
 */
function openEmpleadoModal(id = null) {
    if (!empleadoModalInstance) return;

    const modalEl = document.getElementById('empleado-modal');
    const form = modalEl.querySelector('#empleado-form');
    const modalTitle = modalEl.querySelector('#empleado-modal-title');

    form.reset();
    form.elements.empresa_id.value = selectedEmpresaId;

    if (id) {
        modalTitle.textContent = 'Editar Empleado';
        const empleado = empleadosData.find(e => e.id == id);
        if (empleado) {
            form.elements.id.value = empleado.id;
            form.elements.nombre.value = empleado.nombre;
            form.elements.paterno.value = empleado.paterno;
            form.elements.materno.value = empleado.materno;
            form.elements.rut.value = empleado.rut;
            form.elements.fecha_inicio.value = empleado.fecha_inicio || '';
            form.elements.fecha_expiracion.value = empleado.fecha_expiracion || '';
            form.elements.acceso_permanente.checked = !!empleado.acceso_permanente;
        }
    } else {
        modalTitle.textContent = 'Agregar Empleado';
    }

    form.elements.acceso_permanente.dispatchEvent(new Event('change'));
    empleadoModalInstance.show();
}

/**
 * Selecciona una empresa y carga sus empleados
 * @private
 */
async function selectEmpresa(empresaId) {
    selectedEmpresaId = empresaId;
    const empresasList = mainContent.querySelector('#empresas-list');
    const empleadosHeader = mainContent.querySelector('#empleados-header');
    const pocInfoHeader = mainContent.querySelector('#poc-info-header');
    const addEmpleadoBtn = mainContent.querySelector('#add-empleado-btn');

    // Actualizar selección visual
    if (empresasList) {
        empresasList.querySelectorAll('a.list-group-item').forEach(a => a.classList.remove('active'));
        empresasList.querySelector(`a[data-id="${empresaId}"]`)?.classList.add('active');
    }

    // Actualizar encabezado y info del POC
    const empresa = empresasData.find(e => e.id == empresaId);
    if (empresa) {
        if (empleadosHeader) {
            empleadosHeader.textContent = `Empleados de ${empresa.nombre}`;
        }

        if (pocInfoHeader) {
            let pocInfo = 'Sin POC asignado.';
            if (empresa.poc_nombre) {
                pocInfo = `POC: ${empresa.poc_nombre}`;
                if (empresa.unidad_poc) pocInfo += ` (${empresa.unidad_poc})`;
                if (empresa.poc_anexo) pocInfo += ` - Anexo: ${empresa.poc_anexo}`;
            }
            pocInfoHeader.textContent = pocInfo;
        }

        // Mostrar botones de agregar e importar empleados
        const addEmpleadoBtn = mainContent.querySelector('#add-empleado-btn');
        const importEmpleadosBtn = mainContent.querySelector('#import-empleados-btn');
        if (addEmpleadoBtn) addEmpleadoBtn.style.display = 'block';
        if (importEmpleadosBtn) importEmpleadosBtn.style.display = 'block';
    }

    // Cargar empleados
    await loadAndRenderEmpleados(empresaId);
}

/**
 * Carga todos los datos de personal una sola vez
 * @private
 */
async function loadPersonalData() {
    try {
        allPersonalData = await personalApi.getAll();
    } catch (error) {
        console.error('Error al cargar personal:', error);
        allPersonalData = [];
    }
}

/**
 * Maneja la búsqueda de POC mientras se escribe (filtrado client-side)
 * @private
 */
function handlePocSearch(e) {
    const query = e.target.value.trim().toLowerCase();
    const modalEl = document.getElementById('empresa-modal');
    const resultsContainer = modalEl.querySelector('#poc-search-results');
    const feedback = modalEl.querySelector('#poc-rut-feedback');

    if (!query || query.length < 1) {
        resultsContainer.style.display = 'none';
        feedback.textContent = '';
        return;
    }

    // Filtrar personal client-side
    const filtered = allPersonalData.filter(person => {
        const rut = (person.NrRut || person.RUT || person.rut || '').toString();
        const nombres = (person.Nombres || person.nombres || '').toLowerCase();
        const paterno = (person.Paterno || person.paterno || '').toLowerCase();
        const materno = (person.Materno || person.materno || '').toLowerCase();

        // Buscar en cualquiera de estos campos
        return (
            rut.includes(query) ||
            nombres.includes(query) ||
            paterno.includes(query) ||
            materno.includes(query)
        );
    });

    if (filtered.length > 0) {
        renderPocSearchResults(filtered, modalEl);
        resultsContainer.style.display = 'block';
        feedback.textContent = '';
    } else {
        resultsContainer.style.display = 'none';
        feedback.textContent = 'No se encontraron resultados.';
        feedback.className = 'form-text text-warning';
    }
}

/**
 * Renderiza los resultados de búsqueda de POC
 * @private
 */
function renderPocSearchResults(results, modalEl) {
    const container = modalEl.querySelector('#poc-search-results');
    container.innerHTML = results.map(person => {
        // Manejar variaciones de nombres de campos de la API
        const grado = person.Grado || person.grado || '';
        const nombres = person.Nombres || person.nombres || '';
        const paterno = person.Paterno || person.paterno || '';
        const materno = person.Materno || person.materno || '';
        const rut = person.NrRut || person.RUT || person.rut || '';
        const anexo = person.anexo || person.Anexo || '';

        const nombreCompleto = `${grado} ${nombres} ${paterno}`.trim();

        return `
            <button type="button" class="list-group-item list-group-item-action poc-search-item"
                    data-rut="${rut}" data-nombre="${nombreCompleto}" data-anexo="${anexo}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${nombreCompleto}</strong>
                        <br>
                        <small class="text-muted">RUT: ${rut}</small>
                    </div>
                    ${anexo ? `<small class="text-muted">Anexo: ${anexo}</small>` : ''}
                </div>
            </button>
        `;
    }).join('');
}

/**
 * Selecciona un POC de la lista de búsqueda
 * @private
 */
function selectPocFromSearch(element) {
    const modalEl = document.getElementById('empresa-modal');
    const rutInput = modalEl.querySelector('#poc_rut');
    const nombreInput = modalEl.querySelector('#poc_nombre');
    const anexoInput = modalEl.querySelector('#poc_anexo');
    const resultsContainer = modalEl.querySelector('#poc-search-results');
    const feedback = modalEl.querySelector('#poc-rut-feedback');

    const rut = element.dataset.rut;
    const nombre = element.dataset.nombre;
    const anexo = element.dataset.anexo;

    rutInput.value = rut;
    nombreInput.value = nombre;
    anexoInput.value = anexo;

    feedback.textContent = `Seleccionado: ${nombre}`;
    feedback.className = 'form-text text-success';

    resultsContainer.style.display = 'none';
}

/**
 * Maneja el envío del formulario de empresa
 * @private
 */
async function handleEmpresaFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    // Campos que NO deben convertirse a mayúsculas (solo ID y RUT que son referencias)
    const excludeFromUpperCase = ['id', 'poc_rut', 'poc_nombre'];

    // Convertir campos de texto a mayúsculas
    for (const key in data) {
        if (!excludeFromUpperCase.includes(key) && data[key]) {
            data[key] = data[key].toUpperCase();
        }
    }

    try {
        if (data.id) {
            await empresasApi.update(data);
            showToast('Empresa actualizada correctamente.', 'success');
        } else {
            await empresasApi.create(data);
            showToast('Empresa creada correctamente.', 'success');
        }
        empresaModalInstance.hide();
        await loadAndRenderEmpresas();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Maneja el envío del formulario de empleado
 * @private
 */
async function handleEmpleadoFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    data.acceso_permanente = form.elements.acceso_permanente.checked;

    // Campos que NO deben convertirse a mayúsculas
    const excludeFromUpperCase = ['id', 'rut', 'empresa_id', 'acceso_permanente', 'fecha_expiracion'];

    // Convertir campos de texto a mayúsculas
    for (const key in data) {
        if (!excludeFromUpperCase.includes(key) && data[key] && typeof data[key] === 'string') {
            data[key] = data[key].toUpperCase();
        }
    }

    console.log('Datos de empleado a enviar:', data);

    try {
        if (data.id) {
            await empresasApi.updateEmpleado(data);
            showToast('Empleado actualizado correctamente.', 'success');
        } else {
            await empresasApi.createEmpleado(data);
            showToast('Empleado creado correctamente.', 'success');
        }
        empleadoModalInstance.hide();
        await loadAndRenderEmpleados(selectedEmpresaId);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Elimina una empresa
 * @private
 */
async function deleteEmpresa(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta empresa y todos sus empleados?')) {
        try {
            await empresasApi.delete(id);
            showToast('Empresa eliminada correctamente.', 'success');
            await loadAndRenderEmpresas();

            // Limpiar vista de empleados
            const empleadosTableBody = mainContent.querySelector('#empleados-table-body');
            const empleadosHeader = mainContent.querySelector('#empleados-header');
            const pocInfoHeader = mainContent.querySelector('#poc-info-header');
            const addEmpleadoBtn = mainContent.querySelector('#add-empleado-btn');

            if (empleadosTableBody) {
                empleadosTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted p-4">No hay empresa seleccionada.</td></tr>';
            }
            if (empleadosHeader) {
                empleadosHeader.textContent = 'Seleccione una empresa';
            }
            if (pocInfoHeader) {
                pocInfoHeader.textContent = '';
            }
            if (addEmpleadoBtn) {
                addEmpleadoBtn.style.display = 'none';
            }
            selectedEmpresaId = null;
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Elimina un empleado
 * @private
 */
async function deleteEmpleado(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este empleado?')) {
        try {
            await empresasApi.deleteEmpleado(id);
            showToast('Empleado eliminado correctamente.', 'success');
            await loadAndRenderEmpleados(selectedEmpresaId);
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Abre el modal de importación de empleados
 * @private
 */
function openImportEmpleadosModal() {
    if (!selectedEmpresaId) {
        showToast('Por favor, selecciona una empresa primero.', 'warning');
        return;
    }

    // Crear el modal si no existe
    let importModalEl = document.getElementById('import-empleados-modal');
    if (!importModalEl) {
        importModalEl = document.createElement('div');
        importModalEl.id = 'import-empleados-modal';
        importModalEl.className = 'modal fade';
        importModalEl.tabIndex = '-1';
        document.body.appendChild(importModalEl);
    }

    // Establecer el contenido y inicializar
    importModalEl.innerHTML = window.getImportEmpleadosModalTemplate();
    const importModal = new bootstrap.Modal(importModalEl);

    // Resetear el contenido del modal
    const resetModalContent = () => {
        const fileInput = document.getElementById('empleados-excel-file');
        if (fileInput) fileInput.value = '';
        const progressContainer = document.getElementById('import-progress-container');
        if (progressContainer) progressContainer.classList.add('d-none');
        const progressBar = document.getElementById('import-progress-bar');
        if (progressBar) progressBar.style.width = '0%';
        const resultsContainer = document.getElementById('import-results');
        if (resultsContainer) resultsContainer.classList.add('d-none');
        const startBtn = document.getElementById('start-import-btn');
        if (startBtn) startBtn.disabled = false;
    };

    // Event listener para el botón de iniciar importación
    const startImportBtn = document.getElementById('start-import-btn');
    if (startImportBtn) {
        startImportBtn.onclick = null; // Limpiar eventos anteriores
        startImportBtn.addEventListener('click', () => {
            handleImportEmpleados();
        });
    }

    // Botones para descargar plantillas
    const descargarExcelBtn = importModalEl.querySelector('#descargar-plantilla-excel');
    const descargarCsvBtn = importModalEl.querySelector('#descargar-plantilla-csv');

    if (descargarExcelBtn) {
        descargarExcelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            descargarPlantillaEmpleadosExcel();
        });
    }

    if (descargarCsvBtn) {
        descargarCsvBtn.addEventListener('click', (e) => {
            e.preventDefault();
            descargarPlantillaEmpleadosCsv();
        });
    }

    // Mostrar el modal
    importModal.show();

    // Resetear cuando se oculta
    importModalEl.addEventListener('hidden.bs.modal', resetModalContent);
}

/**
 * Descarga plantilla Excel para importación de empleados
 * @private
 */
function descargarPlantillaEmpleadosExcel() {
    try {
        const headers = ["nombre", "paterno", "materno", "rut", "fecha_inicio", "acceso_permanente", "fecha_expiracion"];
        const ejemploData = [
            ["JUAN", "PÉREZ", "GARCÍA", "12345678", "2025-01-15", "0", "2025-12-31"],
            ["MARÍA", "GARCÍA", "RODRIGUEZ", "15234567", "2025-01-20", "1", ""],
            ["CARLOS", "LOPEZ", "MARTINEZ", "16456789", "2025-02-01", "0", "2025-11-15"]
        ];

        if (typeof XLSX !== 'undefined') {
            const ws = XLSX.utils.aoa_to_sheet([headers, ...ejemploData]);
            ws['!cols'] = [
                {wch: 15}, // nombre
                {wch: 15}, // paterno
                {wch: 15}, // materno
                {wch: 12}, // rut
                {wch: 15}, // fecha_inicio
                {wch: 18}, // acceso_permanente
                {wch: 18}  // fecha_expiracion
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Empleados");
            XLSX.writeFile(wb, "plantilla_empleados.xlsx");
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
 * Descarga plantilla CSV para importación de empleados
 * @private
 */
function descargarPlantillaEmpleadosCsv() {
    try {
        const headers = "nombre,paterno,materno,rut,fecha_inicio,acceso_permanente,fecha_expiracion";
        const ejemploData = [
            "JUAN,PÉREZ,GARCÍA,12345678,2025-01-15,0,2025-12-31",
            "MARÍA,GARCÍA,RODRIGUEZ,15234567,2025-01-20,1,",
            "CARLOS,LOPEZ,MARTINEZ,16456789,2025-02-01,0,2025-11-15"
        ];

        const csvContent = [headers, ...ejemploData].join('\n');
        const blob = new Blob([csvContent], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);

        link.setAttribute('href', url);
        link.setAttribute('download', 'plantilla_empleados.csv');
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
 * Maneja la importación de empleados desde archivo
 * @private
 */
async function handleImportEmpleados() {
    const fileInput = document.getElementById('empleados-excel-file');
    const file = fileInput.files[0];

    if (!file) {
        showToast('Por favor, seleccione un archivo para importar', 'warning');
        return;
    }

    const isExcel = file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ||
                    file.type === 'application/vnd.ms-excel' ||
                    file.name.endsWith('.xlsx') ||
                    file.name.endsWith('.xls');

    const isCsv = file.type === 'text/csv' || file.name.endsWith('.csv');

    if (!isExcel && !isCsv) {
        showToast('El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv)', 'warning');
        return;
    }

    document.getElementById('start-import-btn').disabled = true;
    document.getElementById('import-progress-container').classList.remove('d-none');
    document.getElementById('import-results').classList.add('d-none');
    document.getElementById('import-status').textContent = 'Procesando archivo...';

    try {
        let empleadosDataImport;

        const isExcelFile = file.name.endsWith('.xlsx') || file.name.endsWith('.xls');

        if (isExcelFile) {
            if (typeof XLSX === 'undefined') {
                await loadExcelLibrary();
            }
            empleadosDataImport = await readExcelFile(file);
        } else {
            empleadosDataImport = await readCSVFile(file);
        }

        await processEmpleadosImport(empleadosDataImport);

    } catch (error) {
        console.error('Error en la importación de empleados:', error);
        showToast('Error en la importación: ' + error.message, 'error');
        document.getElementById('import-status').textContent = 'Error durante la importación: ' + error.message;
    }
}

/**
 * Carga dinámicamente la biblioteca xlsx.js
 * @private
 */
function loadExcelLibrary() {
    return new Promise((resolve, reject) => {
        if (typeof XLSX !== 'undefined') {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = 'js/xlsx.full.min.js';
        script.onload = () => {
            console.log('Biblioteca SheetJS cargada correctamente');
            resolve();
        };
        script.onerror = () => {
            console.error('Error al cargar la biblioteca SheetJS');
            reject(new Error('No se pudo cargar la biblioteca para procesar archivos Excel.'));
        };
        document.head.appendChild(script);
    });
}

/**
 * Lee un archivo Excel
 * @private
 */
function readExcelFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            try {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { raw: false });

                if (jsonData.length === 0) {
                    reject(new Error('El archivo Excel está vacío o no tiene datos válidos'));
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
 * Lee un archivo CSV
 * @private
 */
function readCSVFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            try {
                const csv = event.target.result;
                const lines = csv.split('\n');
                const headers = lines[0].split(',').map(header => header.trim());

                const empleados = [];

                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;

                    const values = lines[i].split(',').map(value => value.trim());

                    if (values.length !== headers.length) continue;

                    const empleado = {};
                    headers.forEach((header, index) => {
                        empleado[header] = values[index];
                    });

                    empleados.push(empleado);
                }

                resolve(empleados);

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
 * Procesa la importación de empleados
 * @private
 */
async function processEmpleadosImport(empleadosDataImport) {
    const totalCount = empleadosDataImport.length;
    let successCount = 0;
    let errorCount = 0;
    const errors = [];

    const progressBar = document.getElementById('import-progress-bar');
    const statusText = document.getElementById('import-status');

    for (let i = 0; i < totalCount; i++) {
        const empleado = empleadosDataImport[i];

        try {
            const progress = Math.round(((i + 1) / totalCount) * 100);
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            statusText.textContent = `Procesando ${i + 1} de ${totalCount}...`;

            // Validar campos requeridos
            if (!empleado.nombre || !empleado.paterno || !empleado.rut) {
                throw new Error('Faltan campos requeridos: nombre, paterno y rut');
            }

            const accesoPermanenteValue = String(empleado.acceso_permanente).trim().toLowerCase();
            const accesoPermanente = accesoPermanenteValue === '1' || accesoPermanenteValue === 'true' || accesoPermanenteValue === 'si';

            const empleadoData = {
                empresa_id: selectedEmpresaId,
                nombre: empleado.nombre.toUpperCase(),
                paterno: empleado.paterno.toUpperCase(),
                materno: empleado.materno ? empleado.materno.toUpperCase() : null,
                rut: empleado.rut.toUpperCase(),
                fecha_inicio: empleado.fecha_inicio || new Date().toISOString().split('T')[0],
                acceso_permanente: accesoPermanente,
                fecha_expiracion: accesoPermanente ? null : (empleado.fecha_expiracion || null)
            };

            await empresasApi.createEmpleado(empleadoData);
            successCount++;

        } catch (error) {
            console.error(`Error al procesar empleado ${i + 1}:`, error);
            errorCount++;
            errors.push(`Fila ${i + 1} - ${empleado.nombre || 'Sin nombre'}: ${error.message}`);
        }

        await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Mostrar resultados finales
    document.getElementById('import-results').classList.remove('d-none');
    document.getElementById('import-total-count').textContent = totalCount;
    document.getElementById('import-success-count').textContent = successCount;
    document.getElementById('import-error-count').textContent = errorCount;

    if (errors.length > 0) {
        document.getElementById('import-errors-container').classList.remove('d-none');
        document.getElementById('import-errors-list').innerHTML = errors.map(error =>
            `<div class="text-danger small mb-1">${error}</div>`
        ).join('');
    } else {
        document.getElementById('import-errors-container').classList.add('d-none');
    }

    // Actualizar tabla de empleados si hubo éxito
    if (successCount > 0) {
        try {
            await loadAndRenderEmpleados(selectedEmpresaId);
        } catch (error) {
            console.error("Error al actualizar la tabla de empleados:", error);
        }
    }

    statusText.textContent = `Importación finalizada. ${successCount} empleados importados con éxito.`;

    if (errorCount === 0) {
        showToast(`Importación completada. ${successCount} empleados importados.`, 'success');
    } else {
        showToast(`Importación completada con ${errorCount} errores. ${successCount} empleados importados.`, 'warning');
    }
}
