# Fase 1 — Producción

Objetivo: implementar el módulo Producción (monolito modular, Controller → Service → Model + Query Object cuando aplica).
Rama: `feat/fase-1-produccion`. Entrega por slices; PR al cerrar cada bloque revisable.
Test runner: PHPUnit (`php artisan test`), Vitest (`npm run test:js`), gga como hook de commit.

## Specs

- S1: Catálogo de Etapas (CU-PROD-16..20): ABM + búsqueda. Nombre único (case-insensitive). Sin tiempo en el catálogo.
- S2: Catálogo de Operarios (CU-PROD-24..28): ABM + búsqueda por nombre/apellido/especialidad. Baja bloqueada si el operario está asignado a una OP "En Curso".
- S3: Hoja de ruta (CU-PROD-21/22): asignar/desasignar etapa↔producto con `tiempo_por_unidad > 0`; solo productos terminados de origen "Fabricado"; etapa única por producto; desasignar bloqueado si hay OP "En Curso" con esa hoja de ruta.
- S4: Planificaciones (CU-PROD-01..05): ABM + búsqueda; `fecha_fin >= fecha_inicio`; baja bloqueada si tiene OP asociadas.
- S5: Órdenes de Producción (CU-PROD-06..10): alta vinculada a planificación, estado inicial "Pendiente" o "Pendiente por Insumos" según stock, reserva lógica; anulación solo si no está "En Curso"; modificación solo si está "Pendiente".
- S6: Recursos e inicio (CU-PROD-11/12): asignar operarios/maquinaria por etapa; máquina bloqueada por mantenimiento no seleccionable; `Asignada → En Curso`.
- S7: Parte Diario, Calidad y Cierre (CU-PROD-13/14/15): avance por etapa, consumo de stock, desgaste de maquinaria, control de calidad, cierre solo con calidad de la última etapa.
- S8: Cronograma (CU-PROD-23) y Mantenimiento (CU-MANT-01..11): se planifican al final del bloque.
- S9: Receta (BOM) del producto terminado: insumos con cantidad expresada en la unidad de medida del insumo (5 tablones ≠ 5 ml). Solo Materia Prima o Insumo Consumible activos; a lo sumo una receta por producto. Habilita la reserva lógica de T5 y el consumo de stock de T7.

## Tasks

- [x] T1 — Etapas: migración soft deletes, modelo + scope, Service, Requests, Policy, Controller, rutas, página Vue, tests. Commit `487b279`.
- [x] T2 — Operarios: soft deletes, modelo + scope, Service, Requests, Policy, Controller, rutas, página Vue, tests. Commit `c492216`.
- [x] T3 — Hoja de ruta (asignar/desasignar etapa a producto). Commit `9985fed`; soporte en Inventario `ffd4a6d`.
- [x] T4 — Planificaciones: ABM + búsqueda, período validado (`fecha_fin >= fecha_inicio`), baja bloqueada con órdenes. Commit `212bca6`.
- [x] T4.1 — Receta (BOM) del producto: migración, modelos, Service, Requests, Controller, rutas, sección Receta en la Hoja de Ruta, contrato nuevo en Inventario (`insumosDisponibles`, `resumenesDeArticulos`), docs de dominio. Commit `f57fca9`.
- [ ] T5 — Órdenes de Producción + reservas lógicas.
- [ ] T6 — Recursos e inicio.
- [ ] T7 — Parte Diario, Calidad y Cierre.
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
