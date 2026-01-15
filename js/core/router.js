/**
 * Router - Application Routing and Navigation Controller
 *
 * Handles:
 * - Module navigation and lazy loading
 * - URL/hash state synchronization
 * - History management
 * - Active state management
 *
 * Usage:
 *   import { Router } from './router.js';
 *   const router = new Router(mainContentElement);
 *
 *   router.register('personal', moduleInitFunction);
 *   router.register('vehiculos', vehiculosInitFunction);
 *
 *   // Navigate
 *   router.navigateTo('personal');
 *   router.back();
 *
 * @module Router
 */

import { appState } from './state-manager.js';

export class Router {
    /**
     * Constructor
     *
     * @param {HTMLElement} contentElement Main content container
     */
    constructor(contentElement) {
        this.contentElement = contentElement;
        this.currentModule = null;
        this.previousModule = null;
        this.moduleLoaders = new Map();
        this.history = [];
        this.maxHistorySize = 50;
        this.isNavigating = false;

        // Bind methods
        this.handleHashChange = this.handleHashChange.bind(this);
        this.handlePopState = this.handlePopState.bind(this);

        // Listen for hash changes
        window.addEventListener('hashchange', this.handleHashChange);
        window.addEventListener('popstate', this.handlePopState);

        // Initialize with current hash or default module
        this.initializeFromHash();
    }

    /**
     * Register a module with its loader function
     *
     * @param {string} moduleId Module identifier (e.g., 'personal', 'vehiculos')
     * @param {Function} loaderFn Async function(contentElement) that initializes module
     * @returns {void}
     */
    register(moduleId, loaderFn) {
        if (typeof loaderFn !== 'function') {
            throw new Error(`Module loader for '${moduleId}' must be a function`);
        }

        this.moduleLoaders.set(moduleId, loaderFn);
    }

    /**
     * Navigate to a module
     *
     * @param {string} moduleId Module to navigate to
     * @param {Object} options Navigation options
     * @param {boolean} options.replace Replace history instead of push
     * @param {boolean} options.skipHistory Don't add to history
     * @returns {Promise<void>}
     */
    async navigateTo(moduleId, options = {}) {
        // Prevent duplicate navigation
        if (this.currentModule === moduleId && !options.skipHistory) {
            return;
        }

        // Prevent concurrent navigation
        if (this.isNavigating) {
            console.warn(`Navigation already in progress, ignoring navigateTo('${moduleId}')`);
            return;
        }

        this.isNavigating = true;

        try {
            // Check if module is registered
            if (!this.moduleLoaders.has(moduleId)) {
                throw new Error(`Module '${moduleId}' is not registered`);
            }

            // Update history
            if (!options.skipHistory) {
                if (this.currentModule) {
                    this.previousModule = this.currentModule;
                    this.history.push(this.currentModule);

                    // Limit history size
                    if (this.history.length > this.maxHistorySize) {
                        this.history.shift();
                    }
                }
            }

            // Store current module
            const previousModule = this.currentModule;
            this.currentModule = moduleId;

            // Update state
            appState.set('currentModule', moduleId);
            appState.set('previousModule', previousModule);

            // Load module template
            this.loadModuleTemplate(moduleId);

            // Update navigation UI
            this.updateNavigationUI(moduleId);

            // Set loading state
            appState.setLoading(moduleId, true);

            // Call module loader
            const loaderFn = this.moduleLoaders.get(moduleId);
            await loaderFn(this.contentElement);

            // Update hash
            if (window.location.hash !== `#${moduleId}`) {
                if (options.replace) {
                    window.history.replaceState({ module: moduleId }, '', `#${moduleId}`);
                } else {
                    window.history.pushState({ module: moduleId }, '', `#${moduleId}`);
                }
            }

            // Clear loading state
            appState.setLoading(moduleId, false);

            // Emit event
            this.emit('navigated', { from: previousModule, to: moduleId });

        } catch (error) {
            console.error(`Error navigating to module '${moduleId}':`, error);
            appState.setError(moduleId, error.message);
            appState.setLoading(moduleId, false);

            // Restore previous module
            this.currentModule = this.previousModule;
            this.emit('navigation-error', { module: moduleId, error });
        } finally {
            this.isNavigating = false;
        }
    }

    /**
     * Load module template into content element
     *
     * @private
     * @param {string} moduleId Module ID
     * @returns {void}
     */
    loadModuleTemplate(moduleId) {
        // Try to get template from window (legacy)
        const templateFnName = this.getTemplateFunction(moduleId);

        if (typeof window[templateFnName] === 'function') {
            this.contentElement.innerHTML = window[templateFnName]();
        } else {
            // Fallback: empty content, module will build it
            this.contentElement.innerHTML = `<div id="module-${moduleId}" class="module-container"></div>`;
        }
    }

