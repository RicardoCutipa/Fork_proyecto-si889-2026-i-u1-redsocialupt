# Contexto para continuar con Codex

Este archivo resume el estado util del proyecto para poder continuar desde otra maquina o desde la VPS sin depender de la conversacion completa.

## Proyecto

- Repo local original: `C:\Users\Win\Desktop\proyecto-si889-2026-i-u1-redsocialupt`
- Repo en VPS: `/opt/uptconnect/repo`
- Dominio desplegado: `https://uptconnect.duckdns.org`
- VPS: `152.53.46.127`
- Rama de trabajo actual: `main`
- Remoto Git: `origin/main`

## Servicios principales

- Frontend: servido por Docker, puerto web normal.
- Backend/auth/posts: servicios del `docker-compose.server.yml`.
- WebRTC llamadas: usa senalizacion del sistema y TURN/coturn en VPS.
- Livestream: arquitectura actual `OvenMediaEngine + WHIP + HLS`.
- OME en VPS debe estar levantado como contenedor y expuesto detras de `/ome`.

## Comandos utiles

Local:

```powershell
docker compose up -d --build frontend
docker compose ps
node --check frontend/js/app.js
node --check frontend/js/app-shared.js
node --check frontend/js/app-live-media.js
git status --short --branch
```

Subir cambios:

```powershell
git add frontend/app.html frontend/js/app.js frontend/js/app-shared.js frontend/js/app-live-media.js CONTEXTO_CODEX.md
git commit -m "mensaje"
git push origin main
```

Actualizar VPS:

```bash
cd /opt/uptconnect/repo
git checkout main
git pull origin main
docker compose -p uptconnect -f docker-compose.server.yml up -d --build frontend
docker compose -p uptconnect -f docker-compose.server.yml ps
```

Si se cambian servicios backend u OME, reconstruir tambien los servicios afectados:

```bash
docker compose -p uptconnect -f docker-compose.server.yml up -d --build frontend posts-service auth-service ovenmediaengine
```

## Estado Git reciente

Commits relevantes recientes:

- `464227e extraer utilidades app y contexto codex` esta en `origin/main`.
- `ae50a0c optimizar live` esta incluido debajo de ese commit.
- `ac8f89e estable v1.3.0` es la base estable anterior.
- Antes hubo fixes de live: transicion, OME, proxy `/ome`, scroll movil, cierre WHIP, layout movil.

Importante: evitar `git add .` porque hay muchos archivos temporales y recursos locales sin versionar. Stagear solo archivos necesarios.

## Cambios locales no confirmados actuales

La primera refactorizacion de utilidades compartidas ya esta commiteada en `464227e`.
La extraccion de helpers de media live quedo en commit local `245bb6e extraer helpers media live`, aun no pusheado a GitHub.

Continuacion actual desplegada en VPS, sin commit:

- Nuevo archivo `frontend/js/app-realtime.js` con `buildCollectionSignature()` para detectar cambios en listas sin re-render innecesario.
- `frontend/app.html` carga `/css/app.css?v=34`, `/js/components.js?v=15`, `/js/app-realtime.js?v=1`, `/js/app.js?v=53` y `/js/api.js?v=18`.
- `frontend/js/api.js` cachea por 30s el contexto de amigos usado por `PostsAPI.getFeed()` para que el polling del feed no dispare `SocialAPI.getFriends()` en cada ciclo.
- `frontend/js/app.js` refresca automaticamente sin F5:
  - Feed principal cada 8s, al volver a la pestana y al recuperar foco.
  - Comentarios del modal del feed cada 6.5s.
  - Publicaciones de grupos cada 9s cuando la conversacion esta activa.
  - Comentarios de grupos cada 7s mientras el modal esta abierto.
  - Publicaciones de perfil cada 10s.
  - Comentarios de perfil cada 7s mientras el modal esta abierto.
- En feed principal, si llegan publicaciones nuevas mientras el usuario esta scrolleado, se muestra un boton sticky de nuevas publicaciones para no moverle la lectura.
- `frontend/nginx.conf` ahora fuerza revalidacion: HTML con `no-store` y JS/CSS con `no-cache`, para reducir casos donde el navegador exige F5 tras despliegues.
- `frontend/js/components.js` adapta la barra lateral a movil:
  - Boton hamburguesa en el header movil.
  - Drawer lateral con backdrop.
  - Cierre al navegar, tocar backdrop o presionar Escape.
- Se quito el boton de emoji del composer de publicaciones y se eliminaron listeners/estilos asociados.

Validaciones ya ejecutadas:

