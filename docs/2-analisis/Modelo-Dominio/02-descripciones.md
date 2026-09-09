# Modelo de Dominio — Descripción de Conceptos

> **Disciplina:** Análisis · Proceso Unificado
> **Complementa:** `01-conceptos.md`
> **Fuente vinculante:** `docs/0-propuesta/propuesta-de-sistema-sigpap.md`

Para cada concepto se describe su **Símbolo** (nombre PascalCase, compartimento superior de la clase), su **Intensión** (definición formal del concepto) y su **Extensión** (conjunto de instancias reales que lo componen).

## Producción

### PlanificacionDeProduccion

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | PlanificacionDeProduccion |
| Intensión | Representa la campaña marco de producción que agrupa, en un período determinado, el conjunto de órdenes de fabricación que la fábrica se compromete a ejecutar. Define los límites temporales de inicio y fin que enmarcan el trabajo de la planta. |
| Extensión | El conjunto de todas las campañas reales de la planta de Confort y Muebles (por ejemplo: la campaña "septiembre-noviembre 2026" que agrupa las órdenes de muebles del trimestre). |

### OrdenDeProduccion

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | OrdenDeProduccion |
| Intensión | Representa el mandato formal de fabricación emitido por la gerencia para manufacturar un lote específico de muebles en la planta. Define de manera única el producto a fabricar, la cantidad solicitada y los tiempos límite de inicio y fin, y transita estados durante su ciclo de vida (Pendiente, Pendiente por Insumos, Asignada, En Curso, Cerrada o Anulada). |
| Extensión | El conjunto de todas las órdenes de trabajo reales y activas en el piso de la planta de Confort y Muebles (por ejemplo: la orden OP-501 del 07/09/2026 para fabricar 50 Sillas de Pino). |

### Etapa

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Etapa |
| Intensión | Representa un paso genérico del proceso de fabricación incluido en el catálogo de la planta (Lijado, Pintado, Armado, Embalado). Es independiente del producto: su tiempo de ejecución se define al asignarla a un producto concreto. |
| Extensión | El conjunto de todas las etapas estandarizadas de la fábrica (por ejemplo: Lijado, Pintado, Armado y Embalado), que se combinan para formar las hojas de ruta de los distintos muebles. |

### AsignacionDeEtapa

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | AsignacionDeEtapa |
| Intensión | Representa la inclusión de una etapa específica dentro de la hoja de ruta de un producto, junto con el tiempo estimado que insume producir una unidad en esa etapa. Es el dato que permite calcular los tiempos de fabricación y alimentar el cronograma. |
| Extensión | El conjunto de todas las asignaciones etapa↔producto de la fábrica (por ejemplo: la asignación de "Lijado" a la Silla de Pino con 5 minutos por unidad, y la de "Lijado" a la Mesa de Pino con 20 minutos). |

### ParteDiario

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | ParteDiario |
| Intensión | Representa el registro diario que el Supervisor carga al final del turno, indicando las unidades conformadas en cada etapa. Es la fuente transaccional del avance físico de la orden y el insumo del análisis predictivo. |
| Extensión | El conjunto de todos los partes diarios cargados en la planta (por ejemplo: el parte del 07/09/2026 que registra 40 unidades de Silla de Pino conformadas en la etapa de Armado). |

### Operario

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Operario |
| Intensión | Representa a una persona del piso de planta que participa en la ejecución de las órdenes de producción. Posee una especialidad y es asignado a las etapas de las órdenes junto con la maquinaria. |
| Extensión | El conjunto de todos los operarios de la fábrica Confort y Muebles (por ejemplo: Juan Pérez, operario con especialidad en Pintado). |

### Maquinaria

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Maquinaria |
| Intensión | Representa un equipo de producción de la fábrica. Posee un estado operativo (Disponible, En Mantenimiento, Bloqueada) y acumula desgaste en función del volumen de unidades procesadas, lo que habilita su bloqueo preventivo. |
| Extensión | El conjunto de todas las máquinas del piso de planta (por ejemplo: la lijadora de banda L-03 y la cabina de pintado P-01). |

### ControlDeCalidad

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | ControlDeCalidad |
| Intensión | Representa la inspección que el Supervisor realiza sobre el lote producido, registrando las unidades aprobadas, las rechazadas y el motivo del rechazo. Es requisito obligatorio para cerrar una orden de producción. |
| Extensión | El conjunto de todas las inspecciones realizadas en la planta (por ejemplo: el control del 08/09/2026 que aprobó 48 unidades y rechazó 2 por defecto de lijado). |

### PlanDeMantenimiento

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | PlanDeMantenimiento |
| Intensión | Representa la regla de mantenimiento preventivo que define el umbral de uso (volumen procesado o desgaste) a partir del cual una maquinaria debe bloquearse preventivamente y recibir una intervención, junto con la descripción de la intervención preventiva que dispara el plan. |
| Extensión | El conjunto de todos los planes de mantenimiento de la fábrica (por ejemplo: el plan que obliga a intervenir la cabina de pintado cada 10.000 unidades procesadas). |

