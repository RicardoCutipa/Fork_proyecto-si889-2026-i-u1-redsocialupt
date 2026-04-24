/* ================================================================
   UPT Connect — API Client
   Centraliza todas las llamadas a los 4 microservicios
================================================================= */

const API = {
  auth:   '/api',
  posts:  '/api/posts',
  social: '/api',
  chat:   '/api/chat',
};

/* ── Token helpers ───────────────────────────────────────────── */
function getToken() { return localStorage.getItem('upt_token'); }
function getUser()  { const u = localStorage.getItem('upt_user'); return u ? JSON.parse(u) : null; }
function isLoggedIn() { return !!getToken(); }

function authHeaders() {
  return { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}` };
}

/* Normaliza respuesta: array directo o {data:[...]} paginado */
function getList(result) {
  if (!result || !result.ok) return [];
  if (Array.isArray(result.data)) return result.data;
  if (result.data && Array.isArray(result.data.data)) return result.data.data;
  return [];
}

/* ── Generic fetch (JSON) ────────────────────────────────────── */
async function apiFetch(url, options = {}) {
  try {
    const res = await fetch(url, { headers: authHeaders(), ...options });
    if (res.status === 401) { logout(); return; }
    const text = await res.text();
    const data = text ? JSON.parse(text) : {};
    return { ok: res.ok, status: res.status, data };
  } catch (e) {
    console.error('API error:', e);
    return { ok: false, data: { error: 'Error de conexión' } };
  }
}

/* ── Generic fetch (multipart/FormData) ──────────────────────── */
async function apiFetchForm(url, formData) {
  try {
    // No ponemos Content-Type: el browser lo setea con boundary
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${getToken()}` },
      body: formData,
    });
    if (res.status === 401) { logout(); return; }
    const text = await res.text();
    const data = text ? JSON.parse(text) : {};
    return { ok: res.ok, status: res.status, data };
  } catch (e) {
    console.error('API error (form):', e);
    return { ok: false, data: { error: 'Error de conexión' } };
  }
}

