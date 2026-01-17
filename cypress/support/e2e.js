// cypress/support/e2e.js
// Soporte global para todos los tests E2E

import './commands';

// Antes de cada test
beforeEach(() => {
  // Limpiar storage antes de cada test
  cy.window().then(win => {
    win.localStorage.clear();
    win.sessionStorage.clear();
  });

  // Disable uncaught exception handling para tests
  cy.on('uncaught:exception', (err, runnable) => {
    // Retornar false para no fallar el test
    return false;
  });
});

// Después de cada test
afterEach(() => {
  // Logout automático (opcional)
  cy.window().then(win => {
    if (win.localStorage.getItem('app_access_token')) {
      cy.request('DELETE', '/acceso/api/auth-migrated.php', {
        headers: {
          Authorization: `Bearer ${win.localStorage.getItem('app_access_token')}`
        }
      }).then(() => {
        win.localStorage.clear();
      });
    }
  });
});