    /**
     * Get template function name for module
     *
     * @private
     * @param {string} moduleId Module ID
     * @returns {string} Template function name
     */
    getTemplateFunction(moduleId) {
        const templates = {
            'inicio': 'getInicioTemplate',
            'portico': 'getPorticoTemplate',
            'mantenedor-personal': 'getMantenedorPersonalTemplate',
            'control-personal': 'getControlPersonalTemplate',
            'mantenedor-vehiculos': 'getMantenedorVehiculosTemplate',
            'control-vehiculos': 'getControlVehiculosTemplate',
            'mantenedor-visitas': 'getMantenedorVisitasTemplate',
            'mantenedor-comision': 'getMantenedorComisionTemplate',
            'control-visitas': 'getControlVisitasTemplate',
            'estado-actual': 'getEstadoActualTemplate',
            'horas-extra': 'getHorasExtraTemplate',
            'reportes': 'getReportesTemplate',
            'guardia-servicio': 'getGuardiaServicioTemplate'
        };

        return templates[moduleId] || `get${this.capitalize(moduleId)}Template`;
    }

    /**
     * Update navigation UI
     *
     * @private
     * @param {string} moduleId Module ID
     * @returns {void}
     */
    updateNavigationUI(moduleId) {
        // Remove active class from all nav links
        const allLinks = document.querySelectorAll('.nav-link');
        allLinks.forEach(link => {
            link.classList.remove('active');
        });

        // Add active class to matching link
        const targetLink = document.querySelector(`a[href="#${moduleId}"]`);
        if (targetLink) {
            targetLink.classList.add('active');

            // Expand parent submenu if exists
            const parentCollapse = targetLink.closest('.collapse');
            if (parentCollapse) {
                const parentToggle = document.querySelector(`a[href="#${parentCollapse.id}"]`);
                if (parentToggle) {
                    parentToggle.classList.add('active');

                    // Toggle parent collapse
                    const bsCollapse = new bootstrap.Collapse(parentCollapse, { toggle: false });
                    bsCollapse.show();
                }
            }
        }
    }

    /**
     * Go back to previous module
     *
     * @returns {Promise<void>}
     */
    async back() {
        if (this.history.length === 0) {
            console.warn('No history to go back');
            return;
        }

        const previousModule = this.history.pop();
        await this.navigateTo(previousModule, { skipHistory: true });
    }

    /**
     * Go forward in history
     *
     * @returns {void}
     */
    forward() {
        window.history.forward();
    }

    /**
     * Handle hash change event
     *
     * @private
     * @returns {Promise<void>}
     */
    async handleHashChange() {
        const hash = window.location.hash.substring(1) || 'inicio';

        if (hash !== this.currentModule) {
            await this.navigateTo(hash, { skipHistory: false });
        }
    }

    /**
     * Handle browser back/forward button
     *
     * @private
     * @param {PopStateEvent} event Pop state event
     * @returns {Promise<void>}
     */
    async handlePopState(event) {
        if (event.state && event.state.module) {
            await this.navigateTo(event.state.module, { skipHistory: true });
        }
    }

    /**
     * Initialize router from current hash
     *
     * @private
     * @returns {Promise<void>}
     */
    async initializeFromHash() {
        const hash = window.location.hash.substring(1) || 'inicio';
        await this.navigateTo(hash, { skipHistory: true });
    }

    /**
     * Get current module
     *
     * @returns {string|null} Current module ID
     */
    getCurrentModule() {
        return this.currentModule;
    }

    /**
     * Get navigation history
     *
     * @returns {string[]} Array of module IDs in history
     */
    getHistory() {
        return [...this.history];
    }

    /**
     * Clear history
     *
     * @returns {void}
     */
    clearHistory() {
        this.history = [];
    }

    /**
     * Check if module is registered
     *
     * @param {string} moduleId Module ID
     * @returns {boolean}
     */
    isRegistered(moduleId) {
        return this.moduleLoaders.has(moduleId);
    }

    /**
     * Get list of registered modules
     *
     * @returns {string[]} Array of module IDs
     */
    getRegisteredModules() {
        return Array.from(this.moduleLoaders.keys());
    }

    /**
     * Capitalize string
     *
     * @private
     * @param {string} str String to capitalize
     * @returns {string}
     */
    capitalize(str) {
        return str
            .split('-')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join('');
    }

    /**
     * Simple event emitter
     *
     * @private
     * @param {string} eventName Event name
     * @param {Object} detail Event details
     * @returns {void}
     */
    emit(eventName, detail) {
        const event = new CustomEvent(`router:${eventName}`, { detail });
        window.dispatchEvent(event);
    }

    /**
     * Dispose router (cleanup)
     *
     * @returns {void}
     */
    dispose() {
        window.removeEventListener('hashchange', this.handleHashChange);
        window.removeEventListener('popstate', this.handlePopState);
        this.moduleLoaders.clear();
        this.history = [];
    }
}

export default Router;