- `node --check frontend/js/app.js`
- `node --check frontend/js/api.js`
- `node --check frontend/js/components.js`
- `node --check frontend/js/app-shared.js`
- `node --check frontend/js/app-live-media.js`
- `node --check frontend/js/app-realtime.js`
- `docker compose -p uptconnect -f docker-compose.server.yml up -d --build frontend`
- Dentro de `uptconnect-frontend-1`, Nginx responde `200 OK` para `/js/app-live-media.js`.
- Dentro de `uptconnect-frontend-1`, Nginx responde `200 OK` para `/js/app-realtime.js`.
- Dentro de `uptconnect-frontend-1`, Nginx responde `200 OK` para `/js/app.js`.
- Dentro de `uptconnect-frontend-1`, Nginx responde `200 OK` para `/app.html` con `Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate`.
- Dentro de `uptconnect-frontend-1`, Nginx responde JS con `Cache-Control: no-cache, must-revalidate`.
- En publico, `https://uptconnect.duckdns.org/app.html` contiene `app.css?v=34`, `components.js?v=15` y `app.js?v=53`.
- En publico, `/js/components.js` y `/css/app.css` responden `200 OK` con `Cache-Control: no-cache, must-revalidate`.

Pendiente antes de publicar:

- Commit/push stageando solo los archivos necesarios si se quiere subir a GitHub. En la VPS ya esta desplegado.

## Live / livestream

Objetivo actual del live:

- Mantener `OvenMediaEngine + WHIP + HLS`.
- No cambiar arquitectura a WebRTC viewer directo ni multi-bitrate por ahora.
- Mejorar audio, fluidez, recuperacion y estabilidad sin romper comentarios, reacciones, finalizar live ni cambio de fuente.

Optimizacion ya aplicada en live (helpers ahora en `frontend/js/app-live-media.js` y usados por `frontend/js/app.js`):

- `getLiveAudioConstraints()`
- `getLiveVideoConstraints(source, overrides)`
- `createMixedAudioTrack(displayAudioTrack, micAudioTrack)`
- `applyLiveTrackHints(stream, source)`
- Audio con `echoCancellation`, `noiseSuppression`, `autoGainControl`, `channelCount`, `sampleRate` y `sampleSize`.
- Mezcla PC pantalla + microfono con Web Audio, `GainNode` y `DynamicsCompressorNode`.
- `track.contentHint`: pantalla `detail`, camara `motion`, audio `speech`.
- Pantalla intenta usar hasta 1080p/60fps.
- Camara usa perfil mas estable, especialmente en movil.
- HLS viewer ajustado para no perseguir el live edge de forma tan agresiva.

Comportamiento deseado:

- El blur solo debe aparecer cuando hay cambio real de fuente/camara, no al iniciar el directo.
- Ante cortes breves normales, mantener ultimo frame visible y mostrar spinner, no pantalla negra.
- El boton de cambiar fuente en host PC debe mantenerse visible aunque se abra/cierre DevTools o cambie el ancho.
- Si el host cambia de pagina o cierra navegador, el stream debe finalizar.
- En movil, host y viewer deben poder escribir comentario y reaccionar.
- El scroll de chat movil ya fue corregido y no debe romperse.

Problemas historicos ya tratados:

- Viewer no cargaba HLS por 404 o timeout de manifiestos.
- Cambio de fuente/camara congelaba viewer.
- Cambio de camara movil fallaba por no liberar la camara anterior.
- Chat movil no permitia scroll.
- Layout inmersivo PC en media pantalla deformaba chat.

## Llamadas y videollamadas

Estado general:

- Llamadas y videollamadas funcionan con ventana flotante movible.
- La llamada debe seguir activa fuera de Mensajes.
- Al recargar pagina se debe cortar la llamada.
- Se arreglo audio/video, mute, colgar, camara remota, nuevas llamadas sin F5, y falsos mensajes de llamada no disponible.
- TURN/coturn se monto en VPS para mejorar conectividad.

## Refactorizacion de `app.js`

Meta:

- Reducir cantidad de texto y complejidad de `frontend/js/app.js` sin quitar funcionalidades.
- No minificar manualmente ni hacer cambios opacos.
- Preferir modularizar en archivos claros.

Primera etapa hecha:

- Extraidas utilidades puras a `frontend/js/app-shared.js`.
- `app.js` bajo de aproximadamente 10,386 lineas a unas 10,024 lineas.

Segunda etapa iniciada:

- Extraidos helpers de media live a `frontend/js/app-live-media.js`.
- `app.js` bajo a 9,927 lineas.

Tercera etapa desplegada en VPS:

- Extraido helper pequeno de firmas de colecciones a `frontend/js/app-realtime.js`.
- Agregado refresco automatico de feed, comentarios, grupos y perfil para evitar F5 en cambios de contenido.
- Agregadas cabeceras de cache de Nginx para que HTML/JS/CSS se revaliden tras despliegues.

Siguientes etapas recomendadas:

- Extraer mas piezas pequenas del live cuando esten suficientemente estables (por ejemplo helpers de viewer HLS o UI de comentarios/reacciones), evitando mover el mount completo de golpe.
- Extraer modulo de llamadas/videollamadas.
- Extraer helpers de rutas/render solo si no dependen de estado interno complejo.
- Evitar mover vistas grandes sin pruebas, porque `app.js` tiene mucho estado compartido.

## Conversacion completa

El respaldo crudo de la conversacion esta en:

```text
C:\Users\Win\Desktop\rollout-2026-05-12T22-42-31-019e1f6d-d3b8-7250-928e-f228733ce500.jsonl
```

Pesa aproximadamente 165 MB. Sirve como respaldo, pero para trabajar en la VPS es mejor usar este resumen.
