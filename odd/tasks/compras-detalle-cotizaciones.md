# Compras: mostrar la cotización del proveedor y adjudicar desde Solicitudes

Objetivo: reparar el flujo de respuesta del proveedor. La cotización existía en la base pero no se mostraba en ninguna pantalla; ahora se consulta y se adjudica desde Solicitudes de Cotización, alineado con la pantalla C-02 de Stitch.
Branch: `feat/compras-detalle-cotizaciones`. Delivery: `ask-on-risk`. Tests: `php artisan test --compact`, `npm run test:js`.

## Specs

### S1 — Reproducción del reporte
Usuario: "Cree una solicitud de cotizacion de prueba, se la mande a un proveedor. El proveedor la contesto pero luego no aparecio la cotizacion del proveedor en ningun lado del sistema."

- Al momento del reporte, la base tenía `solicitudes_de_cotizacion` id 1 (`Enviada`), un proveedor con token consumido, `cotizaciones` id 1 y una línea ($10,00 × 2 u, entrega 12/11).
- `GestionOrdenesDeCompraService::solicitudesConCotizaciones()` y `GestionInformeMulticriterioService::paraSolicitudes([1])` devolvían los datos correctamente.
- Causa: la única UI que renderizaba una cotización recibida era el drawer "Emitir OC" de Órdenes de Compra. En Solicitudes solo se veía un contador.

### S2 — Tabla de Solicitudes alineada con C-02
- Columnas: Código (`SC-AAAA-0001`), Insumos, Emitida, Vence, Estado visible, Proveedores, Respuestas (`recibidas / esperadas`), Acciones.
- Acciones por fila: `Ver detalle`, `Modificar` (solo sin cotizaciones y no anulada), `Anular` (no anulada ni adjudicada). Se conservan los filtros, la paginación y los drawers existentes.

### S3 — Estado visible derivado (sin migración)
- `Anulada` y `Adjudicada` se muestran tal cual.
- `Enviada` con al menos una cotización → `Respondida`.
- `Enviada` sin cotizaciones y con vencimiento pasado → `Vencida`.
- El estado persistido no cambia; la derivación vive en el servicio.

### S4 — Detalle de la solicitud con las cotizaciones recibidas
- Drawer con: datos de la solicitud, insumos pedidos, proveedores invitados con su estado (`Respondió` / `Esperando respuesta` / `Sin enlace`), y cada cotización con sus líneas: artículo, precio unitario, cantidad pedida, cantidad ofertada, fecha prometida y subtotal.
- Se muestran dos totales etiquetados para evitar la ambigüedad detectada: **Costo de lo pedido** (cantidad solicitada × precio ofertado) y **Subtotal ofertado** (cantidad ofertada × precio ofertado).
- Se incluye el informe multicriterio de la solicitud (proveedor, precio, días, cobertura, puntaje) y la recomendación cuando exista.
- Sin cotizaciones, estado vacío explícito.

### S5 — Adjudicar y emitir OC desde el detalle
- Botón `Adjudicar y emitir OC` por cotización; reutiliza `POST /compras/ordenes-de-compra` con `cotizacion_id` y confirmación previa.
- Deshabilitado cuando el estado visible es `Adjudicada` o `Anulada`.
- Los errores de validación del endpoint (`solicitud`, `cotizacion`) se muestran dentro del drawer.
- El drawer "Emitir OC" de Órdenes de Compra se mantiene sin cambios.

### Supuestos
- A1: los estados `Respondida` y `Vencida` son de presentación; no se persisten ni se agregan al enum.
- A2: el detalle se envía junto con el listado (15 filas por página), sin endpoint adicional.
- A3: no se modifica el portal del proveedor ni el contrato de emisión de OC.

## Tasks

| ID | Spec | Route | Status | Commit |
| --- | --- | --- | --- | --- |
| T1 | S1, S3 | inline | done | — |
| T2 | S4 | inline | done | — |
| T3 | S2, S5 | inline | done | — |
| T4 | S4, S5 | inline | done | — |

- T1: backend — `listar()` agrega `codigo`, `estado_visible` y `respuestas`; `detallesDeSolicitudes()` arma el detalle.
- T2: backend — el controller de Solicitudes pasa `detalles` e `informes`.
- T3: frontend — tabla C-02 con Respuestas y acciones.
- T4: frontend — drawer de detalle con líneas de cotización, totales, informe y adjudicación; tests de página.

## Log

L1 (2026-11-11, pedido del usuario, verbatim):
"perfecto, ahora vamos a pasar al modulo de compras.
Cree una solicitud de cotizacion de prueba, se la mande a un proveedor. El proveedor la contesto pero luego no aparecio la cotizacion del proveedor en ningun lado del sistema. Revisemos todo ese flujo, que todo se muestre bien, que se utilicen las pantallas correspondientes. En stitch hay pantallas correspondientes para todos estos casos como para usarlas de ejemplo"

L2 (reproducción): la cotización id 1 existía con su línea; el backend la servía; la única UI que la mostraba era el drawer Emitir OC de Órdenes de Compra. Verificado con `php artisan tinker` sobre la base real.

L3 (decisión del usuario): "Detalle + adjudicar, estado derivado (Recomendado)". Sin migraciones; los estados Respondida/Vencida se derivan.

L4 (cierre): implementado. 23 tests entre servicio y controller de Compras; 10 tests de la página; `php artisan test --compact tests/Feature/Modules/Compras` 147/147; `npm run test:js` 177/177; typecheck, eslint, Pint y Prettier limpios. Pantalla de referencia: `SIGPAP — Solicitudes de Cotización (C-02)` del proyecto Stitch `SIGPAP · Salvia & Teja`.
