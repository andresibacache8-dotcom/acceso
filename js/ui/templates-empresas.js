function getEmpresasTemplate() {
    return `
        <h1 class="h2 mb-4">Gestionar Empresas</h1>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Empresas</h5>
                        <button id="add-empresa-btn" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i></button>
                    </div>
                    <div class="card-body p-2">
                        <input type="text" id="search-empresa" class="form-control mb-2" placeholder="Buscar empresa...">
                        <div id="empresas-list-container" style="max-height: 60vh; overflow-y: auto;">
                            <ul class="list-group" id="empresas-list"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0" id="empleados-header">Seleccione una empresa</h5>
                            <small id="poc-info-header" class="text-muted d-block mt-1"></small>
                        </div>
                        <div class="d-flex gap-2">
                            <button id="add-empleado-btn" class="btn btn-primary btn-sm" style="display: none;"><i class="bi bi-person-plus"></i> Agregar</button>
                            <button id="import-empleados-btn" class="btn btn-success btn-sm" style="display: none;"><i class="bi bi-cloud-upload"></i> Importar</button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <input type="text" id="search-empleado-input" class="form-control mb-3" placeholder="Buscar empleado por nombre o RUT...">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre Completo</th>
                                        <th>RUT</th>
                                        <th>Estado</th>
                                        <th>Expira</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="empleados-table-body">
                                    <tr><td colspan="5" class="text-center text-muted p-4">No hay empresa seleccionada.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getEmpresaModalTemplate() {
    return `
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="empresa-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="empresa-modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="empresa_id" name="id">
                    <div class="mb-3">
                        <label for="empresa_nombre" class="form-label">Nombre de la Empresa</label>
                        <input type="text" id="empresa_nombre" name="nombre" class="form-control" required>
                    </div>
                    <hr>
                    <h6 class="text-muted">Punto de Contacto (POC)</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="poc_rut" class="form-label">RUT o Nombre del POC</label>
                            <input type="text" id="poc_rut" name="poc_rut" class="form-control" placeholder="Ingrese RUT o nombre para buscar" autocomplete="off">
                            <input type="hidden" id="poc_nombre" name="poc_nombre">
                            <small id="poc-rut-feedback" class="form-text"></small>

                            <!-- Contenedor para resultados de búsqueda de POC -->
                            <div id="poc-search-results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none;">
                                <!-- Los resultados se insertarán aquí dinámicamente -->
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="unidad_poc" class="form-label">Unidad del POC</label>
                            <input type="text" id="unidad_poc" name="unidad_poc" class="form-control" placeholder="Unidad del POC" readonly>
                        </div>
                        <div class="col-md-12">
                            <label for="poc_anexo" class="form-label">Anexo del POC</label>
                            <input type="text" id="poc_anexo" name="poc_anexo" class="form-control" placeholder="Ej: 123, 4567">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    `;
}

function getEmpresaEmpleadoModalTemplate() {
    return `
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="empleado-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="empleado-modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="empleado_id" name="id">
                    <input type="hidden" id="empleado_empresa_id" name="empresa_id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="empleado_nombre" class="form-label">Nombres</label>
                            <input type="text" id="empleado_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="empleado_paterno" class="form-label">Apellido Paterno</label>
                            <input type="text" id="empleado_paterno" name="paterno" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="empleado_materno" class="form-label">Apellido Materno</label>
                            <input type="text" id="empleado_materno" name="materno" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="empleado_rut" class="form-label">RUT</label>
                            <input type="text" id="empleado_rut" name="rut" class="form-control" required>
                        </div>
                    </div>
                    <hr class="my-3">
                    <h6 class="text-muted">Control de Acceso</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="empleado_fecha_inicio" class="form-label">Fecha de Inicio de Acceso <span class="text-danger">*</span></label>
                            <input type="date" id="empleado_fecha_inicio" name="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="empleado_fecha_expiracion" class="form-label">Fecha de Expiración <span class="text-danger" id="fecha_expiracion_required">*</span></label>
                            <input type="date" id="empleado_fecha_expiracion" name="fecha_expiracion" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="empleado_acceso_permanente" name="acceso_permanente">
                                <label class="form-check-label" for="empleado_acceso_permanente">Acceso Permanente (sin fecha de expiración)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    `;
}

function getImportEmpleadosModalTemplate() {
    return `
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Empleados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info mb-4" role="alert">
                    <strong><i class="bi bi-info-circle me-2"></i>Instrucciones:</strong>
                    <ol class="mb-0 mt-2">
                        <li><strong>Descargue una plantilla</strong> (Excel o CSV)</li>
                        <li><strong>Complete los datos:</strong>
                            <ul class="mt-2 mb-2">
                                <li><strong>Nombre, Paterno, RUT:</strong> Datos básicos del empleado (requeridos)</li>
                                <li><strong>Materno:</strong> Opcional</li>
                                <li><strong>Fecha Inicio:</strong> Fecha en formato YYYY-MM-DD (ej: 2025-01-15) - requerido</li>
                                <li><strong>Acceso Permanente:</strong> Escriba <strong>"1"</strong> para SÍ o <strong>"0"</strong> para NO
                                    <ul class="mt-1">
                                        <li><strong>1 = Acceso sin fecha de expiración</strong></li>
                                        <li><strong>0 = Acceso con fecha de expiración (campo siguiente es obligatorio)</strong></li>
                                    </ul>
                                </li>
                                <li><strong>Fecha Expiración:</strong> Formato YYYY-MM-DD (ej: 2025-12-31) - solo si Acceso Permanente es "0"</li>
                            </ul>
                        </li>
                        <li><strong>Cargue el archivo</strong> completado en este formulario</li>
                    </ol>
                </div>

                <!-- Botones para descargar plantillas -->
                <div class="mb-4 p-3 bg-light rounded">
                    <p class="mb-2"><strong>Descargar Plantilla:</strong></p>
                    <a href="#" id="descargar-plantilla-excel" class="btn btn-sm btn-outline-success me-2">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                    <a href="#" id="descargar-plantilla-csv" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-file-earmark-text"></i> CSV
                    </a>
                </div>

                <!-- Input para seleccionar archivo -->
                <div class="mb-3">
                    <label for="empleados-excel-file" class="form-label">Seleccionar archivo:</label>
                    <input type="file" id="empleados-excel-file" class="form-control" accept=".xlsx,.xls,.csv">
                    <small class="text-muted">Formatos soportados: Excel (.xlsx, .xls), CSV (.csv)</small>
                </div>

                <!-- Barra de progreso (oculta inicialmente) -->
                <div id="import-progress-container" class="d-none">
                    <div class="mb-2">
                        <small id="import-status">Procesando archivo...</small>
                    </div>
                    <div class="progress" style="height: 24px;">
                        <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Resultados de importación (ocultos inicialmente) -->
                <div id="import-results" class="d-none mt-4">
                    <div class="alert alert-success" role="alert">
                        <strong>Importación completada:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Total: <strong id="import-total-count">0</strong></li>
                            <li>Exitosos: <strong id="import-success-count" class="text-success">0</strong></li>
                            <li>Errores: <strong id="import-error-count" class="text-danger">0</strong></li>
                        </ul>
                    </div>

                    <!-- Contenedor de errores -->
                    <div id="import-errors-container" class="d-none mt-3 p-3 bg-danger-subtle rounded">
                        <strong class="text-danger mb-2 d-block">Errores encontrados:</strong>
                        <div id="import-errors-list" style="max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="start-import-btn" class="btn btn-success">
                    <i class="bi bi-upload"></i> Importar
                </button>
            </div>
        </div>
    </div>
    `;
}

export { getEmpresasTemplate, getEmpresaModalTemplate, getEmpresaEmpleadoModalTemplate, getImportEmpleadosModalTemplate };
