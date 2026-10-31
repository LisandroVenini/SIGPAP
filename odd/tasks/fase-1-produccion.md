# Fase 1 — Producción

Objetivo: implementar el módulo Producción (monolito modular, Controller → Service → Model + Query Object cuando aplica).
Rama: `feat/fase-1-produccion`. Entrega por slices; PR al cerrar cada bloque revisable.
Test runner: PHPUnit (`php artisan test`), Vitest (`npm run test:js`), gga como hook de commit.

## Specs

- S1: Catálogo de Etapas (CU-PROD-16..20): ABM + búsqueda. Nombre único (case-insensitive). Sin tiempo en el catálogo.
- S2: Catálogo de Operarios (CU-PROD-24..28): ABM + búsqueda por nombre/apellido/especialidad. Baja bloqueada si el operario está asignado a una OP "En Curso".
- S3: Hoja de ruta (CU-PROD-21/22): asignar/desasignar etapa↔producto con `tiempo_por_unidad > 0`; solo productos terminados de origen "Fabricado"; etapa única por producto; desasignar bloqueado si hay OP "En Curso" con esa hoja de ruta. La hoja de ruta tiene secuencia (`orden`) con unique por producto.
- S4: Planificaciones (CU-PROD-01..05): ABM + búsqueda; `fecha_fin >= fecha_inicio`; baja bloqueada si tiene OP asociadas.
- S5: Órdenes de Producción (CU-PROD-06..10): alta vinculada a planificación, estado inicial "Pendiente" o "Pendiente por Insumos" según stock, reserva lógica; anulación solo si no está "En Curso"; modificación solo si está "Pendiente".
- S6: Recursos e inicio (CU-PROD-11/12): asignar operarios/maquinaria por etapa; máquina bloqueada por mantenimiento no seleccionable; `Asignada → En Curso`.
- S7: Parte Diario, Calidad y Cierre (CU-PROD-13/14/15): avance por etapa, consumo de stock, desgaste de maquinaria, control de calidad, cierre solo con calidad de la última etapa.
- S8: Cronograma (CU-PROD-23) y Mantenimiento (CU-MANT-01..11): se planifican al final del bloque.
- S9: Receta (BOM) del producto terminado: insumos con cantidad expresada en la unidad de medida del insumo (5 tablones ≠ 5 ml). Solo Materia Prima o Insumo Consumible activos; a lo sumo una receta por producto. Habilita la reserva lógica de T5 y el consumo de stock de T7.
- S10: Reserva lógica: la receta no compromete stock; el compromiso nace al crear la OP. Disponible = stock físico − reservas vigentes, con piso 0 (nunca negativo). Alta por lote: una OP por producto, modos `solo_disponibles` (omite las que no alcanzan) y `todas` (las crea en Pendiente por Insumos). Producto sin receta no genera OP. Anulación permitida solo en estados no ejecutados; modificación en Pendiente y Pendiente por Insumos.

## Tasks

