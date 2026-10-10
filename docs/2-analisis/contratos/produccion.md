# Contratos de Operación — Producción

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/1-requisitos/casos-de-uso/produccion.md` · `docs/2-analisis/DSS/produccion.md`
> **Plantilla:** `docs/2-analisis/contratos/_plantilla-contrato.md`

## Nota de alcance

- Se documenta un contrato por **operación del sistema** con reglas de negocio (altas, modificaciones y flujos críticos), en total **16**. El `Nombre` coincide carácter por carácter con el mensaje del DSS (trazabilidad vertical estricta).
- Los ABM triviales y las consultas **no llevan contrato** (Larman): CU-PROD-02, 04, 05, 07, 09, 10, 17, 19, 20, 25, 27, 28.
- Convención de firmas: parámetros por nombre de dominio sin tipo de dato (consistente con `DSS/produccion.md` y `DSS/inventario.md`).
- Los parámetros de la firma que no tienen atributo en el Modelo de Dominio se señalan en `Notas` de cada contrato y se consolidan en el informe de trazabilidad (no se modifica el dominio).

## CU-PROD-01 — Alta de Planificación de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaPlanificacion(nombre, fechaInicio, fechaFin, observaciones)` |
| **Responsabilidades** | Registrar una nueva planificación marco que agrupará múltiples órdenes de producción. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-01 |
| **Notas** | — |
| **Excepciones** | Si `fechaFin` es anterior a `fechaInicio`, el sistema advierte al actor y detiene el guardado hasta su corrección. |
| **Salida** | `confirmacionAlta()`; en caso de error, la advertencia de fechas inválidas. |
| **Precondiciones** | El actor está autenticado con el rol Supervisor de Producción. |
| **Postcondiciones** | Se creó una instancia de `PlanificacionDeProduccion`. Se inicializaron `PlanificacionDeProduccion.nombre`, `PlanificacionDeProduccion.fechaInicio`, `PlanificacionDeProduccion.fechaFin` y `PlanificacionDeProduccion.observaciones`. |

## CU-PROD-03 — Modificación de Planificación de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarPlanificacion(planificacion, nombre, fechaInicio, fechaFin, observaciones)` |
| **Responsabilidades** | Actualizar los datos de una planificación de producción existente. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-03 |
| **Notas** | — |
| **Excepciones** | Si `fechaFin` es anterior a `fechaInicio`, el sistema advierte al actor y detiene el guardado hasta su corrección. |
| **Salida** | `confirmacionModificacion()`. |
| **Precondiciones** | Existe la instancia de `PlanificacionDeProduccion` a modificar. |
| **Postcondiciones** | Se modificaron `PlanificacionDeProduccion.nombre`, `PlanificacionDeProduccion.fechaInicio`, `PlanificacionDeProduccion.fechaFin` y `PlanificacionDeProduccion.observaciones`. |

## CU-PROD-06 — Alta de Orden de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaOrdenProduccion(producto, cantidad, fechaEntrega, planificacion)` |
| **Responsabilidades** | Crear una nueva Orden de Producción vinculada a una planificación, comprometiendo lógicamente los insumos del producto. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-06 |
| **Notas** | La "hoja de ruta" que se carga internamente es la ya existente en `AsignacionDeEtapa` (producto↔etapa); no se recrea aquí. |
| **Excepciones** | Si el stock físico no alcanza para cubrir la reserva lógica, el sistema deja la orden en estado "Pendiente por Insumos" e informa el quiebre inminente al actor. |
| **Salida** | `confirmacionAlta()`. |
| **Precondiciones** | Existe al menos una `PlanificacionDeProduccion` activa y el producto (`Articulo` tipo Producto Terminado) con su hoja de ruta definida. |
| **Postcondiciones** | Se creó una instancia de `OrdenDeProduccion`. Se inicializó `OrdenDeProduccion.estado` en "Pendiente", `OrdenDeProduccion.cantidadRequerida` con `cantidad` y `OrdenDeProduccion.fechaEntrega`. Se asoció la orden a la `PlanificacionDeProduccion` y al `Articulo` (producto). Se crearon las instancias de `Reserva` por insumo y se asociaron a la `OrdenDeProduccion` y al `Articulo` (insumo). |

