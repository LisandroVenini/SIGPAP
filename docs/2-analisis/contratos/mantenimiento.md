# Contratos de Operación — Mantenimiento

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/2-analisis/DSS/mantenimiento.md`
> **Plantilla:** `docs/2-analisis/contratos/_plantilla-contrato.md`

Se documentan solo las operaciones del sistema con regla de negocio (altas, modificaciones y flujos críticos). Los ABM triviales (Baja de Maquinaria, Baja de Plan de Mantenimiento, los listados y las búsquedas) no llevan contrato.

## CU-MANT-01 — altaMaquinaria

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaMaquinaria(nombre, tipo)` |
| **Responsabilidades** | Registrar una nueva maquinaria en la planta, con desgaste acumulado inicial en cero. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-MANT-01 |
| **Notas** | `nombre` y `tipo` tienen respaldo como atributos de `Maquinaria`. El umbral de desgaste crítico no se solicita en el alta (CU-MANT-01 corregido): se define en el Plan de Mantenimiento (CU-MANT-06, `PlanDeMantenimiento.umbralUso`). |
| **Excepciones** | Si los datos obligatorios están incompletos, se detiene el guardado y se informa el error al actor. |
| **Salida** | Mensaje de éxito (`confirmacionAlta()`) y listado de maquinarias actualizado. |
| **Precondiciones** | El `SupervisorDeProduccion` está autenticado. |
| **Postcondiciones** | Se creó una instancia de `Maquinaria`. Se inicializaron `Maquinaria.nombre` y `Maquinaria.tipo`. Se estableció `Maquinaria.desgasteAcumulado` en cero. Se estableció `Maquinaria.estadoOperativo` en Disponible. |

## CU-MANT-03 — modificarMaquinaria

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarMaquinaria(maquinaria, nombre, tipo)` |
| **Responsabilidades** | Actualizar los datos editables de una maquinaria existente. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-MANT-03 |
| **Notas** | `nombre` y `tipo` tienen respaldo como atributos de `Maquinaria`. Por decisión de dominio el umbral de desgaste pertenece a `PlanDeMantenimiento.umbralUso` y se edita en CU-MANT-08; el CU-MANT-03 fue corregido para no listarlo (igual que CU-MANT-01). |
| **Excepciones** | — |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe una `Maquinaria` con la identidad indicada. |
| **Postcondiciones** | Se modificaron `Maquinaria.nombre` y `Maquinaria.tipo`. |

## CU-MANT-06 — altaPlanMantenimiento

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaPlanMantenimiento(maquinaria, umbralUso, descripcion)` |
| **Responsabilidades** | Definir una regla de mantenimiento preventivo por uso para una maquinaria. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-MANT-06 |
| **Notas** | — |
| **Excepciones** | Si `umbralUso` es cero o negativo, se detiene el guardado y se informa el error al actor. |
| **Salida** | Mensaje de éxito (`confirmacionAlta()`) y listado de planes actualizado. |
| **Precondiciones** | Existe una `Maquinaria` a la que aplicará el plan. |
| **Postcondiciones** | Se creó una instancia de `PlanDeMantenimiento` con `umbralUso` y `descripcion` asignados. Se asoció el `PlanDeMantenimiento` a la `Maquinaria`. |

## CU-MANT-08 — modificarPlanMantenimiento

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarPlanMantenimiento(plan, umbralUso, descripcion)` |
| **Responsabilidades** | Actualizar una regla de mantenimiento preventivo existente. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-MANT-08 |
| **Notas** | — |
| **Excepciones** | Si `umbralUso` queda en cero o negativo, se detiene el guardado y se informa el error al actor. |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe un `PlanDeMantenimiento` con la identidad indicada. |
| **Postcondiciones** | Se modificaron `PlanDeMantenimiento.umbralUso` y `PlanDeMantenimiento.descripcion`. |

## CU-MANT-11 — registrarMantenimiento

| Campo | Contenido |
| --- | --- |
| **Nombre** | `registrarMantenimiento(maquinaria, fecha, tipo, observaciones)` |
| **Responsabilidades** | Registrar la ejecución de una intervención de mantenimiento, reiniciar el desgaste de la maquinaria y dejarla operativa. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-MANT-11 |
| **Notas** | `responsable` está modelado en `IntervencionDeMantenimiento`, pero el CU-MANT-11 no lo recolecta; queda disponible para la decisión de diseño. |
| **Excepciones** | Si la maquinaria no está bloqueada, la intervención se registra igualmente como mantenimiento programado y se reinicia el desgaste. |
| **Salida** | Mensaje de éxito (`confirmacionMantenimiento()`). |
| **Precondiciones** | Existe una `Maquinaria`, bloqueada por alcanzar su umbral de desgaste o con mantenimiento programado. |
| **Postcondiciones** | Se creó una instancia de `IntervencionDeMantenimiento` con `fecha`, `tipo` y `observaciones` asignados. Se asoció la `IntervencionDeMantenimiento` a la `Maquinaria`. Se modificó `Maquinaria.desgasteAcumulado` (reiniciado en cero). Se modificó `Maquinaria.estadoOperativo` a Disponible. |
