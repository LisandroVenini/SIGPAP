# Casos de Uso — Análisis Predictivo

| CU-PRED-01 | Ejecutar Reentrenamiento del Modelo |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-02 Prevención Proactiva de Cuellos de Botella |  |
| **Requisitos**  **asociados**  | IRQ-04 Información Analítica, Predictiva y de Simulación RF-04 Análisis Predictivo y Monitoreo de Alertas |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Reloj del Sistema dispare el proceso de reentrenamiento para que el algoritmo de Machine Learning incorpore los datos históricos recientes, delegando la ejecución en el Componente Predictivo. |  |
| **Precondición**  | Ha transcurrido el tiempo configurado para el ciclo de reentrenamiento y existen nuevos datos consolidados (Partes Diarios y Órdenes Cerradas) en la base de datos. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Reloj del Sistema alcanza el intervalo programado (ej. Domingo a las 02:00 AM) y solicita el inicio del reentrenamiento. |
|  | 2 | El sistema principal (Laravel) compila el lote de datos históricos recientes y lo envía al Componente Predictivo mediante invocación de proceso. |
|  | 3 | El Componente Predictivo recibe el set de datos y ejecuta la rutina de entrenamiento del algoritmo. |
|  | 4 | El componente predictivo ajusta sus modelos internos y devuelve al sistema principal un estado de "Entrenamiento Exitoso" junto con las nuevas métricas. |
|  | 5 | El sistema principal actualiza la fecha de último entrenamiento y registra el evento en el log de auditoría del sistema. |
|  |  |  |
|  |  |  |
|  |  |  |
| **Postcondición**  | El modelo de Machine Learning queda actualizado, mejorando sus futuras predicciones. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 4 | Si el Componente Predictivo no responde por una caída temporal (Timeout), el sistema reprograma la tarea para el siguiente ciclo horario y envía una alerta técnica al administrador. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 3 | Entre 1 a 5 minutos |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Media |  |
| **Comentarios**  | Ninguno |  |


| CU-PRED-02 | Recalcular Tiempo de Producción |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-02 Prevención Proactiva de Cuellos de Botella |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-04 Información Analítica, Predictiva y de Simulación RF-04 Análisis Predictivo y Monitoreo de Alertas |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando, de forma reactiva al cierre de un Parte Diario en Producción, el sistema principal dispare el recálculo del cronograma delegando en el componente predictivo la detección de retrasos. |  |
| **Precondición**  | El Supervisor de Producción cerró exitosamente un Parte Diario (CU-PROD-13), lo que dispara automáticamente este proceso. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El sistema principal envía los datos del avance de la Orden de Producción al Componente Predictivo. |
|  | 2 | El componente predictivo procesa la información y calcula la Tasa de Rendimiento Real (TRR) comparándola con los estándares esperados. |
|  | 3 | El componente predictivo proyecta la nueva fecha estimada de finalización para la Orden de Producción basándose en la TRR. |
|  | 4 | El componente predictivo devuelve los resultados de la proyección al sistema principal. |
|  | 5 | El sistema principal actualiza las métricas en la base de datos. |
|  | 6 | Si la fecha proyectada supera la fecha límite de la orden, el sistema principal genera una "Alerta de Cuello de Botella" en el tablero gerencial. |
|  |  |  |
|  |  |  |
| **Postcondición**  | El cronograma general del sistema (Gantt gerencial) se actualiza reflejando la realidad productiva calculada. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 1 | Si la comunicación con el Componente Predictivo falla, el sistema principal encola la petición y realiza reintentos automáticos sin interrumpir el trabajo del operario en planta. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 4 | Menos de 2 segundos |
| **Frecuencia**  | Alta |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PRED-03 | Evaluar Riesgo de Suministro |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-03 Información sobre Proveedores y Trazabilidad de Compras IRQ-04 Información Analítica, Predictiva y de Simulación RF-04 Análisis Predictivo y Monitoreo de Alertas |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Reloj del Sistema dispare la evaluación periódica de la velocidad de consumo de materiales y los tiempos logísticos, delegando en el componente predictivo la sugerencia de compras anticipadas. |  |
| **Precondición**  | Existen Órdenes de Producción activas consumiendo insumos y existen parámetros de tiempos de entrega en los perfiles de los proveedores. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Reloj del Sistema activa la evaluación periódica de inventario. |
|  | 2 | El Monolito envía al componente predictivo el "Stock Físico", las "Reservas Lógicas" y el Cronograma de Fabricación. |
|  | 3 | El componente predictivo calcula la "velocidad de consumo” proyectada y lo cruza matemáticamente con los promedios de entrega reales de los proveedores. |
|  | 4 | El componente predictivo identifica aquellos insumos cuya fecha de quiebre de stock es anterior o peligrosa respecto al tiempo logístico de reposición, y los devuelve en una lista de criticidad. |
|  | 5 | El Monolito toma la lista y genera automáticamente "Borradores de Solicitudes de Cotización" para esos insumos |
|  | 6 | El sistema activa una alerta visual en el módulo de Compras. |
| **Postcondición**  | Se generaron las solicitudes de cotización es estado borrador para que el Encargado de Compras se encargue de su revisión/aprobación antes de que se produzcan quiebras de stock. |  |
| **Excepciones** | **Paso**  | **Acción**  |
|  | 4 | Si un insumo es nuevo y no posee historial de consumo ni de proveedores, el componente predictivo omite la predicción basada en IA y utiliza los umbrales de stock mínimo/máximo estáticos. |
|  | 4 | Si el componente predictivo determina que ningún insumo está en riesgo de quebrar stock, el proceso finaliza en este paso de forma silenciosa sin generar alertas ni borradores. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 5 a 10 segundos |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |

