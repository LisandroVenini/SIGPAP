# Inventario — Ingreso de mercancía

Objetivo: implementar CU-INV-06 (recepción de mercancía contra una OC) y desbloquear el ranking de proveedores.
Rama: `feat/inventario-ingreso` (a crear desde `main`). Runner: PHPUnit, Vitest, gga.

## Specs

- S1: `fecha_confirmacion` en `ordenes_de_compra`, seteada al confirmar la OC (CU-COM-17).
- S2: Inventario orquesta la recepción (`GestionIngresosService`): valida la OC y las cantidades, incrementa `stock_fisico`, crea un `MovimientoDeStock` de tipo Ingreso con `numero_remito` y `orden_de_compra_id`.
- S3: Compras expone `GestionRecepcionesService` con los datos de la OC a recepcionar y el cierre de la recepción (estado `Recibida`, `entrega_parcial`, `DesempenoProveedor` e `Incidencia`s automáticas por faltante o retraso).
- S4: Excedente requiere confirmación explícita; faltante se asienta como entrega parcial cuando el usuario lo confirma.
- S5: UI de recepción con selección de OC, remito y cantidades por línea.
- S6: Permiso `inventario.gestionar`.

## Tasks

- [x] I1 — Ingreso de mercancía (CU-INV-06): migración de `fecha_confirmacion`, `GestionRecepcionesService` en Compras (cierre, desempeño e incidencias), `GestionIngresosService` en Inventario (stock, movimientos y auditoría), Controller, rutas, UI y tests. Commit `f7a397f`.

## Log

- L1: "dale, vamos con eso" — cerrar Compras con PR y seguir con Inventario CU-INV-06 para desbloquear el ranking de proveedores.
- L2: Delegado a `gentle-ai-worker`; corregidas dos violaciones de gga (Query Object para un filtro de una línea y estados de recepción duplicados) y un PHPDoc huérfano.
- L3: Evidencia I1 — PHPUnit Inventario + Compras: 171 tests, 749 assertions. Vitest Inventario: 11 tests. gga PASSED.
- L4: Pendientes no bloqueantes detectados por gga: enum `EstadoOrdenDeCompra` (hoy literales duplicados) y enums para los tipos de `Incidencia`.
- L5: CU-INV-06 desbloquea el ranking de proveedores (CU-COM-18), que ahora tiene datos de `DesempenoProveedor` e `Incidencia`.
