function getPersonalModalTemplate() {
    return `
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="personal-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="personal-modal-title">Agregar/Editar Personal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <!-- Columna de Pestañas Verticales -->
                        <div class="col-md-3">
                            <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active" id="v-pills-personal-tab" data-bs-toggle="pill" data-bs-target="#v-pills-personal" type="button" role="tab" aria-controls="v-pills-personal" aria-selected="true">Info. Personal</button>
                                <button class="nav-link" id="v-pills-laboral-tab" data-bs-toggle="pill" data-bs-target="#v-pills-laboral" type="button" role="tab" aria-controls="v-pills-laboral" aria-selected="false">Info. Laboral</button>
                                <button class="nav-link" id="v-pills-contacto-tab" data-bs-toggle="pill" data-bs-target="#v-pills-contacto" type="button" role="tab" aria-controls="v-pills-contacto" aria-selected="false">Contacto</button>
                                <button class="nav-link" id="v-pills-adicional-tab" data-bs-toggle="pill" data-bs-target="#v-pills-adicional" type="button" role="tab" aria-controls="v-pills-adicional" aria-selected="false">Info. Adicional</button>
                                <button class="nav-link" id="v-pills-emergencia-tab" data-bs-toggle="pill" data-bs-target="#v-pills-emergencia" type="button" role="tab" aria-controls="v-pills-emergencia" aria-selected="false">Emergencia</button>
                                <button class="nav-link" id="v-pills-acceso-tab" data-bs-toggle="pill" data-bs-target="#v-pills-acceso" type="button" role="tab" aria-controls="v-pills-acceso" aria-selected="false">Control de Acceso</button>
                            </div>
                        </div>
                        <!-- Columna de Contenido de Pestañas -->
                        <div class="col-md-9">
                            <div class="tab-content" id="v-pills-tabContent">
                                <!-- Pestaña 1: Info. Personal -->
                                <div class="tab-pane fade show active" id="v-pills-personal" role="tabpanel" aria-labelledby="v-pills-personal-tab">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label for="Grado" class="form-label">Grado</label><input type="text" id="Grado" name="Grado" class="form-control"></div>
                                        <div class="col-md-4"><label for="Nombres" class="form-label">Nombres</label><input type="text" id="Nombres" name="Nombres" class="form-control" required></div>
                                        <div class="col-md-4"><label for="Paterno" class="form-label">Apellido Paterno</label><input type="text" id="Paterno" name="Paterno" class="form-control" required></div>
                                        <div class="col-md-4"><label for="Materno" class="form-label">Apellido Materno</label><input type="text" id="Materno" name="Materno" class="form-control"></div>
                                        <div class="col-md-4"><label for="NrRut" class="form-label">RUT</label><input type="text" id="NrRut" name="NrRut" class="form-control" required></div>
                                        <div class="col-md-4"><label for="fechaNacimiento" class="form-label">Fecha Nacimiento</label><input type="date" id="fechaNacimiento" name="fechaNacimiento" class="form-control"></div>
                                        <div class="col-md-4"><label for="sexo" class="form-label">Sexo</label><select id="sexo" name="sexo" class="form-select"><option value="">Seleccionar...</option><option value="M">Masculino</option><option value="F">Femenino</option></select></div>
                                        <div class="col-md-4"><label for="estadoCivil" class="form-label">Estado Civil</label><input type="text" id="estadoCivil" name="estadoCivil" class="form-control"></div>
                                    </div>
                                </div>
                                <!-- Pestaña 2: Info. Laboral -->
                                <div class="tab-pane fade" id="v-pills-laboral" role="tabpanel" aria-labelledby="v-pills-laboral-tab">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label for="nrEmpleado" class="form-label">N° Empleado</label><input type="text" id="nrEmpleado" name="nrEmpleado" class="form-control"></div>
                                        <div class="col-md-4"><label for="puesto" class="form-label">Puesto</label><input type="text" id="puesto" name="puesto" class="form-control"></div>
                                        <div class="col-md-4"><label for="especialidadPrimaria" class="form-label">Especialidad Primaria</label><input type="text" id="especialidadPrimaria" name="especialidadPrimaria" class="form-control"></div>
                                        <div class="col-md-4"><label for="fechaIngreso" class="form-label">Fecha Ingreso</label><input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control"></div>
                                        <div class="col-md-4"><label for="fechaPresentacion" class="form-label">Fecha Presentación</label><input type="date" id="fechaPresentacion" name="fechaPresentacion" class="form-control"></div>
                                        <div class="col-md-4"><label for="Unidad" class="form-label">Unidad</label><input type="text" id="Unidad" name="Unidad" class="form-control"></div>
                                        <div class="col-md-4"><label for="unidadEspecifica" class="form-label">Unidad Específica</label><input type="text" id="unidadEspecifica" name="unidadEspecifica" class="form-control"></div>
                                        <div class="col-md-4"><label for="categoria" class="form-label">Categoría</label><input type="text" id="categoria" name="categoria" class="form-control"></div>
                                        <div class="col-md-4"><label for="escalafon" class="form-label">Escalafón</label><input type="text" id="escalafon" name="escalafon" class="form-control"></div>
                                        <div class="col-md-4"><label for="trabajoExterno" class="form-label">Trabajo Externo</label><input type="text" id="trabajoExterno" name="trabajoExterno" class="form-control"></div>
                                        <div class="col-md-4 d-flex align-items-center">
                                            <div class="form-check form-switch pt-3">
                                                <input class="form-check-input" type="checkbox" role="switch" id="es_residente" name="es_residente" value="1">
                                                <label class="form-check-label" for="es_residente">Personal Residente</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pestaña 3: Contacto -->
                                <div class="tab-pane fade" id="v-pills-contacto" role="tabpanel" aria-labelledby="v-pills-contacto-tab">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label for="calle" class="form-label">Calle</label><input type="text" id="calle" name="calle" class="form-control"></div>
                                        <div class="col-md-6"><label for="numeroDepto" class="form-label">Número / Depto</label><input type="text" id="numeroDepto" name="numeroDepto" class="form-control"></div>
                                        <div class="col-md-6"><label for="poblacionVilla" class="form-label">Población / Villa</label><input type="text" id="poblacionVilla" name="poblacionVilla" class="form-control"></div>
                                        <div class="col-md-6"><label for="telefonoFijo" class="form-label">Teléfono Fijo</label><input type="text" id="telefonoFijo" name="telefonoFijo" class="form-control"></div>
                                        <div class="col-md-6"><label for="movil1" class="form-label">Móvil 1</label><input type="text" id="movil1" name="movil1" class="form-control"></div>
                                        <div class="col-md-6"><label for="movil2" class="form-label">Móvil 2</label><input type="text" id="movil2" name="movil2" class="form-control"></div>
                                        <div class="col-md-6"><label for="email1" class="form-label">Email 1</label><input type="email" id="email1" name="email1" class="form-control"></div>
                                        <div class="col-md-6"><label for="email2" class="form-label">Email 2</label><input type="email" id="email2" name="email2" class="form-control"></div>
                                        <div class="col-md-6"><label for="anexo" class="form-label">Anexo</label><input type="text" id="anexo" name="anexo" class="form-control"></div>
                                        <div class="col-md-6"><label for="foto" class="form-label">URL de Foto</label><input type="text" id="foto" name="foto" class="form-control"></div>
                                    </div>
                                </div>
                                <!-- Pestaña 4: Info. Adicional -->
                                <div class="tab-pane fade" id="v-pills-adicional" role="tabpanel" aria-labelledby="v-pills-adicional-tab">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label for="prevision" class="form-label">Previsión</label><input type="text" id="prevision" name="prevision" class="form-control"></div>
                                        <div class="col-md-6"><label for="sistemaSalud" class="form-label">Sistema de Salud</label><input type="text" id="sistemaSalud" name="sistemaSalud" class="form-control"></div>
                                        <div class="col-md-6"><label for="regimenMatrimonial" class="form-label">Régimen Matrimonial</label><input type="text" id="regimenMatrimonial" name="regimenMatrimonial" class="form-control"></div>
                                        <div class="col-md-6"><label for="religion" class="form-label">Religión</label><input type="text" id="religion" name="religion" class="form-control"></div>
                                        <div class="col-md-6"><label for="tipoVivienda" class="form-label">Tipo Vivienda</label><input type="text" id="tipoVivienda" name="tipoVivienda" class="form-control"></div>
                                        <div class="col-md-6"><label for="nombreConyuge" class="form-label">Nombre Cónyuge</label><input type="text" id="nombreConyuge" name="nombreConyuge" class="form-control"></div>
                                        <div class="col-md-6"><label for="profesionConyuge" class="form-label">Profesión Cónyuge</label><input type="text" id="profesionConyuge" name="profesionConyuge" class="form-control"></div>
                                    </div>
                                </div>
                                <!-- Pestaña 5: Emergencia -->
                                <div class="tab-pane fade" id="v-pills-emergencia" role="tabpanel" aria-labelledby="v-pills-emergencia-tab">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label for="nombreContactoEmergencia" class="form-label">Nombre Contacto de Emergencia</label><input type="text" id="nombreContactoEmergencia" name="nombreContactoEmergencia" class="form-control"></div>
                                        <div class="col-md-6"><label for="direccionEmergencia" class="form-label">Dirección de Emergencia</label><input type="text" id="direccionEmergencia" name="direccionEmergencia" class="form-control"></div>
                                        <div class="col-md-6"><label for="movilEmergencia" class="form-label">Móvil de Emergencia</label><input type="text" id="movilEmergencia" name="movilEmergencia" class="form-control"></div>
                                    </div>
                                </div>
                                <!-- Pestaña 6: Control de Acceso -->
                                <div class="tab-pane fade" id="v-pills-acceso" role="tabpanel" aria-labelledby="v-pills-acceso-tab">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="Estado" class="form-label">Estado</label>
                                            <select id="Estado" name="Estado" class="form-select">
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fechaExpiracion" class="form-label">Fecha de Expiración</label>
                                            <input type="date" id="fechaExpiracion" name="fechaExpiracion" class="form-control">
                                        </div>
                                        <div class="col-md-4 align-self-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="accesoPermanente" name="accesoPermanente">
                                                <label class="form-check-label" for="accesoPermanente">Acceso Permanente</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>`;
}

