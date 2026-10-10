# Casos de Uso — Compras

| CU-COM-01 | Alta de Proveedor |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee registrar un nuevo proveedor. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de proveedores. |
|  | 2 | El sistema despliega el listado de proveedores existentes. |
|  | 3 | El usuario selecciona la opción "Registrar nuevo proveedor". |
|  | 4 | El usuario ingresa los datos del proveedor: Razón Social, CUIT, Email y Teléfono. |
|  | 5 | El usuario confirma la operación de guardado. |
|  | 6 | El sistema valida que el CUIT no esté duplicado y que el formato del correo electrónico sea válido. |
|  | 7 | El sistema informa que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El proveedor queda habilitado para ser incluido en futuras Solicitudes de Cotización y Órdenes de Compra. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el sistema detecta que el CUIT o el Email ya existen en otro registro, bloquea el guardado e informa del conflicto al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-02 | Baja de Proveedor |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee dar de baja a un proveedor. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. El proveedor existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de proveedores. |
|  | 2 | El sistema despliega el listado de proveedores existentes. |
|  | 3 | El usuario selecciona un proveedor y elige la opción "Dar de Baja". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema inactiva al proveedor (baja lógica), impidiendo futuras comunicaciones pero manteniendo el historial intacto. |
|  | 7 | El sistema informa al usuario que la baja se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El proveedor queda inactivo y deja de poder participar en nuevas licitaciones, conservando su historial. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el proveedor tiene una Solicitud de Cotización u Orden de Compra en curso, el sistema bloquea la baja e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La baja es lógica para conservar la trazabilidad del historial y del ranking. |  |


| CU-COM-03 | Modificación de Proveedor |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee modificar los datos de un proveedor existente. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. El proveedor existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de proveedores. |
|  | 2 | El sistema despliega el listado de proveedores existentes. |
|  | 3 | El usuario selecciona un proveedor y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos del proveedor en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (Razón Social, Email o Teléfono). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | El proveedor queda actualizado con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si el nuevo CUIT o Email ya existen en otro registro, el sistema bloquea el guardado e informa del conflicto al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo |
| **Frecuencia**  | Baja (mantenimientos puntuales). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-04 | Listado de Proveedores |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee visualizar el conjunto de proveedores registrados en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de proveedores. |
|  | 2 | El sistema despliega el listado de proveedores, mostrando para cada uno su razón social, CUIT, email y estado. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de proveedores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen proveedores registrados, el sistema muestra el mensaje "No hay proveedores registrados". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-05 | Buscar Proveedor |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee localizar un proveedor específico mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de proveedores. |
|  | 2 | El sistema despliega el listado de proveedores y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. razón social o CUIT). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra los proveedores y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza los proveedores que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen proveedores que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron proveedores para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-06 | Alta de Solicitud de Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras genere una solicitud de cotización para uno o varios artículos y la distribuya a una lista de proveedores seleccionados. |  |
| **Precondición**  | Los artículos a cotizar existen en el catálogo y hay proveedores activos en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario ingresa a la gestión de Solicitudes de Cotización. |
|  | 2 | El sistema resalta un "Borrador de Solicitud" generado automáticamente por el motor predictivo. |
|  | 3 | El usuario selecciona el borrador para revisarlo. |
|  | 4 | El sistema muestra los artículos sugeridos, las cantidades y los proveedores preseleccionados. |
|  | 5 | El usuario hace clic en "Aprobar y Enviar Solicitud". |
|  | 6 | El sistema consolida la solicitud, le asigna una fecha de vencimiento y genera los enlaces web únicos por cada proveedor. |
|  | 7 | El sistema envía los correos electrónicos automáticamente e informa del éxito al usuario. |
|  |  |  |
| **Postcondición**  | La solicitud de cotización queda enviada y el sistema queda a la espera de las respuestas de los proveedores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | **(Flujo Manual):** Si el usuario no usa el borrador, selecciona "Crear Solicitud de Cotización", ingresa los artículos, las cantidades, la fecha de vencimiento, elige a los proveedores deseados y confirma el envío. |
|  |  |  |
|  |  |  |
|  | 5 | Si el usuario no está de acuerdo con el borrador predictivo, puede elegir "Modificar Borrador" (agregando/quitando ítems) antes de aprobarlo, o "Descartar", anulando el procedimiento. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Hasta 3 segundos |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Pilar del DSS: el sistema arma el borrador y el usuario solo aplica su criterio para aprobarlo. |  |


