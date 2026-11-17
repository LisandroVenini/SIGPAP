# Cronograma de Producción como diagrama de Gantt

Objetivo: reemplazar la tabla de proyección por un Gantt propio (CSS grid, sin librerías) con eje temporal, barras por etapa, línea de hoy, navegación de ventana, filtros y conflictos de entrega.
Branch: `feat/cronograma-gantt`. Delivery: `ask-on-risk`. Tests: `php artisan test --compact`, `npm run test:js`.

## Specs

### S1 — Gantt con eje temporal
Usuario: "Quiero que la hagamos diferente, que implementemos un diagrama de gantt, con todo lo que eso conlleva."

- Columna fija a la izquierda con los datos de la orden y, a la derecha, una columna por día de la ventana visible.
- Encabezado por día con día de la semana y número (ej. `LUN 14`); el día de hoy se marca visualmente.
- Línea vertical punteada sobre la columna de hoy.
- La zona del eje se desplaza horizontalmente en pantallas angostas sin romper la columna fija.

### S2 — Barras por etapa
- Cada etapa de la hoja de ruta se dibuja como un segmento posicionado por su fecha de inicio y su duración, recortado contra la ventana visible.
- Tooltip con nombre de etapa, rango de fechas y días.
- Color por estado de la orden; las órdenes con conflicto de entrega usan el color de peligro (`oxide`).
- Las órdenes `En Curso` muestran su avance (%) en la barra.

### S3 — Controles temporales y filtros
- Navegación `‹ semana ›` y botón `Hoy`.
- Horizonte seleccionable: 7, 14 o 30 días (por defecto 14).
- Filtro por estado de la orden; al filtrar, la ventana temporal no se reinicia.

### S4 — Fila de orden
- Identificador `#id`, badge de estado, producto, cantidad requerida y avance.
- Candado cuando la orden no es reubicable (`En Curso`); acción `Reordenar` visible en el resto.

### S5 — Conflictos
- Las órdenes con `conflicto_entrega` se destacan en la fila y en sus barras.
- Banner superior con la cantidad y los identificadores de las órdenes que incumplen su fecha de entrega.

### S6 — Reordenar (sin cambios de contrato)
- Se conserva el drawer con prioridad, motivo, detalle y confirmación de conflicto, y el endpoint `PUT /produccion/cronograma/{orden}/prioridad`.
- El candado deshabilita la acción para órdenes `En Curso`, igual que hoy.

### S7 — Sin librerías nuevas
- No se agregan dependencias a `package.json`; el Gantt se construye con CSS grid y utilidades del proyecto.

### Supuestos
- A1: la proyección sigue siendo secuencial (una sola cola), por decisión de alcance; el modelado de capacidad quedó documentado en el vault como `CAM-002`.
- A2: la ventana se resuelve en el cliente sobre la proyección completa.
- A3: ventana por defecto de 14 días para que entren etapas largas.

## Tasks

| ID | Spec | Route | Status | Commit |
| --- | --- | --- | --- | --- |
| T1 | S2, S4 | inline | done | — |
| T2 | S1, S3 | inline | done | — |
| T3 | S1, S2, S5 | inline | done | — |
| T4 | S3, S4, S6 | inline | done | — |

- T1: backend — `rutaPorArticulo` expone `etapa_id`; `proyectar()` agrega `hoy`, `cantidad_requerida`, `etapa_actual`, `avance`, `dias_totales` y `producida` por segmento. Test de servicio.
- T2: `resources/js/Pages/Produccion/Cronograma/gantt.ts` (fechas, ventana, recorte, semana) + tests unitarios.
- T3: `GanttTimeline.vue` (columna fija, eje, barras, línea de hoy) + tests.
- T4: `Index.vue` (shell, controles, leyenda, banner, drawer) + tests de página.

## Log

L1 (2026-11-11, pedido del usuario, verbatim):
"Ahora vamos a cambiar el cronograma, esta muy fua la vista. Quiero que la hagamos diferente, que implementemos un diagrama de gantt, con todo lo que eso conlleva. Si es necesario implementemos librerias de graficos o algo por el estilo. Hagamos la planificacion primero y presentame lo que vas a hacer y yo te apruebo o no."

L2 (referencia): pantalla `SIGPAP — Cronograma de Producción (P-09)` del proyecto Stitch `SIGPAP · Salvia & Teja`. Es CSS grid, sin librería.

L3 (decisión del usuario): "Me gusta la opcion 2 pero al estar cortos de tiempo vamos a ir por la 1. Quiero que el apartado de modelar capacidad lo dejes anotado en el vault de obsidian para una futura implementacion". Implementación elegida: Gantt propio con proyección secuencial. Modelado de capacidad anotado en el vault como `CAM-002-capacidad-planta-cronograma.md`.

L4 (cierre): implementado. `rutaPorArticulo` expone `etapa_id`; `proyectar()` agrega `hoy`, `cantidad_requerida`, `etapa_actual`, `avance`, `dias_totales` y `producida` por segmento. Frontend: `gantt.ts` (fechas, ventana, recorte, semana ISO), `GanttTimeline.vue` (columna fija, eje de días, línea de hoy, barras) y `Index.vue` (controles, leyenda, banner, drawer). Verificado: `php artisan test` 617/617, `npm run test:js` 175/175, typecheck y eslint limpios, Pint y Prettier aplicados, Vite compila ambos componentes. Nota de alcance: la proyección sigue siendo secuencial; el modelado de capacidad quedó en el vault como `CAM-002-capacidad-planta-cronograma.md`.
