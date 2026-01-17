/// <reference types="cypress" />

describe('📊 Dashboard - Navigation & Reporting', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/index.html');
  });

  it('Debería cargar el dashboard principal', () => {
    // Verificar que se cargó el dashboard
    cy.get('[data-page="dashboard"]').should('be.visible');

    // Verificar que hay widgets principales
    cy.get('[data-widget="access-summary"]').should('be.visible');
    cy.get('[data-widget="today-visitas"]').should('be.visible');
    cy.get('[data-widget="active-users"]').should('be.visible');
  });

  it('Debería mostrar resumen de accesos del día', () => {
    cy.get('[data-widget="access-summary"]').should('be.visible');

    // Debería mostrar estadísticas
    cy.get('[data-widget="access-summary"]').should('contain', 'Accesos');
    cy.get('[data-widget="access-summary"] [data-metric="total"]').should('be.visible');
    cy.get('[data-widget="access-summary"] [data-metric="permitidos"]').should('be.visible');
    cy.get('[data-widget="access-summary"] [data-metric="denegados"]').should('be.visible');
  });

  it('Debería mostrar resumen de visitas activas', () => {
    cy.get('[data-widget="today-visitas"]').should('be.visible');
    cy.get('[data-widget="today-visitas"]').should('contain', 'Visitas Hoy');

    // Debería mostrar número de visitas
    cy.get('[data-widget="today-visitas"] [data-metric="total"]').should('be.visible');
  });

  it('Debería mostrar usuarios activos en tiempo real', () => {
    cy.get('[data-widget="active-users"]').should('be.visible');
    cy.get('[data-widget="active-users"]').should('contain', 'Usuarios Activos');

    // Debería mostrar lista de usuarios
    cy.get('[data-widget="active-users"] [data-list="users"]').should('be.visible');
  });

  it('Debería mostrar gráfico de accesos por hora', () => {
    cy.get('[data-widget="access-chart"]').should('be.visible');
    cy.get('[data-widget="access-chart"]').should('contain', 'Accesos por Hora');

    // Verificar que el gráfico está cargado
    cy.get('[data-widget="access-chart"] canvas').should('be.visible');
  });

  it('Debería mostrar gráfico de accesos por pórtico', () => {
    cy.get('[data-widget="portico-chart"]').should('be.visible');
    cy.get('[data-widget="portico-chart"]').should('contain', 'Accesos por Pórtico');

    cy.get('[data-widget="portico-chart"] canvas').should('be.visible');
  });

  it('Debería permitir cambiar rango de fechas en dashboard', () => {
    cy.get('[data-date-range="start"]').type('2025-01-01');
    cy.get('[data-date-range="end"]').type('2025-01-20');
    cy.get('[data-action="apply-date-range"]').click();

    cy.wait(1000);

    // Debería actualizar todos los widgets
    cy.get('[data-widget="access-summary"]').should('be.visible');
    cy.get('[data-widget="access-chart"] canvas').should('be.visible');
  });

  it('Debería permitir exportar dashboard a PDF', () => {
    cy.get('[data-action="export-dashboard-pdf"]').click();
    cy.verifySuccess('Dashboard exportado a PDF');
  });
});

