function getPorticoTemplate() {
    return `
        <div id="portico-module">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 m-0 d-flex align-items-center">
                        <i class="bi bi-shield-check me-2 text-primary"></i>
                        Control de Pórtico
                    </h1>
                    <p class="text-muted mt-1 mb-0"><i class="bi bi-info-circle me-1"></i> Sistema para registro de entradas y salidas</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="toggle-control-personal-btn" class="btn btn-warning" title="Click para habilitar/deshabilitar el módulo Control de Unidades">
                        <i class="bi bi-toggle-off me-1"></i> Habilitar Control de Unidades
                    </button>
                    <button id="refresh-portico-btn" class="btn btn-outline-primary" title="Actualizar Registros">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar
                    </button>
                </div>
            </div>
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
                        <div class="card-body p-4" id="portico-scan-section">
                            <form id="scan-portico-form">
                                <label for="scan-portico-input" class="form-label fw-medium">
                                    <i class="bi bi-upc-scan text-primary me-1"></i> Escanee un código QR o ingrese RUT sin dígito verificador:
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-qr-code"></i>
                                    </span>
                                    <input type="text" id="scan-portico-input" 
                                        placeholder="Escanee o ingrese RUT aquí..." 
                                        class="form-control form-control-lg border-2" 
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
                    <div id="portico-scan-feedback" class="mt-3"></div>
                </div>
                
                <!-- Columna de Registro de Actividad -->
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0" id="portico-logs-container">
                        <div class="card-header bg-light py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="m-0">
                                    <i class="bi bi-list-check me-2 text-primary"></i>
                                    Registro de Actividad
                                </h5>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary px-3 py-2 d-flex align-items-center" id="portico-log-count">
                                        <i class="bi bi-person-check me-1"></i> 0
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="input-group mb-3">
                                <input type="text" id="search-portico-log" class="form-control" 
                                    placeholder="Buscar por nombre, ID, tipo o empresa...">
                                <button class="btn btn-outline-secondary" type="button" id="clear-portico-search">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            <div id="portico-search-result-count" class="small text-muted mb-2" style="display:none;"></div>
                            
                            <div class="table-responsive" style="max-height: 65vh;">
                                <table class="table table-hover border">
                                    <tbody id="portico-log-table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                /* Estilos específicos para el módulo de pórtico */
                .scan-ready-pulse {
                    animation: pulse-border 2s ease-out;
                }
                
                @keyframes pulse-border {
                    0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.5); }
                    70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
                }
                
                .spin {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .table-row-even {
                    background-color: #f8f9fa;
                }
                
                .table-row-odd {
                    background-color: #ffffff;
                }
                
                .animate__fadeIn {
                    animation: fadeIn 0.5s ease-in;
                }
                
                .animate__fadeOut {
                    animation: fadeOut 0.5s ease-out;
                }
                
                @keyframes fadeIn {
                    0% { opacity: 0; transform: translateY(-10px); }
                    100% { opacity: 1; transform: translateY(0); }
                }
                
                @keyframes fadeOut {
                    0% { opacity: 1; transform: translateY(0); }
                    100% { opacity: 0; transform: translateY(-10px); }
                }
            </style>
        </div>`;
}

export { getPorticoTemplate };