### IntervencionDeMantenimiento

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | IntervencionDeMantenimiento |
| Intensión | Representa el registro de una intervención de mantenimiento concreta realizada sobre una maquinaria (fecha, tipo, responsable y observaciones). Al registrarse libera el bloqueo preventivo y restablece el desgaste acumulado. |
| Extensión | El conjunto de todas las intervenciones realizadas (por ejemplo: el cambio de rodillos de la lijadora L-03 realizado el 05/09/2026 por el taller externo). |

## Inventario

### Articulo

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Articulo |
| Intensión | Representa un ítem del catálogo de la fábrica, que puede ser materia prima, insumo consumible o producto terminado. Identifica de manera única cada material o producto mediante su SKU, lleva su unidad de medida y su stock, y posee un estado (Activo/Inactivo): la baja lógica lo inactiva conservando el historial en las órdenes pasadas. |
| Extensión | El conjunto de todos los artículos del catálogo de Confort y Muebles (por ejemplo: la madera de pino como materia prima, el barniz como insumo y la Silla de Pino como producto terminado). |

### Reserva

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Reserva |
| Intensión | Representa la reserva lógica de una cantidad de un insumo comprometida para una orden de producción en curso. No descuenta el stock físico; separa la cantidad comprometida de la disponible para nuevas órdenes. |
| Extensión | El conjunto de todas las reservas vigentes de la fábrica (por ejemplo: la reserva de 120 metros de madera de pino para la orden OP-501). |

### MovimientoDeStock

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | MovimientoDeStock |
| Intensión | Representa una variación registrada de las existencias de un artículo, sea un ingreso de mercancía, un consumo por producción, una salida o un ajuste manual. Conserva el historial inmutable de los cambios de stock. |
| Extensión | El conjunto de todos los movimientos de stock registrados (por ejemplo: el ingreso de 200 metros de madera del remito 1234 y el ajuste por merma del 06/09/2026). |

### UnidadDeMedida

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | UnidadDeMedida |
| Intensión | Representa la unidad en la que se expresa y mide un artículo, junto con su factor de conversión respecto de otras unidades equivalentes. |
| Extensión | El conjunto de todas las unidades de medida manejadas en el catálogo (por ejemplo: metro, metro cuadrado, kilogramo, litro y unidad). |

## Proveedores y Compras

### Proveedor

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Proveedor |
| Intensión | Representa a una entidad comercial externa que abastece de insumos a la fábrica. Posee datos de identificación (razón social, CUIT, contacto), un estado (Activo/Inactivo) que lo habilita o no para participar en nuevas licitaciones, y un desempeño histórico de entregas que alimenta su ranking. |
| Extensión | El conjunto de todos los proveedores reales de la cadena de suministro (por ejemplo: Maderera del Norte S.A. y Pinturas Misiones S.R.L.). |

### SolicitudDeDisponibilidad

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | SolicitudDeDisponibilidad |
| Intensión | Representa la consulta directa de compra que la empresa envía a un único proveedor —el mejor rankeado— para confirmar disponibilidad, precio y fecha de entrega de un insumo (Flujo A). Transita estados durante su ciclo de vida (Enviada, En Espera, Respondida). |
| Extensión | El conjunto de todas las consultas directas enviadas (por ejemplo: la solicitud enviada el 06/09/2026 a Maderera del Norte por 100 metros de madera de pino). |

### SolicitudDeCotizacion

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | SolicitudDeCotizacion |
| Intensión | Representa el pedido de cotización (RFQ) enviado a múltiples proveedores con enlaces seguros y una fecha de vencimiento, para comparar ofertas de uno o varios artículos (Flujo B). Se compone de líneas de detalle (artículo + cantidad) y transita estados (Borrador, Enviada, Adjudicada, Anulada); el borrador puede ser generado por el motor predictivo y la anulación se bloquea si ya fue adjudicada. |
| Extensión | El conjunto de todas las RFQ emitidas (por ejemplo: la licitación abierta el 05/09/2026 a cinco proveedores por 500 kilogramos de barniz). |

### Cotizacion

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Cotizacion |
| Intensión | Representa la respuesta formal de un proveedor a una solicitud de cotización, sobre un artículo concreto, con el precio unitario, la cantidad ofrecida y la fecha prometida de entrega. La marca de adjudicación indica si fue seleccionada como oferta ganadora de la licitación. Es la entrada que se compara en la licitación multicriterio. |
| Extensión | El conjunto de todas las ofertas recibidas (por ejemplo: la cotización de Pinturas Misiones con precio de $45 por litro y entrega en 7 días). |

### OrdenDeCompra

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | OrdenDeCompra |
| Intensión | Representa el pedido firme de compra emitido al proveedor ganador de una licitación o de una consulta directa. Se compone de líneas de detalle (artículo + cantidad + precio pactado) y transita estados (Emitida, Confirmada, Recibida —que admite la marca de Entrega Parcial cuando llega menos cantidad—, Anulada, Rechazada) que reflejan el avance del abastecimiento. |
| Extensión | El conjunto de todas las órdenes de compra emitidas (por ejemplo: la OC-330 emitida el 07/09/2026 a Pinturas Misiones por 200 litros de barniz). |

