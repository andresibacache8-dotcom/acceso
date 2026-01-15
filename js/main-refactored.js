/**
 * main-refactored.js
 * Archivo principal refactorizado de la aplicación SCAD
 *
 * @description
 * Orquesta la navegación entre módulos y coordina las inicializaciones.
 * Cada módulo es responsable de su propia lógica.
 *
 * @version 2.0 - Modular
 * @author Refactorización 2025-10-25
 */

// Guardián de la página: redirige a login si no se ha iniciado sesión.
if (sessionStorage.getItem('isLoggedIn') !== 'true') {
    window.location.href = 'login.html';
}

// ============================================================================
// IMPORTS DE MÓDULOS Y UTILIDADES
// ============================================================================

// Utilidades
import { validarRUT, limpiarRUT } from './utils/validators.js';

// APIs
import personalApi from './api/personal-api.js';
import vehiculosApi from './api/vehiculos-api.js';
import visitasApi from './api/visitas-api.js';
import accessLogsApi from './api/access-logs-api.js';
import horasExtraApi from './api/horas-extra-api.js';
import dashboardApi from './api/dashboard-api.js';
import comisionApi from './api/comision-api.js';
import empresasApi from './api/empresas-api.js';

// Módulos de UI
import { initNotifications, showToast } from './modules/ui/notifications.js';
import { initLoading, showLoadingSpinner, hideLoadingSpinner } from './modules/ui/loading.js';
import { openModal, closeModal, clearModalForm, openAndClearModal } from './modules/ui/modal-helpers.js';

// Templates (importados para disponibilidad global)
import {
    getPorticoTemplate,
    getInicioTemplate,
    getMantenedorPersonalTemplate,
    getControlPersonalTemplate,
    getImportVehiculosModalTemplate,
    getMantenedorVehiculosTemplate,
    getControlVehiculosTemplate,
    getMantenedorVisitasTemplate,
    getMantenedorComisionTemplate,
    getControlVisitasTemplate,
    getEstadoActualTemplate,
    getHorasExtraTemplate,
    getReportesTemplate,
    getPersonalModalTemplate,
    getVehiculoHistorialModalTemplate,
    getVehiculoModalTemplate,
    getComisionModalTemplate,
    getClarificationModalTemplate,
    getModuleTemplate,
    getDashboardDetailModalTemplate,
    getGuardiaServicioTemplate,
    getEmpresasTemplate,
    getEmpresaModalTemplate,
    getEmpresaEmpleadoModalTemplate,
    getImportEmpleadosModalTemplate
} from './ui/ui.js';

// Hacer funciones template globales para compatibilidad
window.getPorticoTemplate = getPorticoTemplate;
window.getInicioTemplate = getInicioTemplate;
window.getMantenedorPersonalTemplate = getMantenedorPersonalTemplate;
window.getControlPersonalTemplate = getControlPersonalTemplate;
window.getImportVehiculosModalTemplate = getImportVehiculosModalTemplate;
window.getMantenedorVehiculosTemplate = getMantenedorVehiculosTemplate;
window.getControlVehiculosTemplate = getControlVehiculosTemplate;
window.getMantenedorVisitasTemplate = getMantenedorVisitasTemplate;
window.getMantenedorComisionTemplate = getMantenedorComisionTemplate;
window.getControlVisitasTemplate = getControlVisitasTemplate;
window.getEstadoActualTemplate = getEstadoActualTemplate;
window.getHorasExtraTemplate = getHorasExtraTemplate;
window.getReportesTemplate = getReportesTemplate;
window.getPersonalModalTemplate = getPersonalModalTemplate;
window.getVehiculoHistorialModalTemplate = getVehiculoHistorialModalTemplate;
window.getVehiculoModalTemplate = getVehiculoModalTemplate;
window.getComisionModalTemplate = getComisionModalTemplate;
window.getClarificationModalTemplate = getClarificationModalTemplate;
window.getModuleTemplate = getModuleTemplate;
window.getDashboardDetailModalTemplate = getDashboardDetailModalTemplate;
window.getGuardiaServicioTemplate = getGuardiaServicioTemplate;
window.getEmpresasTemplate = getEmpresasTemplate;
window.getEmpresaModalTemplate = getEmpresaModalTemplate;
window.getEmpresaEmpleadoModalTemplate = getEmpresaEmpleadoModalTemplate;
window.getImportEmpleadosModalTemplate = getImportEmpleadosModalTemplate;

// Módulos funcionales
import { initDashboardModule, refreshDashboardData } from './modules/dashboard.js';
import { initHorasExtraModule } from './modules/horas-extra.js';
import { initPersonalModule } from './modules/personal.js';
import { initComisionModule } from './modules/comision.js';
import { initVehiculosModule } from './modules/vehiculos.js';
import { initVisitasModule } from './modules/visitas.js';
import { initEmpresasModule } from './modules/empresas.js';
import { initControlModule, stopPorticoAutoRefresh } from './modules/control.js';
import { initReportesModule } from './reportes.js';