| CU-PRED-04 | Simular escenario de producción |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-04 Soporte Gerencial mediante Simulación de Escenarios |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-04 Información Analítica, Predictiva y de Simulación RF-05 Simulación Gerencial de Escenarios |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando la Gerencia desee utilizar el entorno de simulación para evaluar el impacto logístico de aceptar un pedido urgente de un cliente. |  |
| **Precondición**  | El Gerente ha iniciado sesión en el sistema y existen Órdenes de Producción en curso. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Gerente ingresa a la herramienta de Simulación Predictiva y selecciona "Nuevo Escenario". |
|  | 2 | El sistema solicita los parámetros del pedido. |
|  | 3 | El Gerente ingresa el artículo, la cantidad requerida y la fecha límite solicitada por el cliente, y presiona "Ejecutar Simulación". |
|  | 4 | El sistema principal compila el cronograma actual y lo envía junto con los parámetros del gerente al Componente Predictivo. |
|  | 5 | El componente predictivo procesa la simulación insertando el pedido como prioridad 1, desplazando la carga operativa y recalculando el efecto dominó sobre todas las demás órdenes, devolviendo el resultado. |
|  | 6 | El componente predictivo devuelve el resultado, listando qué órdenes preexistentes se retrasarían y cuántos días de demora sufrirían. |
|  | 7 | El sistema principal muestra un Dashboard interactivo con los resultados para que el gerente pueda tomar una decisión  |
|  |  |  |
| **Postcondición**  | El gerente obtiene un informe estadístico  sin haber alterado ningún dato dentro del cronograma de producción |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si el componente predictivo determina que es matemáticamente imposible cumplir con la fecha de entrega del pedido urgente simulado, devuelve una alerta de "Escenario Inviable" |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | 5 a 10 segundos |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Media |  |
| **Comentarios**  | Ninguno |  |

| CU-PRED-05 | Consultar Tablero Predictivo |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-02 Prevención Proactiva de Cuellos de Botella OBJ-04 Soporte Gerencial mediante Simulación de Escenarios  |  |
| **Requisitos**  **asociados**  | IRQ-04 Información Analítica, Predictiva y de Simulación |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Gerente ingrese a la pantalla principal para visualizar de forma gráfica e interactiva el estado del cronograma recalculado y las alertas tempranas generadas por el módulo de Análisis Predictivo. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Gerente. Existen datos analíticos procesados por el componente predictivo. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder al "Tablero Predictivo" |
|  | 2 | El sistema recopila la información transaccional y los cálculos más recientes del motor predictivo. |
|  | 3 | El sistema renderiza la interfaz gráfica, mostrando indicadores clave (ej. volumen producido, stock crítico) y el diagrama de Gantt con fechas reales vs. proyectadas. |
|  | 4 | El usuario selecciona un filtro específico (ej. "Ver solo alertas críticas de esta semana"). |
|  | 5 | El sistema actualiza los gráficos en tiempo real reflejando el filtro aplicado. |
|  |  |  |
|  |  |  |
|  |  |  |
| **Postcondición**  | El Gerente obtiene visibilidad instantánea del estado real y proyectado de la fábrica para la toma de decisiones. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si la base de datos predictiva está vacía, el sistema muestra los gráficos en blanco con una leyenda: "Recolectando datos de planta para primeras predicciones". |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | 2 segundos |
| **Frecuencia**  | Alta |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  |  |  |

