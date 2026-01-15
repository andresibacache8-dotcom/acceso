/**
 * reportes.js
 * Módulo para gestión de reportes del sistema
 */

import reportesApi from './api/reportes-api.js';

/**
 * Exporta datos a un archivo Excel
 * @param {string} fileName - Nombre del archivo sin extensión
 * @param {Array} headers - Array con los encabezados de las columnas
 * @param {Array} data - Array bidimensional con los datos a exportar
 * @param {string} [sheetName='Datos'] - Nombre de la hoja de Excel
 */
function exportToExcel(fileName, headers, data, sheetName = 'Datos') {
    // Verificar que la biblioteca XLSX está disponible
    if (typeof XLSX === 'undefined') {
        console.error('La biblioteca XLSX no está cargada');
        showToast('Error: No se puede exportar a Excel', 'error');
        return;
    }

    try {
        // Crear una hoja de cálculo
        const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);

        // Crear libro y agregar hoja
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, sheetName);

        // Descargar archivo
        XLSX.writeFile(wb, `${fileName}.xlsx`);

        return true;
    } catch (error) {
        console.error('Error al exportar a Excel:', error);
        showToast('Error al generar el archivo Excel', 'error');
        return false;
    }
}

/**
 * Formatea el nombre completo del personal con Grado, Nombres, Paterno, Materno
 */
function formatPersonalName(row) {
    const parts = [];
    if (row.Grado) parts.push(row.Grado);
    if (row.Nombres) parts.push(row.Nombres);
    if (row.Paterno) parts.push(row.Paterno);
    if (row.Materno) parts.push(row.Materno);
    return parts.join(' ').trim();
}

/**
 * Renderiza los resultados del reporte en una tabla
 */