function getVehiculoHistorialModalTemplate() {
    return `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Historial de Vehículo <span id="historial-patente" class="badge bg-secondary ms-2"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="card border-0">
                        <div class="card-header bg-light py-3">
                            <div class="row align-items-center">
                                <div class="col-lg-6 mb-2 mb-lg-0">
                                    <h6 class="mb-0"><i class="bi bi-person-fill me-2"></i>Propietario actual: <span id="historial-propietario-actual" class="text-primary fw-bold"></span></h6>
                                </div>
                                <div class="col-lg-6 text-lg-end">
                                    <div class="d-flex justify-content-between justify-content-lg-end">
                                        <span class="text-muted"><i class="bi bi-clock-history me-1"></i>Total cambios: <span id="historial-total-cambios" class="fw-bold"></span></span>
                                        <div class="ms-3 dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="historial-filtro-btn" data-bs-toggle="dropdown">
                                                <i class="bi bi-filter me-1"></i>Filtrar
                                            </button>
                                            <div class="dropdown-menu" id="filtro-tipo-cambio">
                                                <a class="dropdown-item active" href="#" data-filter="todos">Todos los cambios</a>
                                                <a class="dropdown-item" href="#" data-filter="creacion">Creación</a>
                                                <a class="dropdown-item" href="#" data-filter="actualizacion">Actualización</a>
                                                <a class="dropdown-item" href="#" data-filter="cambio_propietario">Cambio de propietario</a>
                                                <a class="dropdown-item" href="#" data-filter="eliminacion">Eliminación</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="historial-buscar" placeholder="Buscar en el historial...">
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover m-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">Fecha</th>
                                            <th class="text-center">Tipo de Cambio</th>
                                            <th class="text-center">Propietario Anterior</th>
                                            <th class="text-center">Propietario Nuevo</th>
                                            <th class="text-center">Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historial-table-body">
                                        <!-- Los registros del historial se cargarán aquí -->
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-start me-auto small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="historial-info-text">Mostrando todos los registros</span>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="exportar-historial-btn">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                    </button>
                </div>
            </div>
        </div>
    `;
}

