/// <reference types="cypress" />

describe('📋 Visitas - CRUD Operations', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/index.html');
    cy.get('[data-section="visitas"]').click();
    cy.url().should('include', 'index.html');
  });

  it('Debería cargar la lista de visitas', () => {
    cy.get('[data-table="visitas"]').should('be.visible');
    cy.get('[data-action="new-visita"]').should('be.visible');
  });

  it('Debería abrir modal para registrar nueva visita', () => {
    cy.get('[data-action="new-visita"]').click();
    cy.get('[data-modal="visita-form"]').should('be.visible');
    cy.get('[data-modal="visita-form"] h2').should('contain', 'Nueva Visita');
  });

  it('Debería validar campos requeridos en visita', () => {
    cy.get('[data-action="new-visita"]').click();
    cy.get('[data-modal="visita-form"] [data-action="save"]').click();

    cy.get('[data-modal="visita-form"] [role="alert"]')
      .should('be.visible')
      .should('contain', 'requerido');
  });

  it('Debería registrar nueva visita exitosamente', () => {
    const testData = {
      visitante_nombre: 'Carlos',
      visitante_rut: '98765432-1',
      empresa: 'Acme Corp',
      razon: 'Reunión de negocios',
      fecha_entrada: '2025-01-20',
      hora_entrada: '10:00'
    };

    cy.get('[data-action="new-visita"]').click();

    cy.fillForm({
      'visita-visitante-nombre': testData.visitante_nombre,
      'visita-visitante-rut': testData.visitante_rut,
      'visita-empresa': testData.empresa,
      'visita-razon': testData.razon,
      'visita-fecha-entrada': testData.fecha_entrada,
      'visita-hora-entrada': testData.hora_entrada
    });

    cy.get('[data-modal="visita-form"] [data-action="save"]').click();

    cy.verifySuccess('Visita registrada exitosamente');
    cy.verifyModalClosed();

    cy.get('[data-table="visitas"]')
      .should('contain', testData.visitante_nombre)
      .should('contain', testData.empresa);
  });

  it('Debería registrar hora de salida de visita', () => {
    // Buscar visita activa
    cy.get('[data-table="visitas"]')
      .contains('tr', 'Activa')
      .first()
      .as('activeVisita');

    // Click en botón de salida
    cy.get('@activeVisita').find('[data-action="register-exit"]').click();

    // Debería mostrar modal de confirmación
    cy.get('[data-modal="exit-confirmation"]').should('be.visible');

    // Confirmar salida
    cy.get('[data-modal="exit-confirmation"] [data-action="confirm"]').click();

    cy.verifySuccess('Salida registrada exitosamente');

    // Verificar que el estado cambió a Completada
    cy.get('@activeVisita').should('contain', 'Completada');
  });

  it('Debería editar visita pendiente', () => {
    cy.get('[data-table="visitas"] tbody tr').first().as('firstRow');
    cy.get('@firstRow').find('[data-action="edit"]').click();

    cy.get('[data-modal="visita-form"]').should('be.visible');

    cy.get('[name="visita-razon"]').clear().type('Inspección técnica');

    cy.get('[data-modal="visita-form"] [data-action="save"]').click();

    cy.verifySuccess('Visita actualizada exitosamente');
    cy.verifyModalClosed();

    cy.get('@firstRow').should('contain', 'Inspección técnica');
  });

  it('Debería buscar visitas por nombre del visitante', () => {
    cy.get('[data-search="visitas"]').type('Carlos');
    cy.wait(500);

    cy.get('[data-table="visitas"] tbody tr').each(($row) => {
      cy.wrap($row).should('contain', 'Carlos');
    });
  });

  it('Debería filtrar visitas por estado', () => {
    cy.get('[data-filter="visita-estado"]').select('completada');
    cy.wait(500);

    cy.get('[data-table="visitas"] [data-field="estado"]').each(($estado) => {
      cy.wrap($estado).should('contain', 'Completada');
    });
  });

  it('Debería filtrar visitas por empresa', () => {
    cy.get('[data-filter="visita-empresa"]').select('Acme Corp');
    cy.wait(500);

    cy.get('[data-table="visitas"] [data-field="empresa"]').each(($empresa) => {
      cy.wrap($empresa).should('contain', 'Acme Corp');
    });
  });

  it('Debería filtrar visitas por rango de fechas', () => {
    cy.get('[data-filter="visita-fecha-desde"]').type('2025-01-01');
    cy.get('[data-filter="visita-fecha-hasta"]').type('2025-01-31');
    cy.get('[data-action="apply-filter"]').click();

    cy.wait(500);

    // Verificar que todas las fechas están en el rango
    cy.get('[data-table="visitas"] [data-field="fecha"]').each(($fecha) => {
      cy.wrap($fecha).should('have.text', /2025-01/);
    });
  });

  it('Debería cancelar visita activa', () => {
    cy.get('[data-table="visitas"]')
      .contains('tr', 'Activa')
      .first()
      .as('activeVisita');

    cy.get('@activeVisita').find('[data-action="cancel"]').click();

    cy.get('[data-dialog="confirm-cancel"]').should('be.visible');
    cy.get('[data-dialog="confirm-cancel"] [data-action="confirm"]').click();

    cy.verifySuccess('Visita cancelada exitosamente');

    cy.get('@activeVisita').should('contain', 'Cancelada');
  });

  it('Debería eliminar visita con confirmación', () => {
    cy.get('[data-table="visitas"] tbody tr').first().as('firstRow');
    cy.get('@firstRow').find('[data-field="visitante"]').invoke('text').as('visitanteToDelete');

    cy.get('@firstRow').find('[data-action="delete"]').click();

    cy.get('[data-dialog="confirm-delete"]').should('be.visible');
    cy.get('[data-dialog="confirm-delete"] [data-action="confirm"]').click();

    cy.verifySuccess('Visita eliminada exitosamente');

    cy.get('@visitanteToDelete').then((visitante) => {
      cy.get('[data-table="visitas"]').should('not.contain', visitante);
    });
  });

  it('Debería exportar reporte de visitas', () => {
    cy.get('[data-action="export-visitas"]').click();
    cy.verifySuccess('Visitas exportadas exitosamente');
  });

  it('Debería mostrar detalles completos de visita', () => {
    cy.get('[data-table="visitas"] tbody tr').first().click();

    cy.get('[data-panel="visita-detail"]').should('be.visible');
    cy.get('[data-panel="visita-detail"]').should('contain', 'Visitante');
    cy.get('[data-panel="visita-detail"]').should('contain', 'Empresa');
    cy.get('[data-panel="visita-detail"]').should('contain', 'Razón');
    cy.get('[data-panel="visita-detail"]').should('contain', 'Fecha Entrada');
    cy.get('[data-panel="visita-detail"]').should('contain', 'Hora Salida');
  });

  it('Debería mostrar historial de accesos de la visita', () => {
    cy.get('[data-table="visitas"] tbody tr').first().click();

    cy.get('[data-panel="visita-accesos"]').should('be.visible');
    cy.get('[data-table="visita-log"]').should('be.visible');

    // Debería mostrar puntos de acceso utilizados
    cy.get('[data-table="visita-log"] thead th').should('contain', 'Pórtico');
    cy.get('[data-table="visita-log"] thead th').should('contain', 'Fecha/Hora');
  });

  it('Debería generar badge de visitante', () => {
    cy.get('[data-table="visitas"] tbody tr').first().as('firstRow');
    cy.get('@firstRow').find('[data-action="generate-badge"]').click();

    cy.get('[data-modal="badge-preview"]').should('be.visible');
    cy.get('[data-modal="badge-preview"]').should('contain', 'Badge');

    // Verificar que se puede descargar el badge
    cy.get('[data-modal="badge-preview"] [data-action="download"]').should('be.visible');
  });
});
