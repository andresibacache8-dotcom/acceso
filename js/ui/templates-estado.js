function getEstadoActualTemplate() {
    return `
        <h1 class="h2 mb-4">Estado Actual de las Instalaciones</h1>
        <div class="row g-4">
            <!-- Tarjeta de Personal Adentro -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0">Personal Adentro (<span id="estado-personal-count">0</span>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul id="lista-personal-adentro" class="list-group list-group-flush" style="max-height: 65vh; overflow-y: auto;">
                            <!-- Items generados dinámicamente -->
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Tarjeta de Vehículos Adentro -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0">Vehículos Adentro (<span id="estado-vehiculos-count">0</span>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul id="lista-vehiculos-adentro" class="list-group list-group-flush" style="max-height: 65vh; overflow-y: auto;">
                            <!-- Items generados dinámicamente -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>`;
}

export { getEstadoActualTemplate };
