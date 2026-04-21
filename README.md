# 🎓 Red Social UPT

Plataforma social universitaria exclusiva para la comunidad de la Universidad Privada de Tacna (UPT). Solo accesible con cuenta institucional `@virtual.upt.pe` mediante Google OAuth.

---

## 🛠️ Stack Tecnológico

| Parte | Tecnología |
|---|---|
| Backend | PHP + Lumen (microservicios) |
| Frontend | HTML + CSS + JavaScript |
| Base de datos | MySQL (una por microservicio) |
| Autenticación | Google OAuth + JWT |
| Contenedores | Docker + Docker Compose |
| Infraestructura | Terraform (Hetzner) |
| CI/CD | GitHub Actions |
| Calidad de código | SonarCloud |
| Seguridad | Snyk |

---

## 🏗️ Arquitectura

El sistema está construido con arquitectura de **microservicios**. Cada servicio es independiente, tiene su propia base de datos MySQL y corre en su propio contenedor Docker.

| Microservicio | Puerto | Base de datos | Descripción |
|---|---|---|---|
| **Auth Service** | 8001 | `auth_db` | Google OAuth, validación `@virtual.upt.pe`, JWT |
| **Posts Service** | 8002 | `posts_db` | Feed, historias, imágenes |
| **Profile & Social Service** | 8003 | `social_db` | Perfiles, likes, comentarios, compartir |
| **Frontend** | 80 | — | HTML + CSS + JS servido con Nginx |

---

## 🚀 Despliegue Local

```bash
# Clonar el repositorio
git clone https://github.com/UPT-FAING-EPIS/proyecto-si889-2026-i-u1-redsocialupt.git
cd proyecto-si889-2026-i-u1-redsocialupt

# Levantar todos los servicios
docker compose up --build

# Acceder a la aplicación
# Frontend:       http://localhost
# Auth Service:   http://localhost:8001
# Posts Service:   http://localhost:8002
# Social Service:  http://localhost:8003
```

---

## 👥 Integrantes

| Nombre | Código | Servicio asignado |
|---|---|---|
| Cutipa Gutierrez, Ricardo | 2021069827 | Auth Service + Setup |
| Malaga Espinoza, Ivan | 2021071086 | Posts Service |
| Chino Rivera, Angel | 2021069830 | Profile & Social Service |

**Curso:** Patrones de Software  
**Docente:** Mag. Ing. Patrick Cuadros Quiroga  
**Universidad:** Universidad Privada de Tacna — 2026-I

---

## ✅ Requerimientos Funcionales

| # | Descripción |
|---|---|
| RF-01 | Inicio de sesión con cuenta Google `@virtual.upt.pe` |
| RF-02 | Autenticación segura con Google OAuth + JWT |
| RF-03 | Crear publicaciones de texto e imagen en el feed |
| RF-04 | Visualizar el feed en orden cronológico |
| RF-05 | Enviar, aceptar y rechazar solicitudes de amistad |
| RF-06 | Chat privado entre amigos (mensajes en tiempo real con polling) |
| RF-07 | Dar likes a publicaciones |
| RF-08 | Comentar publicaciones |
| RF-09 | Compartir publicaciones |
| RF-10 | Gestionar perfil (nombre, foto, carrera, facultad) |
| RF-11 | Panel de administración para gestión de usuarios y contenido |

---

## ⚙️ Requerimientos No Funcionales

| # | Descripción |
|---|---|
| RNF-01 | Acceso exclusivo mediante dominio `@virtual.upt.pe` con Google OAuth |
| RNF-02 | Autenticación delegada a Google (sin almacenamiento de contraseñas) |
| RNF-03 | Comunicación entre servicios autenticada con JWT |
| RNF-04 | Código sin vulnerabilidades Critical/Blocker en SonarCloud |
| RNF-05 | Dependencias sin vulnerabilidades críticas según Snyk |
| RNF-06 | Pipeline CI/CD funcional en GitHub Actions |
| RNF-07 | Sistema desplegado en VPS Debian con Docker |
| RNF-08 | Infraestructura provisionada con Terraform |
| RNF-09 | README con procedimiento completo de despliegue |
| RNF-10 | Wiki de GitHub con características del producto y roadmap |

---

## 🚫 Restricciones

| # | Descripción |
|---|---|
| RE-01 | Solo usuarios con cuenta `@virtual.upt.pe` pueden autenticarse |
| RE-02 | El sistema requiere conexión a Internet para funcionar |
| RE-03 | El almacenamiento de imágenes está limitado al espacio de la VPS |