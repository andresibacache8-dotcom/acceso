function getMantenedorPersonalTemplate() {
    return `
        <div class="card shadow-sm">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestionar Personal</h5>
                <div class="btn-group" role="group">
                    <button id="import-personal-btn" class="btn btn-success" title="Importar personal masivamente desde archivo">
                        <i class="bi bi-upload me-2"></i>Importar Masivo
                    </button>
                    <button id="add-personal-btn" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Personal
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Filtros -->
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-5">Búsqueda</label>
                        <input type="text" id="search-personal-tabla" placeholder="Nombre o RUT..." class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-5">Estado</label>
                        <select id="filter-estado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-5">Unidad</label>
                        <select id="filter-unidad" class="form-select form-select-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-5">Grado</label>
                        <select id="filter-grado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-5">Mostrar</label>
                        <select id="records-per-page" class="form-select form-select-sm">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="150">150</option>
                            <option value="200">200</option>
                            <option value="0">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-5">&nbsp;</label>
                        <button id="reset-filters-btn" class="btn btn-outline-secondary btn-sm w-100" title="Limpiar todos los filtros">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <!-- Info de registros -->
                <div class="mb-3">
                    <small class="text-muted">Mostrando <strong id="current-records">0</strong> de <strong id="total-records">0</strong> registros</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nombre Completo</th>
                                <th>RUT</th>
                                <th>Unidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="personal-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>`;
}

function getControlPersonalTemplate() {
    return `
        <h1 class="h2 mb-4">Control de Unidades</h1>
        <div class="row g-4">
            <!-- Columna de Escaneo/Búsqueda -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title m-0 d-flex align-items-center">
                            <i class="bi bi-qr-code-scan me-2"></i>
                            Registro de Acceso
                        </h5>
                    </div>
                    <div class="card-body p-4" id="personal-scan-section">
                        <form id="scan-personal-form">
                            <label for="scan-personal-input" class="form-label fw-medium">
                                <i class="bi bi-upc-scan text-primary me-1"></i> Escanee un código QR o ingrese RUT sin dígito verificador:
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-qr-code"></i>
                                </span>
                                <input type="text" id="scan-personal-input"
                                    placeholder="Escanee o ingrese RUT aquí..."
                                    class="form-control form-control-lg border-2"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    autocomplete="off"
                                    autofocus>
                                <button type="submit" class="btn btn-primary px-4" title="Registrar Acceso">
                                    <i class="bi bi-arrow-right-circle-fill me-1"></i> Registrar
                                </button>
                            </div>
                            <div class="alert alert-info py-2 mt-3 text-center">
                                <i class="bi bi-info-circle-fill me-1"></i>
                                Escanee el código QR o ingrese el RUT sin dígito verificador y presione "Enter"
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Feedback del escaneo -->
                <div id="personal-scan-feedback" class="mt-3"></div>
            </div>
            <!-- Columna de Registro de Actividad -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0">Registro de Actividad</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" id="search-personal-log" class="form-control"
                                placeholder="Buscar por nombre, RUT o acción...">
                        </div>
                        <div class="table-responsive" style="max-height: 60vh;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Acción</th>
                                        <th>Fecha y Hora</th>
                                    </tr>
                                </thead>
                                <tbody id="personal-log-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

export { getMantenedorPersonalTemplate, getControlPersonalTemplate };
