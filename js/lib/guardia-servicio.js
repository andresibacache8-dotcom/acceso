/**
 * js/guardia-servicio.js
 * 
 * Lógica para el módulo de Guardia & Servicio refactorizado.
 */

// Almacena el ID del personal encontrado para usarlo al actualizar el móvil
let personalIdEncontrado = null;

/**
 * Inicializa el módulo de Guardia & Servicio.
 * Configura todos los event listeners necesarios.
 */
function initGuardiaServicioModule() {
    console.log("Módulo Guardia & Servicio (refactorizado) inicializado.");

    // Cargar datos iniciales en la tabla
    loadGuardiaServicioData();

    // Obtener referencias a los elementos del DOM
    const form = document.getElementById('guardia-servicio-form');
    const verificarRutBtn = document.getElementById('gs-verificar-rut-btn');
    const tipoServicioRadios = document.querySelectorAll('input[name="gs-tipo"]');
    const servicioDetalleContainer = document.getElementById('gs-servicio-detalle-container');
    const tablaBody = document.getElementById('guardia-servicio-table-body');
    const fechaInput = document.getElementById('gs-fecha');

    // Establecer la fecha de ingreso a hoy por defecto
    if (fechaInput) {
        fechaInput.value = new Date().toISOString().split('T')[0];
    }

    // --- Event Listeners ---

    // 1. Botón Verificar RUT
    verificarRutBtn.addEventListener('click', handleVerificarRut);

    // 2. Radios de Tipo (Guardia/Servicio)
    tipoServicioRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            servicioDetalleContainer.classList.toggle('d-none', radio.value !== 'SERVICIO');
        });
    });

    // 3. Envío del formulario
    form.addEventListener('submit', handleFormSubmit);

    // 4. Clics en la tabla (para finalizar)
    tablaBody.addEventListener('click', handleTableClick);
}

/**
 * Maneja la verificación del RUT del personal.
 */
async function handleVerificarRut() {
    const rutInput = document.getElementById('gs-rut');
    const feedbackDiv = document.getElementById('gs-rut-feedback');
    const movilInput = document.getElementById('gs-movil');
    const rut = rutInput.value.trim();

    if (!rut) {
        feedbackDiv.textContent = 'Por favor, ingrese un RUT.';
        feedbackDiv.className = 'form-text text-danger';
        return;
    }

    try {
        const personal = await api.findPersonalByRut(rut);
        if (personal && personal.id) {
            personalIdEncontrado = personal.id; // Guardar ID para posible actualización
            const nombreCompleto = `${personal.Grado || ''} ${personal.Nombres || ''} ${personal.Paterno || ''}`.trim();
            feedbackDiv.textContent = `Personal encontrado: ${nombreCompleto}`;
            feedbackDiv.className = 'form-text text-success';
            movilInput.value = personal.movil1 || '';
            // Guardar valor original para comparar en submit
            movilInput.dataset.originalValue = personal.movil1 || '';
        } else {
            personalIdEncontrado = null;
            feedbackDiv.textContent = 'Personal no encontrado en la base de datos.';
            feedbackDiv.className = 'form-text text-warning';
            movilInput.value = '';
        }
    } catch (error) {
        personalIdEncontrado = null;
        feedbackDiv.textContent = `Error al verificar: ${error.message}`;
        feedbackDiv.className = 'form-text text-danger';
        showToast('Error', error.message, 'error');
    }
}

/**
 * Maneja el envío del formulario para crear un nuevo registro.
 * @param {Event} event - El evento de submit.
 */