| CU-COM-07 | Baja de Solicitud de Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee anular una solicitud de cotización. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. La solicitud de cotización existe y no ha sido adjudicada. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de solicitudes de cotización. |
|  | 2 | El sistema despliega el listado de solicitudes existentes. |
|  | 3 | El usuario selecciona una solicitud y elige la opción "Anular". |
|  | 4 | El sistema solicita el motivo de la anulación. |
|  | 5 | El usuario ingresa el motivo y confirma la operación. |
|  | 6 | El sistema anula la solicitud e invalida los enlaces enviados a los proveedores. |
|  | 7 | El sistema informa al usuario que la anulación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La solicitud de cotización queda anulada y los enlaces de los proveedores dejan de ser válidos. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si la solicitud ya fue adjudicada o convertida en Orden de Compra, el sistema bloquea la anulación e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-08 | Modificación de Solicitud de Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee modificar una solicitud de cotización antes de su envío o vencimiento. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. La solicitud existe y aún no tiene respuestas cargadas por los proveedores. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de solicitudes de cotización. |
|  | 2 | El sistema despliega el listado de solicitudes existentes. |
|  | 3 | El usuario selecciona una solicitud y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos de la solicitud en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (artículos, cantidades, proveedores o fecha de vencimiento). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La solicitud de cotización queda actualizada con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si la solicitud ya recibió respuestas de proveedores, el sistema bloquea la modificación e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo |
| **Frecuencia**  | Baja (ajustes previos al envío). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-09 | Listado de Solicitudes de Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee visualizar el conjunto de solicitudes de cotización del sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de solicitudes de cotización. |
|  | 2 | El sistema despliega el listado de solicitudes, mostrando para cada una su estado, artículos, proveedores y fecha de vencimiento. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de solicitudes de cotización. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen solicitudes registradas, el sistema muestra el mensaje "No hay solicitudes de cotización registradas". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-10 | Buscar Solicitud de Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee localizar una solicitud de cotización específica mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de solicitudes de cotización. |
|  | 2 | El sistema despliega el listado de solicitudes y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. proveedor o rango de fechas). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra las solicitudes y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza las solicitudes que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen solicitudes que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron solicitudes para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-11 | Alta de Orden de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-02 Prevención Proactiva de Cuellos de Botella OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información de Compras IRQ-04 Información Analítica y Predictiva RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras emita una Orden de Compra oficial, ya sea adjudicando la mejor cotización de una licitación o creándola manualmente. |  |
| **Precondición**  | Existe una Solicitud de Cotización con respuestas cargadas por los proveedores, o el usuario decide crear una OC manual. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario ingresa a la gestión de Órdenes de Compra y selecciona una Solicitud de Cotización cerrada. |
|  | 2 | El sistema presenta el "Informe Multicriterio", destacando visualmente la oferta ganadora según la fórmula matemática (relación precio / tiempo de entrega / ranking del proveedor). |
|  | 3 | El usuario revisa el informe y selecciona "Aprobar Sugerencia y Emitir Orden". |
|  | 4 | El sistema genera la Orden de Compra oficial para el proveedor ganador. |
|  | 5 | El sistema envía un correo electrónico al proveedor para que confirme la recepción de la orden. |
|  | 6 | El sistema actualiza el estado de la OC a "Emitida" e informa el éxito de la operación. |
|  |  |  |
| **Postcondición**  | La Orden de Compra queda en estado "Emitida", a la espera de confirmación por parte del proveedor. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 1 | **(Flujo Manual):** El usuario selecciona "Crear Orden de Compra", selecciona proveedor, artículos y precios pactados, y emite la orden sin pasar por una licitación previa. |
|  | 1 | **(Regla de Cota):** Si el monto total de una OC manual supera la cota máxima de compra directa configurada en los parámetros del sistema, el sistema bloquea la emisión directa y sugiere abrir una licitación (la compra directa queda habilitada solo por debajo de la cota). |
|  |  |  |
|  |  |  |
|  | 3 | **(Elección Manual):** Si el usuario decide no aceptar la sugerencia del sistema, selecciona manualmente a otro proveedor de la lista y emite la orden para este. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Hasta 3 segundos |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La adjudicación de la licitación y la emisión de la OC son responsabilidad del Encargado de Compras. La cota máxima de compra directa se configura en Parámetros del Sistema (criterios de adjudicación). |  |


