document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = e.target.username.value;
            const password = e.target.password.value;
            const loginError = document.getElementById('login-error');

            const result = await api.loginUser(username, password);

            if (result.success) {
                sessionStorage.setItem('isLoggedIn', 'true');
                sessionStorage.setItem('userId', result.user.id);
                sessionStorage.setItem('username', result.user.username);
                sessionStorage.setItem('userRole', result.user.role);
                window.location.href = 'index.html';
            } else {
                loginError.textContent = result.message || 'Usuario o contraseña incorrectos.';
                loginError.classList.remove('d-none');
            }
        });
    }
});