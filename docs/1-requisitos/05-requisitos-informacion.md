# Requisitos de Información

Debe tener una lista de requisitos de almacenamientos y de restricciones de información que se haya identificado. 

| IRQ–01  | Información sobre Artículos e Inventario |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ–01** **Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas**  |  |
| **Requisitos asociados** | CU-INV-01…CU-INV-08 |  |
| **Descripción**  | El sistema deberá almacenar la información del catálogo de la fábrica, diferenciando estrictamente el inventario físico real de las reservas lógicas vigentes. |  |
| **Datos específicos**  | Código SKU y Descripción del artículo. Tipo de artículo (Materia Prima, Insumo Consumible o Producto Terminado). Unidad de medida y factores de conversión. Cantidad de stock físico actual. Cantidad de stock comprometido (Reserva Lógica). Umbrales de control (Stock mínimo y máximo) |  |
|  |  |  |
| **Estabilidad**  | Alta  |  |
| **Comentarios**  |  |  |

| IRQ–02 | Información sobre Órdenes de Producción y Avance |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ–01 Digitalización e Integración Operativa OBJ–02 Prevención Proactiva de Cuellos de Botella.** |  |
| **Requisitos asociados** | CU-PROD-06…CU-PROD-15 CU-PROD-21 CU-PROD-22 |  |
| **Descripción**  | El sistema deberá registrar todos los datos correspondientes a la planificación y ejecución de la producción dentro de la fabrica. |  |
| **Datos específicos**  | Identificador de Planificación y de Orden de Producción. Estado de la orden (Pendiente, En curso, Cerrada). Producto a fabricar y cantidad requerida. Fechas planificadas (Inicio y Entrega). Recursos asignados (Operarios y Maquinarias). Registro de unidades conformadas por etapa (Historial de Partes Diarios). Lotes aprobados o rechazados por control de calidad. |  |
|  |  |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Esta información transaccional es la que el monolito enviará de forma asíncrona al microservicio predictivo para calcular la desviación en los tiempos de fabricación. |  |

| IRQ–03  | Información sobre Proveedores y Trazabilidad de Compras |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ–01 Digitalización e Integración Operativa OBJ–03 Sincronización Inteligente de Abastecimiento.** |  |
| **Requisitos asociados** | CU-COM-01…CU-COM-20 |  |
| **Descripción**  | El sistema deberá almacenar los proveedores y la trazabilidad completa del flujo de licitación y compra. |  |
| **Datos específicos**  | Razón social, CUIT y datos de contacto Historial de tiempos de entrega estadísticos del proveedor (Estimado, Realidad). Solicitudes de cotización emitidas (Insumo y cantidad solicitada). Precios y condiciones comerciales ofertadas por los proveedores. Órdenes de compra aprobadas, su estado y sugerencia predictiva asociada. |  |
|  |  |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El tiempo de entrega estadístico de cada proveedor es un dato que el sistema actualizará continuamente comparando la fecha prometida en la orden de compra con la fecha real del ingreso de materiales. |  |

| IRQ–04 | Información Analítica, Predictiva y de Simulación |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ–02 Prevención Proactiva de Cuellos de Botella,  OBJ–04 Soporte Gerencial mediante Simulación de Escenarios.** |  |
| **Requisitos asociados** | CU-PRED-01…CU-PRED-05 CU-REP-01 |  |
| **Descripción**  | El sistema deberá persistir los resultados de los cálculos asíncronos devueltos por el motor de Machine Learning y los parámetros de los escenarios simulados. |  |
| **Datos específicos**  | Tasa de Rendimiento Real (TRR) por sector productivo y desviación respecto al estándar. Alertas activas de cuellos de botella o quiebres de stock. Velocidad de consumo diario de materiales. Parámetros de escenarios de simulación guardados (urgencia, cantidad ficticia, impacto proyectado en cronograma real). Logs de ejecución del microservicio y métricas de reentrenamiento del modelo. |  |
|  |  |  |
| **Estabilidad**  | Media |  |
| **Comentarios**  | Esta información es altamente volátil, ya que es recalculada y reescrita constantemente en la base de datos a medida que ingresan nuevos Partes Diarios. |  |

| IRQ–05 | Información sobre Maquinarias y Desgaste Operativo |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ–01 Digitalización e Integración Operativa.** |  |
| **Requisitos asociados** | CU-MANT-01…CU-MANT-11 |  |
| **Descripción**  | El sistema deberá gestionar los equipos de la fábrica y las reglas de uso que determinan sus bloqueos preventivos. |  |
| **Datos específicos**  | Identificador y nombre de la máquina. Estado operativo (Disponible, En Mantenimiento, Bloqueada). Volumen de unidades procesadas o desgaste acumulado. Reglas o umbrales de uso para disparar mantenimientos preventivos. Historial de intervenciones realizadas. |  |
|  |  |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El desgaste acumulado se incrementa automáticamente a través de reglas de negocio en el sistema cuando se cierran los Partes Diarios correspondientes a las órdenes donde la máquina fue asignada. |  |

