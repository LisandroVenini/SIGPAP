# Casos de Uso — Auditoría

| CU-AUD-01 | Consultar Registro de Auditoría |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador o Gerente requiera rastrear quién realizó una acción específica o visualizar las decisiones automáticas tomadas por el Microservicio. |  |
| **Precondición**  | El usuario ha iniciado sesión con rol de Administrador o Gerente. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita acceder al Registro de Auditoría (Log del sistema). |
|  | 2 | El sistema despliega una grilla con los eventos críticos ordenados cronológicamente (Fecha, Hora, IP, Usuario, Acción). |
|  | 3 | El usuario ingresa parámetros de búsqueda (ej. filtrar por "Acciones del Encargado de Stock" entre dos fechas). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema procesa los filtros y actualiza el listado en pantalla con los resultados coincidentes. |
|  |  |  |
|  |  |  |
|  |  |  |
| **Postcondición**  | El usuario obtiene el detalle de los eventos de auditoría que coinciden con los filtros aplicados. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen eventos que coincidan con los filtros aplicados, el sistema muestra el mensaje "No se encontraron registros para los criterios especificados". |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


