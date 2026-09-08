# Modelo de Dominio — Atributos

> **Disciplina:** Análisis · Proceso Unificado
> **Complementa:** `01-conceptos.md`, `02-descripciones.md`, `03-relaciones.md`
> **Fuente vinculante:** `docs/0-propuesta/propuesta-de-sistema-sigpap.md`

## Reglas aplicadas (Larman)

1. **Tipos de datos simples o conceptuales.** Solo números, textos, fechas, booleanos o tipos conceptuales puros (Cantidad, Porcentaje, Estado). Un atributo jamás es otra clase conceptual.
2. **Sin claves foráneas.** La unión entre conceptos se expresa con asociaciones, nunca con atributos del tipo `id_articulo`.
3. **Redacción declarativa.** Se describe qué representa el dato para el negocio, con unidades y restricciones si corresponde; sin jerga de programación (nada de `varchar`, `float`, nombres de pantalla).

## Producción

### PlanificacionDeProduccion

| Concepto | Atributo – Descripción |
| --- | --- |
| PlanificacionDeProduccion | nombre: denominación de la campaña que la identifica frente a las demás. |
| PlanificacionDeProduccion | fechaInicio: fecha en la que comienza el período planificado. |
| PlanificacionDeProduccion | fechaFin: fecha en la que finaliza el período planificado. |
| PlanificacionDeProduccion | observaciones: notas libres de la campaña (opcional). |

### OrdenDeProduccion

| Concepto | Atributo – Descripción |
| --- | --- |
| OrdenDeProduccion | estado: etapa del ciclo de vida en que se encuentra la orden (Pendiente, Pendiente por Insumos, Asignada, En Curso, Cerrada o Anulada). |
| OrdenDeProduccion | cantidadRequerida: número de unidades del producto que se deben fabricar. |
| OrdenDeProduccion | fechaEntrega: fecha límite en la que el pedido debe estar terminado para el cliente. |
| OrdenDeProduccion | fechaInicio: fecha en que la orden inició efectivamente su ejecución (registrada en el inicio de la orden). |
| OrdenDeProduccion | cantidadProducida: número de unidades válidas consolidadas al cierre de la orden. |

### Etapa

| Concepto | Atributo – Descripción |
| --- | --- |
| Etapa | nombre: denominación de la etapa de fabricación (Lijado, Pintado, Armado, Embalado). |
| Etapa | descripcion: explicación breve de lo que se realiza en la etapa. |

### AsignacionDeEtapa

| Concepto | Atributo – Descripción |
| --- | --- |
| AsignacionDeEtapa | tiempoPorUnidad: tiempo estimado que insume producir una unidad en la etapa asignada. |

### ParteDiario

| Concepto | Atributo – Descripción |
| --- | --- |
| ParteDiario | fecha: fecha del turno al que corresponde el registro. |
| ParteDiario | cantidadConformada: número de unidades conformadas en la etapa durante el turno. |
| ParteDiario | observaciones: comentarios del turno (opcional). |

### Operario

| Concepto | Atributo – Descripción |
| --- | --- |
| Operario | nombre: nombre del operario de planta. |
| Operario | apellido: apellido del operario de planta. |
| Operario | especialidad: tarea o etapa en la que el operario está especializado. |

### Maquinaria

| Concepto | Atributo – Descripción |
| --- | --- |
| Maquinaria | nombre: denominación que identifica el equipo de producción. |
| Maquinaria | tipo: clase de maquinaria (ej. sierra, torno, lijadora). |
| Maquinaria | estadoOperativo: condición de disponibilidad del equipo (Disponible, En Mantenimiento, Bloqueada). |
| Maquinaria | desgasteAcumulado: desgaste acumulado según el volumen de unidades procesadas. |

### ControlDeCalidad

| Concepto | Atributo – Descripción |
| --- | --- |
| ControlDeCalidad | fecha: fecha en que se realizó la inspección. |
| ControlDeCalidad | cantidadAprobada: número de unidades que superaron la inspección. |
| ControlDeCalidad | cantidadRechazada: número de unidades rechazadas. |
| ControlDeCalidad | motivoRechazo: causa del rechazo de las unidades defectuosas. |

### PlanDeMantenimiento

| Concepto | Atributo – Descripción |
| --- | --- |
| PlanDeMantenimiento | umbralUso: volumen de uso (unidades procesadas) a partir del cual se exige el mantenimiento preventivo. |
| PlanDeMantenimiento | descripcion: descripción de la intervención preventiva que dispara el plan. |