function getVehiculoModalTemplate() {
    return `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="vehiculo-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="vehiculo-modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" id="id" name="id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="patente" class="form-label">Patente</label>
                                <input type="text" id="patente" name="patente" class="form-control text-uppercase" 
                                       placeholder="Ej: AA1234 o ABCD12" pattern="^[A-Za-z]{2}[0-9]{4}$|^[A-Za-z]{4}[0-9]{2}$" 
                                       title="Formato de patente chilena" maxlength="6" required>
                                <div class="form-text text-muted" id="patente-format-help">
                                    <i class="bi bi-info-circle-fill me-1"></i> 
                                    Formatos válidos: AA1234 (antiguo) o ABCD12 (nuevo)
                                </div>
                                <div class="invalid-feedback" id="patente-validation-message">
                                    Ingrese un formato válido de patente chilena
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo de Acceso</label>
                                <select id="tipo" name="tipo" class="form-select" onchange="window.handleTipoAccesoChange(this.value)">
                                    <option value="FISCAL">Fiscal</option>
                                    <option value="FUNCIONARIO">Funcionario</option>
                                    <option value="RESIDENTE">Residente</option>
                                    <option value="VISITA">Visita</option>
                                    <option value="EMPRESA">Empresa</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tipo_vehiculo" class="form-label">Tipo de Vehículo</label>
                                <select id="tipo_vehiculo" name="tipo_vehiculo" class="form-select">
                                    <option value="AUTO">Auto</option>
                                    <option value="CAMIONETA">Camioneta</option>
                                    <option value="CAMION">Camión</option>
                                    <option value="MOTO">Moto</option>
                                    <option value="BUS">Bus</option>
                                    <option value="FURGON">Furgón</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text" id="marca" name="marca" class="form-control text-uppercase">
                            </div>
                            <div class="col-md-6">
                                <label for="modelo" class="form-label">Modelo</label>
                                <input type="text" id="modelo" name="modelo" class="form-control text-uppercase">
                            </div>
                            <div class="col-12">
    <label for="personalRut" class="form-label">Asociar a Personal</label>
    <div class="input-group">
        <input type="text" id="personalRut" name="personalRut" class="form-control" placeholder="Ingrese RUT o nombre para buscar" autocomplete="off">
        <button class="btn btn-outline-primary" type="button" id="search-personal-btn">
            <i class="bi bi-search"></i> Buscar
        </button>
    </div>
    <small id="vehiculo-nombre-asociado" class="form-text text-muted mt-1 d-block"></small>
    
    <!-- Contenedor para resultados de búsqueda de personal -->
    <div id="personal-search-results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none;">
        <!-- Los resultados de búsqueda se insertarán aquí dinámicamente -->
    </div>
</div>
                            <hr class="my-3">
                            <h6 class="text-muted">Control de Acceso</h6>
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio de Acceso <span class="text-danger">*</span></label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_expiracion" class="form-label">Fecha de Expiración <span class="text-danger" id="vehiculo_fecha_expiracion_required">*</span></label>
                                <input type="date" id="fecha_expiracion" name="fecha_expiracion" class="form-control" required>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="acceso_permanente" name="acceso_permanente">
                                    <label class="form-check-label" for="acceso_permanente">
                                        Acceso Permanente (sin fecha de expiración)
                                    </label>
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
        </div>`;
}

