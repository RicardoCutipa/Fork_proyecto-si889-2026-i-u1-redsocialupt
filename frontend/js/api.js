/**
 * api.js — Configuración base y helpers para llamadas HTTP a los microservicios.
 */

const API = {
    AUTH: 'http://localhost:8001/api/auth',
    POSTS: 'http://localhost:8002/api',
    SOCIAL: 'http://localhost:8003/api',
};

/**
 * Obtiene el JWT almacenado en localStorage.
 */
function getToken() {
    return localStorage.getItem('jwt_token');
}

/**
 * Guarda el JWT en localStorage.
 */
function setToken(token) {
    localStorage.setItem('jwt_token', token);
}

/**
 * Elimina el JWT de localStorage.
 */
function removeToken() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('user_data');
}

/**
 * Guarda datos del usuario en localStorage.
 */
function setUserData(user) {
    localStorage.setItem('user_data', JSON.stringify(user));
}

/**
 * Obtiene datos del usuario desde localStorage.
 */
function getUserData() {
    const data = localStorage.getItem('user_data');
    return data ? JSON.parse(data) : null;
}

/**
 * Realiza una petición HTTP autenticada con JWT.
 *
 * @param {string} url
 * @param {object} options - fetch options
 * @returns {Promise<Response>}
 */
async function authFetch(url, options = {}) {
    const token = getToken();
    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
    };

    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    return fetch(url, {
        ...options,
        headers,
    });
}