| CU-COM-12 | Baja de Orden de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee anular una Orden de Compra. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. La Orden de Compra existe y aún no fue recibida. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Compra. |
|  | 2 | El sistema despliega el listado de OC existentes. |
|  | 3 | El usuario selecciona una OC y elige la opción "Anular". |
|  | 4 | El sistema solicita el motivo de la anulación. |
|  | 5 | El usuario ingresa el motivo y confirma la operación. |
|  | 6 | El sistema cambia el estado de la OC a "Anulada" y notifica al proveedor. |
|  | 7 | El sistema informa al usuario que la anulación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La Orden de Compra queda en estado "Anulada". |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si la OC ya fue recibida o confirmada, el sistema bloquea la anulación e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-13 | Modificación de Orden de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee modificar una Orden de Compra aún no confirmada por el proveedor. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. La Orden de Compra existe y no fue confirmada por el proveedor. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Compra. |
|  | 2 | El sistema despliega el listado de OC existentes. |
|  | 3 | El usuario selecciona una OC y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos de la OC en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (artículos o cantidades). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La Orden de Compra queda actualizada con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si la OC ya fue confirmada por el proveedor, el sistema bloquea la modificación e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo |
| **Frecuencia**  | Baja (ajustes previos a confirmación). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-14 | Listado de Órdenes de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee visualizar el conjunto de Órdenes de Compra del sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Compra. |
|  | 2 | El sistema despliega el listado de OC, mostrando para cada una su proveedor, artículos, estado y fecha de emisión. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de Órdenes de Compra. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen OC registradas, el sistema muestra el mensaje "No hay órdenes de compra registradas". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-15 | Buscar Orden de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee localizar una Orden de Compra específica mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Compra. |
|  | 2 | El sistema despliega el listado de OC y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. proveedor, estado o rango de fechas). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra las OC y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza las Órdenes de Compra que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen OC que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron órdenes de compra para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-16 | Enviar Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando un Proveedor Externo acceda a la plataforma para ofertar sus precios y tiempos, tanto en una solicitud de cotización (Flujo B) como en una consulta de disponibilidad directa (Flujo A). |  |
| **Precondición**  | El Proveedor ha recibido un correo electrónico con un Link válido y la solicitud o consulta sigue abierta. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Proveedor Externo hace clic en el enlace recibido en su correo. |
|  | 2 | El sistema valida el token de seguridad del enlace y despliega una pantalla web con la lista de insumos solicitados. |
|  | 3 | El Proveedor ingresa su Precio Unitario y el Tiempo Estimado de Entrega para cada artículo (y disponibilidad, en el Flujo A). |
|  | 4 | El Proveedor hace clic en "Enviar Cotización". |
|  | 5 | El sistema almacena los datos, los vincula a la solicitud original y cierra el token para evitar re-envíos. |
|  | 6 | El sistema muestra un mensaje de confirmación al proveedor y notifica internamente al Encargado de Compras. |
|  |  |  |
| **Postcondición**  | La oferta comercial del proveedor queda registrada para ser evaluada por el motor multicriterio (Flujo B) o por el microservicio (Flujo A). |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si el enlace ya fue utilizado, o la fecha límite expiró, el sistema deniega el acceso y muestra un mensaje informando que está cerrada. |
|  |  |  |
|  |  |  |
|  | 4 | Si el proveedor intenta enviar el formulario dejando precios en blanco (o en valor 0) para artículos marcados como obligatorios, el sistema le solicita que complete todos los campos antes de proceder. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 1 segundo |
| **Frecuencia**  | Alta |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Se reutiliza para los dos flujos de compra: el proveedor carga su respuesta vía enlace seguro en ambos casos. |  |


| CU-COM-17 | Confirmar Orden de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Proveedor acepte formalmente el pedido adjudicado, estableciendo el compromiso de entrega. |  |
| **Precondición**  | El sistema ha emitido una Orden de Compra y enviado el enlace de validación al Proveedor. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Proveedor Externo hace clic en el enlace de confirmación en su correo. |
|  | 2 | El sistema valida el token y le muestra el detalle completo de la Orden de Compra oficializada. |
|  | 3 | El Proveedor selecciona el botón "Aceptar y Confirmar Orden". |
|  | 4 | El sistema cambia el estado de la Orden de Compra a "Confirmada". |
|  | 5 | El sistema registra la fecha actual como inicio del reloj para medir el tiempo de entrega real del proveedor y emite un mensaje de éxito. |
|  |  |  |
|  |  |  |
|  |  |  |
| **Postcondición**  | La Orden de Compra queda en estado "Confirmada" y entra formalmente en curso. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | Si el proveedor presiona "Rechazar Pedido" (ej. por falta de stock), el sistema cambia el estado a "Rechazada", anula el pedido y dispara una alerta urgente al Encargado de Compras. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 4 | 1 segundo |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Si el proveedor rechaza, es el Encargado de Compras quien decide cómo continuar (no hay compra automática). |  |


