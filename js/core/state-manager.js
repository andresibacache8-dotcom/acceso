/**
 * StateManager - Centralized Application State Management
 *
 * Implements a Singleton pattern with pub-sub for reactive updates.
 * Replaces scattered state variables across modules:
 * - personal.js: let personalData = []
 * - vehiculos.js: let vehiculosData = []
 * - empresas.js: let empresasData = []
 * etc.
 *
 * Usage:
 *   import { appState } from './state-manager.js';
 *
 *   // Set state
 *   appState.set('personal', data);
 *
 *   // Get state
 *   const personal = appState.get('personal');
 *
 *   // Subscribe to changes
 *   const unsubscribe = appState.subscribe('personal', (newVal, oldVal) => {
 *       console.log('Personal data changed:', newVal);
 *   });
 *
 *   // Unsubscribe
 *   unsubscribe();
 *
 * @module StateManager
 */

class StateManager {
    constructor() {
        // Initialize state object with all application data
        this.state = {
            // Authentication
            user: null,
            isLoggedIn: false,

            // Navigation
            currentModule: null,
            previousModule: null,

            // Data collections
            personal: [],
            vehiculos: [],
            visitas: [],
            empresas: [],
            comision: [],
            horasExtra: [],
            dashboardData: null,

            // UI state
            filters: {},
            pagination: {},
            searchQuery: '',

            // Misc
            lastUpdated: {}
        };

        // Subscriber registry: { key: [callback1, callback2, ...] }
        this.subscribers = {};

        // Loading state: { key: boolean }
        this.loading = {};

        // Error state: { key: error }
        this.errors = {};
    }

