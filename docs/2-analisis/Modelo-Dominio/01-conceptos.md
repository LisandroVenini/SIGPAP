# Modelo de Dominio — Conceptos

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/0-propuesta/propuesta-de-sistema-sigpap.md`
> **Glosario de respaldo:** `docs/1-requisitos/10-glosario.md`

## Criterio de inclusión

Un concepto entra al modelo de dominio si cumple **todas** estas condiciones:

1. Es un **concepto del negocio real**, no del software.
2. Tiene **identidad propia** (es un "qué", no un "cómo").
3. Hay que **recordarlo** (el sistema persiste datos sobre él).

Quedan **excluidos** por principio: valores derivados (Stock Disponible, Ranking), vistas y salidas (Gantt, Dashboard, Reporte), componentes de software (ModeloPredictivo) y procesos (Licitación Predictiva).

## Lista de conceptos idóneos (31)

### Producción

| Concepto | Descripción |
| --- | --- |
| PlanificacionDeProduccion | Campaña que agrupa órdenes de producción en un período. |
| OrdenDeProduccion | Orden de fabricación de un producto, con cantidad y fechas; transita estados. |
| RegistroPrioridad | Historial de cambios de prioridad de una orden de producción, con la justificación de cada reordenamiento (motivo, autor, fecha). |
| Etapa | Paso del catálogo de fabricación (Lijado, Pintado, Armado, Embalado). |
| AsignacionDeEtapa | Vínculo producto↔etapa con su tiempo por unidad (la hoja de ruta). |
| ParteDiario | Registro de avance por etapa y turno (unidades conformadas). |
| Operario | Persona de planta asignada a las órdenes. |
| Maquinaria | Equipo de producción con desgaste acumulado y estado operativo. |
| ControlDeCalidad | Inspección del lote: unidades aprobadas, rechazadas y motivo. |
| PlanDeMantenimiento | Regla de umbral de uso que dispara el mantenimiento preventivo. |
| IntervencionDeMantenimiento | Registro de una intervención concreta sobre una máquina. |

### Inventario

| Concepto | Descripción |
| --- | --- |
| Articulo | Ítem del catálogo (materia prima, insumo o producto terminado), con estado Activo/Inactivo por baja lógica. |
| Reserva | Reserva lógica de un insumo para una orden en curso. |
| MovimientoDeStock | Entrada, salida o ajuste de existencias, con historial. |
| UnidadDeMedida | Unidad y factor de conversión entre unidades. |

### Proveedores y Compras

| Concepto | Descripción |
| --- | --- |
| Proveedor | Entidad comercial externa, con desempeño histórico y estado Activo/Inactivo (los inactivos no participan en nuevas licitaciones). |
| SolicitudDeDisponibilidad | Pedido directo a un proveedor (Flujo A); transita estados de consulta (Enviada, En Espera, Respondida). |
| SolicitudDeCotizacion | RFQ a varios proveedores con vencimiento (Flujo B); transita estados (Borrador, Enviada, Adjudicada, Anulada). |
| Cotizacion | Respuesta: precio, cantidad y fecha prometida; marca si fue la oferta adjudicada. |
| OrdenDeCompra | Pedido firme emitido al proveedor ganador; la recepción admite entrega parcial. |
| Incidencia | Retraso, faltante o rotura que alimenta el ranking. |
| LineaSolicitudCotizacion | Línea de la solicitud: artículo a cotizar y cantidad pedida. |
| LineaOrdenCompra | Línea de la orden: artículo comprado, cantidad y precio pactado. |
| DesempenoProveedor | Registro del tiempo de entrega real de cada entrega; insumo del ranking y la criticidad. |

### Análisis Predictivo

| Concepto | Descripción |
| --- | --- |
| TasaDeRendimiento | TRR por etapa + desviación respecto del estándar. |
| Alerta | Notificación de riesgo (reposición, caducidad, quiebre, cuello de botella, técnica). |
| EscenarioDeSimulacion | Parámetros de una simulación + impacto proyectado. |

### Auditoría

| Concepto | Descripción |
| --- | --- |
| EventoDeAuditoria | Registro de evento crítico (usuario, fecha, IP, estado previo/nuevo). |

### Usuarios y Seguridad

| Concepto | Descripción |
| --- | --- |
| Usuario | Cuenta de acceso al sistema, con estado Activo/Inhabilitado. |
| Rol | Perfil de permisos (RBAC). |
| ParametroGlobal | Variable de configuración de la fábrica (turnos, feriados, umbrales). |

## Decisiones de modelado

1. **`AsignacionDeEtapa` como clase de asociación.** Producto↔Etapa es N:M con dato propio (tiempo por unidad); ese dato no es ni del producto ni de la etapa.
2. **`Articulo` sin jerarquía.** Las tres variantes comparten atributos; la diferencia la captura el atributo `tipo` (Materia Prima / Insumo Consumible / Producto Terminado).
3. **`Proveedor` es actor y concepto a la vez.** No se excluyen mutuamente.
4. **`SectorProductivo` descartado.** Sector == etapa; la TRR se calcula por `Etapa`.
5. **`Lote` fusionado en `ControlDeCalidad`.** El lote es el conjunto inspeccionado; se registra la inspección.
6. **`Alerta` unifica los tipos de notificación** (reposición, caducidad, quiebre de stock, cuello de botella predictivo, preventivo y técnica por falla/timeout del sistema) mediante un atributo `tipo`.
7. **`ParametroGlobal` captura la configuración de negocio** (turnos, feriados, umbrales de tolerancia), que antes aparecía dispersa.
8. **`LineaSolicitudCotizacion` y `LineaOrdenCompra` como clases de asociación.** Solicitud↔Artículo y Orden↔Artículo son N:M con dato propio (cantidad por línea, precio pactado); ese dato no es ni de la cabecera ni del artículo, igual que en `AsignacionDeEtapa`.
9. **`Cotizacion` se asocia al `Articulo` cotizado.** Cada oferta refiere el artículo concreto de la solicitud; el precio, la cantidad ofertada y la fecha prometida quedan en la cotización.
10. **`DesempenoProveedor` registra el desempeño de entregas reales.** Cada entrega confirmada y recibida genera un registro; el ranking y la evaluación de criticidad son valores derivados de este historial.
11. **`RegistroPrioridad` respalda la prioridad de las órdenes.** La prioridad de programación de una OP deja de ser una vista derivada: es un atributo de `OrdenDeProduccion` (`prioridadActual`) y cada cambio de prioridad —con su justificación— se persiste en `RegistroPrioridad`, complementando la auditoría técnica con el historial de negocio.
12. **`Articulo.origen` distingue el producto terminado de tercero.** La diferencia entre fabricar y comprar listo se captura con un atributo (Fabricado / Tercero), no con un tipo de artículo nuevo: un mismo artículo puede cambiar de origen si la fábrica decide fabricarlo. Los de origen Tercero no generan órdenes de producción ni hoja de ruta.

## Excluidos (con justificación)

| Elemento | Motivo de exclusión |
| --- | --- |
| Stock Físico, Stock Disponible | Atributo / valor derivado de `Articulo`. |
| Ranking de Proveedores | Derivado del desempeño (`Incidencia`). |
| Gantt (Cronograma), Dashboard, Reporte | Vistas / salidas, no conceptos. |
| ModeloPredictivo | Componente de software (ML), no concepto de negocio. |
| Licitación Predictiva | Proceso; sus entidades son `SolicitudDeCotizacion`, `Cotizacion`, `OrdenDeCompra`. |
| Umbral de Tolerancia | Parámetro; se modela como `ParametroGlobal`. |
