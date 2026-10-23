# Modelo de Datos — Migraciones

> Implementar en migraciones el diseño aprobado en `docs/4-implementacion/diseno-base-de-datos.md`.
> Rama: `feat/migraciones-modelo-datos` · Delivery: un PR que cierra `#3` · Runner: `php artisan test` (PHPUnit + PostgreSQL).

## Specs

- S1: las 39 tablas del diseño, en orden de dependencia de FK.
- S2: `usuarios` renombrada por migración nueva (`name`→`nombre`, `password`→`clave_acceso`, + `estado`); no se edita la migración ya aplicada (AGENTS.md:29).
- S3: spatie/laravel-permission; `Usuario` con `HasRoles` y morph map `usuario`; pivotes en RESTRICT.
- S4: FKs `ON DELETE RESTRICT`; CHECK XOR en `cotizaciones` y `ordenes_de_compra`.
- S5: modelos Eloquent con `$table` explícito y relaciones.
- S6: tests de esquema y de restricciones.

## Tasks

- [x] T1 — Usuarios/Seguridad base: spatie + `usuarios` + `parametros_globales`
- [x] T2 — Inventario base: `unidades_de_medida`, `articulos`
- [x] T3 — Producción: etapas, operarios, maquinarias, planificaciones, órdenes, pivotes, asignaciones, partes, controles, planes, intervenciones, registros de prioridad
- [x] T4 — Compras: proveedores, solicitudes, pivotes, líneas, cotizaciones, líneas, OC, líneas, incidencias, desempeños
- [x] T5 — Inventario dependiente: reservas, movimientos de stock
- [x] T6 — Predictivo: tasas de rendimiento, escenarios de simulación
- [x] T7 — Shared/Auditoría: alertas, eventos de auditoría
- [ ] T8 — Modelos + relaciones
- [ ] T9 — Factories + tests de esquema/restricciones
- [ ] T10 — Cierre: `Closes #3`

## Log

- L1: "dale, si ya todo quedo correcto, hagamos el primero pr al repo y luego empezamos con las migraciones".
- L2: se vuelve a migración nueva para el renombre de `usuarios` (decisión b), porque `gga` bloquea editar migraciones aplicadas (AGENTS.md:29).
- L3: T2–T7 implementados (32 migraciones) + `DatabaseSchemaTest` (XOR, RESTRICT, unique, existencia de tablas). 14 tests / 83 assertions verdes.
