function getMantenedorVisitasTemplate() {
    return `
        <div class="card shadow-sm" id="visitas-module-container">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestionar Visitas</h5>
                <button id="add-visita-btn" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Agregar Visita
                </button>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <input type="text" id="search-visita-tabla" placeholder="Buscar por Nombre o RUT..." class="form-control" style="max-width: 350px;">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>RUT</th>
                                <th>Tipo</th>
                                <th>Detalle</th>
                                <th>Estado</th>
                                <th>Expira</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="visita-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>`;
}

function getMantenedorComisionTemplate() {
    return `
        <div class="card shadow-sm">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestionar Personal en Comisión</h5>
                <button id="add-comision-btn" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Agregar Personal
                </button>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <input type="text" id="search-comision-tabla" placeholder="Buscar por Nombre, RUT o Unidad..." class="form-control" style="max-width: 350px;">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>RUT</th>
                                <th>Unidad Origen</th>
                                <th>Unidad POC</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="comision-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>`;
}

function getControlVisitasTemplate() {
    return `
        <h1 class="h2 mb-4">Control de Acceso de Visitas</h1>
        <div class="row g-4">
            <!-- Columna de Escaneo/Búsqueda -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Escanear o Buscar Visita</h5>
                        <form id="scan-visita-form">
                            <div class="input-group">
                                <input type="text" id="scan-visita-input" placeholder="Ingresar RUT, Nombre o escanear QR..." class="form-control" autofocus>
                                <button type="submit" class="btn btn-primary" title="Registrar Acceso"><i class="bi bi-qr-code-scan fs-5"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Feedback del escaneo -->
                <div id="visita-scan-feedback" class="mt-4"></div>
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
                                        <th>Nombre</th>
                                        <th>Empresa</th>
                                        <th>Tipo</th>
                                        <th>Acción</th>
                                        <th>Fecha y Hora</th>
                                    </tr>
                                </thead>
                                <tbody id="visita-log-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

export { getMantenedorVisitasTemplate, getMantenedorComisionTemplate, getControlVisitasTemplate };
