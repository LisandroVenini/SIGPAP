# Fase 1 — Inventario → Producción

> Objetivo: catálogo/stock de Inventario y luego el ciclo de Producción.
> Rama: `feat/fase-1-inventario` · Runner: `php artisan test` (PHPUnit + PostgreSQL).

## Specs

- S1: Catálogo de artículos (CU-INV-01..05): alta, baja lógica, modificación, listado con stock físico/comprometido/disponible, búsqueda por SKU/nombre/tipo. Roles: Administrador y Encargado de Stock.
- S2: Umbrales de stock (CU-INV-08): `stock_minimo <= stock_maximo`.
- S3: Ajuste de inventario (CU-INV-07): stock observado + motivo, diferencia, movimiento inmutable, sin stock negativo.
- S4: Ingreso de mercancía (CU-INV-06) → depende de Compras (Fase 2), se difiere.
- S5: Producción (planificaciones, órdenes, etapas, hoja de ruta, parte diario, operarios, maquinarias, mantenimiento, control de calidad) → siguiente bloque.

## Tasks

- [x] T1 — Catálogo backend: enums `TipoArticulo`/`OrigenArticulo`, seeder de unidades, `GestionArticulosService`, `ArticulosQuery`, Form Requests, `ArticuloPolicy`, `ArticuloController`, rutas + tests.
- [x] T2 — Catálogo UI: `Inventario/Index` (listado + drawer) basado en Stitch I-01/I-02 + tests.
- [x] T3 — Ajuste de inventario backend + UI (I-05, página dedicada `/inventario/ajustes`) + tests.
- [ ] T4 — Producción.

## Log

- L1: "dale, arranquemos fase 1".
- L2: se arranca por Inventario; CU-INV-06 (ingreso de mercancía) se difiere a Fase 2 por depender de Compras.
- L3: T1+T2 implementados (catálogo de artículos: servicio, query, requests, policy, controller, UI). Autorización por permiso `inventario.gestionar` vía `UsuariosService::autorizar`. 89 tests PHP / 364 assertions, 21 Vitest, gga PASSED.
- L4: T3 implementado (ajuste de inventario: `GestionAjustesService`, `MovimientoDeStock` de tipo Ajuste, auditoría, lockForUpdate, UI). 96 tests PHP / 382 assertions, 22 Vitest. Convención aclarada en AGENTS.md: verbos de dominio en español + `Usuario` como principal permitido.
- L5: El ajuste de inventario se movió del catálogo a la página dedicada `Inventario/Ajuste` (`/inventario/ajustes`, Stitch I-05), que es la que usa la solapa "Ajuste de Inventario" del sidebar. Se quitó la acción "Ajustar" y el drawer duplicado del catálogo. 98 tests PHP / 395 assertions, 24 Vitest, gga PASSED.
