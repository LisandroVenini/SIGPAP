# Módulo Análisis Predictivo

## Operatoria

Constituye el núcleo del Sistema de Soporte a Decisiones (DSS). Aprovecha los datos históricos (Partes Diarios, recepciones de compras) para alimentar algoritmos de Machine Learning que anticipan el comportamiento futuro de la fábrica. Se implementa como un microservicio Python (FastAPI) llamado por Laravel vía HTTP interno.

## Arquitectura

```
Laravel (monolito) ──HTTP interno (Guzzle)──▶ FastAPI (microservicio ML)
        │                                             │
        │  dispatch job a la cola                     │  ETL → train/predict → escribe PostgreSQL
        │                                             │
        └────────────── PostgreSQL (única) ◀──────────┘
```

El monolito transaccional (Laravel) despacha trabajos a la cola; el microservicio (Python/FastAPI) realiza el ETL, entrena o predice con scikit-learn, y escribe resultados en PostgreSQL. Laravel lee las predicciones y las renderiza.

## Procesos automatizados (alcance vinculante — propuesta definitiva)

Los tres procesos automatizados de la propuesta son el alcance de este módulo:

### P1 — Ajuste predictivo de tasa de rendimiento

1. El supervisor cierra el Parte Diario (Producción).
2. Se dispara un trabajo asíncrono hacia el microservicio.
3. El microservicio calcula la Tasa de Rendimiento Real (TRR) del sector.
4. Si detecta desviación negativa sostenida, recalcula los días para finalizar el lote y ajusta las fechas proyectadas de entrega de las órdenes en cola.
5. Si la nueva fecha supera la fecha límite del cliente, emite una alerta de "Cuello de Botella Predictivo".

### P2 — Abastecimiento inteligente y Licitación Predictiva

1. El sistema monitorea dos variables: velocidad de consumo de insumos y días de entrega estadísticos del proveedor.
2. Si proyecta un quiebre de stock, recalcula y adelanta la fecha límite de pedido.
3. Al alcanzar la nueva fecha, dispara una licitación: selecciona los mejores proveedores y envía Solicitudes de Cotización.
4. Evalúa las respuestas con un algoritmo multicriterio (Precio vs. Fecha Prometida vs. Riesgo Histórico de Retraso).
5. Presenta un informe a la gerencia sugiriendo la opción más segura.

### P3 — Simulador Predictivo de Pedidos Urgentes

1. La gerencia ingresa los parámetros de un pedido urgente.
2. El microservicio proyecta el impacto insertando el pedido como prioridad alta.
3. Recalcula fechas de finalización y detecta el efecto dominó sobre los pedidos preexistentes.
4. Devuelve un reporte interactivo: qué órdenes se retrasarán, cuántos días, y el riesgo de penalización.

## Tareas de soporte (no son casos de uso)

- **Preparación de datos (ETL)**: extracción, limpieza y estructuración en segundo plano de los datos transaccionales. Proceso automático.
- **Reentrenamiento automático**: ejecución programada (Scheduler) para que el modelo incorpore datos recientes. Proceso automático, aunque el disparo está modelado como CU-PRED-01 por tener al microservicio como actor.
- **Detección de drift**: comparación de métricas tras cada reentrenamiento; si empeora más de un umbral, alerta al administrador. Proceso automático.

## Funcionalidades y pasos (casos de uso)

### F1 — Ejecutar reentrenamiento del modelo (CU-PRED-01)

1. El reloj del sistema alcanza el intervalo programado.
2. Laravel compila el lote de datos históricos y lo envía al microservicio.
3. El microservicio entrena el algoritmo y ajusta los modelos.
4. Devuelve el estado "Entrenamiento Exitoso" con las métricas.
5. Laravel actualiza la fecha de último entrenamiento y registra en auditoría.
6. Excepción: si el microservicio no responde (timeout), reprograma para el siguiente ciclo y alerta al administrador.

### F2 — Recalcular tiempo de producción (CU-PRED-02)

1. Se reciben nuevos datos de avance (Parte Diario cerrado).
2. El microservicio calcula la Tasa de Rendimiento Real (TRR) comparándola con el estándar.
3. Proyecta la nueva fecha estimada de finalización de la orden.
4. Si la fecha supera la fecha límite, genera una "Alerta de Cuello de Botella".
5. El cronograma (Gantt) se actualiza.

### F3 — Evaluar riesgo de suministro (CU-PRED-03)

1. El reloj del sistema activa la evaluación periódica de inventario.
2. Laravel envía stock físico, reservas lógicas y cronograma al microservicio.
3. El microservicio calcula la velocidad de consumo y la cruza con los tiempos de entrega de proveedores.
4. Identifica insumos con quiebre de stock inminente y devuelve la lista de criticidad.
5. Laravel genera borradores de solicitudes de cotización.
6. Excepción: insumo sin historial → usa umbrales estáticos; sin riesgos → termina silencioso.

### F4 — Simular escenario de producción (CU-PRED-04)

1. La gerencia ingresa los parámetros del pedido urgente (artículo, cantidad, fecha límite).
2. El microservicio simula insertando el pedido como prioridad 1 y recalcula el efecto dominó.
3. Devuelve qué órdenes se retrasarían y cuántos días.
4. La gerencia decide sin alterar el cronograma real.
5. Excepción: escenario matemáticamente inviable → alerta "Escenario Inviable".

### F5 — Consultar tablero predictivo (CU-PRED-05)

1. La gerencia accede al tablero predictivo.
2. El sistema recopila los cálculos recientes del microservicio.
3. Renderiza indicadores clave y el Gantt con fechas reales vs. proyectadas.
4. Permite filtros interactivos (ej. "alertas críticas de esta semana").
5. Excepción: sin datos predictivos → muestra "Recolectando datos de planta".

## Ideas para futuras implementaciones (NO en el alcance actual)

- Predicción de descartes por producto (sugerir lanzar 106 unidades para obtener 100).
- Detección de cuellos de botella por acumulación entre etapas (cruce lijado vs. pintura).
- Factor climático (API de clima cruzada con rendimiento del sector pintura).
- Simulador de riesgo y costos de proveedores (Proveedor A vs. B).
- Prophet (series temporales con estacionalidad) como evolución del modelo.

## Stack tecnológico

- **PHP + Laravel**: núcleo de la app (rutas, Eloquent, autenticación, Queue, Scheduler).
- **Python + FastAPI**: microservicio ML (endpoints /train, /predict, /metrics).
- **scikit-learn**: modelos (regresión lineal múltiple, RandomForest, etc.), serialización con joblib.
- **Pandas + NumPy**: ETL (limpieza, outliers IQR, ventanas de tiempo).
- **SQLAlchemy**: lectura/escritura en PostgreSQL.
- **Guzzle**: HTTP interno entre Laravel y FastAPI.
- **Laravel Queue + Scheduler**: trabajos en background y reentrenamiento periódico.
- **Chart.js**: gráficos de evolución + banda de confianza.

## Cruces con otros módulos

- **Producción**: el Parte Diario dispara el ajuste de TRR (P1).
- **Inventario**: stock físico, reservas y velocidad de consumo alimentan el abastecimiento inteligente (P2).
- **Compras**: el riesgo histórico de retraso de proveedores alimenta la licitación predictiva (P2).
- **Reportes**: los gráficos de evolución + proyección se renderizan ahí.
