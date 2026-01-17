/// <reference types="cypress" />

describe('🚗 Vehículos - CRUD Operations', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/index.html');
    cy.get('[data-section="vehiculos"]').click();
    cy.url().should('include', 'index.html');
  });

  it('Debería cargar la lista de vehículos', () => {
    cy.get('[data-table="vehiculos"]').should('be.visible');
    cy.get('[data-action="new-vehiculo"]').should('be.visible');
  });

  it('Debería abrir modal para crear nuevo vehículo', () => {
    cy.get('[data-action="new-vehiculo"]').click();
    cy.get('[data-modal="vehiculo-form"]').should('be.visible');
    cy.get('[data-modal="vehiculo-form"] h2').should('contain', 'Nuevo Vehículo');
  });

  it('Debería validar formato de patente', () => {
    cy.get('[data-action="new-vehiculo"]').click();

    // Intentar con patente inválida
    cy.fillForm({
      'vehiculo-patente': 'INVALIDO'
    });

    cy.get('[data-modal="vehiculo-form"] [data-action="save"]').click();

    // Debería mostrar error de validación
    cy.get('[data-modal="vehiculo-form"] [role="alert"]')
      .should('be.visible')
      .should('contain', 'patente');
  });

  it('Debería crear nuevo vehículo exitosamente', () => {
    const testData = {
      patente: 'AA1234',
      marca: 'Toyota',
      modelo: 'Corolla',
      anio: '2020',
      color: 'Blanco',
      propietario_rut: '12345678-9'
    };

    cy.get('[data-action="new-vehiculo"]').click();

    cy.fillForm({
      'vehiculo-patente': testData.patente,
      'vehiculo-marca': testData.marca,
      'vehiculo-modelo': testData.modelo,
      'vehiculo-anio': testData.anio,
      'vehiculo-color': testData.color,
      'vehiculo-propietario': testData.propietario_rut
    });

    cy.get('[data-modal="vehiculo-form"] [data-action="save"]').click();

    cy.verifySuccess('Vehículo creado exitosamente');
    cy.verifyModalClosed();

    cy.get('[data-table="vehiculos"]')
      .should('contain', testData.patente)
      .should('contain', testData.marca);
  });

  it('Debería editar vehículo existente', () => {
    cy.get('[data-table="vehiculos"] tbody tr').first().as('firstRow');
    cy.get('@firstRow').find('[data-action="edit"]').click();

    cy.get('[data-modal="vehiculo-form"]').should('be.visible');
    cy.get('[data-modal="vehiculo-form"] h2').should('contain', 'Editar');

    cy.get('[name="vehiculo-color"]').clear().type('Rojo');

    cy.get('[data-modal="vehiculo-form"] [data-action="save"]').click();

    cy.verifySuccess('Vehículo actualizado exitosamente');
    cy.verifyModalClosed();

    cy.get('@firstRow').should('contain', 'Rojo');
  });

  it('Debería buscar vehículo por patente', () => {
    cy.get('[data-search="vehiculos"]').type('AA1234');
    cy.wait(500);

    cy.get('[data-table="vehiculos"] tbody tr').each(($row) => {
      cy.wrap($row).should('contain', 'AA1234');
    });
  });

  it('Debería filtrar vehículos por marca', () => {
    cy.get('[data-filter="vehiculo-marca"]').select('Toyota');
    cy.wait(500);

    cy.get('[data-table="vehiculos"] [data-field="marca"]').each(($marca) => {
      cy.wrap($marca).should('contain', 'Toyota');
    });
  });

  it('Debería filtrar vehículos por estado', () => {
    cy.get('[data-filter="vehiculo-estado"]').select('activo');
    cy.wait(500);

    cy.get('[data-table="vehiculos"] [data-field="estado"]').each(($estado) => {
      cy.wrap($estado).should('contain', 'Activo');
    });
  });

  it('Debería registrar historial de acceso del vehículo', () => {
    // Click en primer vehículo
    cy.get('[data-table="vehiculos"] tbody tr').first().click();

    // Verificar que se muestra historial
    cy.get('[data-panel="vehiculo-historial"]').should('be.visible');
    cy.get('[data-panel="vehiculo-historial"] h3').should('contain', 'Historial');

    // Debería mostrar tabla de accesos
    cy.get('[data-table="vehiculo-accesos"]').should('be.visible');
  });

  it('Debería eliminar vehículo con confirmación', () => {
    cy.get('[data-table="vehiculos"] tbody tr').first().as('firstRow');
    cy.get('@firstRow').find('[data-field="patente"]').invoke('text').as('patenteToDelete');

    cy.get('@firstRow').find('[data-action="delete"]').click();

    cy.get('[data-dialog="confirm-delete"]').should('be.visible');
    cy.get('[data-dialog="confirm-delete"] [data-action="confirm"]').click();

    cy.verifySuccess('Vehículo eliminado exitosamente');

    cy.get('@patenteToDelete').then((patente) => {
      cy.get('[data-table="vehiculos"]').should('not.contain', patente);
    });
  });

  it('Debería exportar lista de vehículos', () => {
    cy.get('[data-action="export-vehiculos"]').click();
    cy.verifySuccess('Vehículos exportados exitosamente');
  });

  it('Debería mostrar detalles completos del vehículo', () => {
    cy.get('[data-table="vehiculos"] tbody tr').first().click();

    cy.get('[data-panel="vehiculo-detail"]').should('be.visible');
    cy.get('[data-panel="vehiculo-detail"]').should('contain', 'Patente');
    cy.get('[data-panel="vehiculo-detail"]').should('contain', 'Marca');
    cy.get('[data-panel="vehiculo-detail"]').should('contain', 'Modelo');
    cy.get('[data-panel="vehiculo-detail"]').should('contain', 'Año');
    cy.get('[data-panel="vehiculo-detail"]').should('contain', 'Color');
  });

  it('Debería mostrar historial de accesos del vehículo', () => {
    cy.get('[data-table="vehiculos"] tbody tr').first().click();

    cy.get('[data-panel="vehiculo-historial"]').should('be.visible');
    cy.get('[data-table="vehiculo-accesos"]').should('be.visible');

    // Debería mostrar columnas de historial
    cy.get('[data-table="vehiculo-accesos"] thead th').should('contain', 'Fecha');
    cy.get('[data-table="vehiculo-accesos"] thead th').should('contain', 'Hora');
    cy.get('[data-table="vehiculo-accesos"] thead th').should('contain', 'Pórtico');
  });
});