function renderReportResults(data, reportType, contentElement) {
    const resultsContainer = contentElement.querySelector('#report-results');
    if (!resultsContainer) return;

    let headers = [];
    switch (reportType) {
        case 'acceso_personal':
            headers = ['Nombre', 'Acción', 'Punto de Acceso', 'Fecha y Hora'];
            break;
        case 'horas_extra':
            headers = ['Personal', 'Fecha', 'Motivo', 'Hora de Salida'];
            break;
        case 'acceso_general':
            headers = ['ID/Nombre', 'Tipo', 'Acción', 'Fecha y Hora'];
            break;
        case 'acceso_vehiculos':
            headers = ['Patente', 'Marca/Modelo', 'Asociado', 'Acción', 'Fecha y Hora'];
            break;
        case 'acceso_visitas':
            headers = ['Nombre', 'RUT', 'Tipo', 'Acción', 'Fecha y Hora'];
            break;
        case 'personal_comision':
            headers = ['Nombre', 'RUT', 'Unidad Origen', 'Inicio', 'Fin'];
            break;
        case 'salida_no_autorizada':
            headers = ['Nombre', 'RUT', 'Fecha y Hora'];
            break;
    }

    let table = '<table class="table table-striped table-hover"><thead><tr>';
    headers.forEach(header => {
        table += `<th>${header}</th>`;
    });
    table += '</tr></thead><tbody>';

    data.forEach(row => {
        table += '<tr>';
        if (reportType === 'acceso_personal') {
            table += `<td>${formatPersonalName(row)}</td>`;
            table += `<td><span class="badge ${row.action === 'entrada' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${row.action}</span></td>`;

            // Mostrar punto de acceso basado en el campo punto_acceso
            let puntoAcceso = 'N/A';
            if (row.punto_acceso) {
                if (row.punto_acceso.toLowerCase().includes('portico')) {
                    puntoAcceso = '<span class="badge bg-primary-subtle text-primary"><i class="bi bi-shield-check me-1"></i>Pórtico</span>';
                } else if (row.punto_acceso.toLowerCase().includes('control_unidades') || row.punto_acceso.toLowerCase().includes('unidad') || row.punto_acceso.toLowerCase().includes('personal')) {
                    puntoAcceso = '<span class="badge bg-info-subtle text-info"><i class="bi bi-building me-1"></i>Control de Unidades</span>';
                } else {
                    puntoAcceso = `<span class="badge bg-secondary-subtle text-secondary">${row.punto_acceso}</span>`;
                }
            }
            table += `<td>${puntoAcceso}</td>`;
            table += `<td>${new Date(row.log_time).toLocaleString('es-CL', { hour12: false })}</td>`;
        } else if (reportType === 'horas_extra') {
            // Mostrar nombre del personal
            const personalNombre = row.personal_nombre_completo || row.personal_nombre || `${row.Grado || ''} ${row.Nombres || ''} ${row.Paterno || ''} ${row.Materno || ''}`.trim() || 'N/A';
            table += `<td>${personalNombre}</td>`;

            // Fecha - usar fecha_hora_termino (es la única fecha disponible)
            let fecha = 'N/A';
            if (row.fecha_hora_termino) {
                const dateObj = new Date(row.fecha_hora_termino);
                if (!isNaN(dateObj.getTime())) {
                    fecha = dateObj.toLocaleDateString('es-CL');
                } else {
                    fecha = row.fecha_hora_termino;
                }
            }
            table += `<td>${fecha}</td>`;

            // Motivo
            table += `<td>${row.motivo || 'N/A'}</td>`;

            // Hora salida - manejar posibles formatos
            let horaTermino = 'N/A';
            if (row.fecha_hora_termino) {
                const dateObj = new Date(row.fecha_hora_termino);
                if (!isNaN(dateObj.getTime())) {
                    horaTermino = dateObj.toLocaleTimeString('es-CL', { hour12: false });
                } else {
                    horaTermino = row.fecha_hora_termino;
                }
            }
            table += `<td>${horaTermino}</td>`;
        } else if (reportType === 'acceso_general') {
            let name = row.name;
            table += `<td>${name}</td>`;
            table += `<td>${row.target_type}</td>`;
            table += `<td><span class="badge ${row.action === 'entrada' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${row.action}</span></td>`;
            table += `<td>${new Date(row.log_time).toLocaleString('es-CL', { hour12: false })}</td>`;
        } else if (reportType === 'acceso_vehiculos') {
            let asociado = row.personal_nombre_completo ? row.personal_nombre_completo.trim() : 'N/A';
            table += `<td>${row.patente}</td>`;
            table += `<td>${row.marca} ${row.modelo}</td>`;
            table += `<td>${asociado}</td>`;
            table += `<td><span class="badge ${row.action === 'entrada' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${row.action}</span></td>`;
            table += `<td>${new Date(row.log_time).toLocaleString('es-CL', { hour12: false })}</td>`;
        } else if (reportType === 'acceso_visitas') {
            table += `<td>${row.nombre}</td>`;
            table += `<td>${row.rut}</td>`;
            table += `<td>${row.tipo || 'N/A'}</td>`;
            table += `<td><span class="badge ${row.action === 'entrada' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${row.action}</span></td>`;
            table += `<td>${new Date(row.log_time).toLocaleString('es-CL', { hour12: false })}</td>`;
        } else if (reportType === 'personal_comision') {
            const action = row.action ? `<span class="badge ${row.action === 'entrada' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${row.action}</span>` : 'Sin registro';
            const logTime = row.log_time ? new Date(row.log_time).toLocaleString('es-CL', { hour12: false }) : 'Sin registro';
            table += `<td>${row.nombre_completo}</td>`;
            table += `<td>${row.rut}</td>`;
            table += `<td>${row.unidad_origen}</td>`;
            table += `<td>${new Date(row.fecha_fin).toLocaleDateString('es-CL')}</td>`;
            table += `<td>${action}</td>`;
        } else if (reportType === 'salida_no_autorizada') {
            table += `<td>${formatPersonalName(row)}</td>`;
            table += `<td>${row.NrRut}</td>`;
            table += `<td>${new Date(row.log_time).toLocaleString('es-CL', { hour12: false })}</td>`;
        }
        table += '</tr>';
    });

    table += '</tbody></table>';
    resultsContainer.innerHTML = table;
}

/**
 * Inicializa el módulo de reportes
 * @param {HTMLElement} contentElement - Elemento contenedor principal
 */