## CU-PROD-08 — Modificación de Orden de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarOrdenProduccion(orden, cantidad, fechaEntrega)` |
| **Responsabilidades** | Actualizar cantidad y fecha de entrega de una orden aún no iniciada, reajustando las reservas lógicas. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-08 |
| **Notas** | — |
| **Excepciones** | Si la orden está en estado "En Curso", el sistema bloquea la modificación e informa al actor. Si el stock físico no alcanza para la nueva reserva, deja la orden en "Pendiente por Insumos" e informa. |
| **Salida** | `confirmacionModificacion()`. |
| **Precondiciones** | Existe la `OrdenDeProduccion` a modificar y se encuentra en estado "Pendiente". |
| **Postcondiciones** | Se modificaron `OrdenDeProduccion.cantidadRequerida` y `OrdenDeProduccion.fechaEntrega`. Se modificó `Reserva.cantidad` de las reservas asociadas a la orden. |

## CU-PROD-16 — Alta de Etapa de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaEtapa(nombre, descripcion)` |
| **Responsabilidades** | Registrar una nueva etapa del catálogo de fabricación. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-16 |
| **Notas** | La etapa del catálogo no lleva tiempo: el tiempo por unidad se define al asignarla a un producto (CU-PROD-21). |
| **Excepciones** | Si el `nombre` ya existe en otra etapa, el sistema cancela el guardado e informa el error. |
| **Salida** | `confirmacionAlta()`. |
| **Precondiciones** | El actor está autenticado con el rol Supervisor de Producción. |
| **Postcondiciones** | Se creó una instancia de `Etapa`. Se inicializaron `Etapa.nombre` y `Etapa.descripcion`. |

## CU-PROD-18 — Modificación de Etapa de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarEtapa(etapa, nombre, descripcion)` |
| **Responsabilidades** | Actualizar el nombre o la descripción de una etapa del catálogo. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-18 |
| **Notas** | La modificación no afecta los tiempos ya definidos en `AsignacionDeEtapa`. |
| **Excepciones** | Si el nuevo `nombre` ya existe en otra etapa, el sistema cancela el guardado e informa el error. |
| **Salida** | `confirmacionModificacion()`. |
| **Precondiciones** | Existe la `Etapa` a modificar. |
| **Postcondiciones** | Se modificaron `Etapa.nombre` y `Etapa.descripcion`. |

## CU-PROD-21 — Asignar Etapa a Producto

| Campo | Contenido |
| --- | --- |
| **Nombre** | `asignarEtapaProducto(producto, etapa, tiempoPorUnidad)` |
| **Responsabilidades** | Vincular una etapa del catálogo a un producto terminado, definiendo su tiempo estimado por unidad (hoja de ruta). |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-21 |
| **Notas** | `producto` corresponde al concepto `Articulo` (tipo Producto Terminado); el vínculo se materializa en `AsignacionDeEtapa` (clase de asociación). |
| **Excepciones** | Si la etapa ya está asignada al producto, el sistema bloquea la operación. Si `tiempoPorUnidad` es cero o negativo, rechaza el guardado. |
| **Salida** | `confirmacionAsignacion()`. |
| **Precondiciones** | Existen el `Articulo` (producto) y la `Etapa`; la etapa no está aún asignada al producto. |
| **Postcondiciones** | Se creó una instancia de `AsignacionDeEtapa`. Se inicializó `AsignacionDeEtapa.tiempoPorUnidad`. Se asoció la `AsignacionDeEtapa` al `Articulo` (producto) y a la `Etapa`. |

## CU-PROD-23 — Gestionar Cronograma de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `gestionarCronograma(orden, nuevaPosicion)` |
| **Responsabilidades** | Reordenar las órdenes activas dentro del cronograma dinámico (Gantt) y persistir el nuevo ordenamiento. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-23 |
| **Notas** | El cronograma (Gantt) es una vista/salida explícitamente excluida del Modelo de Dominio; `nuevaPosicion`/prioridad no tiene concepto ni atributo persistente. |
| **Excepciones** | Si el reordenamiento incumple una `fechaEntrega`, el sistema advierte del conflicto antes de persistir. Si la orden está "En Curso", bloquea el reordenamiento. |
| **Salida** | `confirmacionCronograma()`; en su caso, la advertencia de conflicto de entrega. |
| **Precondiciones** | Existen `OrdenDeProduccion` activas con su hoja de ruta definida. |
| **Postcondiciones** | Se modificó el ordenamiento de las `OrdenDeProduccion` activas en el cronograma (el orden/posición carece de concepto persistente en el dominio). |

