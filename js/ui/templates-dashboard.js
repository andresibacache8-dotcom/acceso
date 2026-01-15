function getInicioTemplate() {
    return `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 m-0">Sistema de Control de Acceso Digital (SCAD)</h1>
            <button id="refresh-dashboard" class="btn btn-sm btn-outline-primary" title="Actualizar panel">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
        </div>

        <!-- Alertas -->
        <h5 id="alertas-title" class="mb-3 text-danger d-none">Alertas</h5>
        <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
            <div class="col d-none" id="alerta-atrasado-container">
                <div class="card shadow-sm h-100 border-warning" data-category="alerta-atrasado">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-warning fw-normal mb-1">Personal Autorizado - Tiempo Vencido</h6>
                            <span id="alerta-atrasado-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-warning-soft fs-4"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
            <div class="col d-none" id="alerta-no-autorizado-container">
                <div class="card shadow-sm h-100 border-danger" data-category="alerta-no-autorizado">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-danger fw-normal mb-1">Personal No Autorizado - Adentro</h6>
                            <span id="alerta-no-autorizado-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-danger-soft fs-4"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contadores de Personas -->
        <h5 class="mb-3 mt-4">Estado del Personal</h5>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
            <div class="col">
                <div class="card shadow-sm h-100" data-category="personal-general-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Total de persona en BALC</h6>
                            <span id="personal-general-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-primary-soft fs-4"><i class="bi bi-people-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="personal-trabajando">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Personal Trabajando</h6>
                            <span id="personal-trabajando-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-success-soft fs-4"><i class="bi bi-person-workspace"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="personal-residiendo">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Personal Residiendo</h6>
                            <span id="personal-residiendo-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-info-soft fs-4"><i class="bi bi-house-heart-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="personal-otras-actividades">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Personal Otras Actividades</h6>
                            <span id="personal-otras-actividades-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-warning-soft fs-4"><i class="bi bi-person-lines-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="visitas-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Visitas</h6>
                            <span id="visitas-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-secondary-soft fs-4"><i class="bi bi-person-badge-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="personal-en-comision">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Personal en Comisión</h6>
                            <span id="personal-en-comision-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-purple-soft fs-4"><i class="bi bi-person-vcard"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="empresas-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">Empresas</h6>
                            <span id="empresas-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-dark-soft fs-4"><i class="bi bi-building"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contadores de Vehículos -->
        <h5 class="mb-3 mt-4">Estado de Vehículos</h5>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-4">
            <div class="col">
                <div class="card shadow-sm h-100" data-category="vehiculos-fiscal-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">V. Fiscal</h6>
                            <span id="vehiculos-fiscal-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-success-soft fs-4"><i class="bi bi-shield-fill-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="vehiculos-funcionario-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">V. Funcionario</h6>
                            <span id="vehiculos-funcionario-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-dark-soft fs-4"><i class="bi bi-car-front-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="vehiculos-residente-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">V. Residente</h6>
                            <span id="vehiculos-residente-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-info-soft fs-4"><i class="bi bi-truck-front"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="vehiculos-visita-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">V. Visita</h6>
                            <span id="vehiculos-visita-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-warning-soft fs-4"><i class="bi bi-car-front"></i></div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm h-100" data-category="vehiculos-proveedor-adentro">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted fw-normal mb-1">V. Empresa</h6>
                            <span id="vehiculos-proveedor-adentro-count" class="fs-3 fw-bold">0</span>
                        </div>
                        <div class="icon-circle bg-danger-soft fs-4"><i class="bi bi-box-seam-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="dashboard-detail-modal" tabindex="-1" aria-hidden="true"></div>
    `;
}

export { getInicioTemplate };
