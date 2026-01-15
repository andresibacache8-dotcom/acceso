function getHorasExtraTemplate() {
    return `
        <h1 class="h2 mb-4">Registro de Salida Posterior</h1>
        <div class="row g-4">
            <!-- Columna de Formulario -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0">Nuevo Registro</h5>
                    </div>
                    <div class="card-body p-4">
                        <form id="horas-extra-form">
                            <div class="mb-3">
                                <label for="he-rut-input" class="form-label">Añadir Personal por RUT (sin dígito v.)</label>
                                <div class="input-group">
                                    <input type="text" id="he-rut-input" class="form-control" placeholder="Ej: 12345678">
                                    <button class="btn btn-success" type="button" id="he-add-person-btn">+</button>
                                </div>
                                <small id="he-rut-lookup-nombre" class="form-text text-muted mt-1 d-block"></small>
                            </div>
                            <h6 class="mt-4">Personal a Registrar:</h6>
                            <ul id="he-personal-list" class="list-group mb-3">
                                <!-- Las personas añadidas aparecerán aquí -->
                            </ul>
                            
                            <label class="form-label">Fecha y Hora de Término</label>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-7">
                                    <input type="date" id="he-fecha-fin" class="form-control" required>
                                </div>
                                <div class="col-sm-5">
                                    <input type="time" id="he-hora-fin" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="he-motivo" class="form-label">Motivo</label>
                                <select id="he-motivo" class="form-select" required>
                                    <option value="">Seleccione un motivo</option>
                                    <option value="TRABAJO EXTRAORDINARIO">TRABAJO EXTRAORDINARIO</option>
                                    <option value="OTRO">OTRO</option>
                                </select>
                            </div>
                            <div class="mb-3" id="he-motivo-otro-container" style="display: none;">
                                <label for="he-motivo-otro" class="form-label">Especifique el Motivo</label>
                                <textarea id="he-motivo-otro" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="he-autorizado-por" class="form-label">Autorizado Por (RUT sin dígito v.)</label>
                                <input type="text" id="he-autorizado-por" class="form-control" placeholder="Ej: 87654321" required>
                                <small id="he-nombre-autoriza" class="form-text text-muted mt-1 d-block"></small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Registrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Columna de Historial -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0">Historial de Registros</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 65vh;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Personal</th>
                                        <th>Fecha</th>
                                        <th>Motivo</th>
                                        <th>Horas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="horas-extra-log-table"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
}

function getReportesTemplate() {
    return `
        <div class="container-fluid">
            <h1 class="h2 mb-4">Generador de Reportes</h1>
            <div class="card shadow-sm">
                <div class="card-header bg-white p-3">
                    <h5 class="mb-0">Filtros del Reporte</h5>
                </div>
                <div class="card-body p-4">
                    <form id="report-form">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="report-type" class="form-label">Tipo de Reporte</label>
                                <select id="report-type" class="form-select">
                                    <option value="">Seleccione un reporte</option>
                                    <option value="acceso_personal">Historial de acceso por persona</option>
                                    <option value="horas_extra">Salida Posterior por período</option>
                                    <option value="acceso_general">Registro de Acceso General</option>
                                    <option value="acceso_vehiculos">Registro de Acceso de Vehículos</option>
                                    <option value="acceso_visitas">Registro de Acceso de Visitas</option>
                                    <option value="personal_comision">Personal en Comisión</option>
                                                    <option value="salida_no_autorizada">Salida después de las 17 horas sin autorización</option>
                                </select>
                            </div>
                            <!-- Filtros dinámicos se insertarán aquí -->
                            <div id="dynamic-filters" class="col-md-6 row g-3 align-items-end"></div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Generar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Resultados</h5>
                    <button id="export-pdf-btn" class="btn btn-danger" style="display: none;">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Exportar a PDF
                    </button>
                </div>
                <div class="card-body p-4">
                    <div id="report-results" class="table-responsive">
                        <p class="text-center text-muted">Seleccione un tipo de reporte y genere los datos para ver los resultados.</p>
                    </div>
                </div>
            </div>
        </div>`;
}

export { getHorasExtraTemplate, getReportesTemplate };
