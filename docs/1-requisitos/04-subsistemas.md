# Subsistemas del Proyecto

## Diagrama de los Subsistemas

Figura # - Diagrama de Subsistemas


## Descripción de los Subsistemas

### Gestión de Producción

Este subsistema está diseñado para gestionar el ciclo de vida completo de la fabricación, desde la planificación hasta la ejecución a nivel de planta. Su característica principal es la interfaz de baja fricción para la carga del Parte Diario, permitiendo a los supervisores registrar avances físicos de forma ágil. Toda la información recolectada en este subsistema posteriormente alimentará a los modelos predictivos.

#### Mantenimiento (subdominio de Producción)

El mantenimiento se administra dentro del dominio de Producción: el sistema monitorea el desgaste acumulado de las maquinarias a partir del volumen procesado y puede inhabilitar preventivamente la asignación de una máquina que requiera intervención, según reglas preventivas definidas por los supervisores.

### Gestión de Inventario

Su principal función es gestionar la trazabilidad de los insumos y productos terminados, diferenciando el stock físico real (existencia en depósito) del stock comprometido (asignado a órdenes de producción en curso). 

### Gestión de Compras

Este subsistema no solo permite la gestión administrativa clásica de proveedores y órdenes de compra, sino que introduce un circuito ágil de licitación electrónica. Mediante la generación y envío de enlaces seguros, integra a los proveedores externos temporalmente al software para que registren sus cotizaciones de manera directa, descentralizando la carga de datos y acelerando los tiempos de abastecimiento.

### Análisis Predictivo e Inteligencia de Negocios

Representa el diferencial estratégico de SIGPAP y su núcleo como Sistema de Soporte a Decisiones (DSS). Operando como un microservicio independiente, este subsistema procesa asíncronamente los volúmenes de datos generados por la planta y el almacén. Implementa algoritmos de Machine Learning para identificar desviaciones en el cronograma, emitir alertas tempranas sobre cuellos de botella y adelantar decisiones de compra. Asimismo, provee un entorno de simulación que otorga a la gerencia la capacidad de proyectar matemática y operativamente el impacto de la inserción de pedidos urgentes sobre la cola de producción vigente.

### Reportes

Este subsistema centraliza y presenta la información operativa e histórica de las demás áreas del sistema, transformando datos en gráficas y documentos listos para la toma de decisiones. Provee dashboards gerenciales interactivos que combinan KPIs tradicionales con el Gantt Predictivo, herramientas de simulación para evaluar el impacto de pedidos urgentes y la generación de reportes programados con exportación a PDF y Excel.

### Auditoría

Este subsistema garantiza la seguridad, transparencia y trazabilidad de los datos sensibles manipulados por los usuarios. Registra eventos críticos (usuario, fecha, hora y dirección IP), mantiene la trazabilidad de estados (valor anterior vs. nuevo) y aplica borrado lógico para preservar un historial recuperable en la base de datos.

### Gestión de Usuarios y Seguridad (módulo de soporte)

Este módulo de soporte garantiza la integridad, confidencialidad y trazabilidad de la información, materializando el requisito no funcional de "Seguridad y control de Acceso". Implementa un modelo de Control de Acceso Basado en Roles, asegurando que cada actor interactúe únicamente con las interfaces y datos pertinentes a su función operativa o gerencial. Además, centraliza la parametrización global del entorno, permitiendo adaptar las variables fundamentales de la fábrica (turnos, feriados, métricas base) sin necesidad de intervenir el código fuente.

