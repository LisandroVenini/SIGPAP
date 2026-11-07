# Fase 2 — Compras

Objetivo: implementar el módulo Compras (CU-COM-01..20) sobre el monolito modular, en slices revisables.
Rama: `feat/fase-2-compras` (a crear desde `main`). Test runner: PHPUnit (`php artisan test`), Vitest (`npm run test:js`), gga como hook de commit.

## Specs

- S1: Proveedores (CU-COM-01..05): alta con razón social, CUIT único y email opcional único; estado inicial `Activo`; modificación con CUIT inmutable; baja lógica a `Inactivo`; listado paginado y búsqueda por razón social, CUIT o email.
- S2: Permiso `compras.gestionar` para Administrador y Encargado de Compras.
- S3: Auditoría de alta, modificación y baja de proveedor.
- S4: Solicitudes de cotización (CU-COM-06..10): líneas por artículo, proveedores destinatarios con token y fecha de vencimiento; estado inicial `Enviada`; modificación bloqueada si ya hay cotizaciones; anulación si no fue adjudicada.
- S5: Cotizaciones y órdenes de compra (CU-COM-11..17): recepción de cotización por token, adjudicación, emisión de OC con `numero_oc`, modificación solo en `Emitida`, anulación solo en `Emitida`, confirmación del proveedor.
- S6: Disponibilidad y ranking (CU-COM-18..20): depende del Componente Predictivo y del shared kernel `Alerta`; se planifica al final.

## Tasks

- [x] C1 — Proveedores: modelo + scope, Service, Policy, Requests, Controller, rutas, permiso, página Vue, tests. Commit `7b739aa`.
- [ ] C2 — Solicitudes de cotización.
- [ ] C3 — Cotizaciones, adjudicación y órdenes de compra.
- [ ] C4 — Disponibilidad y ranking.

## Log

- L1: "vamos con merge y empecemos con el modulo de compras"
- L2: C1 delegado a `gentle-ai-worker`. gga PASSED al primer intento.
- L3: Evidencia C1 — PHPUnit Compras + Usuarios: 87 tests, 269 assertions. Vitest: 5 tests. typecheck y pint limpios.
- L4: Desvío consciente: la búsqueda usa `ILIKE` (PostgreSQL es case-sensitive) y la unicidad de email se valida en el Service porque el esquema no tiene índice único y las migraciones quedaron fuera de la superficie del slice.
