/**
 * js/services/AuthService.js
 *
 * Servicio centralizado de autenticación con JWT
 * Maneja: login, logout, token refresh, almacenamiento seguro
 */

class AuthService {
    constructor() {
        this.TOKEN_KEY = 'app_access_token';
        this.REFRESH_TOKEN_KEY = 'app_refresh_token';
        this.USER_KEY = 'app_user';
        this.REFRESH_THRESHOLD = 5 * 60; // Refrescar si faltan 5 minutos para expirar
        this.tokenRefreshTimeout = null;
    }

    /**
     * Iniciar sesión con credenciales
     */
    async login(username, password) {
        try {
            const response = await fetch('api/auth-migrated.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error?.message || 'Login failed');
            }

            // Guardar tokens y datos del usuario
            this.setTokens(data.data.token, data.data.refreshToken);
            this.setUser(data.data.user);

            // Programar refresh automático
            this.scheduleTokenRefresh();

            return {
                success: true,
                user: data.data.user
            };
        } catch (error) {
            console.error('Login error:', error);
            return {
                success: false,
                message: error.message || 'Error de conexión o credenciales inválidas'
            };
        }
    }

    /**
     * Cerrar sesión
     */
    async logout() {
        try {
            const token = this.getAccessToken();

            if (token) {
                // Llamar endpoint de logout en backend
                await fetch('api/auth-migrated.php', {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
            }

            // Limpiar tokens y datos locales
            this.clearTokens();
            this.clearUser();
            this.cancelTokenRefresh();

            return { success: true };
        } catch (error) {
            console.error('Logout error:', error);
            // Limpiar de todas formas
            this.clearTokens();
            this.clearUser();
            this.cancelTokenRefresh();
            return { success: false };
        }
    }

    /**
     * Refrescar access token usando refresh token
     */
    async refreshAccessToken() {
        try {
            const refreshToken = this.getRefreshToken();

            if (!refreshToken) {
                throw new Error('No refresh token available');
            }

            const response = await fetch('api/auth-refresh.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${refreshToken}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error?.message || 'Token refresh failed');
            }

            // Actualizar tokens
            this.setAccessToken(data.data.token);

            // Reprogramar refresh
            this.scheduleTokenRefresh();

            return { success: true };
        } catch (error) {
            console.error('Token refresh error:', error);
            // Si refresh falla, hacer logout
            this.logout();
            return { success: false };
        }
    }

    /**
     * Obtener access token actual
     */
    getAccessToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    }

    /**
     * Obtener refresh token
     */
    getRefreshToken() {
        return localStorage.getItem(this.REFRESH_TOKEN_KEY);
    }

    /**
     * Guardar tokens en localStorage
     */
    setTokens(accessToken, refreshToken) {
        localStorage.setItem(this.TOKEN_KEY, accessToken);
        if (refreshToken) {
            localStorage.setItem(this.REFRESH_TOKEN_KEY, refreshToken);
        }
    }

    /**
     * Establecer solo access token
     */
    setAccessToken(accessToken) {
        localStorage.setItem(this.TOKEN_KEY, accessToken);
    }

    /**
     * Limpiar tokens de localStorage
     */
    clearTokens() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.REFRESH_TOKEN_KEY);
    }

    /**
     * Guardar datos del usuario
     */
    setUser(user) {
        localStorage.setItem(this.USER_KEY, JSON.stringify(user));
    }

    /**
     * Obtener datos del usuario actual
     */
    getUser() {
        const userJson = localStorage.getItem(this.USER_KEY);
        return userJson ? JSON.parse(userJson) : null;
    }

    /**
     * Limpiar datos del usuario
     */
    clearUser() {
        localStorage.removeItem(this.USER_KEY);
    }

    /**
     * Verificar si usuario está autenticado
     */
    isAuthenticated() {
        return !!this.getAccessToken();
    }

    /**
     * Obtener tiempo de expiración del token
     */
    getTokenExpirationTime() {
        const token = this.getAccessToken();
        if (!token) return null;

        try {
            const parts = token.split('.');
            if (parts.length !== 3) return null;

            const payload = JSON.parse(atob(parts[1]));
            return payload.exp ? payload.exp * 1000 : null; // Convertir a millisegundos
        } catch (error) {
            console.error('Error decoding token:', error);
            return null;
        }
    }

    /**
     * Calcular segundos hasta expiración del token
     */
    getSecondsUntilExpiration() {
        const expirationTime = this.getTokenExpirationTime();
        if (!expirationTime) return null;

        const now = Date.now();
        const secondsLeft = Math.floor((expirationTime - now) / 1000);
        return Math.max(0, secondsLeft);
    }

    /**
     * Programar refresh automático del token
     */
    scheduleTokenRefresh() {
        this.cancelTokenRefresh();

        const secondsLeft = this.getSecondsUntilExpiration();
        if (!secondsLeft || secondsLeft <= 0) return;

        // Refrescar cuando falten REFRESH_THRESHOLD segundos
        const delay = (secondsLeft - this.REFRESH_THRESHOLD) * 1000;

        if (delay > 0) {
            this.tokenRefreshTimeout = setTimeout(() => {
                console.log('Auto-refreshing access token...');
                this.refreshAccessToken();
            }, delay);
        }
    }

    /**
     * Cancelar refresh automático programado
     */
    cancelTokenRefresh() {
        if (this.tokenRefreshTimeout) {
            clearTimeout(this.tokenRefreshTimeout);
            this.tokenRefreshTimeout = null;
        }
    }
}

// Instancia global del servicio
const authService = new AuthService();