| CU-COM-18 | Consultar Ranking de Proveedores |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras IRQ-04 Información Analítica, Predictiva y de Simulación RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee visualizar el ranking dinámico de proveedores y su desempeño histórico. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder al ranking de proveedores. |
|  | 2 | El sistema despliega el ranking ordenado, mostrando para cada proveedor su posición, desempeño y métricas (retrasos, faltantes, roturas). |
|  | 3 | El usuario puede seleccionar un proveedor para ver el detalle de su historial. |
|  | 4 | El sistema muestra el desglose de incidencias y el puntaje de riesgo predictivo del proveedor. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el ranking y el desempeño de los proveedores para apoyar sus decisiones de compra. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no hay datos suficientes para calcular el ranking, el sistema muestra el mensaje "Recolectando datos de desempeño de proveedores". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos |
| **Frecuencia**  | Media (antes de cada decisión de compra). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El ranking se calcula automáticamente (proceso interno + microservicio); este CU es solo la consulta. |  |


| CU-COM-19 | Solicitar Disponibilidad a Proveedor |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-02 Prevención Proactiva de Cuellos de Botella OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras desee consultar disponibilidad a un único proveedor (compra directa - Flujo A) en lugar de abrir una licitación competitiva. |  |
| **Precondición**  | El sistema detectó stock bajo y propuso un proveedor mejor rankeado. El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El sistema notifica al usuario la detección de stock bajo y resalta al proveedor mejor rankeado como sugerencia. |
|  | 2 | El usuario revisa la sugerencia y aprueba la intención de consultar disponibilidad a ese proveedor. |
|  | 3 | El sistema genera y envía al proveedor un enlace seguro con el detalle: "Confort y Muebles desea pedirte X. Confirmá disponibilidad, precio actualizado y fecha de entrega". |
|  | 4 | El sistema deja la consulta en estado "En espera de respuesta" y notifica al usuario. |
|  |  |  |
| **Postcondición**  | La consulta de disponibilidad queda enviada y el sistema espera la respuesta del proveedor (CU-COM-16). |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si el usuario no está de acuerdo con el proveedor sugerido, puede elegir manualmente otro proveedor del ranking antes de enviar la consulta. |
|  |  |  |
|  |  |  |
|  | 3 | Si el proveedor seleccionado no tiene email registrado, el sistema bloquea el envío e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 3 | Hasta 3 segundos |
| **Frecuencia**  | Media (cada vez que se detecta stock bajo crítico). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La detección automática de stock bajo es un proceso del sistema (no CU); este CU lo acciona el Encargado de Compras. |  |


| CU-COM-20 | Resolver Disponibilidad de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-02 Prevención Proactiva de Cuellos de Botella OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras IRQ-04 Información Analítica, Predictiva y de Simulación RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Microservicio Predictivo evalúe la respuesta del proveedor a una consulta de disponibilidad (Flujo A) y recomiende una acción al Encargado de Compras. |  |
| **Precondición**  | Existe una consulta de disponibilidad con respuesta cargada por el proveedor (CU-COM-16). |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Monolito envía al Microservicio la respuesta del proveedor (disponibilidad, precio, fecha) y el umbral de tolerancia configurado. |
|  | 2 | El Microservicio compara la respuesta contra el umbral de tolerancia y el cronograma de producción. |
|  | 3 | El Microservicio clasifica el resultado en uno de tres escenarios (Ideal, Sin Stock, Variación Fuerte) y devuelve una recomendación. |
|  | 4 | El Monolito presenta la recomendación al Encargado de Compras para su decisión. |
|  |  |  |
| **Postcondición**  | El Encargado de Compras recibe la recomendación del microservicio y es quien decide la acción final (emitir OC, cotizar con otro proveedor o aprobar el nuevo costo). |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | **Escenario Sin Stock:** Si el proveedor respondió "Stock 0", el microservicio descarta al proveedor para esta compra y dispara la consulta al siguiente proveedor del ranking. |
|  |  |  |
|  |  |  |
|  | 3 | **Escenario Variación Fuerte:** Si el precio subió por encima del umbral o el tiempo rompe el Gantt, el microservicio frena y alerta al Encargado de Compras: "el proveedor confirmó, pero el precio supera el umbral esperado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 3 | Menos de 5 segundos |
| **Frecuencia**  | Media (una por cada consulta de disponibilidad respondida). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El microservicio EVALÚA y RECOMIENDA, pero nunca emite la Orden de Compra: la decisión final (y la emisión vía CU-COM-11) es siempre del Encargado de Compras. |  |


