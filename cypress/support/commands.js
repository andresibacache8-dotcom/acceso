// cypress/support/commands.js
// Comandos personalizados reutilizables

/**
 * Login con usuario de test
 * Uso: cy.login()
 */
Cypress.Commands.add('login', (username = Cypress.env('testUser'), password = Cypress.env('testPassword')) => {
  cy.visit('/login.html');

  // Rellenar formulario
  cy.get('#username').clear().type(username);
  cy.get('#password').clear().type(password);

  // Enviar
  cy.get('button[type="submit"]').click();

  // Esperar a que se redirija a index.html
  cy.url().should('include', 'index.html');

  // Verificar que el token se guardó
  cy.window().then(win => {
    expect(win.localStorage.getItem('app_access_token')).to.exist;
  });
});

/**
 * Logout del usuario
 * Uso: cy.logout()
 */
Cypress.Commands.add('logout', () => {
  cy.window().then(win => {
    const token = win.localStorage.getItem('app_access_token');
    if (token) {
      cy.request('DELETE', Cypress.env('apiBaseUrl') + '/auth-migrated.php', {
        headers: {
          Authorization: `Bearer ${token}`
        }
      });
    }
    win.localStorage.clear();
  });

  cy.visit('/login.html');
  cy.url().should('include', 'login.html');
});

/**
 * Hacer una solicitud autenticada a la API
 * Uso: cy.apiCall('GET', '/personal-migrated.php')
 */
Cypress.Commands.add('apiCall', (method, endpoint, body = null) => {
  return cy.window().then(win => {
    const token = win.localStorage.getItem('app_access_token');
    const url = Cypress.config('baseUrl') + Cypress.env('apiBaseUrl') + endpoint;

    const options = {
      method,
      url,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      failOnStatusCode: false
    };

    if (body) {
      options.body = body;
    }

    return cy.request(options);
  });
});

/**
 * Esperar y encontrar elemento visible
 * Uso: cy.findVisible('.modal')
 */
Cypress.Commands.add('findVisible', (selector) => {
  return cy.get(selector).should('be.visible');
});

/**
 * Scroll al elemento y hacer click
 * Uso: cy.clickElement('button.save')
 */
Cypress.Commands.add('clickElement', (selector) => {
  cy.get(selector).scrollIntoView().click();
});

/**
 * Llenar un formulario con múltiples campos
 * Uso: cy.fillForm({ name: 'Juan', email: 'juan@test.com' })
 */
Cypress.Commands.add('fillForm', (data) => {
  Object.entries(data).forEach(([key, value]) => {
    cy.get(`[name="${key}"]`).clear().type(value);
  });
});

/**
 * Verificar que un elemento contiene texto
 * Uso: cy.verifyText('h1', 'Bienvenido')
 */
Cypress.Commands.add('verifyText', (selector, text) => {
  cy.get(selector).should('contain', text);
});

/**
 * Esperar y verificar que el modal se cerró
 * Uso: cy.verifyModalClosed()
 */
Cypress.Commands.add('verifyModalClosed', () => {
  cy.get('[role="dialog"]').should('not.exist');
});

/**
 * Verificar notificación de éxito
 * Uso: cy.verifySuccess('Personal creado exitosamente')
 */
Cypress.Commands.add('verifySuccess', (message = null) => {
  cy.get('.alert-success, .toast-success, [role="alert"]')
    .should('be.visible');

  if (message) {
    cy.get('.alert-success, .toast-success, [role="alert"]')
      .should('contain', message);
  }
});

/**
 * Verificar notificación de error
 * Uso: cy.verifyError('Error al crear')
 */
Cypress.Commands.add('verifyError', (message = null) => {
  cy.get('.alert-danger, .toast-error, [role="alert"].error')
    .should('be.visible');

  if (message) {
    cy.get('.alert-danger, .toast-error, [role="alert"].error')
      .should('contain', message);
  }
});