### Incidencia

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Incidencia |
| Intensión | Representa un evento de desempeño negativo de un proveedor —retraso, faltante o rotura— registrado durante el abastecimiento. Alimenta el ranking dinámico de proveedores. |
| Extensión | El conjunto de todas las incidencias registradas (por ejemplo: el retraso de 4 días de Maderera del Norte en la entrega de la OC-318). |

### LineaSolicitudCotizacion

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | LineaSolicitudCotizacion |
| Intensión | Representa la inclusión de un artículo dentro de una solicitud de cotización, con la cantidad de ese artículo que se pide cotizar. Es el dato propio de la asociación Solicitud↔Artículo, que permite RFQ multi-artículo. |
| Extensión | El conjunto de todas las líneas de las RFQ emitidas (por ejemplo: la línea de la licitación del 05/09/2026 que pide cotizar 500 kilogramos de barniz). |

### LineaOrdenCompra

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | LineaOrdenCompra |
| Intensión | Representa la inclusión de un artículo dentro de una orden de compra firme, con la cantidad comprada y el precio unitario pactado. Es el dato propio de la asociación Orden↔Artículo, que permite OC multi-artículo. |
| Extensión | El conjunto de todas las líneas de las órdenes de compra emitidas (por ejemplo: la línea de la OC-330 que compra 200 litros de barniz a $45 el litro). |

### DesempenoProveedor

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | DesempenoProveedor |
| Intensión | Representa el registro estadístico del tiempo de entrega real de una entrega concreta del proveedor (días entre la confirmación y la recepción de la mercancía). Es el insumo del ranking de proveedores y de la evaluación de criticidad. |
| Extensión | El conjunto de todos los registros de desempeño acumulados (por ejemplo: el registro del 09/09/2026 que mide 4 días de entrega real para la OC-330 confirmada el 05/09/2026). |

## Análisis Predictivo

### TasaDeRendimiento

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | TasaDeRendimiento |
| Intensión | Representa la métrica calculada por el motor predictivo que compara la velocidad real de producción de una etapa contra su estándar esperado, junto con la desviación detectada. |
| Extensión | El conjunto de todas las tasas de rendimiento calculadas por etapa (por ejemplo: la TRR del 07/09/2026 que muestra la etapa de Pintado operando un 20% por debajo del estándar). |

### Alerta

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Alerta |
| Intensión | Representa una notificación proactiva del sistema ante una condición de riesgo: reposición por stock bajo, caducidad de insumos, quiebre de stock proyectado, cuello de botella (predictivo o preventivo) o falla técnica del sistema. |
| Extensión | El conjunto de todas las alertas emitidas por el sistema (por ejemplo: la alerta de cuello de botella predictivo emitida el 07/09/2026 para la orden OP-501). |

### EscenarioDeSimulacion

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | EscenarioDeSimulacion |
| Intensión | Representa un escenario evaluado por el simulador predictivo de pedidos urgentes: los parámetros del pedido ficticio (artículo, cantidad, urgencia) y el impacto proyectado sobre el cronograma real. |
| Extensión | El conjunto de todas las simulaciones realizadas por la gerencia (por ejemplo: la simulación del 07/09/2026 que evaluó insertar 30 Sillas de Pino con fecha límite el 15/09/2026). |

## Auditoría

### EventoDeAuditoria

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | EventoDeAuditoria |
| Intensión | Representa el registro inmutable de un evento crítico del sistema: quién lo realizó, cuándo, desde qué dirección y qué cambió (estado anterior vs. nuevo). Es la base de la trazabilidad y de la investigación de incidentes. |
| Extensión | El conjunto de todos los eventos auditados (por ejemplo: la aprobación de la OC-330 por el Encargado de Compras el 07/09/2026 a las 14:32). |

## Usuarios y Seguridad

### Usuario

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Usuario |
| Intensión | Representa a una persona con cuenta de acceso al sistema, identificada por sus credenciales y asociada a un rol que determina qué módulos y operaciones puede utilizar. Posee un estado de cuenta (Activo/Inhabilitado): el usuario inhabilitado no puede iniciar sesión. |
| Extensión | El conjunto de todas las cuentas de acceso activas (por ejemplo: la cuenta del Supervisor de Producción, la del Encargado de Compras y la del Gerente). |

### Rol

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | Rol |
| Intensión | Representa el perfil de permisos que determina qué módulos y operaciones puede ejecutar un usuario dentro del sistema. |
| Extensión | El conjunto de todos los roles definidos (por ejemplo: Supervisor de Producción, Encargado de Stock, Encargado de Compras, Gerente y Administrador). |

### ParametroGlobal

| Elemento | Descripción del Concepto |
| --- | --- |
| Símbolo | ParametroGlobal |
| Intensión | Representa una variable de configuración de la fábrica agrupada por categoría, que define reglas de negocio transversales como turnos, feriados, métricas base y umbrales de tolerancia. |
| Extensión | El conjunto de todos los parámetros configurables (por ejemplo: el umbral de tolerancia de precio del 10% del Flujo A y el feriado nacional del 12/10). |
