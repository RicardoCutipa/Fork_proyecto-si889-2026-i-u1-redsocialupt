// UPT Connect - Web Components for unified layout

class AppHeader extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
<header class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-6 h-16 bg-white border-b border-slate-200">
<div class="flex items-center gap-4">
<a href="/pages/feed.html" class="text-lg font-bold text-[#1B2A6B] uppercase tracking-wider">UPT Connect</a>
</div>
<div class="flex-1 max-w-xl mx-8 hidden md:block">
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
<input class="w-full bg-slate-100 border-none rounded-full py-2.5 pl-10 pr-4 text-sm focus:ring-1 focus:ring-[#1B2A6B] outline-none" placeholder="Buscar en UPT Connect..." type="text"/>
</div>
</div>
<div class="flex items-center gap-4">
<div class="relative group" id="notif-container">
<button class="p-2 text-slate-600 hover:bg-slate-50 rounded-full transition-colors relative cursor-pointer" onclick="if(window.loadNotifications) window.loadNotifications()">
<span class="material-symbols-outlined">notifications</span>
<span id="notif-badge" class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white hidden"></span>
</button>
<div class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-100 py-2 hidden group-hover:block focus-within:block z-50" id="notifications-dropdown">
  <div class="px-4 py-2 border-b border-slate-100 font-bold text-sm text-slate-700">Notificaciones</div>
  <div id="notifications-list" class="max-h-80 overflow-y-auto">
    <div class="px-4 py-6 text-sm text-slate-500 text-center">No tienes notificaciones pendientes</div>
  </div>
