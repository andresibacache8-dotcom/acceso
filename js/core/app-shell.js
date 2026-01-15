/**
 * AppShell - Main Application Shell and Orchestration
 *
 * Responsible for:
 * - Checking authentication
 * - Initializing router
 * - Registering all modules
 * - Setting up global event listeners
 * - Managing app lifecycle
 *
 * Replaces the 305-line main-refactored.js with clean separation of concerns.
 *
 * Usage:
 *   import { AppShell } from './app-shell.js';
 *
 *   const app = new AppShell();
 *   app.init();
 *
 * @module AppShell
 */

import { Router } from './router.js';
import { appState } from './state-manager.js';

export class AppShell {
    constructor() {
        this.router = null;
        this.initialized = false;
        this.authCheckInterval = null;
    }

    /**
     * Initialize application
     *
     * @async
     * @returns {Promise<void>}
     */
    async init() {
        try {
            // Prevent double initialization
            if (this.initialized) {
                console.warn('AppShell already initialized');
                return;
            }

            // Check authentication
            await this.checkAuthentication();

            // Initialize UI components
            this.initializeUIComponents();

            // Create router
            const mainContent = document.querySelector('main');
            if (!mainContent) {
                throw new Error('Main content element not found');
            }

            this.router = new Router(mainContent);

            // Register all modules
            await this.registerModules();

            // Setup event listeners
            this.setupEventListeners();

            // Setup global error handler
            this.setupErrorHandler();

            // Show app (hide loading spinner)
            const appElement = document.getElementById('app');
            if (appElement) {
                appElement.classList.remove('d-none');
            }

            const splashScreen = document.getElementById('splash-screen');
            if (splashScreen) {
                splashScreen.classList.add('d-none');
            }

            // Mark as initialized
            this.initialized = true;

            console.log('✓ AppShell initialized successfully');

            // Auto-sync state with sessionStorage
            this.setupStatePersistence();

            // Start auth check interval
            this.startAuthCheckInterval();

        } catch (error) {
            console.error('Failed to initialize AppShell:', error);
            this.showErrorPage(error);
        }
    }

    /**
     * Check if user is authenticated
     *
     * @private
     * @async
     * @returns {Promise<void>}
     */
    async checkAuthentication() {
        const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';

        if (!isLoggedIn) {
            console.log('User not authenticated, redirecting to login');
            window.location.href = 'login.html';
            return;
        }

        // Load user data from sessionStorage
        const userJson = sessionStorage.getItem('user');
        if (userJson) {
            try {
                const user = JSON.parse(userJson);
                appState.set('user', user);
                appState.set('isLoggedIn', true);
            } catch (error) {
                console.warn('Failed to parse user data from sessionStorage:', error);
            }
        }
    }

    /**
     * Initialize UI components (notifications, loading, etc.)
     *
     * @private
     * @returns {void}
     */
    initializeUIComponents() {
        // Make global functions available
        window.showToast = this.createToastFunction();
        window.showLoadingSpinner = this.createLoadingSpinnerFunction(true);
        window.hideLoadingSpinner = this.createLoadingSpinnerFunction(false);

        // Setup notification container if not exists
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        // Setup loading spinner if not exists
        let spinner = document.getElementById('loading-spinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loading-spinner';
            spinner.className = 'd-none';
            spinner.innerHTML = `
                <div class="position-fixed top-50 start-50 translate-middle" style="z-index: 9998;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
            document.body.appendChild(spinner);
        }
    }

    /**
     * Create toast notification function
     *
     * @private
     * @returns {Function}
     */
    createToastFunction() {
        return (message, type = 'info', duration = 4000) => {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toastId = `toast-${Date.now()}`;
            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'info': 'bg-info'
            }[type] || 'bg-info';

            const toastHtml = `
                <div id="${toastId}" class="toast ${bgClass} text-white" role="alert" aria-live="assertive">
                    <div class="toast-body">
                        ${message}
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', toastHtml);

            const toastEl = document.getElementById(toastId);
            const bootstrap = window.bootstrap || {};
            const toastInstance = new (bootstrap.Toast || window.Toast)(toastEl, {
                autohide: true,
                delay: duration
            });

            toastInstance.show();

            // Remove element after hide
            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        };
    }

    /**
     * Create loading spinner function
     *
     * @private
     * @param {boolean} show Show or hide spinner
     * @returns {Function}
     */
    createLoadingSpinnerFunction(show) {
        return () => {
            const spinner = document.getElementById('loading-spinner');
            if (!spinner) return;

            if (show) {
                spinner.classList.remove('d-none');
            } else {
                spinner.classList.add('d-none');
            }
        };
    }