describe('📈 Reportes - Generation & Download', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/index.html');
    cy.get('[data-section="reportes"]').click();
  });

  it('Debería cargar la sección de reportes', () => {
    cy.get('[data-page="reportes"]').should('be.visible');
    cy.get('[data-list="report-types"]').should('be.visible');
  });

  it('Debería listar tipos de reportes disponibles', () => {
    // Debería mostrar opciones de reportes
    cy.get('[data-report-type="accesos"]').should('be.visible');
    cy.get('[data-report-type="visitas"]').should('be.visible');
    cy.get('[data-report-type="personal"]').should('be.visible');
    cy.get('[data-report-type="vehiculos"]').should('be.visible');
    cy.get('[data-report-type="seguridad"]').should('be.visible');
  });

  it('Debería generar reporte de accesos', () => {
    cy.get('[data-report-type="accesos"]').click();

    // Debería mostrar formulario de configuración
    cy.get('[data-form="report-config"]').should('be.visible');

    // Seleccionar período
    cy.get('[name="report-period"]').select('ultimos-7-dias');

    // Generar reporte
    cy.get('[data-action="generate-report"]').click();

    // Debería mostrar vista previa
    cy.get('[data-panel="report-preview"]').should('be.visible');
  });

  it('Debería generar reporte de visitas', () => {
    cy.get('[data-report-type="visitas"]').click();

    cy.get('[name="report-period"]').select('ultimos-30-dias');
    cy.get('[name="report-status"]').select('completadas');

    cy.get('[data-action="generate-report"]').click();

    cy.get('[data-panel="report-preview"]').should('be.visible');
    cy.get('[data-panel="report-preview"]').should('contain', 'Visitas');
  });

  it('Debería generar reporte de personal activo', () => {
    cy.get('[data-report-type="personal"]').click();

    cy.get('[name="report-department"]').select('todas');

    cy.get('[data-action="generate-report"]').click();

    cy.get('[data-panel="report-preview"]').should('be.visible');
  });

  it('Debería generar reporte de seguridad', () => {
    cy.get('[data-report-type="seguridad"]').click();

    cy.get('[name="report-security-type"]').select('intentos-fallidos');
    cy.get('[name="report-period"]').select('ultimos-7-dias');

    cy.get('[data-action="generate-report"]').click();

    cy.get('[data-panel="report-preview"]').should('be.visible');
    cy.get('[data-panel="report-preview"]').should('contain', 'Seguridad');
  });

  it('Debería exportar reporte a PDF', () => {
    // Generar reporte primero
    cy.get('[data-report-type="accesos"]').click();
    cy.get('[name="report-period"]').select('hoy');
    cy.get('[data-action="generate-report"]').click();

    cy.wait(500);

    // Exportar a PDF
    cy.get('[data-action="export-pdf"]').click();

    cy.verifySuccess('Reporte exportado a PDF');
  });

  it('Debería exportar reporte a Excel', () => {
    cy.get('[data-report-type="visitas"]').click();
    cy.get('[name="report-period"]').select('este-mes');
    cy.get('[data-action="generate-report"]').click();

    cy.wait(500);

    cy.get('[data-action="export-excel"]').click();

    cy.verifySuccess('Reporte exportado a Excel');
  });

  it('Debería exportar reporte a CSV', () => {
    cy.get('[data-report-type="personal"]').click();
    cy.get('[data-action="generate-report"]').click();

    cy.wait(500);

    cy.get('[data-action="export-csv"]').click();

    cy.verifySuccess('Reporte exportado a CSV');
  });

  it('Debería permitir programar reportes automáticos', () => {
    cy.get('[data-action="schedule-report"]').click();

    cy.get('[data-modal="schedule-report"]').should('be.visible');

    // Configurar reporte programado
    cy.fillForm({
      'schedule-name': 'Reporte Diario de Accesos',
      'schedule-type': 'accesos',
      'schedule-frequency': 'diario',
      'schedule-time': '08:00',
      'schedule-email': 'admin@example.com'
    });

    cy.get('[data-modal="schedule-report"] [data-action="save"]').click();

    cy.verifySuccess('Reporte programado exitosamente');
  });

  it('Debería listar reportes programados', () => {
    cy.get('[data-action="view-scheduled"]').click();

    cy.get('[data-panel="scheduled-reports"]').should('be.visible');
    cy.get('[data-table="scheduled-reports"]').should('be.visible');
  });

  it('Debería permitir editar reporte programado', () => {
    cy.get('[data-action="view-scheduled"]').click();

    cy.get('[data-table="scheduled-reports"] tbody tr').first().as('firstReport');
    cy.get('@firstReport').find('[data-action="edit"]').click();

    cy.get('[data-modal="schedule-report"]').should('be.visible');

    cy.get('[name="schedule-time"]').clear().type('09:00');
    cy.get('[data-modal="schedule-report"] [data-action="save"]').click();

    cy.verifySuccess('Reporte actualizado exitosamente');
  });

  it('Debería permitir eliminar reporte programado', () => {
    cy.get('[data-action="view-scheduled"]').click();

    cy.get('[data-table="scheduled-reports"] tbody tr').first().as('firstReport');
    cy.get('@firstReport').find('[data-action="delete"]').click();

    cy.get('[data-dialog="confirm-delete"]').should('be.visible');
    cy.get('[data-dialog="confirm-delete"] [data-action="confirm"]').click();

    cy.verifySuccess('Reporte eliminado exitosamente');
  });

  it('Debería mostrar historial de reportes generados', () => {
    cy.get('[data-action="view-history"]').click();

    cy.get('[data-panel="report-history"]').should('be.visible');
    cy.get('[data-table="report-history"]').should('be.visible');

    // Debería mostrar columnas
    cy.get('[data-table="report-history"] thead th').should('contain', 'Tipo');
    cy.get('[data-table="report-history"] thead th').should('contain', 'Fecha');
    cy.get('[data-table="report-history"] thead th').should('contain', 'Usuario');
  });

  it('Debería permitir descargar reporte del historial', () => {
    cy.get('[data-action="view-history"]').click();

    cy.get('[data-table="report-history"] tbody tr').first().as('firstReport');
    cy.get('@firstReport').find('[data-action="download"]').click();

    // Debería iniciar descarga
    cy.verifySuccess('Reporte descargado exitosamente');
  });

  it('Debería filtrar reportes por tipo', () => {
    cy.get('[data-action="view-history"]').click();

    cy.get('[data-filter="report-type"]').select('accesos');
    cy.wait(500);

    cy.get('[data-table="report-history"] [data-field="tipo"]').each(($tipo) => {
      cy.wrap($tipo).should('contain', 'Accesos');
    });
  });

  it('Debería filtrar reportes por rango de fechas', () => {
    cy.get('[data-action="view-history"]').click();

    cy.get('[data-filter="date-from"]').type('2025-01-01');
    cy.get('[data-filter="date-to"]').type('2025-01-20');
    cy.get('[data-action="apply-filter"]').click();

    cy.wait(500);

    cy.get('[data-table="report-history"]').should('be.visible');
  });
});