### IntervencionDeMantenimiento

| Concepto | Atributo – Descripción |
| --- | --- |
| IntervencionDeMantenimiento | fecha: fecha en que se realizó la intervención. |
| IntervencionDeMantenimiento | tipo: clase de intervención realizada sobre la máquina. |
| IntervencionDeMantenimiento | responsable: persona o taller que ejecutó el mantenimiento. |
| IntervencionDeMantenimiento | observaciones: comentarios de la intervención (opcional). |

## Inventario

### Articulo

| Concepto | Atributo – Descripción |
| --- | --- |
| Articulo | codigoSKU: código único que identifica el artículo en el catálogo. |
| Articulo | descripcion: denominación o detalle del artículo. |
| Articulo | tipo: clasificación del artículo (Materia Prima, Insumo Consumible o Producto Terminado). |
| Articulo | stockFisico: cantidad real del artículo existente en el depósito. |
| Articulo | stockMinimo: umbral mínimo que dispara la alerta de reposición. |
| Articulo | stockMaximo: tope de existencias deseado para el artículo. |
| Articulo | fechaVencimiento: fecha de caducidad, aplicable a los insumos consumibles. |
| Articulo | estado: condición del artículo en el catálogo (Activo, Inactivo); la baja lógica lo inactiva conservando su historial. |

### Reserva

| Concepto | Atributo – Descripción |
| --- | --- |
| Reserva | cantidad: cantidad del insumo comprometida lógicamente para una orden. |

### MovimientoDeStock

| Concepto | Atributo – Descripción |
| --- | --- |
| MovimientoDeStock | tipo: clase de movimiento (ingreso, consumo, salida o ajuste). |
| MovimientoDeStock | cantidad: número de unidades que variaron en el movimiento. |
| MovimientoDeStock | fecha: fecha en que se registró el movimiento. |
| MovimientoDeStock | motivo: causa del movimiento, aplicable a ajustes y mermas. |
| MovimientoDeStock | numeroRemito: número de remito de la recepción, aplicable a ingresos. |

### UnidadDeMedida

| Concepto | Atributo – Descripción |
| --- | --- |
| UnidadDeMedida | nombre: denominación de la unidad (metro, kilogramo, litro, unidad). |
| UnidadDeMedida | factorConversion: factor que relaciona la unidad con su unidad base. |

## Proveedores y Compras

### Proveedor

| Concepto | Atributo – Descripción |
| --- | --- |
| Proveedor | razonSocial: denominación legal o comercial del proveedor. |
| Proveedor | cuit: código de identificación tributaria del proveedor. |
| Proveedor | email: correo electrónico de contacto. |
| Proveedor | telefono: número de teléfono de contacto. |
| Proveedor | estado: condición del proveedor (Activo, Inactivo); los inactivos no participan en nuevas licitaciones. |

### SolicitudDeDisponibilidad

| Concepto | Atributo – Descripción |
| --- | --- |
| SolicitudDeDisponibilidad | fecha: fecha en que se envió la consulta al proveedor. |
| SolicitudDeDisponibilidad | cantidadSolicitada: cantidad del insumo sobre la que se consulta disponibilidad. |
| SolicitudDeDisponibilidad | estado: ciclo de vida de la consulta (Enviada, En Espera, Respondida). |

### SolicitudDeCotizacion

| Concepto | Atributo – Descripción |
| --- | --- |
| SolicitudDeCotizacion | fecha: fecha en que se emitió la solicitud de cotización. |
| SolicitudDeCotizacion | fechaVencimiento: fecha límite para recibir cotizaciones. |
| SolicitudDeCotizacion | estado: ciclo de vida de la solicitud (Borrador, Enviada, Adjudicada, Anulada); el borrador puede generarlo el motor predictivo y la anulación se bloquea si fue adjudicada. |

### Cotizacion

| Concepto | Atributo – Descripción |
| --- | --- |
| Cotizacion | precioUnitario: precio ofertado por cada unidad. |
| Cotizacion | cantidadOfertada: cantidad que el proveedor se compromete a entregar. |
| Cotizacion | fechaPrometida: fecha en la que el proveedor promete entregar. |
| Cotizacion | adjudicada: marca si la cotización fue seleccionada como oferta ganadora (verdadero) o no (falso). |

### OrdenDeCompra