export function initReportesModule(contentElement) {
    // Usar el contenedor proporcionado en lugar de document
    const reportForm = contentElement.querySelector('#report-form');
    const reportTypeSelect = contentElement.querySelector('#report-type');
    const dynamicFiltersContainer = contentElement.querySelector('#dynamic-filters');
    const exportPdfBtn = contentElement.querySelector('#export-pdf-btn');

    if (!reportForm || !reportTypeSelect) {
        console.error('Elementos del módulo de reportes no encontrados');
        return;
    }

    reportTypeSelect.addEventListener('change', () => {
        updateReportFilters(reportTypeSelect.value);
    });

    // Helper function to gather and clean active filters
    function getActiveFilters() {
        const reportType = reportTypeSelect.value;
        const filters = { report_type: reportType };

        const fields = [
            { id: 'report-personal-rut', name: 'rut' },
            { id: 'report-comision-rut', name: 'rut' },
            { id: 'report-fecha-inicio', name: 'fecha_inicio' },
            { id: 'report-fecha-fin', name: 'fecha_fin' },
            { id: 'report-access-type', name: 'access_type' },
            { id: 'report-patente', name: 'patente' },
            { id: 'report-visita-rut', name: 'rut' }
        ];

        fields.forEach(field => {
            const element = contentElement.querySelector(`#${field.id}`);
            if (element) {
                filters[field.name] = element.value;
            }
        });

        // Clean up any keys that have undefined, null, or the string 'undefined' as values
        const cleanFilters = {};
        for (const key in filters) {
            if (filters[key] !== undefined && filters[key] !== null && filters[key] !== 'undefined') {
                cleanFilters[key] = filters[key];
            }
        }

        return cleanFilters;
    }

    reportForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const reportType = reportTypeSelect.value;
        if (!reportType) {
            showToast('Por favor, seleccione un tipo de reporte.', 'warning');
            return;
        }

        const filters = getActiveFilters();

        try {
            const data = await reportesApi.getReport(filters);
            renderReportResults(data, reportType, contentElement);
            exportPdfBtn.style.display = 'block';
        } catch (error) {
            showToast(error.message, 'error');
            const resultsContainer = contentElement.querySelector('#report-results');
            if (resultsContainer) {
                resultsContainer.innerHTML = `<p class="text-center text-danger">Error al generar el reporte: ${error.message}</p>`;
            }
            exportPdfBtn.style.display = 'none';
        }
    });

    exportPdfBtn.addEventListener('click', () => {
        const filters = getActiveFilters();
        filters.export = 'pdf';

        const params = new URLSearchParams(filters);
        window.open(`./api/reportes.php?${params.toString()}`, '_blank');
    });

    function updateReportFilters(reportType) {
        dynamicFiltersContainer.innerHTML = '';
        switch (reportType) {
            case 'acceso_personal':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-6">
                        <label for="report-personal-rut" class="form-label">RUT del Personal</label>
                        <input type="text" id="report-personal-rut" class="form-control" placeholder="Ingrese RUT">
                    </div>
                    <div class="col-md-3">
                        <label for="report-fecha-inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" id="report-fecha-inicio" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                `;
                break;
            case 'horas_extra':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-6">
                        <label for="report-fecha-inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" id="report-fecha-inicio" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                `;
                break;
            case 'acceso_general':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-4">
                        <label for="report-fecha-inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" id="report-fecha-inicio" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="report-access-type" class="form-label">Tipo de Acceso</label>
                        <select id="report-access-type" class="form-select">
                            <option value="">Seleccionar...</option>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                        </select>
                    </div>
                `;
                break;
            case 'acceso_vehiculos':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-6">
                        <label for="report-fecha-inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" id="report-fecha-inicio" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                `;
                break;
            case 'acceso_visitas':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-6">
                        <label for="report-fecha-inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" id="report-fecha-inicio" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                `;
                break;
            case 'personal_comision':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-6">
                        <label for="report-comision-rut" class="form-label">RUT del Personal</label>
                        <input type="text" id="report-comision-rut" class="form-control" placeholder="Ingrese RUT">
                    </div>
                    <div class="col-md-6">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                `;
                break;
            case 'salida_no_autorizada':
                dynamicFiltersContainer.innerHTML = `
                    <div class="col-md-6">
                        <label for="report-fecha-inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" id="report-fecha-inicio" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="report-fecha-fin" class="form-label">Fecha Fin</label>
                        <input type="date" id="report-fecha-fin" class="form-control">
                    </div>
                `;
                break;
        }
    }
}