function getComisionModalTemplate() {
    return `
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="comision-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="comision-modal-title">Agregar/Editar Personal en Comisión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <div class="row g-4">
                        
                        <!-- COLUMNA IZQUIERDA: Datos Personales -->
                        <div class="col-lg-6">
                            <div class="card border-primary shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <i class="bi bi-person-fill me-2"></i>Datos Personales
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="rut" class="form-label">RUT <span class="text-danger">*</span></label>
                                            <input type="text" id="rut" name="rut" class="form-control" placeholder="ej: 12.345.678-9" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="grado" class="form-label">Grado</label>
                                            <input type="text" id="grado" name="grado" class="form-control" placeholder="ej: Capitán, Teniente">
                                        </div>
                                        <div class="col-12">
                                            <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                                            <input type="text" id="nombres" name="nombres" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="paterno" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                            <input type="text" id="paterno" name="paterno" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="materno" class="form-label">Apellido Materno</label>
                                            <input type="text" id="materno" name="materno" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Comisión + POC -->
                        <div class="col-lg-6">
                            <!-- Card de Comisión -->
                            <div class="card border-success shadow-sm mb-3">
                                <div class="card-header bg-success text-white">
                                    <i class="bi bi-briefcase-fill me-2"></i>Datos de Comisión
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="unidad_origen" class="form-label">Unidad de Origen</label>
                                            <input type="text" id="unidad_origen" name="unidad_origen" class="form-control">
                                        </div>
                                        <div class="col-6">
                                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control">
                                        </div>
                                        <div class="col-6">
                                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label for="motivo" class="form-label">Motivo</label>
                                            <textarea id="motivo" name="motivo" class="form-control" rows="2" placeholder="Especifica el motivo de la comisión..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de POC -->
                            <div class="card border-info shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <i class="bi bi-person-badge-fill me-2"></i>Punto de Contacto (POC)
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="poc_nombre" class="form-label">Nombre del POC</label>
                                            <input type="text" id="poc_nombre" name="poc_nombre" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label for="unidad_poc" class="form-label">Unidad del POC</label>
                                            <input type="text" id="unidad_poc" name="unidad_poc" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label for="poc_anexo" class="form-label">Anexo del POC</label>
                                            <input type="text" id="poc_anexo" name="poc_anexo" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <div class="me-auto text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Los campos marcados con <span class="text-danger">*</span> son obligatorios
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>`;
}

