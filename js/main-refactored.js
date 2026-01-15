/**
 * main-refactored.js
 * Application Entry Point - SCAD v2.0
 *
 * Simplified entry point that delegates to AppShell for:
 * - Authentication checks
 * - Router initialization
 * - Module registration
 * - Event listeners setup
 * - UI components initialization
 *
 * This file was originally 305 lines with mixed concerns.
 * Now it's just 40 lines - everything else is delegated to core modules.
 *
 * Core modules:
 * - AppShell: Main app orchestration
 * - Router: Navigation and routing
 * - StateManager: Centralized state
 * - BaseModule: Base class for all feature modules
 *
 * @version 3.0 - Refactored with App Shell
 * @author Refactorización 2025
 */

import { AppShell } from './core/app-shell.js';

// Import all template functions to make them globally available
// (Some legacy code may access these from window)
import * as templates from './ui/ui.js';

// Export all template functions to window for legacy compatibility
Object.keys(templates).forEach(key => {
    window[key] = templates[key];
});

/**
 * Initialize application on DOM ready
 */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Create and initialize app shell
        const app = new AppShell();
        await app.init();

        // Store reference to app for debugging
        window._app = app;

        console.log('✓ SCAD Application initialized successfully');
    } catch (error) {
        console.error('Failed to initialize application:', error);
        alert('Error initializing application: ' + error.message);
    }
});
