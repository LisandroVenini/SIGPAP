# Glosario de Términos

| Término | Categoría | Comentarios |
| :---- | :---- | :---- |
| DSS (Decision Support System) | Dominio | Sistema de Soporte a la Toma de Decisiones. Naturaleza principal de SIGPAP: no solo registra datos, sino que los analiza para anticipar problemas y apoyar decisiones gerenciales. |
| TRR (Tasa de Rendimiento Real) | Dominio | Métrica calculada por el componente predictivo a partir de los Partes Diarios. Compara la velocidad real de producción de una etapa contra su estándar esperado para detectar desviaciones. |
| Parte Diario | Dominio | Registro diario que carga el Supervisor con las unidades completadas por etapa al final del turno. Es el insumo transaccional del motor predictivo. |
| Orden de Producción (OP) | Dominio | Entidad central de Producción. Agrupa un producto, una cantidad y una fecha de entrega, y transita estados: Pendiente, Asignada, En Curso, Cerrada (más Pendiente por Insumos y Anulada). |
| Planificación de Producción | Dominio | Campaña marco que agrupa múltiples órdenes de producción (ej. "septiembre-noviembre 2026"). |
| Hoja de Ruta | Dominio | Conjunto de etapas de un producto con su tiempo estimado por unidad. Se define una vez por producto y la OP la instancia al crearse. |
| Etapa de Producción | Dominio | Paso del catálogo de fabricación (ej. Lijado, Pintado, Armado, Embalado). Es genérica; el tiempo por unidad se define al asignarla a un producto. |
| Stock Físico | Dominio | Existencias reales en el depósito. |
| Stock Comprometido (Reserva Lógica) | Dominio | Cantidad reservada para órdenes de producción en curso. No se descuenta físicamente hasta el consumo real. |
| Stock Disponible | Dominio | Stock físico menos stock comprometido. Es lo que se usa para decidir si una orden puede crearse. |
| Cuello de Botella Predictivo | Dominio | Alerta emitida cuando la proyección recalculada supera la fecha límite de una orden, indicando riesgo de retraso en una etapa. |
| Licitación Predictiva | Dominio | Proceso automatizado que adelanta la fecha de compra ante un quiebre de stock proyectado y dispara una solicitud de cotización. |
| Solicitud de Cotización (RFQ) | Dominio | Pedido de cotización enviado a múltiples proveedores con enlaces seguros (Flujo B). |
| Solicitud de Disponibilidad | Dominio | Consulta directa de compra a un único proveedor, el mejor rankeado (Flujo A). |
| Orden de Compra (OC) | Dominio | Pedido firme emitido al proveedor ganador. Estados: Emitida, Confirmada, Recibida, Anulada, Rechazada. |
| Ranking de Proveedores | Dominio | Ordenamiento dinámico por desempeño real (retrasos, faltantes, roturas). Se calcula automáticamente y alimenta las sugerencias de compra. |
| Umbral de Tolerancia | Dominio | Porcentaje máximo de variación de precio/tiempo que el sistema acepta sin alertar al humano en el Flujo A. Lo configura el Encargado de Compras. |
| Cuello de Botella Preventivo | Dominio | Bloqueo automático de una maquinaria al superar su umbral de desgaste, impidiendo su asignación hasta registrar el mantenimiento. |
| CRUD / ABM | Dominio | Operaciones básicas sobre una entidad: Alta, Baja, Modificación (o "Alta, Baja, Modificación") + Listado y Buscar. Patrón de casos de uso exigido por la cátedra. |
| Gantt (Cronograma) | Dominio | Diagrama de barras temporal que muestra las órdenes de producción y sus etapas en el calendario. Permite reordenar y visualizar fechas reales vs. proyectadas. |
| Operario | Dominio | Persona de planta que participa en la ejecución de las órdenes de producción. El Supervisor lo asigna a las etapas de una orden junto con la maquinaria. Se gestiona con ABM propio dentro del módulo Producción. |
| Maquinaria | Dominio | Equipo de producción de la fábrica. Tiene estado operativo (Disponible, En Mantenimiento, Bloqueada) y acumula desgaste según el volumen de unidades procesadas. Su gestión corresponde al subdominio de Mantenimiento. |
| Control de Calidad | Dominio | Inspección que registra el Supervisor sobre el lote producido: unidades aprobadas, rechazadas y motivo. Es prerrequisito obligatorio para cerrar una orden de producción. |
| Plan de Mantenimiento Preventivo | Dominio | Regla que define el umbral de uso (volumen procesado o desgaste) a partir del cual una maquinaria se bloquea preventivamente y exige una intervención. |
| Intervención de Mantenimiento | Dominio | Registro de una intervención concreta realizada sobre una maquinaria (fecha, tipo, responsable). Al registrarse libera el bloqueo preventivo y resetea el desgaste acumulado. |
| Artículo | Dominio | Unidad central del catálogo de Inventario. Puede ser Materia Prima, Insumo Consumible o Producto Terminado. Lleva SKU, descripción, tipo, unidad de medida y stock; soporta múltiples unidades y factores de conversión. |
| Movimiento de Stock | Dominio | Registro de una variación de existencias: ingreso de mercancía, consumo por producción, salida o ajuste manual. Conserva un historial inmutable para trazabilidad. |
| Unidad de Medida | Dominio | Unidad en la que se expresa un artículo (metro, kilogramo, unidad) junto con su factor de conversión respecto a otras unidades. |
| Proveedor | Dominio | Entidad comercial externa que abastece de insumos. Datos: razón social, CUIT y contacto. Acumula desempeño histórico (retrasos, faltantes, roturas) que alimenta el ranking. Es a la vez actor externo (ACT-04) y concepto del dominio. |
| Cotización | Dominio | Respuesta de un proveedor a una Solicitud de Cotización (RFQ): precio unitario, cantidad ofrecida y fecha prometida. Es la entrada que se compara en la licitación multicriterio. |
| Incidencia | Dominio | Evento de desempeño negativo de un proveedor (retraso, faltante o rotura). Se registra automáticamente y alimenta el ranking dinámico. |
| Escenario de Simulación | Dominio | Parámetros de un escenario evaluado por el Simulador Predictivo de Pedidos Urgentes (pedido ficticio, cantidad, urgencia) junto con el impacto proyectado sobre el cronograma real. |
| Alerta | Dominio | Notificación proactiva del sistema ante una condición de riesgo: reposición por stock bajo, caducidad, quiebre de stock proyectado, cuello de botella predictivo (retraso de entrega) o preventivo (bloqueo de maquinaria). |
| Evento de Auditoría | Dominio | Registro inmutable de un evento crítico: usuario, fecha, hora, IP y cambio de estado (valor anterior vs. nuevo). Base de la trazabilidad y la investigación de incidentes. |
| Usuario | Dominio | Cuenta de acceso al sistema, asociada a una persona. Opera únicamente los módulos habilitados para su rol (RBAC). |
| Rol | Dominio | Perfil de permisos que determina qué módulos y operaciones puede ejecutar un usuario (Supervisor de Producción, Encargado de Compras, Gerente, Administrador...). |
| Parámetro Global | Dominio | Variable de configuración de la fábrica agrupada por categoría (turnos, feriados, métricas base, umbrales de negocio). Se administra desde el módulo de Usuarios y Seguridad. |
| RBAC (Role-Based Access Control) | Técnico | Control de Acceso Basado en Roles. Cada usuario opera solo los módulos habilitados para su rol. |
| Componente Predictivo | Técnico | Componente Python que ejecuta el ML. Recibe datos del monolito, calcula y devuelve predicciones. Es el actor ACT-05. |
| Monolito | Técnico | Aplicación principal Laravel (transaccional): rutas, Eloquent, autenticación, colas y scheduler. |
| Petición Asíncrona / Job | Técnico | Trabajo en segundo plano que el monolito despacha al componente predictivo sin bloquear al usuario. Implementado con Laravel Queue. |
| scikit-learn | Técnico | Librería Python de Machine Learning (regresión, RandomForest, etc.). |
| joblib (.pkl) | Técnico | Serialización de modelos scikit-learn para no reentrenar en cada request. |
| Pandas / NumPy | Técnico | Librerías Python de ETL: limpieza de datos, detección de outliers, operaciones vectoriales. |
| Reentrenamiento | Técnico | Job programado (Scheduler) que actualiza el modelo de ML con datos recientes. |
| Drift Detection | Técnico | Comparación de métricas tras cada reentrenamiento; si empeoran más de un umbral, alerta al administrador. |
| Prophet | Técnico | Librería de series temporales (Meta) para predicción estacional. Marcada como evolución futura del modelo, no en el alcance actual. |
| Borrado Lógico (Soft Delete) | Técnico | Las bajas no eliminan registros físicamente: se inactivan conservando el historial. |
| CASE | Metodología (UP) | Computer-Aided Software Engineering. Herramientas que asisten el modelado (contexto general del trabajo). |
| Caso de Uso | Metodología (UP) | Interacción entre un actor y el sistema con valor observable. En SIGPAP se redactan en formato tabular extendido. |
| Actor | Metodología (UP) | Rol externo que interactúa con el sistema (humano o sistema externo, como el Reloj del Sistema o el Componente Predictivo). |
| Actor Secundario | Metodología (UP) | Sistema externo que participa sin iniciar el caso de uso (ej. Componente Predictivo, Proveedor externo). |
| Matriz de Rastreabilidad | Metodología (UP) | Tabla que vincula objetivos, requisitos y casos de uso para verificar cobertura y completitud. |
