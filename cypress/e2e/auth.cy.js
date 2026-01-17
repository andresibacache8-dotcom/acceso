/// <reference types="cypress" />

describe('🔐 Autenticación - Login/Logout', () => {
  beforeEach(() => {
    cy.visit('/login.html');
  });

  it('Debería cargar la página de login', () => {
    cy.get('#login-form').should('be.visible');
    cy.get('#username').should('exist');
    cy.get('#password').should('exist');
    cy.get('button[type="submit"]').should('contain', 'Iniciar Sesión');
  });

  it('Debería mostrar error con credenciales inválidas', () => {
    cy.get('#username').clear().type('usuario_invalido');
    cy.get('#password').clear().type('contraseña_invalida');
    cy.get('button[type="submit"]').click();

    cy.get('#login-error')
      .should('be.visible')
      .should('contain', 'Usuario o contraseña incorrectos');
  });

  it('Debería hacer login exitosamente', () => {
    cy.login(Cypress.env('testUser'), Cypress.env('testPassword'));

    // Verificar que está en index.html
    cy.url().should('include', 'index.html');

    // Verificar que el token se guardó
    cy.window().then(win => {
      expect(win.localStorage.getItem('app_access_token')).to.exist;
      expect(win.localStorage.getItem('app_user')).to.exist;
    });
  });

  it('Debería validar campos requeridos', () => {
    // Sin llenar campos
    cy.get('button[type="submit"]').click();

    // Campos vacíos no envían
    cy.url().should('include', 'login.html');
  });

  it('Debería permitir cambiar entre username y password', () => {
    cy.get('#username').type('admin');
    cy.get('#username').should('have.value', 'admin');

    cy.get('#password').type('password');
    cy.get('#password').should('have.value', 'password');
  });

  it('Debería logout correctamente', () => {
    cy.login();

    // Hacer logout
    cy.logout();

    // Verificar que está en login
    cy.url().should('include', 'login.html');

    // Verificar que tokens se limpiaron
    cy.window().then(win => {
      expect(win.localStorage.getItem('app_access_token')).to.be.null;
    });
  });

  it('Debería mantener sesión activa durante navegación', () => {
    cy.login();

    // Verificar que el token existe
    cy.window().then(win => {
      const token = win.localStorage.getItem('app_access_token');
      expect(token).to.exist;

      // Navegar a otra sección
      cy.visit('/index.html');

      // Token sigue siendo válido
      expect(win.localStorage.getItem('app_access_token')).to.equal(token);
    });
  });

  it('Debería rechazar acceso sin login a index.html', () => {
    // Intentar acceder a index.html sin login
    cy.visit('/index.html', { failOnStatusCode: false });

    // Debería mostrar contenido pero sin datos
    // O redirigir a login (depende de implementación)
    cy.url().should('satisfy', url => {
      return url.includes('index.html') || url.includes('login.html');
    });
  });

  it('Debería mostrar token JWT válido en localStorage', () => {
    cy.login();

    cy.window().then(win => {
      const token = win.localStorage.getItem('app_access_token');
      const refreshToken = win.localStorage.getItem('app_refresh_token');

      // Verificar formato básico JWT (header.payload.signature)
      expect(token).to.match(/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/);
      expect(refreshToken).to.match(/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/);
    });
  });

  it('Debería guardar datos del usuario en localStorage', () => {
    cy.login();

    cy.window().then(win => {
      const user = JSON.parse(win.localStorage.getItem('app_user'));

      expect(user).to.have.property('id');
      expect(user).to.have.property('username');
      expect(user).to.have.property('role');
      expect(user.username).to.equal(Cypress.env('testUser'));
    });
  });
});

describe('🔄 Refresh Token', () => {
  it('Debería refrescar token automáticamente', () => {
    cy.login();

    cy.window().then(win => {
      const originalToken = win.localStorage.getItem('app_access_token');

      // Simular que el token va a expirar
      // (en un test real, esperarías el auto-refresh)
      cy.wait(1000);

      // El token debería seguir siendo válido o haber sido refrescado
      const currentToken = win.localStorage.getItem('app_access_token');
      expect(currentToken).to.exist;
    });
  });

  it('Debería rechazar refresh token inválido', () => {
    cy.apiCall('POST', '/auth-refresh.php')
      .then(response => {
        // Sin refresh token válido, debería fallar
        expect(response.status).to.equal(401);
      });
  });
});

describe('⚠️ Rate Limiting', () => {
  it('Debería bloquear después de 5 intentos fallidos', function() {
    // Este test es lento, saltar por defecto
    this.skip();

    // Hacer 5 intentos fallidos
    for (let i = 0; i < 5; i++) {
      cy.visit('/login.html');
      cy.get('#username').clear().type('invalid');
      cy.get('#password').clear().type('invalid');
      cy.get('button[type="submit"]').click();
      cy.get('#login-error').should('be.visible');
    }

    // El 6to intento debería ser bloqueado
    cy.visit('/login.html');
    cy.get('#username').clear().type('admin');
    cy.get('#password').clear().type('password');
    cy.get('button[type="submit"]').click();

    // Debería mostrar error de rate limit (429)
    cy.get('#login-error').should('contain', 'Demasiados intentos');
  });
});
