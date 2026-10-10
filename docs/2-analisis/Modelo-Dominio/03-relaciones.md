# Modelo de Dominio — Relaciones entre Conceptos

> **Disciplina:** Análisis · Proceso Unificado
> **Complementa:** `01-conceptos.md`, `02-descripciones.md`
> **Fuente vinculante:** `docs/0-propuesta/propuesta-de-sistema-sigpap.md`

## Convención

Cada relación se documenta en su propia tabla de dos columnas: la izquierda expresa la regla de negocio ("A es [vínculo] de/a B") y la derecha el par de conceptos que la cumple, escrito como "A - B".

## Relaciones

| Concepto | Relación |
| --- | --- |
| A es una parte lógica de B | OrdenDeProduccion - PlanificacionDeProduccion |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | AsignacionDeEtapa - Articulo |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | ParteDiario - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | RegistroPrioridad - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | RegistroPrioridad - Usuario |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | ControlDeCalidad - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | MovimientoDeStock - Articulo |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | IntervencionDeMantenimiento - Maquinaria |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | EventoDeAuditoria - Usuario |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | Incidencia - Proveedor |

| Concepto | Relación |
| --- | --- |
| A es un registro de B | DesempenoProveedor - Proveedor |

| Concepto | Relación |
| --- | --- |
| A es asignado a B | Operario - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A es asignado a B | Maquinaria - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A es asignado a B | Rol - Usuario |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | AsignacionDeEtapa - Etapa |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | OrdenDeProduccion - Articulo |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | Articulo - UnidadDeMedida |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | ParteDiario - Etapa |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | ParteDiario - Maquinaria |

| Concepto | Relación |
| --- | --- |
| A compromete a B | Reserva - Articulo |

| Concepto | Relación |
| --- | --- |
| A compromete a B | Reserva - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | SolicitudDeDisponibilidad - Proveedor |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | SolicitudDeDisponibilidad - Articulo |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | SolicitudDeCotizacion - Proveedor |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | LineaSolicitudCotizacion - SolicitudDeCotizacion |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | LineaSolicitudCotizacion - Articulo |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | Cotizacion - SolicitudDeCotizacion |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | Cotizacion - SolicitudDeDisponibilidad |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | Cotizacion - Proveedor |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | Cotizacion - Articulo |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | OrdenDeCompra - Proveedor |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | OrdenDeCompra - Cotizacion |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | OrdenDeCompra - SolicitudDeDisponibilidad |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | LineaOrdenCompra - OrdenDeCompra |

| Concepto | Relación |
| --- | --- |
| A se vincula a B | LineaOrdenCompra - Articulo |

| Concepto | Relación |
| --- | --- |
| A se emite/origina sobre B | MovimientoDeStock - OrdenDeCompra |

| Concepto | Relación |
| --- | --- |
| A se calcula/emite sobre B | TasaDeRendimiento - Etapa |

| Concepto | Relación |
| --- | --- |
| A se calcula/emite sobre B | Alerta - OrdenDeProduccion |

| Concepto | Relación |
| --- | --- |
| A se calcula/emite sobre B | Alerta - Articulo |

| Concepto | Relación |
| --- | --- |
| A se calcula/emite sobre B | Alerta - Maquinaria |

| Concepto | Relación |
| --- | --- |
| A se calcula/emite sobre B | EscenarioDeSimulacion - Articulo |

| Concepto | Relación |
| --- | --- |
| A rige a B | PlanDeMantenimiento - Maquinaria |

## Notas

1. **`ParametroGlobal` no aparece en las relaciones**: es un concepto autónomo de configuración global (turnos, feriados, umbrales) que no se asocia directamente con los demás conceptos del dominio.
2. **Categoría "parte física" sin instancias**: el modelo no incluye contenedores físicos (Planta, Almacén). Si se quisieran modelar, `Maquinaria - Planta` y `Articulo - Almacén` completarían esa categoría.
3. **`Articulo` juega doble rol** según la relación: es *producto* (fabricado por la orden, evaluado por la simulación) o *insumo* (reservado, solicitado, movido). La relación se lee correctamente por el contexto del par.
4. **`EventoDeAuditoria` admite un actor de sistema además del humano.** La relación `EventoDeAuditoria - Usuario` documenta el registro de acciones de usuarios reales, pero las decisiones automáticas del ComponentePredictivo se auditan con un usuario técnico sintético (`ComponentePredictivo`); la asociación admite ambos actores sin cambiar la relación.
