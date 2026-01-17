// acceso/js/api.js

const API_BASE_URL = 'api';

const api = {
    // Helper functions for loading spinner (will be defined in main.js and passed here)
    _showLoading: null,
    _hideLoading: null,

    setLoadingFunctions: (showFn, hideFn) => {
        api._showLoading = showFn;
        api._hideLoading = hideFn;
    },

    _callApi: async (url, options = {}, retryCount = 0) => {
        if (api._showLoading) api._showLoading();
        try {
            // Asegurarnos de que el método está explícito para GET
            if (!options.method) {
                options.method = 'GET';
            }

            // Agregar encabezados para evitar caché
            if (!options.headers) {
                options.headers = {};
            }
            options.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            options.headers['Pragma'] = 'no-cache';
            options.headers['Expires'] = '0';

            // Agregar JWT token en Authorization header si está disponible
            if (typeof authService !== 'undefined' && authService.isAuthenticated()) {
                const token = authService.getAccessToken();
                options.headers['Authorization'] = `Bearer ${token}`;
            }

            // Incluir cookies de sesión en todas las solicitudes
            options.credentials = 'include';

            const response = await fetch(url, options);

            // Handle 204 No Content response separately
            if (response.status === 204) {
                return null;
            }

            // Manejar token expirado (401)
            if (response.status === 401 && retryCount === 0 && typeof authService !== 'undefined' && authService.isAuthenticated()) {
                console.log('Token expirado, intentando refrescar...');
                const refreshResult = await authService.refreshAccessToken();

                if (refreshResult.success) {
                    // Reintentar la solicitud con el nuevo token
                    return api._callApi(url, options, 1);
                } else {
                    // Refresh falló, redirigir a login
                    window.location.href = 'login.html';
                    throw new Error('Sesión expirada. Por favor, inicie sesión de nuevo.');
                }
            }

            if (!response.ok) {
                let errorMessage = `Error HTTP: ${response.status}`;
                try {
                    const errorJson = await response.json();
                    if (errorJson.error?.message) {
                        errorMessage = errorJson.error.message;
                    } else if (errorJson.message) {
                        errorMessage = errorJson.message;
                    } else {
                        errorMessage = await response.text();
                    }
                } catch (e) {
                    // If parsing JSON fails, use the raw text response
                    errorMessage = await response.text().catch(() => `Error ${response.status}`);
                }
                throw new Error(errorMessage);
            }
            return await response.json();
        } finally {
            if (api._hideLoading) api._hideLoading();
        }
    },

    getReport: async (filters) => {
        try {
            const params = new URLSearchParams(filters);
            return await api._callApi(`${API_BASE_URL}/reportes.php?${params.toString()}`);
        } catch (error) {
            console.error("Error al obtener reporte:", error);
            throw new Error(error.message || "Error al obtener el reporte.");
        }
    },

    getPersonal: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/personal.php`);
        } catch (error) {
            console.error("Error al obtener datos de personal:", error);
            throw new Error(error.message || "Error al obtener datos de personal.");
        }
    },

    getInsidePersonal: async () => {
        try {
            // Llama a la API con el nuevo parámetro para obtener solo el personal "dentro"
            return await api._callApi(`${API_BASE_URL}/personal.php?status=inside`);
        } catch (error) {
            console.error("Error al obtener datos de personal que está dentro:", error);
            throw new Error(error.message || "Error al obtener datos de personal que está dentro.");
        }
    },

    getDashboardData: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/dashboard.php`);
        } catch (error) {
            console.error("Error al obtener datos del dashboard:", error);
            throw new Error(error.message || "Error al obtener datos del dashboard.");
        }
    },

    getDashboardDetails: async (category) => {
        try {
            return await api._callApi(`${API_BASE_URL}/dashboard.php?details=${category}`);
        } catch (error) {
            console.error(`Error al obtener detalles para ${category}:`, error);
            throw new Error(error.message || `Error al obtener detalles de ${category}.`);
        }
    },

    createPersonal: async (personalData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/personal.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(personalData)
            });
        } catch (error) {
            console.error("Error al crear personal:", error);
            throw new Error(error.message || "Error al crear personal.");
        }
    },

    updatePersonal: async (personalData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/personal.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(personalData)
            });
        } catch (error) {
            console.error("Error al actualizar personal:", error);
            throw new Error(error.message || "Error al actualizar personal.");
        }
    },

    deletePersonal: async (id) => {
        try {
            await api._callApi(`${API_BASE_URL}/personal.php?id=${id}`, {
                method: 'DELETE'
            });
            return true;
        } catch (error) {
            console.error("Error al eliminar personal:", error);
            throw new Error(error.message || "Error al eliminar personal.");
        }
    },



    findPersonalByRut: async (rut) => {
        try {
            return await api._callApi(`${API_BASE_URL}/personal.php?rut=${rut}`);
        } catch (error) {
            if (error.message.includes('404') || error.message.toLowerCase().includes('no encontrado')) {
                return null; 
            }
            console.error("Error al buscar personal por RUT:", error);
            throw new Error(error.message || "Error al buscar personal por RUT.");
        }
    },
    
    searchPersonal: async function(query, tipo) {
        try {
            return await this._callApi(`${API_BASE_URL}/buscar_personal.php?query=${encodeURIComponent(query)}&tipo=${encodeURIComponent(tipo)}`);
        } catch (error) {
            console.error("Error al buscar personal:", error);
            throw new Error(error.message || "Error al buscar personal.");
        }
    },

    getVehiculos: async () => {
        try {
            // Usamos el endpoint principal
            return await api._callApi(`${API_BASE_URL}/vehiculos.php`);
        } catch (error) {
            console.error("Error al obtener datos de vehículos:", error);
            throw new Error(error.message || "Error al obtener datos de vehículos.");
        }
    },

    createVehiculo: async (vehiculoData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/vehiculos.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(vehiculoData)
            });
        } catch (error) {
            console.error("Error al crear vehículo:", error);
            throw new Error(error.message || "Error al crear vehículo.");
        }
    },

    updateVehiculo: async (vehiculoData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/vehiculos.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(vehiculoData)
            });
        } catch (error) {
            console.error("Error al actualizar vehículo:", error);
            throw new Error(error.message || "Error al actualizar vehículo.");
        }
    },

    deleteVehiculo: async (id) => {
        try {
            await api._callApi(`${API_BASE_URL}/vehiculos.php?id=${id}`, {
                method: 'DELETE'
            });
            return true;
        } catch (error) {
            console.error("Error al eliminar vehículo:", error);
            throw new Error(error.message || "Error al eliminar vehículo.");
        }
    },
    
    getVehiculoHistorial: async (vehiculo_id) => {
        try {
            return await api._callApi(`${API_BASE_URL}/vehiculo_historial.php?vehiculo_id=${vehiculo_id}`);
        } catch (error) {
            console.error("Error al obtener historial del vehículo:", error);
            throw new Error(error.message || "Error al obtener historial del vehículo.");
        }
    },
    
    // Función eliminada: testVehiculoApi

    getVisitas: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/visitas.php`);
        } catch (error) {
            console.error("Error al obtener datos de visitas:", error);
            throw new Error(error.message || "Error al obtener datos de visitas.");
        }
    },

    createVisita: async (visitaData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/visitas.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(visitaData)
            });
        } catch (error) {
            console.error("Error al crear visita:", error);
            throw new Error(error.message || "Error al crear visita.");
        }
    },

    updateVisita: async (visitaData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/visitas.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(visitaData)
            });
        } catch (error) {
            console.error("Error al actualizar visita:", error);
            throw new Error(error.message || "Error al actualizar visita.");
        }
    },

    deleteVisita: async (id) => {
        try {
            await api._callApi(`${API_BASE_URL}/visitas.php?id=${id}`, {
                method: 'DELETE'
            });
            return true;
        } catch (error) {
            console.error("Error al eliminar visita:", error);
            throw new Error(error.message || "Error al eliminar visita.");
        }
    },

    toggleBlacklistVisita: async (id, en_lista_negra) => {
        try {
            return await api._callApi(`${API_BASE_URL}/visitas.php?action=toggle_blacklist&id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ en_lista_negra })
            });
        } catch (error) {
            console.error("Error al actualizar estado de lista negra:", error);
            throw new Error(error.message || "Error al actualizar estado de lista negra.");
        }
    },

    getComision: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/comision.php`);
        } catch (error) {
            console.error("Error al obtener datos de comision:", error);
            throw new Error(error.message || "Error al obtener datos de comision.");
        }
    },

    createComision: async (data) => {
        try {
            return await api._callApi(`${API_BASE_URL}/comision.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error("Error al crear comision:", error);
            throw new Error(error.message || "Error al crear comision.");
        }
    },

    updateComision: async (data) => {
        try {
            return await api._callApi(`${API_BASE_URL}/comision.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error("Error al actualizar comision:", error);
            throw new Error(error.message || "Error al actualizar comision.");
        }
    },

    deleteComision: async (id) => {
        try {
            await api._callApi(`${API_BASE_URL}/comision.php?id=${id}`, {
                method: 'DELETE'
            });
            return true;
        } catch (error) {
            console.error("Error al eliminar comision:", error);
            throw new Error(error.message || "Error al eliminar comision.");
        }
    },

    getAccessLogs: async (targetType) => {
        try {
            // Añadimos un timestamp para evitar caché
            const timestamp = new Date().getTime();
            return await api._callApi(`${API_BASE_URL}/log_access.php?target_type=${targetType}&nocache=${timestamp}`);
        } catch (error) {
            console.error(`Error al obtener logs de ${targetType}:`, error);
            throw new Error(error.message || `Error al obtener logs de ${targetType}.`);
        }
    },

    logAccess: async (targetId, targetType, puntoAcceso = 'desconocido') => {
        try {
            return await api._callApi(`${API_BASE_URL}/log_access.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    target_id: targetId, 
                    target_type: targetType,
                    punto_acceso: puntoAcceso
                })
            });
        } catch (error) {
            console.error(`Error al registrar acceso para ${targetType}:`, error);
            throw new Error(error.message || `Error al registrar acceso para ${targetType}.`);
        }
    },

    logPorticoAccess: async (id) => {
        try {
            // Añadimos un timestamp para evitar caché
            const timestamp = new Date().getTime();
            return await api._callApi(`${API_BASE_URL}/portico.php?nocache=${timestamp}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
        } catch (error) {
            console.error(`Error al registrar acceso por pórtico:`, error);
            throw new Error(error.message || `Error al registrar acceso por pórtico.`);
        }
    },

    getHorasExtra: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/horas_extra.php`);
        } catch (error) {
            console.error("Error al obtener horas extra:", error);
            throw new Error(error.message || "Error al obtener horas extra.");
        }
    },

    createHorasExtra: async (data) => {
        try {
            return await api._callApi(`${API_BASE_URL}/horas_extra.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error("Error al crear horas extra:", error);
            throw new Error(error.message || "Error al crear horas extra.");
        }
    },

    updateHorasExtra: async (data) => {
        try {
            return await api._callApi(`${API_BASE_URL}/horas_extra.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error("Error al actualizar horas extra:", error);
            throw new Error(error.message || "Error al actualizar horas extra.");
        }
    },

    deleteHorasExtra: async (id) => {
        try {
            await api._callApi(`${API_BASE_URL}/horas_extra.php?id=${id}`, {
                method: 'DELETE'
            });
            return true;
        } catch (error) {
            console.error("Error al eliminar horas extra:", error);
            throw new Error(error.message || "Error al eliminar horas extra.");
        }
    },

    loginUser: async (username, password) => {
        try {
            const result = await api._callApi(`${API_BASE_URL}/auth.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            return result; // result ya contiene success y message/user
        } catch (error) {
            console.error("Error al iniciar sesión:", error);
            throw new Error(error.message || "Error de conexión o credenciales inválidas.");
        }
    },

    getUsers: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/users.php`);
        } catch (error) {
            console.error("Error al obtener usuarios:", error);
            throw new Error(error.message || "Error al obtener usuarios.");
        }
    },

    createUser: async (userData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/users.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData)
            });
        } catch (error) {
            console.error("Error al crear usuario:", error);
            throw new Error(error.message || "Error al crear usuario.");
        }
    },

    updateUser: async (userData) => {
        try {
            return await api._callApi(`${API_BASE_URL}/users.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData)
            });
        } catch (error) {
            console.error("Error al actualizar usuario:", error);
            throw new Error(error.message || "Error al actualizar usuario.");
        }
    },

    deleteUser: async (id) => {
        try {
            await api._callApi(`${API_BASE_URL}/users.php?id=${id}`, {
                method: 'DELETE'
            });
            return true;
        } catch (error) {
            console.error("Error al eliminar usuario:", error);
            throw new Error(error.message || "Error al eliminar usuario.");
        }
    },

    logClarifiedAccess: async (data) => {
        try {
            return await api._callApi(`${API_BASE_URL}/log_clarified_access.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error("Error al registrar acceso clarificado:", error);
            throw new Error(error.message || "Error al registrar acceso clarificado.");
        }
    },

    // --- API para Empresas ---
    getEmpresas: async () => {
        return await api._callApi(`${API_BASE_URL}/empresas.php`);
    },
    createEmpresa: async (data) => {
        return await api._callApi(`${API_BASE_URL}/empresas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    },
    updateEmpresa: async (data) => {
        return await api._callApi(`${API_BASE_URL}/empresas.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    },
    deleteEmpresa: async (id) => {
        return await api._callApi(`${API_BASE_URL}/empresas.php?id=${id}`, { method: 'DELETE' });
    },

    // --- API para Empleados de Empresa ---
    getEmpresaEmpleados: async (empresaId) => {
        return await api._callApi(`${API_BASE_URL}/empresa_empleados.php?empresa_id=${empresaId}`);
    },
    createEmpresaEmpleado: async (data) => {
        return await api._callApi(`${API_BASE_URL}/empresa_empleados.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    },
    updateEmpresaEmpleado: async (data) => {
        return await api._callApi(`${API_BASE_URL}/empresa_empleados.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    },
    deleteEmpresaEmpleado: async (id) => {
        return await api._callApi(`${API_BASE_URL}/empresa_empleados.php?id=${id}`, { method: 'DELETE' });
    },

    // --- API para Guardia y Servicio ---
    /**
     * Obtiene la lista de registros activos de guardia/servicio
     * @returns {Promise<Array>} - Array de objetos con registros activos
     */
    getGuardiaServicio: async () => {
        try {
            return await api._callApi(`${API_BASE_URL}/guardia-servicio.php?action=list`);
        } catch (error) {
            console.error("Error al obtener registros de guardia/servicio:", error);
            throw new Error(error.message || "Error al obtener registros de guardia/servicio.");
        }
    },

    /**
     * Crea un nuevo registro de guardia/servicio
     * @param {Object} data - Datos del registro (personal_rut, personal_nombre, tipo, etc.)
     * @returns {Promise<Object>} - Respuesta con el ID del nuevo registro
     */
    createGuardiaServicio: async (data) => {
        try {
            return await api._callApi(`${API_BASE_URL}/guardia-servicio.php?action=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        } catch (error) {
            console.error("Error al crear registro de guardia/servicio:", error);
            throw new Error(error.message || "Error al crear registro de guardia/servicio.");
        }
    },

    /**
     * Finaliza un registro de guardia/servicio (marca salida)
     * @param {number} id - ID del registro a finalizar
     * @returns {Promise<Object>} - Respuesta de éxito
     */
    finishGuardiaServicio: async (id) => {
        try {
            return await api._callApi(`${API_BASE_URL}/guardia-servicio.php?action=finish`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
        } catch (error) {
            console.error("Error al finalizar registro de guardia/servicio:", error);
            throw new Error(error.message || "Error al finalizar registro de guardia/servicio.");
        }
    }
};