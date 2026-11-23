# Alerta de calidad legible y detalle de rechazo en órdenes

Objetivo: hacer comprensible la alerta `calidad_baja` y permitir ver en Órdenes de producción por qué una orden tiene rechazo. Branch: `feat/alerta-calidad-legible`. Delivery: `ask-on-risk`. Tests: `php artisan test --compact` y `npm run test:js`.

## Specs

### S1 — Alerta de calidad comprensible
Usuario: "Me parece que la alerta es poco intuitiva para la persona que lee. Veamos de mejorar esto para esta alerta."

- El mensaje debe nombrar el producto real, no `Orden #N`.
- Debe expresar la magnitud: cantidad rechazada sobre el total del parte.
- Debe indicar la etapa y el motivo cuando existan.
- El detalle no debe volcar claves técnicas ni ids internos.

### S2 — Ver el porqué del rechazo en Órdenes de producción
Usuario: "Dentro de ordenes de produccion no puedo ver porque esa orden de produccvion tiene tanto rechazo ni nada por el estilo, veamos eso tambien"

- La tabla de etapas debe mostrar las unidades rechazadas por etapa.
- El drawer de calidad debe listar los controles ya registrados (fecha, etapa, aprobadas, rechazadas, motivo).
- Debe mostrarse un resumen acumulado de rechazo de la orden.

### S3 — Alcance acordado
Usuario: "vamos con A+B, luego vemos el ciclo de vida"

- A: mensaje, contexto, migración de datos y detalle legible.
- B: rechazadas por etapa e historial de calidad en la orden.

### S4 — Ciclo de vida de la alerta
Usuario: "dale, vamos con el ciclo de vidas de las alertas"

- Una sola alerta abierta por orden y regla de `calidad_baja`.
- Cada control posterior actualiza esa alerta con su porcentaje, etapa y motivo mientras siga superando el umbral.
- Si un control posterior queda por debajo del umbral, la alerta se resuelve.
- Al cerrar la orden, su alerta abierta de calidad se resuelve.
- La migración consolida las alertas abiertas históricas y resuelve las de órdenes cerradas o anuladas.

## Tasks

| ID | Spec | Route | Status | Commit |
| --- | --- | --- | --- | --- |
| T1 | S1 | inline | done | — |
| T2 | S1 | inline | done | — |
| T3 | S1 | inline | done | — |
| T4 | S1 | inline | done | — |
| T5 | S2 | inline | done | — |
| T6 | S2 | inline | done | — |
| T7 | S1, S2 | inline | done | — |
| T8 | S4 | inline | done | — |
| T9 | S4 | inline | done | — |
| T10 | S4 | inline | done | — |
| T11 | S4 | inline | done | — |

- T1: `GestionCalidadService` resuelve el producto con `GestionArticulosService` y agrega `rechazadas`, `unidades`, `etapa`, `motivo`.
- T2: catálogo (`variables` y `detalle`) y mensaje por defecto en `ReglaDeAlertaSeeder`.
- T3: migración de datos que actualiza solo la regla con el texto viejo y rehace las alertas abiertas.
- T4: detalle legible por tipo en `Alertas/Index.vue`.
- T5: `listar()` incluye `controles` con `CalidadQuery::controlesPorOrdenes`.
- T6: columna Rechazadas e historial de calidad en `OrdenesDeProduccion/Index.vue`.
- T7: tests backend y frontend, pint y suite.
- T8: referencia `orden:<id>` y emisión en cada control (actualiza o resuelve).
- T9: `GestionCierreService` resuelve la alerta de calidad al cerrar.
- T10: migración `2026_11_18_170000_consolidate_calidad_baja_alerts_per_order`.
- T11: tests de actualización, resolución por mejora y resolución al cerrar.

## Log

L1 (2026-11-18, pedido del usuario, verbatim):
"perfecto, estoy viendo las alertas y veo que tengo una que dice \"la orden 4 tiene 6.25% de rechazo\" y sus detalles \"orden: 4 · producto: Orden #4 · rechazo pct: 6.25 · orden de produccion id: 4\".

1. Me parece que la alerta es poco intuitiva para la persona que lee. Veamos de mejorar esto para esta alerta.
2. Dentro de ordenes de produccion no puedo ver porque esa orden de produccvion tiene tanto rechazo ni nada por el estilo, veamos eso tambien 
Definamos un plan primero y luego seguimos viendo"

L2 (2026-11-18, decisión del usuario, verbatim):
"vamos con A+B, luego vemos el ciclo de vida"

L3 (cierre): implementado A+B. `GestionCalidadService` resuelve el producto con `GestionArticulosService` y emite contexto con `rechazadas`, `unidades`, `etapa` y `motivo`. La regla por defecto usa la nueva plantilla y el catálogo expone `detalle` por tipo. La migración `2026_11_18_160000_refresh_calidad_baja_alert_copy` actualiza la regla solo si conserva el texto viejo y rehace las alertas abiertas: la alerta local pasó a «Mesa Paraíso Extensible 1,80 m» tiene 6.25% de rechazo: 1 de 16 unidades en Corte. En Órdenes de producción se agregó la columna Rechazadas por etapa y el historial de controles con resumen en el drawer de calidad. Verificación: 764 tests PHP, 229 tests JS, `npm run build` y `pint` en verde.

L4 (verificación independiente): el verificador marcó PARTIAL por un blocker real: las alertas `calidad_baja` legacy (resueltas o sin regla) no tienen `rechazadas`, `unidades`, `etapa` ni `motivo`, así que la plantilla de detalle quedaba deformada. Corrección: `contextoLegible` usa `tipo.detalle` solo si todos los placeholders tienen valor; si no, cae al listado genérico con etiquetas y sin ids. Se agregó el test de regresión de alerta legacy, se endureció el parseo de `clave_dedupe` y el `down()` de la migración. Recheck acotado: PASS. Además se cubrió el mensaje de control a nivel orden (`la orden completa`). Verificación final: 764 tests PHP, 231 tests JS, `npm run build` y `pint` en verde.

L5 (2026-11-18, pedido del usuario, verbatim):
"dale, vamos con el ciclo de vidas de las alertas"

L6 (cierre del ciclo de vida): `calidad_baja` usa referencia `orden:<id>`, así que cada control actualiza la misma alerta abierta o la resuelve si queda bajo el umbral. `GestionCierreService` resuelve la alerta al cerrar la orden. La migración `2026_11_18_170000_consolidate_calidad_baja_alerts_per_order` deja una abierta por (regla, orden), resuelve duplicados y las de órdenes cerradas/anuladas; en la base local la alerta pasó de `8:calidad_baja:calidad:3` a `8:calidad_baja:orden:4`. La migración además resuelve la alerta conservada si el último control ya está bajo el umbral, y resuelve las alertas sin regla que no pueden reevaluarse. Verificación independiente: PASS en S4; el recheck del borde detectó el caso de regla borrada y se cerró con la resolución de alertas sin regla. Tests: actualización entre controles, resolución por mejora y resolución al cerrar.
