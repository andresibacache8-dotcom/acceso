function getImportVehiculosModalTemplate() {
    return `
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Carga Masiva de Vehículos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p>Seleccione un archivo Excel con los datos de los vehículos a importar.</p>
                    <p class="text-muted small">El archivo debe tener las siguientes columnas: patente, marca, modelo, tipo, tipo_vehiculo, personalNrRut, fecha_inicio, acceso_permanente, fecha_expiracion</p>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Formato del archivo:</strong>
                        <ul class="mb-0 ps-4 mt-2">
                            <li>Formato Excel (.xlsx o .xls) o CSV</li>
                            <li>La primera fila debe contener los nombres de las columnas exactos</li>
                            <li>Patentes en formato chileno (AA1234, ABCD12, A1234)</li>
                            <li>Tipos válidos: PERSONAL, FUNCIONARIO, RESIDENTE, FISCAL, EMPLEADO, EMPRESA, VISITA</li>
                            <li>Tipos de vehículos: AUTO, CAMIONETA, CAMION, MOTO, BUS, FURGON, OTRO</li>
                            <li>personalNrRut: RUT sin puntos ni guión (ej: 12345678)</li>
                            <li>fecha_inicio: Fecha desde cuando el vehículo puede ingresar (YYYY-MM-DD)</li>
                            <li>Para acceso_permanente use 1 (sí) o 0 (no)</li>
                            <li>fecha_expiracion: Fecha hasta cuando el vehículo tiene acceso (opcional si acceso_permanente=1)</li>
                            <li>Todas las fechas en formato YYYY-MM-DD</li>
                        </ul>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2">
                        <a href="templates/plantilla_vehiculos.xlsx" download class="btn btn-sm btn-outline-primary mb-3 me-2">
                            <i class="bi bi-file-earmark-excel me-1"></i>Descargar plantilla Excel
                        </a>
                        <a href="templates/plantilla_vehiculos.csv" download class="btn btn-sm btn-outline-secondary mb-3">
                            <i class="bi bi-file-earmark-text me-1"></i>Descargar plantilla CSV
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <input type="file" class="form-control" id="vehiculos-excel-file" accept=".xlsx,.xls,.csv">
                    </div>
                </div>
                
                <div id="import-progress-container" class="d-none">
                    <div class="progress mb-3">
                        <div id="import-progress-bar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p id="import-status" class="text-muted small"></p>
                </div>
                
                <div id="import-results" class="d-none">
                    <div class="alert alert-success mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Proceso Finalizado</strong>
                        </div>
                        <div class="d-flex justify-content-between border-top border-success pt-2 mt-2">
                            <span>Vehículos procesados:</span>
                            <strong id="import-total-count">0</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Vehículos importados:</span>
                            <strong id="import-success-count" class="text-success">0</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Vehículos con error:</span>
                            <strong id="import-error-count" class="text-danger">0</strong>
                        </div>
                    </div>
                    <div id="import-errors-container" class="d-none">
                        <h6 class="mb-2">Detalle de errores:</h6>
                        <div id="import-errors-list" class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="start-import-btn">
                    <i class="bi bi-upload me-1"></i>Iniciar Importación
                </button>
            </div>
        </div>
    </div>`;
}

