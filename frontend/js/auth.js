/**
 * auth.js — Lógica de autenticación con Google OAuth.
 */

/**
 * Callback de Google Sign-In.
 * Recibe el ID Token y lo envía al backend para autenticación.
 */
async function handleGoogleAuth(response) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.style.display = 'none';

    try {
        const res = await fetch(API.AUTH + '/google', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_token: response.credential }),
        });

        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.error || 'Error de autenticación');
        }

        // Guardar token y datos del usuario
        setToken(data.token);
        setUserData(data.user);

        // Mostrar interfaz de usuario autenticado
        showUserSection(data.user);

    } catch (error) {
        errorDiv.textContent = error.message;
        errorDiv.style.display = 'block';
    }
}

/**
 * Muestra la sección de usuario autenticado y oculta el login.
 */
function showUserSection(user) {
    console.log('[Auth] Usuario autenticado:', user); // debug temporal

    document.getElementById('login-section').style.display = 'none';
    document.getElementById('user-section').style.display = 'block';

    document.getElementById('user-name').textContent = user.name || user.email;
    document.getElementById('user-email').textContent = user.email;

    var avatar = document.getElementById('user-avatar');
    if (user.avatar_url) {
        avatar.src = user.avatar_url;
        avatar.onerror = function() {
            // Si falla la imagen de Google, mostrar inicial del nombre
            avatar.style.display = 'none';
            var fallback = document.createElement('span');
            fallback.textContent = (user.name || user.email).charAt(0).toUpperCase();
            fallback.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:50px;height:50px;border-radius:50%;background:#4285f4;color:#fff;font-size:22px;font-weight:bold;';
            avatar.parentNode.insertBefore(fallback, avatar);
        };
    } else {
        avatar.style.display = 'none';
    }

    // Mostrar botón de admin si el usuario es admin
    if (user.role === 'admin') {
        document.getElementById('btn-admin').style.display = 'inline-block';
    }
}

/**
 * Cierra sesión: elimina el token y recarga la página.
 */
function logout() {
    removeToken();
    window.location.reload();
}

/**
 * Al cargar la página, verificar si ya hay sesión activa.
 */
window.addEventListener('DOMContentLoaded', function() {
    const user = getUserData();
    const token = getToken();

    if (user && token) {
        showUserSection(user);
    }
});