- [x] T1 — Etapas: migración soft deletes, modelo + scope, Service, Requests, Policy, Controller, rutas, página Vue, tests. Commit `487b279`.
- [x] T2 — Operarios: soft deletes, modelo + scope, Service, Requests, Policy, Controller, rutas, página Vue, tests. Commit `c492216`.
- [x] T3 — Hoja de ruta (asignar/desasignar etapa a producto). Commit `9985fed`; soporte en Inventario `ffd4a6d`.
- [x] T3.1 — Secuencia de etapas en la hoja de ruta: columna `orden` con backfill, unique `(articulo_id, orden)`, asignar con `max+1`, reindexado al desasignar, mover subir/bajar y `primeraEtapaPorArticulo`/consumo usando la secuencia real. Commit `02cc7c0`.
- [x] T4 — Planificaciones: ABM + búsqueda, período validado (`fecha_fin >= fecha_inicio`), baja bloqueada con órdenes. Commit `212bca6`.
- [x] T4.1 — Receta (BOM) del producto: migración, modelos, Service, Requests, Controller, rutas, sección Receta en la Hoja de Ruta, contrato nuevo en Inventario (`insumosDisponibles`, `resumenesDeArticulos`), docs de dominio. Commit `f57fca9`.
- [x] T5 — Órdenes de Producción + reservas lógicas: contrato de reservas en Inventario (`GestionReservasService`, `ReservasQuery`), alta por lote con modos, listado/búsqueda, anulación y modificación. Commits `a4939b7` y `c2c7a4e`.
- [x] T6 — Recursos e inicio: asignación a nivel orden (operarios y maquinarias), enum `EstadoMaquinaria`, solo máquinas `Disponible` seleccionables, reversión del estado al desasignar todo según cobertura de reservas, inicio explícito `Asignada → En Curso` con `fecha_inicio`. Commit `5f47550`.
- [x] T7.1 — Parte Diario: registro por etapa y fecha (acumulativo), consumo de insumos solo en la primera etapa con el delta, desgaste por etapa, avance real (mínimo conformado entre etapas de la ruta). Commit `d14258e`.
- [ ] T7.2 — Calidad (por parte o por orden), cierre estricto (solo con la cantidad requerida completa) y posposición con pausa y opción de liberar o retener reservas.
- [ ] T8 — Cronograma y Mantenimiento.

## Log