function getMantenedorVehiculosTemplate() {
    return `
        <div class="card shadow-sm">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestionar Vehículos</h5>
                <div>
                    <button id="import-vehiculos-btn" class="btn btn-outline-success me-2">
                        <i class="bi bi-file-earmark-excel me-1"></i>Carga Masiva
                    </button>
                    <button id="add-vehiculo-btn" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Vehículo
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Buscador básico con botón para mostrar/ocultar filtros avanzados -->
                <div class="mb-3 d-flex">
                    <input type="text" id="search-vehiculo-tabla" placeholder="Búsqueda rápida..." class="form-control me-2" style="max-width: 350px;">
                    <button class="btn btn-outline-primary" id="toggle-advanced-search">
                        <i class="bi bi-funnel"></i> Filtros Avanzados
                    </button>
                </div>
                
                <!-- Filtros Avanzados (inicialmente ocultos) -->
                <div id="advanced-search-filters" class="mb-4 card p-3" style="display: none;">
                    <h6 class="mb-3">Filtros Avanzados</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filter-patente" class="form-label">Patente</label>
                            <input type="text" class="form-control form-control-sm advanced-filter" id="filter-patente" placeholder="Ej: AA1234">
                        </div>
                        <div class="col-md-3">
                            <label for="filter-marca" class="form-label">Marca</label>
                            <input type="text" class="form-control form-control-sm advanced-filter" id="filter-marca" placeholder="Ej: Toyota">
                        </div>
                        <div class="col-md-3">
                            <label for="filter-modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control form-control-sm advanced-filter" id="filter-modelo" placeholder="Ej: Corolla">
                        </div>
                        <div class="col-md-3">
                            <label for="filter-tipo" class="form-label">Tipo</label>
                            <select class="form-select form-select-sm advanced-filter" id="filter-tipo">
                                <option value="">Todos</option>
                                <option value="FISCAL">Fiscal</option>
                                <option value="FUNCIONARIO">Funcionario</option>
                                <option value="RESIDENTE">Residente</option>
                                <option value="VISITA">Visita</option>
                                <option value="PROVEEDOR">Empresa</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter-status" class="form-label">Estado</label>
                            <select class="form-select form-select-sm advanced-filter" id="filter-status">
                                <option value="">Todos</option>
                                <option value="autorizado">Autorizado</option>
                                <option value="no autorizado">No Autorizado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter-permanente" class="form-label">Acceso</label>
                            <select class="form-select form-select-sm advanced-filter" id="filter-permanente">
                                <option value="">Todos</option>
                                <option value="1">Permanente</option>
                                <option value="0">Temporal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="filter-asociado" class="form-label">Asociado</label>
                            <input type="text" class="form-control form-control-sm advanced-filter" id="filter-asociado" placeholder="Nombre o RUT del asociado">
                        </div>
                        <div class="col-md-12 text-end">
                            <button class="btn btn-sm btn-secondary" id="reset-advanced-filters">
                                <i class="bi bi-x-circle"></i> Limpiar Filtros
                            </button>
                            <button class="btn btn-sm btn-primary" id="apply-advanced-filters">
                                <i class="bi bi-search"></i> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Patente</th>
                                <th class="text-center">Marca</th>
                                <th class="text-center">Asociado a</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Inicia</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Expira</th>
                                <th class="text-center">Permanente</th>
                                <th class="text-center">QR</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="vehiculo-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>`;
}

function getControlVehiculosTemplate() {
    return `
        <h1 class="h2 mb-4">Control de Acceso de Vehículos</h1>
        <div class="row g-4">
            <!-- Columna de Escaneo/Búsqueda -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Escanear o Ingresar Patente</h5>
                        <form id="scan-vehiculo-form">
                            <div class="input-group">
                                <input type="text" id="scan-vehiculo-input" placeholder="Ingresar Patente o escanear QR..." class="form-control text-uppercase" autofocus>
                                <button type="submit" class="btn btn-primary" title="Registrar Acceso"><i class="bi bi-qr-code-scan fs-5"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Feedback del escaneo -->
                <div id="vehiculo-scan-feedback" class="mt-4"></div>
            </div>
            <!-- Columna de Registro de Actividad -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0">Registro de Actividad</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 65vh;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center">Patente</th>
                                        <th class="text-center">Asociado</th>
                                        <th class="text-center">Acción</th>
                                        <th class="text-center">Fecha y Hora</th>
                                    </tr>
                                </thead>
                                <tbody id="vehiculo-log-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

export { getImportVehiculosModalTemplate, getMantenedorVehiculosTemplate, getControlVehiculosTemplate };