| Concepto | Atributo – Descripción |
| --- | --- |
| OrdenDeCompra | numeroOC: número de orden de compra, identificador natural con el que se referencia el pedido firme. |
| OrdenDeCompra | estado: estado del pedido firme (Emitida, Confirmada, Recibida —incluye la variante Entrega Parcial cuando la recepción llega con menos cantidad—, Anulada o Rechazada). |
| OrdenDeCompra | fechaEmision: fecha en que se emitió la orden de compra. |

### Incidencia

| Concepto | Atributo – Descripción |
| --- | --- |
| Incidencia | tipo: clase de incidencia (retraso, faltante o rotura). |
| Incidencia | fecha: fecha en que se registró la incidencia. |
| Incidencia | detalle: descripción del evento de desempeño negativo. |

### LineaSolicitudCotizacion

| Concepto | Atributo – Descripción |
| --- | --- |
| LineaSolicitudCotizacion | cantidadSolicitada: número de unidades del artículo pedidas a cotizar. |

### LineaOrdenCompra

| Concepto | Atributo – Descripción |
| --- | --- |
| LineaOrdenCompra | cantidad: número de unidades del artículo compradas. |
| LineaOrdenCompra | precioPactado: precio unitario acordado para el artículo. |

### DesempenoProveedor

| Concepto | Atributo – Descripción |
| --- | --- |
| DesempenoProveedor | tiempoEntregaReal: días transcurridos entre la confirmación y la recepción de la mercancía, de una entrega concreta. |
| DesempenoProveedor | fechaRegistro: fecha en que se registró esa entrega. |

## Análisis Predictivo

### TasaDeRendimiento

| Concepto | Atributo – Descripción |
| --- | --- |
| TasaDeRendimiento | valor: velocidad real de producción calculada para la etapa. |
| TasaDeRendimiento | desviacion: diferencia porcentual entre la velocidad real y el estándar esperado. |
| TasaDeRendimiento | fechaCalculo: fecha en que se calculó la tasa. |

### Alerta

| Concepto | Atributo – Descripción |
| --- | --- |
| Alerta | tipo: clase de condición de riesgo (reposición, caducidad, quiebre de stock, cuello de botella predictivo, preventiva o técnica por falla/timeout del sistema). |
| Alerta | fecha: fecha en que se emitió la alerta. |
| Alerta | descripcion: detalle de la condición que motiva la alerta. |

### EscenarioDeSimulacion

| Concepto | Atributo – Descripción |
| --- | --- |
| EscenarioDeSimulacion | cantidadFicticia: cantidad del pedido urgente que se quiere evaluar. |
| EscenarioDeSimulacion | fechaLimite: fecha límite deseada para el pedido simulado. |
| EscenarioDeSimulacion | prioridad: nivel de urgencia del pedido dentro del escenario. |
| EscenarioDeSimulacion | impactoProyectado: demora estimada (en días) sobre las órdenes existentes. |

## Auditoría

### EventoDeAuditoria

| Concepto | Atributo – Descripción |
| --- | --- |
| EventoDeAuditoria | fechaHora: momento en que ocurrió el evento auditado. |
| EventoDeAuditoria | direccionIP: dirección desde la que se realizó la operación. |
| EventoDeAuditoria | accion: operación crítica registrada. |
| EventoDeAuditoria | estadoAnterior: valor que tenía el registro antes del cambio. |
| EventoDeAuditoria | estadoNuevo: valor del registro después del cambio. |

## Usuarios y Seguridad

### Usuario

| Concepto | Atributo – Descripción |
| --- | --- |
| Usuario | nombre: nombre de la persona titular de la cuenta. |
| Usuario | email: correo electrónico que identifica la cuenta de acceso. |
| Usuario | claveAcceso: contraseña de autenticación del usuario. |
| Usuario | estado: condición de la cuenta (Activo, Inhabilitado); el inhabilitado no puede iniciar sesión. |

### Rol

| Concepto | Atributo – Descripción |
| --- | --- |
| Rol | nombre: denominación del perfil de permisos (Supervisor de Producción, Gerente, Administrador, etc.). |

### ParametroGlobal

| Concepto | Atributo – Descripción |
| --- | --- |
| ParametroGlobal | nombre: denominación del parámetro de configuración. |
| ParametroGlobal | valor: valor asignado al parámetro. |
| ParametroGlobal | categoria: agrupación a la que pertenece (Producción, Compras, Motor Predictivo). |
