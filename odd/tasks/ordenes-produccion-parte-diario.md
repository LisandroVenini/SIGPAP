# Rediseño de Órdenes de Producción + sección Parte Diario

Objetivo: reordenar la tabla de órdenes, agregar subtabla de etapas por orden, agrupar acciones en un menú de 3 puntos y separar Parte Diario en su propia sección.
Branch: `feat/ordenes-produccion-parte-diario`. Delivery: `ask-on-risk` (3 commits; la estrategia de PR se define recién al abrir el PR). Tests: `php artisan test --compact`, `npm run test:js`.

## Specs

### S1 — Tabla de órdenes con mejor orden
Usuario: "quiero que la tabla tenga un mejor orden"

- Columnas, de izquierda a derecha: Producto (descripción + SKU y planificación en línea secundaria), Estado (badge), Requerida, Producida, Avance (barra + "producido / requerido"), Entrega, Recursos (operarios y maquinarias en líneas compactas), Acciones.
- Columnas numéricas alineadas a la derecha con la utilidad `num`; encabezados alineados igual que su columna.
- Se mantiene el orden del servidor: `fecha_entrega` asc, `id` asc. No se agregan controles de ordenamiento nuevos.
- Se conservan filtros, paginación, empty state y banner de flash existentes.

### S2 — Fila expandible con subtabla de etapas
Usuario: "quiero que al precionar una orden se despligue una subtabla donde se vean las etapas de esa orden y como es el estado de cada una con sus cantidades ya producidad y cuantas faltan por etapa"

- Al presionar la fila (o su control con chevron) se despliega debajo una subtabla de esa orden; al volver a presionar se cierra.
- Una sola orden expandida a la vez.
- Subtabla: Etapa, Estado de la etapa, Producida, Faltante, en el orden de la hoja de ruta del producto.
- Estado de la etapa: `Sin iniciar` si conformado = 0; `En curso` si 0 < conformado < requerida; `Completada` si conformado >= requerida.
- Producida = suma de `cantidad_conformada` de los partes de esa etapa.
- Faltante = `max(0, cantidad_requerida + rechazadas - producida)`, redondeado a 2 decimales (misma regla que usa el servidor para el tope del parte).
- Los números vienen del backend por orden (`etapas_detalle`), no se calculan agrupando `partes` en el navegador.
- Accesibilidad: control con `aria-expanded` y `aria-controls`; la celda de Acciones no dispara el expandir.

### S3 — Acciones agrupadas en menú de 3 puntos
Usuario: "La seccion de acciones quiero que agrupemos y se desplieguen con un boton de 3 puntos para cada una de las ordenes."

- Cada fila tiene un botón de 3 puntos que abre el menú de acciones de esa orden; un solo menú abierto a la vez.
- Cierra al hacer click afuera, con `Escape` y al elegir una acción.
- Se conservan exactamente las condiciones actuales: Modificar (siempre), Anular (`fecha_inicio` null y sin partes), Asignar recursos (siempre visible, deshabilitado salvo Pendiente / Pendiente por Insumos / Asignada), Iniciar (Asignada), Calidad y Cerrar (En Curso), Posponer (Pendiente / Pendiente por Insumos / Asignada / En Curso), Reanudar (Pospuesta).
- "Anular" se separa visualmente por ser destructiva.
- Accesible por teclado: `aria-expanded` / `aria-haspopup`, `Escape` cierra y devuelve el foco al botón.

### S4 — Parte Diario con sección propia
Usuario: "La parte de parte diario no se tiene que hacer desde ordenes de produccion, parte diario va a tener su propia seccion, eso que hicimos de unificar ambas seccciones esta mal"

Decisión del usuario (pregunta respondida): "Carga + avance por etapas" — "Elegís una orden En Curso, ves sus etapas con producido y faltante, cargás el parte desde ahí y abajo ves los partes registrados del día."