## CU-PROD-24 — Alta de Operario

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaOperario(nombre, apellido, especialidad)` |
| **Responsabilidades** | Registrar un nuevo operario de la planta. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-24 |
| **Notas** | — |
| **Excepciones** | Si los datos obligatorios están incompletos, el sistema detiene el guardado e indica los campos faltantes. |
| **Salida** | `confirmacionAlta()`. |
| **Precondiciones** | El actor está autenticado con el rol Supervisor de Producción. |
| **Postcondiciones** | Se creó una instancia de `Operario`. Se inicializaron `Operario.nombre`, `Operario.apellido` y `Operario.especialidad`. |

## CU-PROD-26 — Modificación de Operario

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarOperario(operario, nombre, apellido, especialidad)` |
| **Responsabilidades** | Actualizar los datos de un operario existente. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-26 |
| **Notas** | — |
| **Excepciones** | Si los datos obligatorios quedan incompletos, el sistema detiene el guardado e indica los campos faltantes. |
| **Salida** | `confirmacionModificacion()`. |
| **Precondiciones** | Existe el `Operario` a modificar. |
| **Postcondiciones** | Se modificaron `Operario.nombre`, `Operario.apellido` y `Operario.especialidad`. |

## CU-PROD-11 — Asignar Recursos a Orden

| Campo | Contenido |
| --- | --- |
| **Nombre** | `asignarRecursosOrden(orden, operarios, maquinaria)` |
| **Responsabilidades** | Asignar operarios responsables y maquinaria a las etapas de una orden, dejándola lista para iniciar. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-11 |
| **Notas** | La desasignación de un recurso es un flujo de modificación dentro del mismo caso de uso. |
| **Excepciones** | Si una `Maquinaria` está bloqueada por mantenimiento, el sistema la muestra deshabilitada y no permite su selección. |
| **Salida** | `confirmacionAsignacion()`. |
| **Precondiciones** | La `OrdenDeProduccion` está en estado "Pendiente" o "Pendiente por Insumos"; existen `Operario` y `Maquinaria` activas. |
| **Postcondiciones** | Se asociaron los `Operario` a la `OrdenDeProduccion`. Se asoció la `Maquinaria` a la `OrdenDeProduccion`. Se modificó `OrdenDeProduccion.estado` a "Asignada". |

## CU-PROD-12 — Iniciar Orden de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `iniciarOrdenProduccion(orden)` |
| **Responsabilidades** | Iniciar explícitamente la ejecución de una orden ya asignada. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-12 |
| **Notas** | — |
| **Excepciones** | Si la orden no tiene todos sus recursos asignados, el sistema bloquea el inicio e informa al actor. |
| **Salida** | `confirmacionInicio()`. |
| **Precondiciones** | La `OrdenDeProduccion` está en estado "Asignada", con operarios y maquinaria asignados. |
| **Postcondiciones** | Se modificó `OrdenDeProduccion.estado` a "En Curso". Se inicializó `OrdenDeProduccion.fechaInicio` con la fecha de inicio de la ejecución. |

## CU-PROD-13 — Cargar Parte Diario

