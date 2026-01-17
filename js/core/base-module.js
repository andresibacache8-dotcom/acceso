/**
 * BaseModule - Abstract Base Class for All Modules
 *
 * Eliminates 8 duplicate patterns across modules:
 * 1. Modal initialization (40+ lines × 6 modules)
 * 2. Form handling (50+ lines × 7 modules)
 * 3. Table rendering (30+ lines × 8 modules)
 * 4. Event listener setup (30+ lines × 8 modules)
 * 5. Data loading (15+ lines × 8 modules)
 * 6. Search/filter logic (40+ lines × 6 modules)
 * 7. Delete confirmation (10+ lines × 7 modules)
 * 8. Import/export (300+ lines × 3 modules)
 *
 * Modules extend this class and only implement custom logic.
 * Expected 40-50% code reduction per module.
 *
 * Usage:
 *   import { BaseModule } from './base-module.js';
 *   import personalApi from './api/personal-api.js';
 *
 *   export class PersonalModule extends BaseModule {
 *       constructor(contentElement) {
 *           super(contentElement, personalApi);
 *           this.searchFields = ['Nombres', 'Paterno', 'NrRut'];
 *       }
 *
 *       async init() {
 *           this.setupModal('modal-id', window.getTemplate, this.handleSubmit);
 *           this.setupEventListeners();
 *           await this.loadData();
 *       }
 *
 *       renderTable() {
 *           // Custom table rendering
 *       }
 *   }
 *
 * @module BaseModule
 */

import { appState } from './state-manager.js';

export class BaseModule {
    /**
     * Constructor
     *
     * @param {HTMLElement} contentElement Content container for this module
     * @param {Object} apiClient API client instance (personal-api, vehiculos-api, etc.)
     */
    constructor(contentElement, apiClient) {
        this.content = contentElement;
        this.api = apiClient;

        // Data state
        this.data = [];
        this.filteredData = [];

        // Modal instances
        this.modals = new Map();

        // Pagination
        this.currentPage = 1;
        this.recordsPerPage = 50;
        this.totalRecords = 0;

        // Filters and search
        this.filters = {};
        this.searchQuery = '';
        this.sortField = null;
        this.sortDirection = 'asc';

        // Event listeners for cleanup
        this.eventListeners = [];
    }

    /**
     * Initialize module (MUST be implemented by subclass)
     *
     * @abstract
     * @async
     * @returns {Promise<void>}
     */
    async init() {
        throw new Error('init() must be implemented by subclass');
    }

    /**
     * Setup modal with Bootstrap integration
     *
     * Pattern: Modal initialization (40+ lines duplicated × 6 modules)
     *
     * @param {string} modalId HTML element ID of modal
     * @param {Function} templateFn Function that returns modal HTML
     * @param {Function} onSubmit Form submit handler function
     * @returns {bootstrap.Modal} Modal instance
     */
    setupModal(modalId, templateFn, onSubmit) {
        const modalEl = document.getElementById(modalId);

        if (!modalEl) {
            console.warn(`Modal element #${modalId} not found`);
            return null;
        }

        // Create modal if not exists
        if (!this.modals.has(modalId)) {
            // Inject template HTML
            if (templateFn && typeof templateFn === 'function') {
                modalEl.innerHTML = templateFn();
            }

            // Create Bootstrap modal instance
            const instance = new bootstrap.Modal(modalEl);
            this.modals.set(modalId, instance);

            // Setup form submission
            const form = modalEl.querySelector('form');
            if (form && onSubmit) {
                const listener = (e) => {
                    e.preventDefault();
                    onSubmit.call(this, e, instance);
                };
                form.addEventListener('submit', listener);
                this.eventListeners.push({ element: form, event: 'submit', handler: listener });
            }
        }

        return this.modals.get(modalId);
    }

    /**
     * Open modal with optional data
     *
     * @param {string} modalId Modal ID
     * @param {Object} data Optional data to populate form
     * @returns {void}
     */
    openModal(modalId, data = null) {
        const modal = this.modals.get(modalId);

        if (!modal) {
            console.warn(`Modal '${modalId}' not initialized`);
            return;
        }

        // Populate form if data provided
        if (data) {
            this.populateModalForm(modalId, data);
        } else {
            this.clearModalForm(modalId);
        }

        modal.show();
    }

