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
- [x] C2 — Solicitudes de cotización: alta con líneas y proveedores, token UUID por proveedor, envío real por SMTP (Mailtrap), portal público con token de un solo uso, listado con búsqueda (proveedor, estado, fechas), modificación bloqueada con cotizaciones y anulación. Commits `043dd82` y `93af01c`.
- [x] C3 — Adjudicación y órdenes de compra: emisión desde una cotización con `numero_oc` secuencial, líneas derivadas, marca de adjudicación, listado con búsqueda, modificación y anulación solo en `Emitida`, y confirmación/rechazo del proveedor por token de un solo uso con correo. Commits `db66743`, `0110609` y `00004d8`.
- [ ] C4 — Disponibilidad y ranking.

## Log

- L1: "vamos con merge y empecemos con el modulo de compras"
- L2: C1 delegado a `gentle-ai-worker`. gga PASSED al primer intento.
- L3: Evidencia C1 — PHPUnit Compras + Usuarios: 87 tests, 269 assertions. Vitest: 5 tests. typecheck y pint limpios.
- L4: Desvío consciente: la búsqueda usa `ILIKE` (PostgreSQL es case-sensitive) y la unicidad de email se valida en el Service porque el esquema no tiene índice único y las migraciones quedaron fuera de la superficie del slice.
- L5: "necesitamos si o si mandarlo por mail / hay que implementar envio por mails" — se configuró SMTP de Mailtrap en `.env` y se verificó un envío real antes de codificar el flujo.
- L6: `.env.example` documentado con el transporte SMTP (sin credenciales); `.env` queda fuera del repo.
- L7: Correcciones del padre sobre el output del worker: al modificar una solicitud se regeneraba el token de proveedores existentes sin reenviarles el correo (perdían el enlace) y el portal aceptaba artículos ajenos a la solicitud. Ambos con test.
- L8: gga bloqueó la primera versión por lectura directa de `eventos_de_auditoria` (ahora vía `AuditoriaService::usuarioResponsableDe`) y por agregación en el Service (movida a `SolicitudesDeCotizacionQuery`). Además: mails fuera de la transacción, búsqueda CU-COM-10, throttling del portal y topes de precio/cantidad.
- L9: Evidencia C2 — PHPUnit Compras: 62 tests, 301 assertions. Vitest Compras + Portal: 16 tests. gga PASSED en ambos commits.
- L10: Correcciones del padre: se eliminó un Query Object para un filtro de una línea, se movió la consulta multi-tabla de solicitudes con cotizaciones a `SolicitudesDeCotizacionQuery`, y se limpiaron PHPDoc huérfanos y código muerto detectados por gga.
- L11: Evidencia C3 — PHPUnit Compras: 103 tests, 521 assertions. Vitest Compras + Portal: 25 tests. gga PASSED en los tres commits.
- L12: El token de confirmación de OC se agregó con una migración nueva; quedó en un commit aparte por olvido de staging, ya corregido.