| Campo | Contenido |
| --- | --- |
| **Nombre** | `cargarParteDiario(orden, cantidad, observaciones)` |
| **Responsabilidades** | Registrar el avance de un turno: asentar unidades conformadas, consumir stock físico, incrementar desgaste y disparar la evaluación predictiva. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-13 |
| **Notas** | La evaluación de la `TasaDeRendimiento` se dispara de forma asíncrona hacia el Componente Predictivo (sin retorno al actor en este flujo). |
| **Excepciones** | Si `cantidad` supera `OrdenDeProduccion.cantidadRequerida`, el sistema exige confirmación doble antes de guardar. |
| **Salida** | `confirmacionCargaParte()`. |
| **Precondiciones** | Existe una `OrdenDeProduccion` en estado "En Curso". |
| **Postcondiciones** | Se creó una instancia de `ParteDiario`. Se inicializaron `ParteDiario.fecha`, `ParteDiario.cantidadConformada` con `cantidad` y `ParteDiario.observaciones` con `observaciones`. Se asoció el `ParteDiario` a la `OrdenDeProduccion`, a la `Etapa` y a la `Maquinaria`. Se creó una instancia de `MovimientoDeStock` de consumo y se asoció al `Articulo` (insumo). Se decrementó `Articulo.stockFisico`. Se rompió la asociación entre las `Reserva` comprometidas y la `OrdenDeProduccion`. Se incrementó `Maquinaria.desgasteAcumulado`. |

## CU-PROD-14 — Registrar Control de Calidad

| Campo | Contenido |
| --- | --- |
| **Nombre** | `registrarControlCalidad(parteDiario, unidadesRechazadas, motivo)` |
| **Responsabilidades** | Registrar la inspección de calidad de un lote, descontando las unidades rechazadas. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-14 |
| **Notas** | En el dominio el `ControlDeCalidad` se asocia a la `OrdenDeProduccion` (no al `ParteDiario`). `cantidadAprobada` se deriva como unidades declaradas menos rechazadas. |
| **Excepciones** | Si `unidadesRechazadas` supera la cantidad declarada en el parte, el sistema rechaza el guardado. |
| **Salida** | `confirmacionControlCalidad()`. |
| **Precondiciones** | Existe un `ParteDiario` previamente cargado con unidades declaradas. |
| **Postcondiciones** | Se creó una instancia de `ControlDeCalidad`. Se inicializaron `ControlDeCalidad.fecha`, `ControlDeCalidad.cantidadRechazada` con `unidadesRechazadas`, `ControlDeCalidad.motivoRechazo` con `motivo` y `ControlDeCalidad.cantidadAprobada` (derivada). Se asoció el `ControlDeCalidad` a la `OrdenDeProduccion`. |

## CU-PROD-15 — Cerrar Orden de Producción

| Campo | Contenido |
| --- | --- |
| **Nombre** | `cerrarOrdenProduccion(orden, unidadesFinales)` |
| **Responsabilidades** | Cerrar explícitamente una orden, consolidando las unidades válidas producidas. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-15 |
| **Notas** | — |
| **Excepciones** | Si no existe `ControlDeCalidad` para la última etapa, el sistema bloquea el cierre e informa al actor. |
| **Salida** | `confirmacionCierre()`. |
| **Precondiciones** | La `OrdenDeProduccion` está en estado "En Curso", la última etapa reportó avance completo y existe `ControlDeCalidad` para ella. |
| **Postcondiciones** | Se modificó `OrdenDeProduccion.estado` a "Cerrada". Se inicializó `OrdenDeProduccion.cantidadProducida` con `unidadesFinales`. |

## CU-PROD-22 — Desasignar Etapa de Producto

| Campo | Contenido |
| --- | --- |
| **Nombre** | `desasignarEtapaProducto(producto, etapa)` |
| **Responsabilidades** | Quitar una etapa de la hoja de ruta de un producto, rompiendo la asignación existente. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PROD-22 |
| **Notas** | Rompe la asociación materializada en `AsignacionDeEtapa` (rotura de asociación, postcondición contractual según la plantilla). El bloqueo aplica mientras existan órdenes en curso que usen esa hoja de ruta. |
| **Excepciones** | Si existe una `OrdenDeProduccion` en estado "En Curso" que usa la etapa, el sistema bloquea la desasignación e informa al actor. |
| **Salida** | `confirmacionDesasignacion()`. |
| **Precondiciones** | Existe la `AsignacionDeEtapa` entre el `Articulo` (producto) y la `Etapa`. |
| **Postcondiciones** | Se destruyó la instancia de `AsignacionDeEtapa` que vinculaba el `Articulo` (producto) con la `Etapa`. Se rompió la asociación producto-etapa. |
