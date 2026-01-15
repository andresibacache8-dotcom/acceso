/**
 * ui.js
 * ARCHIVO PRINCIPAL DE UI - Importa y re-exporta todas las plantillas HTML
 *
 * Este archivo fue reorganizado para mejor mantenibilidad.
 * Las funciones template están organizadas en archivos separados por módulo.
 *
 * @author Sistema SCAD
 * @date 2025-10-27
 * @version 2.0.0 (Refactorizado)
 */

// ============================================================================
// IMPORTAR TEMPLATES DE MÓDULOS
// ============================================================================

// Templates de Pórtico
import { getPorticoTemplate } from './templates-portico.js';

// Templates de Dashboard
import { getInicioTemplate } from './templates-dashboard.js';

// Templates de Personal
import { getMantenedorPersonalTemplate, getControlPersonalTemplate } from './templates-personal.js';

// Templates de Vehículos
import { getImportVehiculosModalTemplate, getMantenedorVehiculosTemplate, getControlVehiculosTemplate } from './templates-vehiculos.js';

// Templates de Visitas
import { getMantenedorVisitasTemplate, getMantenedorComisionTemplate, getControlVisitasTemplate } from './templates-visitas.js';

// Templates de Estado
import { getEstadoActualTemplate } from './templates-estado.js';

// Templates de Horas Extra
import { getHorasExtraTemplate, getReportesTemplate } from './templates-horas-extra.js';

// Templates de Modales
import {
    getPersonalModalTemplate,
    getVehiculoHistorialModalTemplate,
    getVehiculoModalTemplate,
    getComisionModalTemplate,
    getClarificationModalTemplate,
    getModuleTemplate,
    getDashboardDetailModalTemplate,
    getGuardiaServicioTemplate
} from './templates-modals.js';

// Templates de Empresas
import {
    getEmpresasTemplate,
    getEmpresaModalTemplate,
    getEmpresaEmpleadoModalTemplate,
    getImportEmpleadosModalTemplate
} from './templates-empresas.js';

// ============================================================================
// RE-EXPORTAR TODAS LAS FUNCIONES
// ============================================================================

export {
    // Pórtico
    getPorticoTemplate,

    // Dashboard
    getInicioTemplate,

    // Personal
    getMantenedorPersonalTemplate,
    getControlPersonalTemplate,

    // Vehículos
    getImportVehiculosModalTemplate,
    getMantenedorVehiculosTemplate,
    getControlVehiculosTemplate,

    // Visitas
    getMantenedorVisitasTemplate,
    getMantenedorComisionTemplate,
    getControlVisitasTemplate,

    // Estado
    getEstadoActualTemplate,

    // Horas Extra y Reportes
    getHorasExtraTemplate,
    getReportesTemplate,

    // Modales
    getPersonalModalTemplate,
    getVehiculoHistorialModalTemplate,
    getVehiculoModalTemplate,
    getComisionModalTemplate,
    getClarificationModalTemplate,
    getModuleTemplate,
    getDashboardDetailModalTemplate,

    // Guardia y Servicio
    getGuardiaServicioTemplate,

    // Empresas
    getEmpresasTemplate,
    getEmpresaModalTemplate,
    getEmpresaEmpleadoModalTemplate,
    getImportEmpleadosModalTemplate
};
