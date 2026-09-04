# Módulo Reportes

## Operatoria

El módulo traduce la base de datos y las inferencias del motor predictivo en herramientas visuales de alto impacto para la dirección de la empresa.

## Alcance (validado)

Reportes es un módulo de **visualización y exportación**. No calcula: consume los resultados de los demás módulos y del motor predictivo, y los presenta.

- **Dashboards gerenciales interactivos**: KPIs tradicionales + Gantt Predictivo (cálculos de Análisis Predictivo).
- **Generación y exportación de reportes**: grillas exportables a PDF y Excel.
- **Reportes programados**: resúmenes automáticos por correo (proceso automático, sin actor).

La **simulación de escenarios** (pedidos urgentes, cambio de proveedor) NO pertenece a Reportes: vive en Análisis Predictivo (CU-PRED-04).

## Frontera entre módulos

| Responsabilidad | Módulo |
| --- | --- |
| Calcular (TRR, riesgo, simulación) | Análisis Predictivo |
| Renderizar, visualizar, exportar | Reportes |
| Operar transacciones | Producción, Inventario, Compras |

## Funcionalidades y pasos

### F1 — Consultar dashboards gerenciales (visualización)

1. La gerencia accede al dashboard interactivo.
2. El sistema combina KPIs tradicionales (volumen producido, stock crítico) con el Gantt Predictivo de órdenes en curso.
3. El Gantt muestra fechas reales vs. proyectadas, alimentado por los cálculos del motor predictivo.
4. Permite filtros interactivos (período, sector, tipo de alerta).

> Nota: este dashboard se solapa conceptualmente con el "Tablero Predictivo" (CU-PRED-05). Al redactar los casos de uso se definirá si son el mismo CU o dos vistas del mismo panel. En esta especificación se documenta como visualización consolidada del módulo Reportes.

### F2 — Generar y exportar reportes (CU-REP-01)

1. El usuario ingresa al generador de reportes.
2. El sistema muestra los tipos disponibles (Producción, Inventario, Compras).
3. El usuario selecciona el tipo, rango de fechas y parámetros.
4. El sistema consolida la información y genera el archivo en PDF o Excel.
5. El sistema presenta el reporte con opción de descarga.
6. Excepción: sin datos para el rango → "Sin datos para generar".

### Proceso automático (no es caso de uso)

- **Reportes programados**: envío automático de resúmenes semanales por correo electrónico a la gerencia. Proceso programado (Scheduler), sin actor que lo dispare.

## Cruces con otros módulos

- **Análisis Predictivo**: Reportes renderiza los gráficos de evolución + proyección y la banda de confianza de las predicciones.
- **Producción**: los datos de avance (Partes Diarios) alimentan los KPIs de producción.
- **Inventario**: el stock crítico se muestra en los dashboards.
- **Compras**: las licitaciones y el ranking se reflejan en los reportes gerenciales.

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Nomenclatura consistente**: nombres descriptivos y uniformes.
2. Los reportes no son ABM, así que no aplica el desglose Alta/Baja/Modificación/Listado/Buscar. Aplica "Listado" en el sentido de listar/generar reportes por tipo.
