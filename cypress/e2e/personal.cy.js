/// <reference types="cypress" />

describe('👥 Personal - CRUD Operations', () => {
  beforeEach(() => {
    // Login antes de cada test
    cy.login();
    // Navegar a la sección de Personal
    cy.visit('/index.html');
    cy.get('[data-section="personal"]').click();
    cy.url().should('include', 'index.html');
  });

  it('Debería cargar la lista de personal', () => {
    // Verificar que se cargó la tabla de personal
    cy.get('[data-table="personal"]').should('be.visible');

    // Verificar que hay botones de acción
    cy.get('[data-action="new-personal"]').should('be.visible');
  });

  it('Debería abrir modal para crear nuevo personal', () => {
    // Click en botón nuevo
    cy.get('[data-action="new-personal"]').click();

    // Verificar que se abrió el modal
    cy.get('[data-modal="personal-form"]').should('be.visible');
    cy.get('[data-modal="personal-form"] h2').should('contain', 'Nuevo Personal');
  });

  it('Debería validar campos requeridos en formulario de personal', () => {
    cy.get('[data-action="new-personal"]').click();

    // Intentar guardar sin rellenar campos
    cy.get('[data-modal="personal-form"] [data-action="save"]').click();

    // Debería mostrar error de validación
    cy.get('[data-modal="personal-form"] [role="alert"]')
      .should('be.visible')
      .should('contain', 'requerido');
  });

  it('Debería crear nuevo personal exitosamente', () => {
    const testData = {
      nombre: 'Juan',
      apellido: 'Pérez',
      rut: '12345678-9',
      email: 'juan.perez@test.com',
      telefono: '912345678'
    };

    cy.get('[data-action="new-personal"]').click();

    // Rellenar formulario
    cy.fillForm({
      'personal-nombre': testData.nombre,
      'personal-apellido': testData.apellido,
      'personal-rut': testData.rut,
      'personal-email': testData.email,
      'personal-telefono': testData.telefono
    });

    // Guardar
    cy.get('[data-modal="personal-form"] [data-action="save"]').click();

    // Verificar éxito
    cy.verifySuccess('Personal creado exitosamente');
    cy.verifyModalClosed();

    // Verificar que aparece en la tabla
    cy.get('[data-table="personal"]')
      .should('contain', testData.nombre)
      .should('contain', testData.rut);
  });

  it('Debería editar personal existente', () => {
    // Buscar primer registro
    cy.get('[data-table="personal"] tbody tr').first().as('firstRow');

    // Click en botón editar
    cy.get('@firstRow').find('[data-action="edit"]').click();

    // Verificar que se abrió modal de edición
    cy.get('[data-modal="personal-form"]').should('be.visible');
    cy.get('[data-modal="personal-form"] h2').should('contain', 'Editar');

    // Modificar campo
    cy.get('[name="personal-telefono"]').clear().type('987654321');

    // Guardar
    cy.get('[data-modal="personal-form"] [data-action="save"]').click();

    // Verificar éxito
    cy.verifySuccess('Personal actualizado exitosamente');
    cy.verifyModalClosed();

    // Verificar cambio en tabla
    cy.get('@firstRow').should('contain', '987654321');
  });

  it('Debería buscar personal por nombre', () => {
    // Rellenar buscador
    cy.get('[data-search="personal"]').type('Juan');

    // Esperar a que se filtre
    cy.wait(500);

    // Verificar que solo muestra resultados relevantes
    cy.get('[data-table="personal"] tbody tr').each(($row) => {
      cy.wrap($row).should('contain', 'Juan');
    });
  });

  it('Debería filtrar personal por estado', () => {
    // Seleccionar filtro
    cy.get('[data-filter="personal-estado"]').select('activo');

    // Esperar filtrado
    cy.wait(500);

    // Verificar que todos los registros muestran estado activo
    cy.get('[data-table="personal"] [data-field="estado"]').each(($state) => {
      cy.wrap($state).should('contain', 'Activo');
    });
  });

  it('Debería eliminar personal con confirmación', () => {
    // Obtener nombre del primer registro para verificación
    cy.get('[data-table="personal"] tbody tr').first().as('firstRow');
    cy.get('@firstRow').find('[data-field="nombre"]').invoke('text').as('nombreToDelete');

    // Click en botón eliminar
    cy.get('@firstRow').find('[data-action="delete"]').click();

    // Verificar diálogo de confirmación
    cy.get('[data-dialog="confirm-delete"]').should('be.visible');
    cy.get('[data-dialog="confirm-delete"]').should('contain', 'seguro');

    // Confirmar eliminación
    cy.get('[data-dialog="confirm-delete"] [data-action="confirm"]').click();

    // Verificar éxito
    cy.verifySuccess('Personal eliminado exitosamente');

    // Verificar que ya no aparece en tabla
    cy.get('@nombreToDelete').then((nombre) => {
      cy.get('[data-table="personal"]').should('not.contain', nombre);
    });
  });

  it('Debería cancelar eliminación de personal', () => {
    // Obtener cantidad inicial de registros
    cy.get('[data-table="personal"] tbody tr').its('length').as('initialCount');

    // Click en botón eliminar
    cy.get('[data-table="personal"] tbody tr').first().find('[data-action="delete"]').click();

    // Cancelar eliminación
    cy.get('[data-dialog="confirm-delete"] [data-action="cancel"]').click();

    // Verificar que no se eliminó
    cy.get('[data-table="personal"] tbody tr').its('length').should('equal', cy.get('@initialCount'));
  });

  it('Debería exportar lista de personal a Excel', () => {
    // Click en botón exportar
    cy.get('[data-action="export-personal"]').click();

    // Verificar que se descargó el archivo
    // Nota: El archivo exacto depende de la configuración del navegador
    cy.verifySuccess('Personal exportado exitosamente');
  });

  it('Debería cargar archivo de personal en lote', () => {
    // Click en botón importar
    cy.get('[data-action="import-personal"]').click();

    // Verificar que se abrió el modal de importación
    cy.get('[data-modal="import-form"]').should('be.visible');

    // Seleccionar archivo (archivo dummy para test)
    cy.get('[name="import-file"]').selectFile('cypress/fixtures/personal-import.csv');

    // Importar
    cy.get('[data-modal="import-form"] [data-action="import"]').click();

    // Verificar éxito
    cy.verifySuccess('Personal importado exitosamente');
  });

  it('Debería mostrar detalles completos del personal', () => {
    // Click en primer registro
    cy.get('[data-table="personal"] tbody tr').first().click();

    // Verificar que se abrió vista de detalles
    cy.get('[data-panel="personal-detail"]').should('be.visible');

    // Verificar que se muestran todos los campos
    cy.get('[data-panel="personal-detail"]').should('contain', 'Nombre');
    cy.get('[data-panel="personal-detail"]').should('contain', 'RUT');
    cy.get('[data-panel="personal-detail"]').should('contain', 'Email');
    cy.get('[data-panel="personal-detail"]').should('contain', 'Teléfono');
    cy.get('[data-panel="personal-detail"]').should('contain', 'Fecha de Creación');
  });
});