/* ── Auth Service ─────────────────────────────────────────────── */
const AuthAPI = {
  googleLogin: (idToken) => apiFetch(`${API.auth}/auth/google`, {
    method: 'POST', body: JSON.stringify({ id_token: idToken })
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
      return apiFetchForm(`${API.auth}/auth/profile`, data);
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
  getFeed: (page = 1) => apiFetch(`${API.posts}?page=${page}`),

  // Crea un post. Si imageFile es un File, usa multipart; si no, usa JSON.
  createPost: ({ content, imageFile, visibility = 'all' }) => {
    if (imageFile) {
      const fd = new FormData();
      if (content) fd.append('content', content);
      fd.append('image', imageFile);
      fd.append('visibility', visibility);
      return apiFetchForm(`${API.posts}`, fd);
    }
    return apiFetch(`${API.posts}`, {
      method: 'POST',
      body: JSON.stringify({ content, visibility }),
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
  getDirectory: (params = '') => apiFetch(`/api/directory?${params}`),
  getFriends: () => apiFetch(`/api/friends`),
  getPendingRequests: () => apiFetch(`/api/friends/pending`),
  sendRequest: (receiverId) => apiFetch(`/api/friends/request`, {
    method: 'POST', body: JSON.stringify({ receiver_id: receiverId })
  }),
  acceptRequest: (requestId) => apiFetch(`/api/friends/${requestId}/accept`, { method: 'PUT' }),
  rejectRequest: (requestId) => apiFetch(`/api/friends/${requestId}/reject`, { method: 'PUT' }),
  removeFriend: (friendId) => apiFetch(`/api/friends/${friendId}`, { method: 'DELETE' }),
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
  'FAING':    '#6B1B1B',
  'FACEM':    '#1B6B2A',
  'FAEDCOH':  '#1B2A6B',
  'FADE':     '#1B8BC9',
  'FACSA':    '#6B1B6B',
  'FAU':      '#C96B1B',
};

// Also map school names to faculty for backwards compatibility
const SCHOOL_TO_FACULTY = {
  'Ingeniería Civil': 'FAING', 'Ingeniería de Sistemas': 'FAING',
  'Ingeniería Electrónica': 'FAING', 'Ingeniería Agroindustrial': 'FAING',
  'Ingeniería Ambiental': 'FAING', 'Ingeniería Industrial': 'FAING',
  'Ciencias Contables y Financieras': 'FACEM', 'Ingeniería Comercial': 'FACEM',
  'Administración de Negocios Internacionales': 'FACEM',
  'Administración Turístico Hotelera': 'FACEM', 'Economía y Microfinanzas': 'FACEM',
  'Educación': 'FAEDCOH', 'Ciencias de la Comunicación': 'FAEDCOH',
  'Psicología': 'FAEDCOH', 'Humanidades': 'FAEDCOH',
  'Derecho': 'FADE',
  'Medicina Humana': 'FACSA', 'Odontología': 'FACSA',
  'Tecnología Médica: Laboratorio Clínico y Anatomía Patológica': 'FACSA',
  'Tecnología Médica: Terapia Física y Rehabilitación': 'FACSA',
  'Arquitectura': 'FAU',
};

function getFacultyColor(facultyOrSchool) {
  if (!facultyOrSchool) return '#1B2A6B';
  // Direct faculty sigla match
  if (FACULTY_COLORS[facultyOrSchool]) return FACULTY_COLORS[facultyOrSchool];
  // School name match
  const faculty = SCHOOL_TO_FACULTY[facultyOrSchool];
  if (faculty) return FACULTY_COLORS[faculty];
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

/* ── Notifications (friend requests) ─────────────────────────── */
async function loadNotifications() {
  const list = document.getElementById('notifications-list');
  if (!list) return;
  list.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">Cargando...</div>';

  const result = await SocialAPI.getPendingRequests();
  const requests = getList(result);
  if (result && result.ok) {
    if (requests.length === 0) {
      list.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">No tienes notificaciones pendientes</div>';
      const badge = document.getElementById('notif-badge');
      if (badge) badge.classList.add('hidden');
    } else {
      const badge = document.getElementById('notif-badge');
      if (badge) badge.classList.remove('hidden');
      list.innerHTML = requests.map(req => {
        const u = req.sender || req;
        const ini = initials(u.name);
        const color = getFacultyColor(u.school || '');
        return `
          <div class="px-4 py-3 border-b border-slate-50 hover:bg-slate-50 transition-colors">
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0" style="background:${color}">${ini}</div>
                <div>
                  <div class="font-bold text-sm text-slate-900">${u.name || 'Usuario'}</div>
                  <div class="text-xs text-slate-500">Solicitud de amistad</div>
                </div>
              </div>
              <div class="flex flex-col gap-1">
                <button onclick="acceptFriendRequest(${req.id})" class="text-[10px] font-bold text-white bg-[#1B2A6B] hover:bg-[#142052] px-2 py-1 rounded">Aceptar</button>
                <button onclick="rejectFriendRequest(${req.id})" class="text-[10px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded">Rechazar</button>
              </div>
            </div>
          </div>`;
      }).join('');
    }
  } else {
    list.innerHTML = '<div class="px-4 py-6 text-sm text-slate-500 text-center">Error al cargar</div>';
  }
}

async function acceptFriendRequest(id) {
  const result = await SocialAPI.acceptRequest(id);
  if (result && result.ok) { showToast('Solicitud aceptada'); loadNotifications(); }
  else { showToast('Error al aceptar', 'error'); }
}

async function rejectFriendRequest(id) {
  const result = await SocialAPI.rejectRequest(id);
  if (result && result.ok) { showToast('Solicitud rechazada'); loadNotifications(); }
  else { showToast('Error al rechazar', 'error'); }
}

/* Auto-check pending requests on page load */
document.addEventListener('DOMContentLoaded', async () => {
  if (isLoggedIn() && document.getElementById('notif-badge')) {
    const result = await SocialAPI.getPendingRequests();
    if (getList(result).length > 0) {
      document.getElementById('notif-badge').classList.remove('hidden');
    }
  }
});