</div>
</div>
<div class="relative group">
<div class="w-9 h-9 rounded-full bg-[#1B2A6B] flex items-center justify-center text-white font-bold cursor-pointer" id="header-initials" tabindex="0">U</div>
<div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
<div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-2 hidden group-hover:block focus-within:block z-50">
  <a href="/pages/profile.html" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-[#1B2A6B]"><span class="material-symbols-outlined align-middle mr-2 text-[18px]">person</span>Mi Perfil</a>
  <button onclick="if(window.logout) window.logout()" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"><span class="material-symbols-outlined align-middle mr-2 text-[18px]">logout</span>Cerrar sesión</button>
</div>
</div>
</div>
</header>
    `;
  }
}

class AppSidebar extends HTMLElement {
  connectedCallback() {
    const activeNav = this.getAttribute('active-nav');
    
    // Helper function to style the active nav item
    const getNavClass = (navId) => {
      if (navId === activeNav) {
        return "flex items-center gap-3 p-3 bg-slate-100 text-[#1B2A6B] font-medium rounded-xl transition-all duration-200";
      }
      return "flex items-center gap-3 p-3 text-slate-700 hover:bg-slate-50 rounded-xl transition-all duration-200";
    };

    this.className = "hidden md:flex md:col-span-3 flex-col gap-6";
    this.innerHTML = `
<!-- Profile Card -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
<div class="h-16" id="profile-banner" style="background:#1B2A6B"></div>
<div class="px-4 pb-4 relative">
<div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl border-4 border-white absolute -top-8 bg-cover bg-center" id="sidebar-avatar" style="background:#1B2A6B">U</div>
<div class="pt-10">
<div class="flex items-center gap-2 mb-1">
<h2 class="font-bold text-lg text-slate-900" id="sidebar-name">Cargando...</h2>
<span class="text-white text-[10px] font-bold px-2 py-0.5 rounded-full ml-auto" id="sidebar-faculty-badge" style="background:#1B2A6B">UPT</span>
</div>
<p class="text-slate-500 text-sm" id="sidebar-career">—</p>
</div>
</div>
</div>
<!-- SideNavBar -->
<nav class="bg-white rounded-2xl border border-slate-200 p-2 flex flex-col gap-1 shadow-sm">
<a class="${getNavClass('feed')}" href="/pages/feed.html" id="nav-feed">
<span class="material-symbols-outlined text-[20px]">home</span><span class="text-sm">Inicio</span>
</a>
<a class="${getNavClass('profile')}" href="/pages/profile.html" id="nav-profile">
<span class="material-symbols-outlined text-[20px]">person</span><span class="text-sm">Mi Perfil</span>
</a>
<a class="${getNavClass('messages')}" href="/pages/messages.html" id="nav-messages">
<span class="material-symbols-outlined text-[20px]">chat</span><span class="text-sm">Mensajes</span>
</a>
<a class="${getNavClass('companions')}" href="/pages/companions.html" id="nav-companions">
<span class="material-symbols-outlined text-[20px]">groups</span><span class="text-sm">Compañeros</span>
</a>
<a class="${getNavClass('admin')}" href="/pages/admin.html" id="nav-admin" style="display:none">
<span class="material-symbols-outlined text-[20px]">admin_panel_settings</span><span class="text-sm">Admin</span>
</a>
<a class="flex items-center gap-3 p-3 text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 mt-2 cursor-pointer" onclick="if(window.logout) window.logout()">
<span class="material-symbols-outlined text-[20px]">logout</span><span class="text-sm">Cerrar sesión</span>
</a>
</nav>
    `;
  }
}

customElements.define('app-header', AppHeader);
customElements.define('app-sidebar', AppSidebar);

// Función global para configurar el perfil en los componentes
window.setupLayoutData = function(user) {
  if (!user) return;
  
  // Helpers
  const getFacultyColor = (school) => {
    const s = (school || '').toLowerCase();
    if(s.includes('sistemas')||s.includes('civil')||s.includes('electrónica')||s.includes('industrial')||s.includes('arquitectura')||s.includes('ingeniería')) return '#6B1B1B';
    if(s.includes('medicina')||s.includes('odontología')||s.includes('enfermería')||s.includes('salud')) return '#6B1B6B';
    if(s.includes('negocios')||s.includes('contabilidad')||s.includes('turismo')||s.includes('administración')) return '#1B6B2A';
    if(s.includes('derecho')||s.includes('comunicación')||s.includes('ciencias')) return '#1B8BC9';
    return '#1B2A6B';
  };
  const initials = (name) => {
    if(!name) return 'U';
    const parts = name.trim().split(' ');
    if(parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return parts[0][0].toUpperCase();
  };

  const ini = initials(user.name);
  const color = getFacultyColor(user.school || user.career || '');
  
  const headerInitials = document.getElementById('header-initials');
  if (headerInitials) {
    if (user.avatar_url) {
      headerInitials.textContent = '';
      headerInitials.style.backgroundImage = `url('${user.avatar_url}')`;
      headerInitials.style.backgroundSize = 'cover';
      headerInitials.style.backgroundPosition = 'center';
    } else {
      headerInitials.textContent = ini;
      headerInitials.style.background = color;
      headerInitials.style.backgroundImage = '';
    }
  }
  
  const sidebarAvatar = document.getElementById('sidebar-avatar');
  if (sidebarAvatar) {
    if (user.avatar_url) {
      sidebarAvatar.textContent = '';
      sidebarAvatar.style.backgroundImage = `url('${user.avatar_url}')`;
      sidebarAvatar.style.backgroundSize = 'cover';
      sidebarAvatar.style.backgroundPosition = 'center';
    } else {
      sidebarAvatar.textContent = ini;
      sidebarAvatar.style.background = color;
      sidebarAvatar.style.backgroundImage = '';
    }
  }
  
  const profileBanner = document.getElementById('profile-banner');
  if (profileBanner) {
    if (user.banner_url) {
      profileBanner.style.backgroundImage = `url('${user.banner_url}')`;
      profileBanner.style.backgroundSize = 'cover';
      profileBanner.style.backgroundPosition = 'center';
    } else {
      profileBanner.style.background = color;
      profileBanner.style.backgroundImage = '';
    }
  }
  
  const sidebarName = document.getElementById('sidebar-name');
  if (sidebarName) sidebarName.textContent = user.name || 'Usuario';
  
  const sidebarCareer = document.getElementById('sidebar-career');
  if (sidebarCareer) sidebarCareer.textContent = user.school || user.career || '—';
  
  const badge = document.getElementById('sidebar-faculty-badge');
  if (badge) {
    badge.textContent = user.faculty || 'UPT';
    badge.style.background = color;
  }
  
  const navAdmin = document.getElementById('nav-admin');
  if (navAdmin && user.role === 'admin') navAdmin.style.display = 'flex';
};