async function handleFormSubmit(event) {
    event.preventDefault();

    const rutInput = document.getElementById('gs-rut');
    const feedbackDiv = document.getElementById('gs-rut-feedback');
    const movilInput = document.getElementById('gs-movil');
    const fechaInput = document.getElementById('gs-fecha');

    if (!feedbackDiv.textContent.includes('Personal encontrado')) {
        showToast('Atención', 'Debe verificar un RUT válido antes de agregar.', 'warning');
        return;
    }
    if (!fechaInput.value) {
        showToast('Atención', 'Debe seleccionar una fecha de ingreso.', 'warning');
        return;
    }

    const formData = {
        personal_rut: rutInput.value.trim(),
        personal_nombre: feedbackDiv.textContent.replace('Personal encontrado: ', ''),
        tipo: document.querySelector('input[name="gs-tipo"]:checked').value,
        servicio_detalle: document.getElementById('gs-servicio-detalle').value,
        anexo: document.getElementById('gs-anexo').value.trim(),
        movil: movilInput.value.trim(),
        fecha_ingreso: fechaInput.value
    };

    if (formData.tipo !== 'SERVICIO') {
        formData.servicio_detalle = null;
    }

    try {
        await api.createGuardiaServicio(formData);
        showToast('Éxito', 'Registro agregado correctamente.', 'success');

        if (personalIdEncontrado && movilInput.dataset.originalValue !== formData.movil) {
            await api.updatePersonal({ id: personalIdEncontrado, movil1: formData.movil });
            showToast('Información', 'Número de móvil del personal actualizado.', 'info');
        }

        // Limpiar y recargar
        document.getElementById('guardia-servicio-form').reset();
        document.getElementById('gs-servicio-detalle-container').classList.add('d-none');
        feedbackDiv.textContent = '';
        personalIdEncontrado = null;
        fechaInput.value = new Date().toISOString().split('T')[0]; // Resetear fecha a hoy
        loadGuardiaServicioData();

    } catch (error) {
        showToast('Error', `No se pudo agregar el registro: ${error.message}`, 'error');
    }
}

/**
 * Maneja los clics en la tabla, específicamente para el botón de finalizar.
 * @param {Event} event - El evento de clic.
 */
async function handleTableClick(event) {
    const finishButton = event.target.closest('.finish-btn');
    if (finishButton) {
        const id = finishButton.dataset.id;
        if (confirm(`¿Está seguro de que desea finalizar este registro?`)) {
            try {
                await api.finishGuardiaServicio(id);
                showToast('Éxito', 'Registro finalizado.', 'success');
                loadGuardiaServicioData(); // Recargar la tabla
            } catch (error) {
                showToast('Error', `No se pudo finalizar: ${error.message}`, 'error');
            }
        }
    }
}

/**
 * Carga los datos de la API y los renderiza en la tabla.
 */
async function loadGuardiaServicioData() {
    try {
        const data = await api.getGuardiaServicio();
        renderGuardiaServicioTable(data);
    } catch (error) {
        showToast('Error', `No se pudieron cargar los registros: ${error.message}`, 'error');
    }
}

/**
 * Renderiza las filas de la tabla con los datos proporcionados.
 * @param {Array} data - Un array de objetos de guardia/servicio.
 */
function renderGuardiaServicioTable(data) {
    const tablaBody = document.getElementById('guardia-servicio-table-body');
    tablaBody.innerHTML = '';

    if (!data || data.length === 0) {
        tablaBody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-muted">No hay registros activos.</td></tr>';
        return;
    }

    data.forEach(item => {
        const tr = document.createElement('tr');
        const nombreCompleto = `${item.Grado || ''} ${item.personal_nombre}`.trim();
        const fechaFormateada = new Date(item.fecha_ingreso + 'T00:00:00').toLocaleDateString('es-CL', {
            year: 'numeric', month: '2-digit', day: '2-digit'
        });

        tr.innerHTML = `
            <td>${nombreCompleto}</td>
            <td>${item.personal_rut}</td>
            <td><span class="badge bg-${item.tipo === 'GUARDIA' ? 'secondary' : 'info'}">${item.tipo}</span></td>
            <td>${item.servicio_detalle || 'N/A'}</td>
            <td>${item.anexo || '-'}</td>
            <td>${item.movil || '-'}</td>
            <td>${fechaFormateada}</td>
            <td>
                <button class="btn btn-success btn-sm finish-btn" data-id="${item.id}" title="Finalizar Registro">
                    <i class="bi bi-check-circle-fill"></i>
                </button>
            </td>
        `;
        tablaBody.appendChild(tr);
    });
}