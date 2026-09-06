# Casos de Uso — Reportes

| CU-REP-01 | Generar Reportes |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-03 Información sobre Proveedores y Trazabilidad de Compras |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando los distintos actores necesiten extraer información consolidada de su módulo en un formato exportable |  |
| **Precondición**  | El usuario tiene los permisos adecuados para el área de la cual solicita el reporte. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción** |
|  | 1 | El usuario ingresa al generador de reportes. |
|  | 2 | El sistema muestra los tipos de reportes disponibles (ej. Producción, Inventario, Compras). |
|  | 3 | El usuario selecciona un tipo de reporte, define el rango de fechas y parámetros específicos. |
|  | 4 | El usuario presiona "Generar y Exportar". |
|  | 5 | El sistema consulta la base de datos, consolida la información y genera el archivo en el formato deseado (PDF o Excel). |
|  | 6 | El sistema presenta el reporte en pantalla con la opción de descargar. |
|  |  |  |
|  |  |  |
| **Postcondición**  | El reporte queda generado y disponible para su visualización y descarga en el formato elegido. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si la consulta no arroja datos para el rango seleccionado, el sistema interrumpe la generación del archivo y muestra una advertencia en pantalla ("Sin datos para generar"). |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 2 a 5 segundos |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