// ============================================================================
// INICIALIZACIÓN PRINCIPAL
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // --- SELECTORES DEL DOM ---
    const logoutButton = document.getElementById('logout-button');
    const navLinks = document.querySelectorAll('.nav-link');
    const mainContent = document.querySelector('main');
    const toastEl = document.getElementById('toast');
    const loadingSpinner = document.getElementById('loading-spinner');

    // Inicializar módulos de UI
    initNotifications(toastEl);
    initLoading(loadingSpinner);

    // Hacer showToast global para que sea accesible desde otros módulos
    window.showToast = showToast;

    // --- FUNCIONES DE NAVEGACIÓN ---

    /**
     * Maneja el click en el botón de logout
     */
    function handleLogout() {
        sessionStorage.clear();
        window.location.href = 'login.html';
    }

    /**
     * Maneja el click en los enlaces de navegación
     */
    function handleNavigation(e) {
        const link = e.currentTarget;

        // Si es un toggle de submenú, no hacer nada
        if (link.getAttribute('data-bs-toggle') === 'collapse') {
            return;
        }

        e.preventDefault();
        const targetId = link.getAttribute('href').substring(1);
        navigateTo(targetId);
    }

    /**
     * Navega a un módulo específico
     * @param {string} moduleId - ID del módulo a cargar
     */
    async function navigateTo(moduleId) {
        // Cargar plantilla del módulo
        mainContent.innerHTML = getModuleTemplate(moduleId);

        // Actualizar navegación
        updateNavigation(moduleId);

        // Inicializar el módulo
        await bindModuleEvents(moduleId);
    }

    /**
     * Actualiza la clase active en la navegación
     */
    function updateNavigation(moduleId) {
        const allNavLinks = document.querySelectorAll('.nav-link');
        const targetLink = document.querySelector(`.nav-link[href="#${moduleId}"]`);

        // Remover active de todos los links
        allNavLinks.forEach(link => link.classList.remove('active'));

        if (targetLink) {
            // Activar el target link
            targetLink.classList.add('active');

            // Verificar si es un submenu
            const parentCollapse = targetLink.closest('.collapse');
            if (parentCollapse) {
                const parentToggle = document.querySelector(`a[href="#${parentCollapse.id}"]`);
                if (parentToggle) {
                    parentToggle.classList.add('active');
                }
                const bsCollapse = new bootstrap.Collapse(parentCollapse, { toggle: false });
                bsCollapse.show();
            }
        }
    }

    /**
     * Inicializa el módulo correspondiente según su ID
     */
    async function bindModuleEvents(moduleId) {
        try {
            switch(moduleId) {
                case 'inicio':
                    // Inicializar el módulo dashboard
                    initDashboardModule(mainContent);
                    break;

                case 'portico':
                    initControlModule(mainContent, refreshDashboardData);
                    break;

                case 'mantenedor-personal':
                    initPersonalModule(mainContent);
                    break;

                case 'mantenedor-vehiculos':
                    initVehiculosModule(mainContent);
                    break;

                case 'mantenedor-visitas':
                    initVisitasModule(mainContent);
                    break;

                case 'mantenedor-comision':
                    initComisionModule(mainContent);
                    break;

                case 'mantenedor-empresas':
                    initEmpresasModule(mainContent);
                    break;

                case 'control-personal':
                    initControlModule(mainContent);
                    break;

                case 'horas-extra':
                    initHorasExtraModule(mainContent);
                    break;

                case 'guardia-servicio':
                    mainContent.innerHTML = getGuardiaServicioTemplate();
                    // Cargar dinámicamente el script de guardia-servicio si no está cargado
                    if (typeof initGuardiaServicioModule === 'undefined') {
                        const script = document.createElement('script');
                        script.src = './js/lib/guardia-servicio.js';
                        script.onload = () => {
                            if (typeof initGuardiaServicioModule === 'function') {
                                initGuardiaServicioModule();
                            } else {
                                console.error('initGuardiaServicioModule no encontrada después de cargar el script');
                            }
                        };
                        script.onerror = () => {
                            console.error('Error al cargar el script de guardia-servicio');
                            showToast('Error al cargar el módulo de Guardia/Servicio', 'error');
                        };
                        document.head.appendChild(script);
                    } else {
                        initGuardiaServicioModule();
                    }
                    break;

                case 'reportes':
                    initReportesModule(mainContent);
                    break;

                case 'gestion-usuarios':
                    // TODO: Implementar
                    showToast('Módulo Gestión de Usuarios en desarrollo', 'info');
                    break;

                default:
                    showToast('Módulo no encontrado', 'warning');
            }
        } catch (error) {
            console.error(`Error al inicializar módulo ${moduleId}:`, error);
            showToast(`Error al cargar el módulo: ${error.message}`, 'error');
        }
    }

    /**
     * Inicializa la aplicación
     */
    function init() {
        document.getElementById('app').classList.remove('d-none');

        // Configurar event listeners
        if (logoutButton) {
            logoutButton.addEventListener('click', handleLogout);
        }
        if (navLinks) {
            navLinks.forEach(link => link.addEventListener('click', handleNavigation));
        }

        // Navegar al módulo inicial
        navigateTo('inicio');
    }

    // --- INICIO ---
    init();

    // Hacer funciones globales si se necesita acceso desde otros scripts
    window.showToast = showToast;
    window.navigateTo = navigateTo;
    window.showLoadingSpinner = showLoadingSpinner;
    window.hideLoadingSpinner = hideLoadingSpinner;
});