- Ruta nueva `GET /produccion/partes-diarios`, nombre `produccion.partes-diarios.index`, bajo middleware `auth`, en el módulo Producción.
- Página Inertia `Produccion/ParteDiario/Index`.
- Contenido: selector de orden limitado a órdenes `En Curso`; tabla de etapas de la orden elegida con producida y faltante (mismas reglas que S2); formulario de carga con etapa (solo etapas de la hoja de ruta, por defecto la primera con faltante), maquinaria (solo asignadas a la orden), fecha (por defecto hoy, no posterior a hoy), cantidad (> 0 y <= faltante de la etapa elegida), observaciones (<= 500); y listado de los partes registrados hoy para esa orden.
- Se reutiliza el endpoint existente `POST /produccion/ordenes-de-produccion/{orden}/partes` con su validación; al registrar, redirige a la sección Parte Diario con la orden seleccionada.
- La página de Órdenes de Producción deja de mostrar la acción "Cargar parte", su drawer, su formulario y su test.

### S5 — Navegación
- En el menú Producción, "Parte Diario" apunta a `/produccion/partes-diarios` (sin query param) y se marca activo en esa ruta.
- `AppLayout.test.ts` deja de usar `?estado=En Curso` como caso de "Parte Diario".

### Supuestos
- A1: "mejor orden" = jerarquía y alineación visual, no ordenamiento interactivo por columna.
- A2: una fila expandida a la vez (acordeón).
- A3: "faltan por etapa" incluye las rechazadas, igual que el tope que aplica el servidor.
- A4: el listado de partes registrados en Parte Diario muestra los del día para la orden seleccionada.

## Tasks

| ID | Spec | Route | Status | Commit |
| --- | --- | --- | --- | --- |
| T1 | S2, S4 | inline | pending | — |
| T2 | S4 | inline | pending | — |
| T3 | S4 | delegated | pending | — |
| T4 | S1, S2, S3 | delegated | pending | — |
| T5 | S5 | inline | pending | — |

- T1: backend — `PartesDiariosQuery::rechazadasPorOrdenes`, `etapas_detalle` por orden en `GestionOrdenesDeProduccionService::listar`, y datos del panel de Parte Diario. Tests de Feature.
- T2: backend — `ParteDiarioController::index`, ruta, redirect del store a la nueva sección, opciones para el formulario. Tests de Feature.
- T3: frontend — página `Produccion/ParteDiario/Index.vue` + test.
- T4: frontend — tabla de órdenes: columnas reordenadas, fila expandible con `etapas_detalle`, menú de 3 puntos, eliminación del parte diario embebido. Tests actualizados.
- T5: frontend — entrada de navegación y `AppLayout.test.ts`.

## Log

L1 (2026-11-11, pedido del usuario, verbatim):
"vamos a cambiar la vista de las ordenes de produccion, quiero que la tabla tenga un mejor orden, quiero que al precionar una orden se despligue una subtabla donde se vean las etapas de esa orden y como es el estado de cada una con sus cantidades ya producidad y cuantas faltan por etapa. La seccion de acciones quiero que agrupemos y se desplieguen con un boton de 3 puntos para cada una de las ordenes. La parte de parte diario no se tiene que hacer desde ordenes de produccion, parte diario va a tener su propia seccion, eso que hicimos de unificar ambas seccciones esta mal"

L2: decisión de alcance de Parte Diario = "Carga + avance por etapas" (selector de orden En Curso, avance por etapas, carga y partes del día).

L3 (evidencia de mapeo): `GestionOrdenesDeProduccionService::listar` ya arma `etapas_con_ids`, `partes`, `avance`; `PartesDiariosQuery::conformadoPorOrdenes` y `rechazadasPorEtapa` existen; el tope del servidor es `requerida + rechazadas - conformado`; no existe página ni acción `index` de Parte Diario; la entrada del menú era `/produccion/ordenes-de-produccion?estado=En Curso`.
