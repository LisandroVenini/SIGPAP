# Definición de Actores

| ACT–01  | Supervisor de Producción  |
| :---- | :---- |
| **Descripción**  | Este actor representa al responsable operativo en el piso de planta. Es el encargado de interactuar con el sistema para ingresar nuevas órdenes de producción y registrar los avances físicos de fabricación mediante el Parte Diario. También se encarga de documentar controles de calidad y registrar intervenciones de mantenimiento en las maquinarias. |
| **Comentarios**  | Ninguno   |


| ACT–02 | Encargado de Stock  |
| :---- | :---- |
| **Descripción**  | Este actor representa al responsable logístico del depósito y almacén. Interactúa con el sistema para gestionar el catálogo de artículos, asentar ingresos de mercancía y registrar mermas o ajustes. |
| **Comentarios**  | Ninguno   |


| ACT–03 | Encargado de Compras  |
| :---- | :---- |
| **Descripción**  | Este actor representa al responsable comercial de abastecimiento de la fábrica. Utiliza el sistema para gestionar proveedores, emitir o aprobar solicitudes de cotización, y adjudicar órdenes de compra oficiales basándose en las sugerencias del motor predictivo. |
| **Comentarios**  | Ninguno   |


| ACT–04 | Proveedor |
| :---- | :---- |
| **Descripción**  | Este actor representa a las entidades comerciales fuera de la fábrica. Es un actor temporal que interactúa con el sistema desde el exterior mediante enlaces seguros de un solo uso, con el fin exclusivo de ingresar cotizaciones y confirmar la recepción de órdenes de compra. |
| **Comentarios**  | Ninguno   |


| ACT–05 | Componente Predictivo  |
| :---- | :---- |
| **Descripción**  | Este actor representa al componente Python de Machine Learning, invocado por el monolito para recibir datos, procesar cálculos matemáticos complejos, evaluar riesgos logísticos y devolver tiempos recalculados para el simulador de escenarios. |
| **Comentarios**  | Este actor representa el núcleo del Sistema de Soporte a Decisiones (DSS). Es invocado por el monolito para recibir datos, calcular y devolver resultados, tanto en procesos automáticos como en solicitudes directas de simulación.   |


| ACT–06 | Gerente  |
| :---- | :---- |
| **Descripción**  | Este actor representa a la dirección estratégica y táctica de la empresa. Utiliza el sistema para consultar tableros de Inteligencia de Negocios (BI), leer registros de auditoría gerencial y operar el simulador predictivo de escenarios  |
| **Comentarios**  | Ninguno   |

| ACT–07 | Administrador  |
| :---- | :---- |
| **Descripción**  | Este actor representa al personal técnico responsable de la seguridad y configuración de la plataforma. Se encarga de gestionar los perfiles de usuario, asignar roles, configurar parámetros globales y auditar los registros del sistema. |
| **Comentarios**  | Ninguno   |


