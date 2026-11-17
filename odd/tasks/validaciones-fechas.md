# Validaciones transversales de fechas y coherencia — Producción y Compras

Objetivo: implementar las validaciones pendientes detectadas al construir el calendario, en front y backend, con tests.
Branch: `feat/validaciones-fechas`. Delivery: `ask-on-risk`.

## Specs

### V1 — Zona horaria unificada
- `config/app.php` usa `APP_TIMEZONE` con default `America/Argentina/Buenos_Aires`, para que `today()` del backend coincida con la fecha local del navegador.

### V2 — Planificaciones coherentes
- Un período no puede superar los 90 días.
- No puede superponerse con otra planificación existente (al editar se excluye a sí misma).
- Errores en `fecha_inicio`/`fecha_fin` con el nombre de la planificación conflictiva.

### V3 — Entrega de la orden coherente
- Al crear: `fecha_entrega >= fecha_inicio` de la planificación elegida.
- Al editar: `fecha_entrega >= fecha_prevista_inicio` (si existe) y `>= fecha_inicio` de su planificación.
- Posponer no se bloquea si la fecha prevista supera la entrega: la proyección ya marca el conflicto; bloquearlo impediría reprogramar.
- La excepción de fecha histórica sin cambios del calendario se mantiene.

### V4 — Parte diario coherente
- La fecha del parte no puede ser anterior al inicio de la orden ni posterior a hoy.
- Flujo secuencial: una etapa no puede declarar más unidades de las que la etapa anterior ya tiene conformadas.

### V5 — Cotización del portal
- `fecha_prometida` no anterior a la fecha de la solicitud ni posterior a su vencimiento.
- El front acota el calendario con ese máximo.

### V6 — Solicitud de cotización
- Vencimiento máximo de 30 días desde hoy (además de no anterior a hoy).
- No se pueden enviar solicitudes a proveedores inactivos.

### V7 — Mantenimiento
- Una sola intervención por máquina y por día (evita duplicados de carga).

### V8 — Reportes y auditoría
- El rango entre `fecha_desde` y `fecha_hasta` no puede superar 366 días.

### V9 — Límites en el front
- Calidad: el máximo de rechazadas es el conformado disponible.
- Solicitud: el calendario de vencimiento no supera los 30 días.
- Posponer: el calendario no supera la fecha de entrega.
- Portal: el calendario no supera el vencimiento de la solicitud.

### Supuestos y decisiones
- A1: el rango de 90 días y el de 30 días son constantes de negocio; si cambian, se ajustan en el servicio/request correspondiente.
- A2: se descarta validar "intervención no anterior al alta de la máquina" porque no existe un campo de alta de negocio y bloquearía la carga de historial.
- A3: la secuencialidad del parte es por cantidades, no por fechas.
- A4: posponer más allá de la entrega es una decisión válida; el conflicto se muestra en el cronograma.

## Tasks

| ID | Spec | Route | Status |
| --- | --- | --- | --- |
| T1 | V1, V2 | inline | done |
| T2 | V3 | inline | done |
| T3 | V4 | inline | done |
| T4 | V5, V6 | inline | done |
| T5 | V7, V8 | inline | done |
| T6 | V9 | inline | done |

## Log

L1 (2026-11-16, pedido del usuario, verbatim):
"quedo muy bueno el calendario. Sigamos con todas las otras validaciones que dijiste. Arma un plan de implementacion y ejecutalo"

L2 (cierre): implementado por bloques con tests. Backend 639/639 y frontend 196/196. Decisiones tomadas durante la implementación:
- Posponer una orden más allá de su entrega no se bloquea: el conflicto queda visible en el cronograma y bloquearlo impediría reprogramar.
- Se descartó validar la intervención contra el alta de la máquina (no existe una fecha de alta de negocio y bloquearía la carga de historial); se mantiene una intervención por máquina y día.
- Las comparaciones de fechas en los Form Requests usan Carbon en lugar de string, para aceptar formatos no ISO.

## Auditoría de validaciones faltantes (Producción + Inventario)

Hallazgos revisados y resolución:

- Reservas al cerrar una orden: **corregido** (se liberan las remanentes).
- `reservar` duplicaba reservas: **corregido** (`updateOrCreate`; la disponibilidad ya no cuenta las reservas propias).
- `liberar` no era atómico: **corregido** (transacción).
- Baja de artículo con reservas vigentes u órdenes activas: **bloqueada**.
- Cambio de `tipo`/`origen` de un artículo con historial: **bloqueado**.
- Operarios dados de baja asignables: **bloqueado** (`exists` con `whereNull(deleted_at)` + `distinct`).
- Parte diario con máquina no disponible: **bloqueado**.
- Hoja de ruta modificable con órdenes activas: **bloqueado** en asignar, mover y desasignar.
- Eliminar una etapa con partes diarios: **bloqueado**.
- Eliminar máquina/operario con cualquier orden activa: **bloqueado** (antes solo En Curso).
- "Modificar" visible en estados que el servicio rechaza: **alineado** (solo Pendiente y Pendiente por Insumos).
- Orden para un producto sin hoja de ruta: **rechazada** en el servicio.
- Mismo artículo repetido en un lote: **rechazado** (`distinct`).
- `etapa_id`/`maquinaria_id` del parte sin `exists`: **agregado**.
- `fecha_vencimiento` del artículo: **incorporada** (alta/edición, no anterior a hoy, borrable, listado y UI).
- Nombres duplicados de máquina/operario: **rechazados** case-insensitive.
- Eliminación de artículos: no existe ruta; sin acción.

Decisiones:

- No se valida la intervención contra el alta de la máquina: no hay una fecha de alta de negocio y bloquearía la carga de historial.
- La consulta de historial Inventario → Producción se resuelve con `app()` al Service público para evitar un ciclo de constructores; no se accede a modelos de otro módulo.
- Los duplicados de máquina/operario se validan en el request (no índice único) para no volver frágiles las factories con nombres aleatorios.
- El cierre reutiliza `CalidadQuery` en lugar de duplicar la regla de aprobadas.
