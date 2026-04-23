/* ================================================================
   UPT Connect — API Client
   Centraliza todas las llamadas a los 4 microservicios
================================================================= */

const API = {
  auth:   'http://localhost:8001/api',
  posts:  'http://localhost:8002/api',
  social: 'http://localhost:8003/api',
  chat:   'http://localhost:8004/api/chat',
};

/* ── Token helpers ───────────────────────────────────────────── */
function getToken() { return localStorage.getItem('upt_token'); }
function getUser()  { const u = localStorage.getItem('upt_user'); return u ? JSON.parse(u) : null; }
function isLoggedIn() { return !!getToken(); }

function authHeaders() {
  return { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}` };
}

/* ── Generic fetch ───────────────────────────────────────────── */
async function apiFetch(url, options = {}) {
  try {
    const res = await fetch(url, { headers: authHeaders(), ...options });
    if (res.status === 401) { logout(); return; }
    const data = await res.json();
    return { ok: res.ok, status: res.status, data };
  } catch (e) {
    console.error('API error:', e);
    return { ok: false, data: { error: 'Error de conexión' } };
  }
}

/* ── Auth Service ─────────────────────────────────────────────── */
const AuthAPI = {
  googleLogin: (credential) => apiFetch(`${API.auth}/auth/google`, {
    method: 'POST', body: JSON.stringify({ credential })
  }),
  completeProfile: (data) => apiFetch(`${API.auth}/auth/complete-profile`, {
    method: 'POST', body: JSON.stringify(data)
  }),
  getProfile: (userId) => {
    if (!userId) return apiFetch(`${API.auth}/auth/me`);
    return apiFetch(`${API.auth}/auth/users/${userId}`);
  },
  updateProfile: (data) => {
    if (data instanceof FormData) {
      // Need a special form data fetcher
      return fetch(`${API.auth}/auth/profile`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${getToken()}` },
        body: data
      }).then(res => res.json()).then(data => ({ ok: true, data })).catch(e => ({ ok: false }));
    }
    return apiFetch(`${API.auth}/auth/profile`, {
      method: 'PUT', body: JSON.stringify(data)
    });
  },
  updateAcademic: (userId, data) => apiFetch(`${API.auth}/auth/admin/users/${userId}/academic`, {
    method: 'PUT', body: JSON.stringify(data)
  }),
  listUsers: (params = '') => apiFetch(`${API.auth}/auth/users?${params}`),
};

/* ── Posts Service ────────────────────────────────────────────── */
const PostsAPI = {
  getFeed: (page = 1) => apiFetch(`${API.posts}/posts?page=${page}`),
  createPost: (data) => {
    if (data.imageFile) {
      const fd = new FormData();
      fd.append('content', data.content);
      fd.append('image', data.imageFile);
      if (data.visibility) fd.append('visibility', data.visibility);
      return fetch(`${API.posts}/posts`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${getToken()}` },
        body: fd
      }).then(res => res.json()).then(data => ({ ok: true, data })).catch(e => ({ ok: false }));
    }
    return apiFetch(`${API.posts}/posts`, {
      method: 'POST', body: JSON.stringify(data)
    });
  },
  deletePost: (id) => apiFetch(`${API.posts}/${id}`, { method: 'DELETE' }),
  likePost: (id) => apiFetch(`${API.posts}/${id}/like`, { method: 'POST' }),
  getComments: (postId) => apiFetch(`${API.posts}/${postId}/comments`),
  addComment: (postId, content) => apiFetch(`${API.posts}/${postId}/comments`, {
    method: 'POST', body: JSON.stringify({ content })
  }),
  deleteComment: (postId, commentId) => apiFetch(`${API.posts}/${postId}/comments/${commentId}`, { method: 'DELETE' }),
  adminListPosts: () => apiFetch(`${API.posts}/admin`),
};

/* ── Social Service ───────────────────────────────────────────── */
const SocialAPI = {
  getDirectory: (params = '') => apiFetch(`${API.social}/directory?${params}`),
  getFriends: () => apiFetch(`${API.social}/friends`),
  getPendingRequests: () => apiFetch(`${API.social}/friends/pending`),
  sendRequest: (receiverId) => apiFetch(`${API.social}/friends/request`, {
    method: 'POST', body: JSON.stringify({ receiver_id: receiverId })
  }),
  acceptRequest: (requestId) => apiFetch(`${API.social}/friends/${requestId}/accept`, { method: 'PUT' }),
  rejectRequest: (requestId) => apiFetch(`${API.social}/friends/${requestId}/reject`, { method: 'PUT' }),
  removeFriend: (friendId) => apiFetch(`${API.social}/friends/${friendId}`, { method: 'DELETE' }),
};

/* ── Chat Service ─────────────────────────────────────────────── */
const ChatAPI = {
  getInbox: () => apiFetch(`${API.chat}/inbox`),
  getConversation: (userId, limit = 50) => apiFetch(`${API.chat}/messages/${userId}?limit=${limit}`),
  sendMessage: (receiverId, content, imageUrl = null) => apiFetch(`${API.chat}/messages`, {
    method: 'POST', body: JSON.stringify({ receiver_id: receiverId, content, image_url: imageUrl })
  }),
};

/* ── Auth actions ─────────────────────────────────────────────── */
function saveSession(token, user) {
  localStorage.setItem('upt_token', token);
  localStorage.setItem('upt_user', JSON.stringify(user));
}

function logout() {
  localStorage.removeItem('upt_token');
  localStorage.removeItem('upt_user');
  window.location.href = '/index.html';
}

/* ── Faculty color helper ─────────────────────────────────────── */
const FACULTY_COLORS = {
  'Ingeniería de Sistemas':     '#1B2A6B',
  'Medicina Humana':            '#B71C1C',
  'Derecho':                    '#1A237E',
  'Administración de Empresas': '#E65100',
  'Ingeniería Civil':           '#1B5E20',
  'Arquitectura':               '#4A148C',
  'Educación Inicial':          '#006064',
  'Contabilidad':               '#33691E',
};

function getFacultyColor(career) {
  for (const [key, color] of Object.entries(FACULTY_COLORS)) {
    if (career && career.toLowerCase().includes(key.toLowerCase().split(' ')[0].toLowerCase())) return color;
  }
  return '#1B2A6B';
}

function initials(name) {
  if (!name) return 'U';
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

/* ── Toast ────────────────────────────────────────────────────── */
function showToast(msg, type = '') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3600);
}

/* ── Guard: redirect to login if not authenticated ────────────── */
function requireAuth() {
  if (!isLoggedIn()) { window.location.href = '/index.html'; }
}

/* ── Guard: redirect to feed if already authenticated ─────────── */
function requireGuest() {
  if (isLoggedIn()) { window.location.href = '/pages/feed.html'; }
}