    /**
     * Get state value by key
     *
     * @param {string} key State key (supports dot notation: 'user.id')
     * @returns {*} State value or undefined
     */
    get(key) {
        if (!key.includes('.')) {
            return this.state[key];
        }

        // Handle dot notation for nested access
        const keys = key.split('.');
        let value = this.state;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return undefined;
            }
        }

        return value;
    }

    /**
     * Set state value and notify subscribers
     *
     * @param {string} key State key
     * @param {*} value New value
     * @returns {void}
     */
    set(key, value) {
        const oldValue = this.get(key);

        // Avoid unnecessary updates
        if (JSON.stringify(oldValue) === JSON.stringify(value)) {
            return;
        }

        if (!key.includes('.')) {
            this.state[key] = value;
        } else {
            // Handle nested keys
            const keys = key.split('.');
            const lastKey = keys.pop();
            let obj = this.state;

            for (const k of keys) {
                if (!(k in obj)) {
                    obj[k] = {};
                }
                obj = obj[k];
            }

            obj[lastKey] = value;
        }

        // Record last update timestamp
        if (!this.state.lastUpdated) {
            this.state.lastUpdated = {};
        }
        this.state.lastUpdated[key] = new Date().toISOString();

        // Notify all subscribers
        this.notify(key, value, oldValue);
    }

    /**
     * Update state object with partial data
     *
     * @param {string} key State key
     * @param {Object} partialData Partial object to merge
     * @returns {void}
     */
    merge(key, partialData) {
        const currentValue = this.get(key);

        if (typeof currentValue !== 'object' || Array.isArray(currentValue)) {
            throw new Error(`Cannot merge into non-object state at key: ${key}`);
        }

        const newValue = {
            ...currentValue,
            ...partialData
        };

        this.set(key, newValue);
    }

    /**
     * Subscribe to state changes
     *
     * @param {string} key State key to watch
     * @param {Function} callback Function(newValue, oldValue) called on change
     * @returns {Function} Unsubscribe function
     */
    subscribe(key, callback) {
        if (typeof callback !== 'function') {
            throw new Error('Callback must be a function');
        }

        if (!this.subscribers[key]) {
            this.subscribers[key] = [];
        }

        this.subscribers[key].push(callback);

        // Return unsubscribe function
        return () => {
            this.subscribers[key] = this.subscribers[key].filter(cb => cb !== callback);
        };
    }

    /**
     * Subscribe to state changes (one-time only)
     *
     * @param {string} key State key to watch
     * @param {Function} callback Function(newValue, oldValue) called once then auto-unsubscribe
     * @returns {Function} Unsubscribe function
     */
    subscribeOnce(key, callback) {
        const unsubscribe = this.subscribe(key, (newVal, oldVal) => {
            callback(newVal, oldVal);
            unsubscribe();
        });

        return unsubscribe;
    }

    /**
     * Notify all subscribers of state change
     *
     * @private
     * @param {string} key State key
     * @param {*} newValue New value
     * @param {*} oldValue Old value
     * @returns {void}
     */
    notify(key, newValue, oldValue) {
        if (!this.subscribers[key]) {
            return;
        }

        // Notify all subscribers asynchronously to prevent blocking
        this.subscribers[key].forEach(callback => {
            try {
                callback(newValue, oldValue);
            } catch (error) {
                console.error(`Error in subscriber for key '${key}':`, error);
            }
        });
    }

    /**
     * Set loading state for a data key
     *
     * @param {string} key State key
     * @param {boolean} isLoading Loading state
     * @returns {void}
     */
    setLoading(key, isLoading) {
        this.loading[key] = isLoading;
        this.notify(`loading:${key}`, isLoading);
    }

    /**
     * Check if data is loading
     *
     * @param {string} key State key
     * @returns {boolean} True if loading
     */
    isLoading(key) {
        return this.loading[key] || false;
    }

    /**
     * Set error state
     *
     * @param {string} key State key
     * @param {Error|string|null} error Error object or message
     * @returns {void}
     */
    setError(key, error) {
        this.errors[key] = error;
        this.notify(`error:${key}`, error);
    }

    /**
     * Get error state
     *
     * @param {string} key State key
     * @returns {Error|string|null} Error object or message
     */
    getError(key) {
        return this.errors[key] || null;
    }

    /**
     * Clear error state
     *
     * @param {string} key State key
     * @returns {void}
     */
    clearError(key) {
        this.setError(key, null);
    }

    /**
     * Append to an array in state
     *
     * @param {string} key State key (must be an array)
     * @param {*} item Item to append
     * @returns {void}
     */
    push(key, item) {
        const currentValue = this.get(key);

        if (!Array.isArray(currentValue)) {
            throw new Error(`State at key '${key}' is not an array`);
        }

        const newValue = [...currentValue, item];
        this.set(key, newValue);
    }

    /**
     * Remove item from array in state
     *
     * @param {string} key State key (must be an array)
     * @param {Function|*} predicate Function to find item or value to match
     * @returns {void}
     */
    remove(key, predicate) {
        const currentValue = this.get(key);

        if (!Array.isArray(currentValue)) {
            throw new Error(`State at key '${key}' is not an array`);
        }

        let newValue;

        if (typeof predicate === 'function') {
            newValue = currentValue.filter((item, index) => !predicate(item, index));
        } else {
            newValue = currentValue.filter(item => item !== predicate);
        }

        this.set(key, newValue);
    }

    /**
     * Update item in array by predicate
     *
     * @param {string} key State key (must be an array)
     * @param {Function} predicate Function to find item
     * @param {Object} updates Partial object to merge
     * @returns {void}
     */
    updateArray(key, predicate, updates) {
        const currentValue = this.get(key);

        if (!Array.isArray(currentValue)) {
            throw new Error(`State at key '${key}' is not an array`);
        }

        const newValue = currentValue.map(item => {
            if (predicate(item)) {
                return { ...item, ...updates };
            }
            return item;
        });

        this.set(key, newValue);
    }

    /**
     * Reset specific state or all state
     *
     * @param {string|null} key State key to reset (null = reset all)
     * @returns {void}
     */
    reset(key = null) {
        if (key === null) {
            // Reset all state to initial values
            this.state = {
                user: null,
                isLoggedIn: false,
                currentModule: null,
                previousModule: null,
                personal: [],
                vehiculos: [],
                visitas: [],
                empresas: [],
                comision: [],
                horasExtra: [],
                dashboardData: null,
                filters: {},
                pagination: {},
                searchQuery: '',
                lastUpdated: {}
            };
            this.subscribers = {};
            this.loading = {};
            this.errors = {};
            this.notify('reset', null, null);
        } else {
            // Reset specific key
            const initialValue = Array.isArray(this.state[key]) ? [] : null;
            this.set(key, initialValue);
        }
    }

    /**
     * Get entire state snapshot
     *
     * @returns {Object} State object
     */
    snapshot() {
        return JSON.parse(JSON.stringify(this.state));
    }

    /**
     * Restore state from snapshot
     *
     * @param {Object} snapshot State snapshot
     * @returns {void}
     */
    restore(snapshot) {
        const oldState = this.snapshot();
        this.state = JSON.parse(JSON.stringify(snapshot));

        // Notify all subscribers of changes
        for (const key of Object.keys(this.state)) {
            if (JSON.stringify(oldState[key]) !== JSON.stringify(this.state[key])) {
                this.notify(key, this.state[key], oldState[key]);
            }
        }
    }

    /**
     * Get size of array or object in state
     *
     * @param {string} key State key
     * @returns {number} Size
     */
    size(key) {
        const value = this.get(key);

        if (Array.isArray(value)) {
            return value.length;
        }

        if (typeof value === 'object' && value !== null) {
            return Object.keys(value).length;
        }

        return 0;
    }

    /**
     * Check if value exists in state
     *
     * @param {string} key State key
     * @param {*} value Value to check
     * @returns {boolean} True if value exists
     */
    has(key, value) {
        const stateValue = this.get(key);

        if (Array.isArray(stateValue)) {
            return stateValue.includes(value);
        }

        if (typeof stateValue === 'object' && stateValue !== null) {
            return Object.values(stateValue).includes(value);
        }

        return stateValue === value;
    }

    /**
     * Clear all data (keep user/auth info)
     *
     * @returns {void}
     */
    clearData() {
        this.state.personal = [];
        this.state.vehiculos = [];
        this.state.visitas = [];
        this.state.empresas = [];
        this.state.comision = [];
        this.state.horasExtra = [];
        this.state.dashboardData = null;
        this.state.filters = {};
        this.state.pagination = {};
        this.loading = {};
        this.errors = {};
    }
}

// Export singleton instance
export const appState = new StateManager();

export default appState;
