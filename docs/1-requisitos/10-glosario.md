# Glosario de Términos

| Término | Categoría | Comentarios |
| :---- | :---- | :---- |
| DSS (Decision Support System) | Dominio | Sistema de Soporte a la Toma de Decisiones. Naturaleza principal de SIGPAP: no solo registra datos, sino que los analiza para anticipar problemas y apoyar decisiones gerenciales. |
| TRR (Tasa de Rendimiento Real) | Dominio | Métrica calculada por el microservicio a partir de los Partes Diarios. Compara la velocidad real de producción de un sector contra su estándar esperado para detectar desviaciones. |
| Parte Diario | Dominio | Registro diario que carga el Supervisor con las unidades completadas por etapa al final del turno. Es el insumo transaccional del motor predictivo. |
| Orden de Producción (OP) | Dominio | Entidad central de Producción. Agrupa un producto, una cantidad y una fecha de entrega, y transita estados: Pendiente, Asignada, En Curso, Cerrada (más Pendiente por Insumos y Anulada). |
| Planificación de Producción | Dominio | Campaña marco que agrupa múltiples órdenes de producción (ej. "septiembre-noviembre 2026"). |
| Hoja de Ruta | Dominio | Conjunto de etapas de un producto con su tiempo estimado por unidad. Se define una vez por producto y la OP la instancia al crearse. |
| Etapa de Producción | Dominio | Paso del catálogo de fabricación (ej. Lijado, Pintado, Armado, Embalado). Es genérica; el tiempo por unidad se define al asignarla a un producto. |
| Stock Físico | Dominio | Existencias reales en el depósito. |
| Stock Comprometido (Reserva Lógica) | Dominio | Cantidad reservada para órdenes de producción en curso. No se descuenta físicamente hasta el consumo real. |
| Stock Disponible | Dominio | Stock físico menos stock comprometido. Es lo que se usa para decidir si una orden puede crearse. |
| Cuello de Botella Predictivo | Dominio | Alerta emitida cuando la proyección recalculada supera la fecha límite de una orden, indicando riesgo de retraso en un sector. |
| Licitación Predictiva | Dominio | Proceso automatizado que adelanta la fecha de compra ante un quiebre de stock proyectado y dispara una solicitud de cotización. |
| Solicitud de Cotización (RFQ) | Dominio | Pedido de cotización enviado a múltiples proveedores con enlaces seguros (Flujo B). |
| Solicitud de Disponibilidad | Dominio | Consulta directa de compra a un único proveedor, el mejor rankeado (Flujo A). |
| Orden de Compra (OC) | Dominio | Pedido firme emitido al proveedor ganador. Estados: Emitida, Confirmada, Recibida, Anulada, Rechazada. |
| Ranking de Proveedores | Dominio | Ordenamiento dinámico por desempeño real (retrasos, faltantes, roturas). Se calcula automáticamente y alimenta las sugerencias de compra. |
| Umbral de Tolerancia | Dominio | Porcentaje máximo de variación de precio/tiempo que el sistema acepta sin alertar al humano en el Flujo A. Lo configura el Encargado de Compras. |
| Cuello de Botella Preventivo | Dominio | Bloqueo automático de una maquinaria al superar su umbral de desgaste, impidiendo su asignación hasta registrar el mantenimiento. |
| CRUD / ABM | Dominio | Operaciones básicas sobre una entidad: Alta, Baja, Modificación (o "Alta, Baja, Modificación") + Listado y Buscar. Patrón de casos de uso exigido por la cátedra. |
| Gantt (Cronograma) | Dominio | Diagrama de barras temporal que muestra las órdenes de producción y sus etapas en el calendario. Permite reordenar y visualizar fechas reales vs. proyectadas. |
| RBAC (Role-Based Access Control) | Técnico | Control de Acceso Basado en Roles. Cada usuario opera solo los módulos habilitados para su rol. |
| Microservicio Predictivo | Técnico | Servicio Python (FastAPI) que ejecuta el ML. Recibe pedidos asíncronos del monolito y devuelve predicciones. Es el actor ACT-05. |
| Monolito | Técnico | Aplicación principal Laravel (transaccional): rutas, Eloquent, autenticación, colas y scheduler. |
| Petición Asíncrona / Job | Técnico | Trabajo en segundo plano que el monolito despacha al microservicio sin bloquear al usuario. Implementado con Laravel Queue. |
| FastAPI | Técnico | Framework Python para construir el microservicio de ML. |
| Guzzle | Técnico | Cliente HTTP de PHP/Laravel usado para la comunicación interna monolito ↔ microservicio. |
| scikit-learn | Técnico | Librería Python de Machine Learning (regresión, RandomForest, etc.). |
| joblib (.pkl) | Técnico | Serialización de modelos scikit-learn para no reentrenar en cada request. |
| Pandas / NumPy | Técnico | Librerías Python de ETL: limpieza de datos, detección de outliers, operaciones vectoriales. |
| SQLAlchemy | Técnico | ORM Python para leer/escribir en PostgreSQL desde el microservicio. |
| Reentrenamiento | Técnico | Job programado (Scheduler) que actualiza el modelo de ML con datos recientes. |
| Drift Detection | Técnico | Comparación de métricas tras cada reentrenamiento; si empeoran más de un umbral, alerta al administrador. |
| Prophet | Técnico | Librería de series temporales (Meta) para predicción estacional. Marcada como evolución futura del modelo, no en el alcance actual. |
| Borrado Lógico (Soft Delete) | Técnico | Las bajas no eliminan registros físicamente: se inactivan conservando el historial. |
| CASE | Metodología (UP) | Computer-Aided Software Engineering. Herramientas que asisten el modelado (contexto general del trabajo). |
| Caso de Uso | Metodología (UP) | Interacción entre un actor y el sistema con valor observable. En SIGPAP se redactan en formato tabular extendido. |
| Actor | Metodología (UP) | Rol externo que interactúa con el sistema (humano o sistema externo, como el Reloj del Sistema o el Microservicio). |
| Actor Secundario | Metodología (UP) | Sistema externo que participa sin iniciar el caso de uso (ej. Microservicio Predictivo, Proveedor externo). |
| Matriz de Rastreabilidad | Metodología (UP) | Tabla que vincula objetivos, requisitos y casos de uso para verificar cobertura y completitud. |