- L1: "dale, borrala y arranquemos con produccion".
- L2: "dale, agrupalas" — se separó el trabajo pendiente en slices revisables: inventario (soporte), T1, T2, T3 y este documento.
- L3: Evidencia — PHPUnit `tests/Feature/Modules/Produccion` + `tests/Feature/Modules/Inventario`: 70 tests, 255 assertions, en verde. Vitest `resources/js/Pages/Produccion`: 4 archivos, 17 tests, en verde. gga en verde en los cuatro commits de código.
- L4: Los slices comparten `routes/web.php` y `ProduccionServiceProvider`; se commitearon con su porción de rutas y policies para que cada uno quede autocontenido. El test de `productosTerminadosFabricados` se agregó en el slice de Inventario porque el cambio de comportamiento lo exigía.
- L5: "okey, sigamos con el modulo de produccion" — T4 implementado test-first: 16 tests nuevos en rojo (clase/route inexistentes) y luego en verde.
- L6: Evidencia T4 — PHPUnit Producción + Inventario + Usuarios: 152 tests, 508 assertions. Vitest Producción: 5 archivos, 22 tests. `npm run typecheck`, Prettier y ESLint sin errores.
- L7: "recien me doy cuenta que nos olvidamos de esto, es lo mas importante. Vamos con la B. Cada producto terminado va a tener la posibilidad de tener una receta, en esta receta vamos a tener las materias primas y agregamos cantidad de la materia prima, la cantidad tiene que variar dependiendo que tipo de unidad de medida tiene el articulo, no es lo mismo poner 5 tablones de madera que 5ml de cola vinilica"
- L8: Hallazgo previo a T5 — el dominio no tenía receta/BOM, así que la reserva lógica de CU-PROD-06 no tenía fuente de verdad; se resolvió con T4.1 antes de T5.
- L9: T4.1 implementado por `gentle-ai-worker` con el brief de contrato (política de delegación adoptada). gga bloqueó el primer commit por una relación Eloquent cruzada (`ComponenteDeReceta::articulo()` apuntando al modelo de Inventario): se quitó la relación y la validación `exists:articulos,id`, y el insumo se valida por el Service público. Se agregó además el control de pertenencia al quitar un componente (`404` si la receta es de otro producto) y el manejo de la violación de unicidad como error de validación.
- L10: Evidencia T4.1 — PHPUnit Producción + Inventario: 107 tests, 374 assertions. Vitest Producción: 27 tests. `vue-tsc`, Prettier y pint sin errores.
- L11: "perfecto, vamos con la A" — una OP fabrica un producto; el alta es por lote y el agrupador sigue siendo la planificación.
- L12: "disponible nunca puede ir en negativo. Una cosa es la receta del producto y otra cosa distinta es la orden de produccion..." — la receta es definición pura; el compromiso nace al crear la OP y el disponible se acota en 0.
- L13: T5 implementado en dos unidades: contrato de reservas inline y Producción delegado a `gentle-ai-worker`. gga bloqueó dos veces: agregación en Service (movida a `ReservasQuery`/`PlanificacionesQuery`), tests con nombres en español, y un bug real de búsqueda (`when([])` descartaba el filtro y devolvía todas las órdenes). Se corrigió con test de regresión RED→GREEN. También se endureció la validación del producto de la línea y el test de Vue que era tautológico.
- L14: Evidencia T5 — PHPUnit Producción + Inventario: 137 tests, 478 assertions. Vitest Producción: 33 tests. `vue-tsc`, pint y Prettier sin errores.
- L15: "vamos con la A" — la asignación de recursos es a nivel orden (contrato CU-PROD-11 y esquema de pivotes); las etapas se muestran solo como contexto.
- L16: T6 delegado a `gentle-ai-worker`. Correcciones del padre: el drawer no precargaba los recursos ya asignados (al guardar borraba la asignación) y el service devolvía mapas keyed-by-id contra un tipo TS de lista. Se agregaron `operario_ids`/`maquinaria_ids` a la fila del listado y tests de precarga. gga PASSED en el primer intento del commit.
- L17: Evidencia T6 — PHPUnit Producción + Inventario: 153 tests, 519 assertions. Vitest Producción: 38 tests. `vue-tsc`, pint y Prettier sin errores.
- L18: "dale, sigamos con la T7. Cuando la produccion real no cincide con lo planificado se consume el stock de la produccion real y el resto sigue quedando como reserva logica"
- L19: "1. Podriamos implentar la posibilidad de hacer las dos... controles de calidad por etapa... o un control de calidad para la orden entera... 2. explicame bien tu propuesta... 3. nunca se puede cerrar una orden de produccion sin fabricar todos los productos definidos... se tiene que poder posponer... La op no se tiene que poder eliminar si ya se comenzo con una de sus etapas" — se define: calidad dual (parte u orden, con `parte_diario_id` nullable), cierre solo con cantidad completa, posposición con pausa (opción A) y anulación solo si nunca se inició.
- L20: "vamos con la A, si se puede posponer una orden ya iniciada... Al seleccionar posponer... podemos desplegar un modal preguntando si se desea devolver esos productos al stock... o si la reserva sigue quedando plasmada" — posponer ofrece liberar o retener las reservas vigentes.
- L21: T7.1 delegado a `gentle-ai-worker`. Corrección del padre: el avance sumaba las unidades de todas las etapas (una unidad contada tres veces); ahora usa el mínimo conformado entre las etapas de la ruta, que es independiente del orden. gga PASSED.
- L22: Evidencia T7.1 — PHPUnit Producción + Inventario: 173 tests, 575 assertions. Vitest Producción: 43 tests.
- L23: Gap detectado — `asignaciones_de_etapa` no tiene secuencia, así que "primera/última etapa" es arbitrario. T7.1 usa el menor `etapa_id` como primera (provisional) y el avance no depende de eso. El cierre de T7.2 necesita la secuencia real: agregar `orden` a la hoja de ruta.
- L24: "agreguemos las secuencias si" — se agrega `orden` a la hoja de ruta para que la primera y la última etapa sean datos reales y no una convención por id.
- L25: Correcciones del padre sobre el output del worker: se eliminó `ultimaEtapaPorArticulo` (código muerto hasta T7.2), se sacó `etapa_id` de `MoverEtapaRequest` porque el controller usa el `{etapa}` de la ruta y el front no lo enviaba —sin ese fix el mover fallaba siempre con error de validación— y se reforzó la aserción del orden en Vitest con un `data-testid`.
- L26: Evidencia — PHPUnit Producción + Inventario: 183 tests, 599 assertions. Vitest Producción: 47 tests. gga PASSED (con un timeout transitorio del proveedor en el primer intento).