function getClarificationModalTemplate(personDetails) {
    const photoUrl = personDetails.photoUrl ? `../foto-emple/${personDetails.photoUrl}` : 'assets/imagenes/placeholder-avatar.png';
    const rut = personDetails.rut || 'No disponible';
    const currentDate = new Date().toLocaleDateString('es-CL', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const currentTime = new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
    
    // Determinar información adicional según el tipo de persona
    const unidad = personDetails.unidad || 'Sin unidad especificada';
    const grado = personDetails.grado || '';
    const badgeColor = personDetails.es_residente == 1 ? 'bg-success' : 'bg-warning';
    const badgeText = personDetails.es_residente == 1 ? 'Residente' : 'No Residente';
    const alertType = personDetails.es_residente == 1 ? 'alert-success' : 'alert-warning';
    const alertIcon = personDetails.es_residente == 1 ? 'bi-house-check-fill' : 'bi-exclamation-triangle-fill';
    const alertText = personDetails.es_residente == 1 
        ? 'Esta persona es residente. Por favor, confirme el motivo de su ingreso:' 
        : 'Esta persona no es residente. Por favor, especifique el motivo del ingreso:';
    
    return `
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-shield-check me-2"></i>Clarificación de Acceso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="badge ${badgeColor} text-white px-3 py-2 fs-6">
                        <i class="bi ${personDetails.es_residente == 1 ? 'bi-house-heart-fill' : 'bi-building'} me-1"></i> 
                        Personal ${badgeText}
                    </div>
                    <div class="text-end">
                        <span class="d-block text-muted small">${currentDate}</span>
                        <span class="d-block text-muted small fw-bold">${currentTime}</span>
                    </div>
                </div>
                
                <div class="card border-0 bg-light shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3 text-center">
                                <img src="${photoUrl}" class="rounded-circle mb-2 img-thumbnail shadow-sm" width="100" height="100" style="object-fit: cover;" alt="Foto de ${personDetails.name}">
                                <div class="badge bg-primary text-white mt-1 d-block px-2 py-1">
                                    ${grado}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="mb-1 d-flex align-items-center">
                                    ${personDetails.name}
                                </h4>
                                <div class="d-flex flex-column gap-1 mt-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building text-primary me-2"></i>
                                        <span class="text-dark">${unidad}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-vcard text-primary me-2"></i>
                                        <span class="text-dark">RUT: ${rut}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-history text-primary me-2"></i>
                                        <span class="text-dark">Ingreso: ${currentTime}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert ${alertType} d-flex align-items-center py-3">
                    <i class="bi ${alertIcon} fs-5 me-3"></i>
                    <div>${alertText}</div>
                </div>
                
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-3"><i class="bi bi-question-circle me-2"></i>Seleccione el motivo del ingreso:</h6>
                        
                        <div class="d-flex flex-column gap-3">
                            ${personDetails.es_residente == 1 ? `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="clarificationReason" id="reason-residencia" value="residencia" checked>
                                <label class="form-check-label fw-medium" for="reason-residencia">
                                    <span class="d-flex align-items-center">
                                        <span class="badge bg-success-subtle text-success me-2">
                                            <i class="bi bi-house-heart"></i>
                                        </span>
                                        Ir a residencia
                                    </span>
                                    <small class="d-block text-muted mt-1 ps-4">Se registrará como entrada a residencia</small>
                                </label>
                            </div>` : ''}
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="clarificationReason" id="reason-trabajo" value="trabajo" ${personDetails.es_residente == 1 ? '' : 'checked'}>
                                <label class="form-check-label fw-medium" for="reason-trabajo">
                                    <span class="d-flex align-items-center">
                                        <span class="badge bg-primary-subtle text-primary me-2">
                                            <i class="bi bi-briefcase"></i>
                                        </span>
                                        Actividad laboral
                                    </span>
                                    <small class="d-block text-muted mt-1 ps-4">Se registrará como entrada por trabajo</small>
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="clarificationReason" id="reason-otros" value="otros">
                                <label class="form-check-label fw-medium" for="reason-otros">
                                    <span class="d-flex align-items-center">
                                        <span class="badge bg-secondary-subtle text-secondary me-2">
                                            <i class="bi bi-journal-text"></i>
                                        </span>
                                        Otro motivo
                                    </span>
                                    <small class="d-block text-muted mt-1 ps-4">Requiere especificar detalles adicionales</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" id="clarification-otros-details-container" style="display: none;">
                    <div class="card-body p-3 bg-light rounded">
                        <label for="clarification-otros-details" class="form-label">
                            <i class="bi bi-pencil-square me-1"></i>
                            Detalle el motivo del ingreso:
                        </label>
                        <textarea id="clarification-otros-details" class="form-control" rows="3" 
                            placeholder="Por favor, especifique detalladamente el motivo de su ingreso..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="clarification-submit-btn" data-person-id="${personDetails.id}">
                    <i class="bi bi-check-circle me-1"></i> Confirmar Acceso
                </button>
            </div>
        </div>
    </div>`;
}



/**
 * Genera el HTML para un módulo completo basado en su ID.
 * @param {string} moduleId - El ID del módulo a renderizar.
 * @returns {string} - El HTML del módulo.
 */
function getModuleTemplate(moduleId) {
    switch (moduleId) {
        case 'inicio':
            return getInicioTemplate();
        case 'portico':
            return getPorticoTemplate();
        case 'mantenedor-personal':
            return getMantenedorPersonalTemplate();
        case 'control-personal':
            return getControlPersonalTemplate();
        case 'mantenedor-vehiculos':
            return getMantenedorVehiculosTemplate();
        case 'control-vehiculos':
            return getControlVehiculosTemplate();
        case 'mantenedor-visitas':
            return getMantenedorVisitasTemplate();
        case 'mantenedor-comision':
            return getMantenedorComisionTemplate();
        case 'mantenedor-empresas':
            return getEmpresasTemplate();
        case 'control-visitas':
            return getControlVisitasTemplate();
        case 'estado-actual':
            return getEstadoActualTemplate();
        case 'horas-extra':
            return getHorasExtraTemplate();
        case 'reportes':
            return getReportesTemplate();
        default:
            return `
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h2">${moduleId.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</h1>
                        <p>Módulo en construcción.</p>
                    </div>
                </div>`;
    }
}

function getDashboardDetailModalTemplate() {
    return `
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboard-detail-modal-title">Detalles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="dashboard-detail-search" class="form-control" placeholder="Buscar...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead id="dashboard-detail-table-head">
                            <!-- Headers will be injected here -->
                        </thead>
                        <tbody id="dashboard-detail-table-body">
                            <!-- Rows will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>`;
}

function getGuardiaServicioTemplate() {
    return `
    <div class="container-fluid p-4">
        <div class="row">
            <!-- Columna Izquierda: Formulario -->
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-shield-fill-check me-2"></i>Registrar Guardia / Servicio</h5>
                    </div>
                    <div class="card-body">
                        <form id="guardia-servicio-form">
                            <!-- RUT y Verificación -->
                            <div class="mb-3">
                                <label for="gs-rut" class="form-label">RUT del Personal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="gs-rut" class="form-control" placeholder="Ej: 12345678" required>
                                    <button type="button" id="gs-verificar-rut-btn" class="btn btn-outline-primary">
                                        <i class="bi bi-search me-1"></i>Verificar
                                    </button>
                                </div>
                                <div id="gs-rut-feedback" class="form-text"></div>
                            </div>

                            <!-- Móvil -->
                            <div class="mb-3">
                                <label for="gs-movil" class="form-label">Móvil</label>
                                <input type="text" id="gs-movil" class="form-control" placeholder="Ej: 912345678">
                                <div class="form-text">Se actualizará automáticamente en la ficha del personal si cambió</div>
                            </div>

                            <!-- Fecha de Ingreso -->
                            <div class="mb-3">
                                <label for="gs-fecha" class="form-label">Fecha de Ingreso <span class="text-danger">*</span></label>
                                <input type="date" id="gs-fecha" class="form-control" required>
                            </div>

                            <!-- Tipo: Guardia / Servicio -->
                            <div class="mb-3">
                                <label class="form-label d-block">Tipo <span class="text-danger">*</span></label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gs-tipo" id="gs-tipo-guardia" value="GUARDIA" checked>
                                    <label class="form-check-label" for="gs-tipo-guardia">
                                        <i class="bi bi-shield-check me-1"></i>Guardia
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gs-tipo" id="gs-tipo-servicio" value="SERVICIO">
                                    <label class="form-check-label" for="gs-tipo-servicio">
                                        <i class="bi bi-tools me-1"></i>Servicio
                                    </label>
                                </div>
                            </div>

                            <!-- Servicio Detalle (solo si es SERVICIO) -->
                            <div id="gs-servicio-detalle-container" class="mb-3 d-none">
                                <label for="gs-servicio-detalle" class="form-label">Detalle del Servicio</label>
                                <input type="text" id="gs-servicio-detalle" class="form-control" placeholder="Ej: Servicio de Comunicaciones">
                            </div>

                            <!-- Anexo -->
                            <div class="mb-3">
                                <label for="gs-anexo" class="form-label">Anexo</label>
                                <input type="text" id="gs-anexo" class="form-control" placeholder="Ej: 1234">
                            </div>

                            <!-- Botón Agregar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Agregar Registro
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tabla de Registros Activos -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Registros Activos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>RUT</th>
                                        <th>Tipo</th>
                                        <th>Servicio</th>
                                        <th>Anexo</th>
                                        <th>Móvil</th>
                                        <th>Fecha Ingreso</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="guardia-servicio-table-body">
                                    <tr>
                                        <td colspan="8" class="text-center p-4 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No hay registros activos
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Nota informativa -->
                <div class="alert alert-info mt-3" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Importante:</strong> El personal registrado aquí como Guardia o Servicio activo
                    <strong>NO aparecerá</strong> en las alertas de "Personal No Autorizado", incluso si permanecen
                    en la base después de las 16:30. Al finalizar su turno, presione el botón <i class="bi bi-check-circle-fill"></i>
                    para completar el registro.
                </div>
            </div>
        </div>
    </div>`;
}

export { getPersonalModalTemplate, getVehiculoHistorialModalTemplate, getVehiculoModalTemplate, getComisionModalTemplate, getClarificationModalTemplate, getModuleTemplate, getDashboardDetailModalTemplate, getGuardiaServicioTemplate };
