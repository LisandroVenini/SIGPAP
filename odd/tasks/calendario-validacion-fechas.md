# Calendario propio y validación de fechas

Objetivo: reemplazar los `input type="date"` nativos por un calendario propio integrado al diseño, y validar fechas operativas en front y backend, empezando por Planificaciones.
Branch: `feat/calendario-fechas`. Delivery: `ask-on-risk`. Tests: `php artisan test --compact`, `npm run test:js`.

## Specs

### S1 — Componente de calendario propio
Usuario: "Quiero que crees nuestro propio componente de calendario, siguiendo los lineamientos del front, asi queda mejor integrado"

- `resources/js/Components/DatePicker.vue`: input de texto con formato `dd/mm/aaaa` y popover de calendario.
- Navegación por mes, día de hoy marcado, día seleccionado resaltado, días fuera de rango deshabilitados.
- Props `min` y `max` (ISO), `invalid`, `disabled`, `placeholder`.
- Acepta tipeo en `dd/mm/aaaa` o `YYYY-MM-DD`; descarta valores inválidos o fuera de rango.
- Accesible: `aria-expanded`, `aria-haspopup="dialog"`, cierre con `Escape`, click afuera y scroll; foco vuelve al input.
- Los atributos (`id`, `data-testid`, `required`) se reenvían al input interno para no romper formularios ni tests.
- Sin librerías nuevas: grilla con Tailwind y los tokens del sistema.

### S2 — Regla de fechas operativas
Usuario: "La fecha de inicio y la de fin nunca puede ser anterior a la fecha del día corriente... Esto lo debemos validar para todas las operaciones con calendario. Quiero que la validacion la hagas tanto en el front como en el backend."

- Fechas que programan el futuro (planificación, entrega, vencimiento, fecha prometida): no anteriores a hoy.
- Fechas que registran hechos (parte diario, intervención de mantenimiento): no posteriores a hoy.
- Filtros de consulta: siguen permitiendo el pasado; solo se valida `desde <= hasta`.
- El mínimo de la fecha de fin depende del inicio elegido.
- Al mover el inicio a una fecha posterior a la fin ya cargada, la fin se limpia en el front.

### S3 — Planificaciones
- Front: `DatePicker` con `min=hoy` en inicio y `min=max(hoy, inicio)` en fin; limpieza de fin inválida.
- Back: `StorePlanificacionRequest` exige `after_or_equal:today` en ambas fechas.
- Back: `UpdatePlanificacionRequest` exige `after_or_equal:today` solo cuando la fecha cambia; una fecha histórica sin modificar sigue siendo válida para poder corregir nombre u observaciones.

### S4 — Órdenes de Producción
- Front: `fecha_entrega` (alta y modificación) y `fecha_prevista_inicio` (posponer) con `min=hoy`; filtros sin mínimo y `hasta` acotado por `desde`.
- Back: `StoreOrdenDeProduccionRequest` exige `after_or_equal:today` en `fecha_entrega`.
- Back: `UpdateOrdenDeProduccionRequest` aplica la misma excepción de fecha histórica sin cambios.

### S5 — Resto de calendarios
- Migrados los 18 `input type="date"` a `DatePicker`: solicitudes de cotización, órdenes de compra, auditoría, reportes, parte diario, mantenimiento, portal de cotización y órdenes de producción.
- Filtros: `hasta` acotado por `desde`, sin bloquear el pasado.
- Parte diario e intervenciones: `max=hoy`.
- Portal del proveedor: `fecha_prometida` con `min=hoy`.

### Supuestos
- A1: "hoy" es la fecha local del navegador; el backend usa la zona de la aplicación.
- A2: los registros históricos se pueden editar sin mover sus fechas (excepción explícita).
- A3: los filtros de reportes y auditoría no se restringen al futuro porque analizan el pasado.

## Tasks

| ID | Spec | Route | Status | Commit |
| --- | --- | --- | --- | --- |
| T1 | S1 | inline | done | — |
| T2 | S2, S3 | inline | done | — |
| T3 | S2, S4 | inline | done | — |
| T4 | S5 | inline | done | — |

- T1: `lib/fechas.ts`, `DatePicker.vue` y tests; `gantt.ts` reutiliza los helpers.
- T2: Planificaciones (front + requests + tests).
- T3: Órdenes (front + requests + tests).
- T4: migración del resto de los calendarios.

## Log

L1 (2026-11-11, pedido del usuario, verbatim):
"ahora que tenemos los datos voy a ir dandote validadicones que quiero que arregles.
EMpecemos por el modulo de produccion.
1. Al crear una nueva planificacion hay cosas que estan mal:
 * Quiero que crees nuestro propio componente de calendario, siguiendo los lineamientos del front, asi queda mejor integrado
 * La fecha de inicio y la de fin nunca puede ser anterior a la fecha del dia corriente. Quiero que el componente bloquee las selcciones dependiendo que dia se selecciona en el inicio, etc. Esto lo debemos validar para todas las operaciones con calendario. Quiero que la validacion la hagas tanto en el front como en el backend.
Si en el camino se te ocurren otras validaciones que todavia no vi redactamelas y las vemos luego"

L2 (cierre): 18 calendarios migrados, 0 `input type="date"` restantes. Suite: 630/630 backend y 197/197 frontend. Pint, Prettier, typecheck y eslint limpios.