    /**
     * Register all application modules
     *
     * @private
     * @async
     * @returns {Promise<void>}
     */
    async registerModules() {
        try {
            // Import and register dashboard
            const { initDashboardModule } = await import('../modules/dashboard.js');
            this.router.register('inicio', initDashboardModule);

            // Import and register personal
            const { initPersonalModule } = await import('../modules/personal.js');
            this.router.register('mantenedor-personal', initPersonalModule);

            // Import and register vehiculos
            const { initVehiculosModule } = await import('../modules/vehiculos.js');
            this.router.register('mantenedor-vehiculos', initVehiculosModule);

            // Import and register empresas
            const { initEmpresasModule } = await import('../modules/empresas.js');
            this.router.register('mantenedor-empresas', initEmpresasModule);

            // Import and register visitas
            const { initVisitasModule } = await import('../modules/visitas.js');
            this.router.register('mantenedor-visitas', initVisitasModule);

            // Import and register comision
            const { initComisionModule } = await import('../modules/comision.js');
            this.router.register('mantenedor-comision', initComisionModule);

            // Import and register control
            const { initControlModule } = await import('../modules/control.js');
            this.router.register('portico', initControlModule);
            this.router.register('control-personal', initControlModule);
            this.router.register('control-vehiculos', initControlModule);
            this.router.register('control-visitas', initControlModule);

            // Import and register horas-extra
            const { initHorasExtraModule } = await import('../modules/horas-extra.js');
            this.router.register('horas-extra', initHorasExtraModule);

            // Import and register reportes
            const { initReportesModule } = await import('../reportes.js');
            this.router.register('reportes', initReportesModule);

            console.log('✓ All modules registered');

        } catch (error) {
            console.error('Error registering modules:', error);
            throw error;
        }
    }

    /**
     * Setup global event listeners
     *
     * @private
     * @returns {void}
     */
    setupEventListeners() {
        // Logout button
        const logoutButton = document.getElementById('logout-button');
        if (logoutButton) {
            logoutButton.addEventListener('click', () => this.handleLogout());
        }

        // Navigation links
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Navigation toggle (mobile)
        const navToggle = document.querySelector('.navbar-toggler');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                const navCollapse = document.querySelector('.navbar-collapse');
                if (navCollapse && navCollapse.classList.contains('show')) {
                    const toggleInstance = new (window.bootstrap?.Collapse || window.Collapse)(navCollapse);
                    toggleInstance.hide();
                }
            });
        }

        // Listen for router events
        window.addEventListener('router:navigated', (e) => {
            console.log(`Navigated from ${e.detail.from} to ${e.detail.to}`);
        });

        window.addEventListener('router:navigation-error', (e) => {
            console.error(`Navigation error for ${e.detail.module}:`, e.detail.error);
            window.showToast(`Error: ${e.detail.error}`, 'error');
        });
    }

    /**
     * Handle navigation click
     *
     * @private
     * @param {Event} e Click event
     * @returns {void}
     */
    handleNavigation(e) {
        const link = e.currentTarget;

        // Skip if it's a submenu toggle
        if (link.getAttribute('data-bs-toggle') === 'collapse') {
            return;
        }

        e.preventDefault();

        const href = link.getAttribute('href');
        if (href && href.startsWith('#')) {
            const moduleId = href.substring(1);
            this.router.navigateTo(moduleId);
        }
    }

    /**
     * Handle logout
     *
     * @private
     * @async
     * @returns {Promise<void>}
     */
    async handleLogout() {
        try {
            // Confirm logout
            if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                return;
            }

            // Clear state and session
            appState.reset();
            sessionStorage.clear();
            localStorage.clear();

            // Cleanup
            if (this.router) {
                this.router.dispose();
            }

            if (this.authCheckInterval) {
                clearInterval(this.authCheckInterval);
            }

            // Redirect to login
            window.location.href = 'login.html';

        } catch (error) {
            console.error('Error during logout:', error);
            window.showToast('Error al cerrar sesión', 'error');
        }
    }

    /**
     * Setup global error handler
     *
     * @private
     * @returns {void}
     */
    setupErrorHandler() {
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
            appState.setError('global', event.error);
        });

        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            appState.setError('global', event.reason);
        });
    }

    /**
     * Setup state persistence
     *
     * @private
     * @returns {void}
     */
    setupStatePersistence() {
        // Persist user on login state change
        appState.subscribe('isLoggedIn', (isLoggedIn) => {
            sessionStorage.setItem('isLoggedIn', isLoggedIn ? 'true' : 'false');
        });

        // Persist user data
        appState.subscribe('user', (user) => {
            if (user) {
                sessionStorage.setItem('user', JSON.stringify(user));
            } else {
                sessionStorage.removeItem('user');
            }
        });

        // Persist current module
        appState.subscribe('currentModule', (module) => {
            sessionStorage.setItem('currentModule', module || '');
        });
    }

    /**
     * Start periodic authentication check
     *
     * @private
     * @returns {void}
     */
    startAuthCheckInterval() {
        // Check every 5 minutes
        this.authCheckInterval = setInterval(() => {
            const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';
            if (!isLoggedIn && this.initialized) {
                console.log('Session expired, redirecting to login');
                this.handleLogout();
            }
        }, 5 * 60 * 1000);
    }

    /**
     * Show error page
     *
     * @private
     * @param {Error} error Error object
     * @returns {void}
     */
    showErrorPage(error) {
        const appElement = document.getElementById('app');
        if (appElement) {
            appElement.innerHTML = `
                <div class="container mt-5">
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Error de Inicialización</h4>
                        <p>${error.message}</p>
                        <hr>
                        <p class="mb-0">Por favor, recarga la página o contacta al administrador.</p>
                    </div>
                </div>
            `;
            appElement.classList.remove('d-none');
        }
    }

    /**
     * Dispose app (cleanup)
     *
     * @returns {void}
     */
    dispose() {
        if (this.router) {
            this.router.dispose();
        }

        if (this.authCheckInterval) {
            clearInterval(this.authCheckInterval);
        }

        appState.reset();
        this.initialized = false;
    }
}

export default AppShell;
