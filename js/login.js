document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = e.target.username.value;
            const password = e.target.password.value;
            const loginError = document.getElementById('login-error');

            // Usar AuthService para login
            const result = await authService.login(username, password);

            if (result.success) {
                // AuthService maneja almacenamiento de token y datos
                window.location.href = 'index.html';
            } else {
                loginError.textContent = result.message || 'Usuario o contraseña incorrectos.';
                loginError.classList.remove('d-none');
            }
        });
    }
});