    /**
     * Close modal
     *
     * @param {string} modalId Modal ID
     * @returns {void}
     */
    closeModal(modalId) {
        const modal = this.modals.get(modalId);

        if (modal) {
            modal.hide();
            this.clearModalForm(modalId);
        }
    }

    /**
     * Populate form fields with data
     *
     * Pattern: Form handling (50+ lines duplicated × 7 modules)
     *
     * @param {string} modalId Modal ID
     * @param {Object} data Data object with form values
     * @returns {void}
     */
    populateModalForm(modalId, data) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) return;

        const form = modalEl.querySelector('form');
        if (!form) return;

        // Populate all form fields
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);

            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = Boolean(data[key]);
                } else if (input.type === 'radio') {
                    const radio = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                    if (radio) radio.checked = true;
                } else {
                    input.value = data[key] || '';
                }
            }
        });
    }

    /**
     * Clear form fields
     *
     * @param {string} modalId Modal ID
     * @returns {void}
     */
    clearModalForm(modalId) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) return;

        const form = modalEl.querySelector('form');
        if (!form) return;

        form.reset();
        form.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
            el.classList.remove('is-invalid', 'is-valid');
        });
        form.classList.remove('was-validated');
    }

    /**
     * Load data from API
     *
     * Pattern: Data loading (15+ lines duplicated × 8 modules)
     *
     * @param {Object} filters Optional filter parameters
     * @returns {Promise<void>}
     */
    async loadData(filters = {}) {
        try {
            appState.setLoading(this.getModuleName(), true);

            // Call API
            const response = await this.api.getAll(this.currentPage, this.recordsPerPage, filters);

            if (response.success) {
                this.data = response.data || [];
                this.totalRecords = response.meta?.pagination?.total || this.data.length;
                this.applyFilters();
                this.renderTable();
                appState.clearError(this.getModuleName());
            } else {
                throw new Error(response.error?.message || 'Error loading data');
            }
        } catch (error) {
            console.error('Error loading data:', error);
            appState.setError(this.getModuleName(), error.message);
            window.showToast(error.message || 'Error al cargar datos', 'error');
        } finally {
            appState.setLoading(this.getModuleName(), false);
        }
    }

    /**
     * Apply filters to data
     *
     * Pattern: Search/filter logic (40+ lines duplicated × 6 modules)
     *
     * @returns {void}
     */
    applyFilters() {
        // Apply custom filter from subclass
        this.filteredData = this.data.filter(item => this.filterItem(item));

        // Apply sorting
        if (this.sortField) {
            this.filteredData.sort((a, b) => this.compareItems(a, b));
        }
    }

    /**
     * Filter single item (OVERRIDE in subclass for custom filters)
     *
     * @param {Object} item Item to filter
     * @returns {boolean} True if item passes filter
     */
    filterItem(item) {
        // Subclass can override for custom filtering
        return true;
    }

    /**
     * Compare items for sorting
     *
     * @private
     * @param {Object} a Item A
     * @param {Object} b Item B
     * @returns {number} Sort order
     */
    compareItems(a, b) {
        const aVal = a[this.sortField];
        const bVal = b[this.sortField];

        if (this.sortDirection === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    }

    /**
     * Render table (MUST be implemented by subclass)
     *
     * Pattern: Table rendering (30+ lines duplicated × 8 modules)
     *
     * @abstract
     * @returns {void}
     */
    renderTable() {
        throw new Error('renderTable() must be implemented by subclass');
    }

    /**
     * Setup search input listener
     *
     * @param {string} inputId Input element ID
     * @param {string[]} searchFields Fields to search in
     * @returns {void}
     */
    setupSearch(inputId, searchFields) {
        const searchInput = this.content.querySelector(`#${inputId}`);

        if (!searchInput) {
            console.warn(`Search input #${inputId} not found`);
            return;
        }

        const listener = (e) => {
            this.searchQuery = e.target.value.toLowerCase().trim();
            this.currentPage = 1; // Reset to first page

            this.filteredData = this.data.filter(item => {
                if (!this.searchQuery) return true;

                return searchFields.some(field => {
                    const value = String(item[field] || '').toLowerCase();
                    return value.includes(this.searchQuery);
                });
            });

            this.renderTable();
        };

        searchInput.addEventListener('input', listener);
        this.eventListeners.push({
            element: searchInput,
            event: 'input',
            handler: listener
        });
    }

    /**
     * Setup delete confirmation dialog
     *
     * Pattern: Delete confirmation (10+ lines duplicated × 7 modules)
     *
     * @param {*} id Item ID to delete
     * @param {string} itemName Display name of item (for confirmation)
     * @returns {Promise<void>}
     */
    async confirmDelete(id, itemName) {
        const confirmed = confirm(`¿Estás seguro de eliminar "${itemName}"?`);

        if (!confirmed) {
            return;
        }

        try {
            appState.setLoading('delete', true);

            const response = await this.api.delete(id);

            if (response.success) {
                window.showToast('Eliminado correctamente', 'success');
                await this.loadData();
            } else {
                throw new Error(response.error?.message || 'Error al eliminar');
            }
        } catch (error) {
            console.error('Delete error:', error);
            window.showToast(error.message || 'Error al eliminar', 'error');
        } finally {
            appState.setLoading('delete', false);
        }
    }

    /**
     * Export data to Excel
     *
     * @param {string} filename Filename without extension
     * @returns {void}
     */
    exportToExcel(filename) {
        try {
            if (typeof XLSX === 'undefined') {
                window.showToast('Library XLSX not loaded', 'error');
                return;
            }

            const ws = XLSX.utils.json_to_sheet(this.filteredData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Datos');

            const timestamp = new Date().toISOString().split('T')[0];
            XLSX.writeFile(wb, `${filename}_${timestamp}.xlsx`);

            window.showToast('Archivo exportado correctamente', 'success');
        } catch (error) {
            console.error('Export error:', error);
            window.showToast(error.message || 'Error al exportar', 'error');
        }
    }

    /**
     * Get module name (for state/logs)
     *
     * @protected
     * @returns {string}
     */
    getModuleName() {
        return this.constructor.name;
    }

    /**
     * Render pagination controls
     *
     * Helper method for pagination UI
     *
     * @returns {string} HTML string for pagination
     */
    renderPaginationHTML() {
        const totalPages = Math.ceil(this.totalRecords / this.recordsPerPage);

        if (totalPages <= 1) {
            return '';
        }

        return `
            <nav class="mt-4">
                <ul class="pagination">
                    <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                        <button class="page-link" onclick="this.previousPage()">Anterior</button>
                    </li>
                    <li class="page-item active">
                        <span class="page-link">
                            Página ${this.currentPage} de ${totalPages}
                        </span>
                    </li>
                    <li class="page-item ${this.currentPage === totalPages ? 'disabled' : ''}">
                        <button class="page-link" onclick="this.nextPage()">Siguiente</button>
                    </li>
                </ul>
            </nav>
        `;
    }

    /**
     * Go to next page
     *
     * @returns {Promise<void>}
     */
    async nextPage() {
        const totalPages = Math.ceil(this.totalRecords / this.recordsPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            await this.loadData();
        }
    }

    /**
     * Go to previous page
     *
     * @returns {Promise<void>}
     */
    async previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            await this.loadData();
        }
    }

    /**
     * Cleanup module (called when navigating away)
     *
     * @returns {void}
     */
    destroy() {
        // Remove event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];

        // Dispose modals
        this.modals.forEach(modal => {
            try {
                modal.dispose();
            } catch (error) {
                // Silently fail
            }
        });
        this.modals.clear();

        // Clear data
        this.data = [];
        this.filteredData = [];
    }

    /**
     * Set up event delegation for common actions
     *
     * Pattern: Event listener setup (30+ lines duplicated × 8 modules)
     *
     * @param {string} selector CSS selector for delegated elements
     * @param {string} eventType Event type (click, change, etc.)
     * @param {Function} handler Handler function
     * @returns {void}
     */
    setupDelegatedListener(selector, eventType, handler) {
        const listener = (e) => {
            const target = e.target.closest(selector);
            if (target) {
                handler.call(this, e, target);
            }
        };

        this.content.addEventListener(eventType, listener);
        this.eventListeners.push({
            element: this.content,
            event: eventType,
            handler: listener
        });
    }

    /**
     * Get selected items from checkboxes
     *
     * @param {string} selectorOrName Selector or checkbox name
     * @returns {any[]} Array of selected values
     */
    getSelectedItems(selectorOrName) {
        let selector;

        if (selectorOrName.startsWith('.') || selectorOrName.startsWith('#')) {
            selector = selectorOrName;
        } else {
            selector = `input[name="${selectorOrName}"]:checked`;
        }

        const elements = this.content.querySelectorAll(selector);
        return Array.from(elements).map(el => el.value || el.dataset.id);
    }
}

export default BaseModule;
