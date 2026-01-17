import { defineConfig } from 'cypress';

export default defineConfig({
  e2e: {
    // Base URL de la aplicación
    baseUrl: 'http://localhost/acceso',

    // Timeout para esperar elementos
    defaultCommandTimeout: 10000,

    // Timeout para cargar página
    pageLoadTimeout: 30000,

    // Viewport por defecto
    viewportWidth: 1280,
    viewportHeight: 720,

    // Retries en CI
    retries: {
      runMode: 2,
      openMode: 0,
    },

    // Configuración de video
    video: true,
    videoCompression: 32,
    videosFolder: 'cypress/videos',

    // Configuración de screenshots
    screenshotOnRunFailure: true,
    screenshotsFolder: 'cypress/screenshots',

    // Especificación de archivos de test
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',

    // Soporte
    supportFile: 'cypress/support/e2e.js',

    // Variables de entorno
    env: {
      // Usuario de test
      testUser: 'admin',
      testPassword: 'password',

      // API
      apiBaseUrl: '/acceso/api',

      // Timeouts
      waitTime: 1000,
      slowTime: 2000,
    },

    // Experimentales
    experimentalStudio: true,
  },

  // Configuración global
  chromeWebSecurity: false, // Permitir CORS en tests
  trashAssetsBeforeRuns: true,
});
