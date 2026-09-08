# Casos de Uso — Producción

| CU-PROD-01 | Alta de Planificación de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee registrar una nueva planificación marco que agrupará múltiples órdenes de producción. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planificaciones. |
|  | 2 | El sistema despliega el listado de planificaciones existentes. |
|  | 3 | El usuario selecciona la opción "Crear nueva planificación". |
|  | 4 | El usuario ingresa los datos de la planificación: Nombre de campaña, Fecha estimada de inicio, Fecha estimada de fin y Observaciones. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema valida los datos y registra la nueva planificación en la base de datos. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La planificación de producción queda registrada en el sistema y disponible para vincularle futuras órdenes de producción. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el sistema detecta que la Fecha de Fin es anterior a la Fecha de Inicio, emite una advertencia al usuario y detiene el guardado hasta que se corrija. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 2 segundos. |
| **Frecuencia**  | Baja (1 a 2 veces por mes). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-02 | Baja de Planificación de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee dar de baja una planificación de producción que ya no resulte necesaria. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. Existe al menos una planificación registrada. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planificaciones. |
|  | 2 | El sistema despliega el listado de planificaciones existentes. |
|  | 3 | El usuario selecciona una planificación del listado y elige la opción "Dar de baja". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema verifica que la planificación no contenga Órdenes de Producción asociadas y procede con la baja. |
|  | 7 | El sistema informa al usuario que la baja se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La planificación de producción queda eliminada del sistema. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si la planificación contiene Órdenes de Producción asociadas, el sistema bloquea la baja e informa al usuario que primero debe reasignar o eliminar dichas órdenes. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-03 | Modificación de Planificación de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee modificar los datos de una planificación de producción existente. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. Existe la planificación a modificar. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planificaciones. |
|  | 2 | El sistema despliega el listado de planificaciones existentes. |
|  | 3 | El usuario selecciona una planificación del listado y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos de la planificación en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (Nombre de campaña, fechas u Observaciones). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La planificación de producción queda actualizada con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si el sistema detecta que la Fecha de Fin es anterior a la Fecha de Inicio, emite una advertencia al usuario y detiene el guardado hasta que se corrija. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 2 segundos. |
| **Frecuencia**  | Baja (1 a 2 veces por mes). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-04 | Listado de Planificaciones de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar el conjunto de planificaciones de producción registradas en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planificaciones. |
|  | 2 | El sistema despliega el listado de planificaciones existentes, mostrando para cada una su nombre de campaña, fechas y estado. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de planificaciones de producción. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen planificaciones registradas, el sistema muestra el mensaje "No hay planificaciones registradas". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-05 | Buscar Planificación de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee localizar una planificación específica mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planificaciones. |
|  | 2 | El sistema despliega el listado de planificaciones y un campo de búsqueda. |
|  | 3 | El usuario ingresa criterios de búsqueda (ej. nombre de campaña o rango de fechas). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra las planificaciones y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza las planificaciones que coinciden con los criterios ingresados. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen planificaciones que coincidan con los criterios, el sistema muestra el mensaje "No se encontraron planificaciones para los criterios especificados". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-06 | Alta de Orden de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee registrar una nueva Orden de Producción (OP). |  |
| **Precondición**  | Existe al menos una Planificación activa. El producto a fabricar existe en el catálogo de productos terminados y tiene su hoja de ruta (etapas) definida. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Producción. |
|  | 2 | El sistema muestra el listado de las OP actuales. |
|  | 3 | El usuario selecciona la opción "Crear nueva Orden". |
|  | 4 | El usuario selecciona el Producto, ingresa la Cantidad requerida, la Fecha de entrega esperada y vincula la orden a una Planificación. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema genera la OP, le asigna el estado "Pendiente" y carga internamente la hoja de ruta del producto. |
|  | 7 | El sistema informa al usuario el éxito de la operación. |
|  |  |  |
| **Postcondición**  | La Orden de Producción queda creada en estado "Pendiente" y los materiales necesarios quedan "Comprometidos" en el inventario (reserva lógica). |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el stock físico no alcanza para cubrir la reserva lógica, el sistema alerta al usuario del quiebre inminente y deja la OP en estado "Pendiente por Insumos", disparando una alerta para el módulo de Compras. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 2 segundos. |
| **Frecuencia**  | Alta (Múltiples veces por semana). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-07 | Baja de Orden de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee anular una Orden de Producción que ya no se ejecutará. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. La Orden de Producción se encuentra en estado "Pendiente" o "Pendiente por Insumos". |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Producción. |
|  | 2 | El sistema muestra el listado de las OP actuales. |
|  | 3 | El usuario selecciona una OP y elige la opción "Anular". |
|  | 4 | El sistema solicita el motivo de la anulación. |
|  | 5 | El usuario ingresa el motivo y confirma la operación. |
|  | 6 | El sistema cambia el estado de la OP a "Anulada" y libera las reservas lógicas de inventario asociadas. |
|  | 7 | El sistema informa al usuario el éxito de la operación. |
|  |  |  |
| **Postcondición**  | La Orden de Producción queda en estado "Anulada" y los insumos comprometidos vuelven a estar disponibles. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si la OP ya se encuentra en ejecución ("En Curso"), el sistema bloquea la anulación e informa al usuario que la orden ya fue iniciada. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-08 | Modificación de Orden de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee modificar los datos de una Orden de Producción aún no iniciada. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. La Orden de Producción se encuentra en estado "Pendiente". |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Producción. |
|  | 2 | El sistema muestra el listado de las OP actuales. |
|  | 3 | El usuario selecciona una OP y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos de la OP en el formulario para su edición. |
|  | 5 | El usuario modifica las cantidades o fechas deseadas. |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro, recalculando las reservas lógicas si cambió la cantidad. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La Orden de Producción queda actualizada con los nuevos valores y sus reservas lógicas ajustadas. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si la OP ya se encuentra en ejecución ("En Curso"), el sistema bloquea la modificación e informa al usuario. |
|  | 7 | Si el stock físico no alcanza para cubrir la nueva reserva lógica, el sistema deja la OP en estado "Pendiente por Insumos" y dispara una alerta para el módulo de Compras. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 2 segundos. |
| **Frecuencia**  | Media (ajustes puntuales). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-09 | Listado de Órdenes de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar el conjunto de Órdenes de Producción registradas en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Producción. |
|  | 2 | El sistema despliega el listado de OP, mostrando para cada una su producto, cantidad, estado y fecha de entrega. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de Órdenes de Producción. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen Órdenes de Producción registradas, el sistema muestra el mensaje "No hay órdenes de producción registradas". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos. |
| **Frecuencia**  | Alta (consultas diarias). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-10 | Buscar Orden de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee localizar una Orden de Producción específica mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Producción. |
|  | 2 | El sistema despliega el listado de OP y un campo de búsqueda. |
|  | 3 | El usuario ingresa criterios de búsqueda (ej. producto, estado o rango de fechas). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra las órdenes y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza las Órdenes de Producción que coinciden con los criterios ingresados. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen órdenes que coincidan con los criterios, el sistema muestra el mensaje "No se encontraron órdenes de producción para los criterios especificados". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos. |
| **Frecuencia**  | Alta (consultas diarias). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-16 | Alta de Etapa de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee registrar una nueva etapa de producción en el catálogo (ej. Lijado, Pintado, Armado, Embalado). |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de etapas de producción. |
|  | 2 | El sistema despliega el listado de etapas existentes. |
|  | 3 | El usuario selecciona la opción "Crear nueva etapa". |
|  | 4 | El usuario ingresa los datos de la etapa: nombre y descripción. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema valida que el nombre de la etapa no esté duplicado y registra la nueva etapa en el catálogo. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La etapa de producción queda registrada en el catálogo y disponible para ser asignada a productos. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el nombre de la etapa ya existe en el catálogo, el sistema cancela el guardado e informa el error al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (el catálogo se define una sola vez y se amplía esporádicamente). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La etapa del catálogo no tiene tiempo asociado: el tiempo por unidad se define al asignar la etapa a un producto (CU-PROD-21). |  |


| CU-PROD-17 | Baja de Etapa de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee dar de baja una etapa de producción del catálogo. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. Existe al menos una etapa registrada. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de etapas de producción. |
|  | 2 | El sistema despliega el listado de etapas existentes. |
|  | 3 | El usuario selecciona una etapa del listado y elige la opción "Dar de baja". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema verifica que la etapa no esté asignada a ningún producto y procede con la baja. |
|  | 7 | El sistema informa al usuario que la baja se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La etapa de producción queda eliminada del catálogo. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si la etapa está asignada a uno o más productos, el sistema bloquea la baja e informa que primero debe desasignarse de dichos productos (CU-PROD-22). |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-18 | Modificación de Etapa de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee modificar el nombre o la descripción de una etapa de producción del catálogo. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. Existe la etapa a modificar. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de etapas de producción. |
|  | 2 | El sistema despliega el listado de etapas existentes. |
|  | 3 | El usuario selecciona una etapa del listado y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos de la etapa en el formulario para su edición. |
|  | 5 | El usuario modifica el nombre o la descripción. |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La etapa de producción queda actualizada con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si el nuevo nombre ya existe en otra etapa del catálogo, el sistema cancela el guardado e informa el error al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La modificación no afecta los tiempos ya definidos en las asignaciones a productos. |  |


| CU-PROD-19 | Listado de Etapas de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar el conjunto de etapas de producción del catálogo. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de etapas de producción. |
|  | 2 | El sistema despliega el listado de etapas existentes, mostrando para cada una su nombre y descripción. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de etapas de producción. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen etapas registradas, el sistema muestra el mensaje "No hay etapas de producción registradas". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-20 | Buscar Etapa de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee localizar una etapa de producción específica del catálogo mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de etapas de producción. |
|  | 2 | El sistema despliega el listado de etapas y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. nombre de la etapa). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra las etapas y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza las etapas que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen etapas que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron etapas para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-21 | Asignar Etapa a Producto |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee asignar una etapa del catálogo a un Producto Terminado, definiendo su tiempo estimado por unidad. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. El producto existe en el catálogo y la etapa a asignar existe en el catálogo de etapas. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de hoja de ruta de productos. |
|  | 2 | El sistema despliega el listado de productos terminados. |
|  | 3 | El usuario selecciona un producto y solicita la opción "Asignar etapa". |
|  | 4 | El sistema muestra las etapas ya asignadas al producto y sus tiempos, junto con el catálogo de etapas disponibles. |
|  | 5 | El usuario selecciona una etapa del catálogo e ingresa el tiempo estimado por unidad para ese producto. |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y registra la asignación etapa↔producto con su tiempo. |
|  | 8 | El sistema informa al usuario que la asignación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La etapa queda asignada al producto con su tiempo estimado por unidad, formando parte de su hoja de ruta. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si la etapa ya está asignada al producto, el sistema bloquea la operación e informa que la etapa ya forma parte de la hoja de ruta. |
|  | 7 | Si el tiempo ingresado es cero o negativo, el sistema rechaza el guardado y solicita un valor válido. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (la hoja de ruta se define una sola vez por producto). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El tiempo por unidad se define aquí y no en el catálogo de etapas (CU-PROD-16), porque el mismo tipo de etapa puede durar distinto según el producto. |  |


| CU-PROD-22 | Desasignar Etapa de Producto |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee quitar una etapa de la hoja de ruta de un Producto Terminado. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. La etapa está asignada al producto. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de hoja de ruta de productos. |
|  | 2 | El sistema despliega el listado de productos terminados. |
|  | 3 | El usuario selecciona un producto y solicita la opción "Ver hoja de ruta". |
|  | 4 | El sistema muestra las etapas asignadas al producto y sus tiempos. |
|  | 5 | El usuario selecciona una etapa y elige "Desasignar". |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema verifica que no existan Órdenes de Producción en curso que usen la hoja de ruta del producto y quita la etapa. |
|  | 8 | El sistema informa al usuario que la desasignación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La etapa queda quitada de la hoja de ruta del producto. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si existen Órdenes de Producción en curso que usan la hoja de ruta del producto, el sistema bloquea la desasignación e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (ajustes puntuales de la hoja de ruta). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Desasignar una etapa no elimina la etapa del catálogo (CU-PROD-17), solo la quita del producto. |  |


| CU-PROD-23 | Gestionar Cronograma de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-02 Prevención Proactiva de Cuellos de Botella |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-04 Información Analítica, Predictiva y de Simulación RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar y administrar el cronograma dinámico de producción (Gantt), donde se muestran las órdenes de producción y sus etapas ordenadas en el tiempo, y desde el cual se pueden asignar y reordenar dichas órdenes. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. Existen órdenes de producción registradas con sus hojas de ruta (etapas y tiempos por unidad) definidas. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder al Cronograma de Producción. |
|  | 2 | El sistema recopila las órdenes de producción activas y calcula su proyección temporal a partir del tiempo por unidad de cada etapa de su hoja de ruta. |
|  | 3 | El sistema renderiza el diagrama de Gantt, mostrando cada orden y sus etapas ubicadas en el calendario según sus tiempos estimados. |
|  | 4 | El usuario visualiza qué órdenes de producción y qué etapas corresponden a cada día. |
|  | 5 | El usuario selecciona una orden del cronograma y la reordena (asignándole una nueva posición o prioridad) o ajusta su ventana temporal. |
|  | 6 | El sistema recalcula el cronograma y despliega el impacto del reordenamiento sobre las demás órdenes. |
|  | 7 | El usuario confirma los cambios y el sistema persiste el nuevo cronograma. |
|  | 8 | El sistema informa al usuario que el cronograma fue actualizado con éxito. |
|  |  |  |
| **Postcondición**  | El cronograma de producción queda actualizado con el nuevo ordenamiento y refleja la proyección temporal recalculada de las órdenes. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen órdenes de producción activas, el sistema muestra el cronograma vacío con la leyenda "No hay órdenes de producción para programar". |
|  |  |  |
|  |  |  |
|  | 6 | Si el reordenamiento propuesto provoca que una orden incumpla su fecha de entrega, el sistema muestra una advertencia resaltando el conflicto antes de persistir el cambio. |
|  | 7 | Si una orden en ejecución es desplazada de su ventana actual, el sistema bloquea el reordenamiento e informa que las órdenes "En Curso" no pueden reubicarse. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 3 | Menos de 3 segundos. |
|  | 6 | Menos de 3 segundos. |
| **Frecuencia**  | Media (consultas y ajustes de planificación periódicos). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El cronograma es un único caso de uso (no se desglosa en ABM) porque representa una vista dinámica de planificación, no una entidad con alta/baja/modificación. Se actualiza además automáticamente con los datos reales de avance (Partes Diarios) y las predicciones del Microservicio (CU-PRED-02). |  |


| CU-PROD-24 | Alta de Operario |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee registrar un nuevo operario de la planta. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de operarios. |
|  | 2 | El sistema despliega el listado de operarios existentes. |
|  | 3 | El usuario selecciona la opción "Registrar nuevo operario". |
|  | 4 | El usuario ingresa los datos del operario: nombre, apellido y especialidad (ej. carpintería, pintura). |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema valida los datos y registra al nuevo operario. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El operario queda registrado y disponible para ser asignado a las etapas de las órdenes de producción (CU-PROD-11). |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si los datos obligatorios están incompletos, el sistema detiene el guardado e indica los campos faltantes. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (altas iniciales y altas puntuales de personal). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-25 | Baja de Operario |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee dar de baja a un operario que ya no trabaja en la planta. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. El operario existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de operarios. |
|  | 2 | El sistema despliega el listado de operarios existentes. |
|  | 3 | El usuario selecciona un operario y elige la opción "Dar de baja". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema verifica que el operario no esté asignado a órdenes de producción en curso y procede con la baja. |
|  | 7 | El sistema informa al usuario que la baja se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El operario queda dado de baja y ya no puede asignarse a nuevas órdenes. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el operario está asignado a una orden de producción "En Curso", el sistema bloquea la baja e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-26 | Modificación de Operario |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee modificar los datos de un operario existente. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. El operario existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de operarios. |
|  | 2 | El sistema despliega el listado de operarios existentes. |
|  | 3 | El usuario selecciona un operario y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos del operario en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (nombre, apellido o especialidad). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | El operario queda actualizado con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si los datos obligatorios quedan incompletos, el sistema detiene el guardado e indica los campos faltantes. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-27 | Listado de Operarios |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar el conjunto de operarios registrados en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de operarios. |
|  | 2 | El sistema despliega el listado de operarios, mostrando para cada uno su nombre, apellido y especialidad. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de operarios. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen operarios registrados, el sistema muestra el mensaje "No hay operarios registrados". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-28 | Buscar Operario |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee localizar un operario específico mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de operarios. |
|  | 2 | El sistema despliega el listado de operarios y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. nombre o especialidad). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra los operarios y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza los operarios que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen operarios que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron operarios para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-11 | Asignar Recursos a Orden |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-05 Información sobre Maquinarias y Desgaste RF-01 Gestión de la Producción y Parte Diario RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor necesite asignar los operarios responsables y la maquinaria a las etapas de una Orden de Producción específica antes de iniciarla. |  |
| **Precondición**  | La Orden de Producción se encuentra en estado "Pendiente" o "Pendiente por Insumos". Existen operarios (CU-PROD-24..28) y maquinarias activas en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario selecciona una OP Pendiente y solicita la opción "Asignar Recursos". |
|  | 2 | El sistema despliega las etapas de la hoja de ruta de la OP seleccionada. |
|  | 3 | El usuario selecciona de las listas desplegables a los operarios responsables y la maquinaria para cada etapa. |
|  | 4 | El usuario confirma la asignación. |
|  | 5 | El sistema valida la disponibilidad de los recursos seleccionados. |
|  | 6 | El sistema registra la asignación y cambia el estado de la OP a "Asignada". |
|  | 7 | El sistema confirma el éxito de la operación. |
|  |  |  |
| **Postcondición**  | La OP queda en estado "Asignada", con sus operarios y maquinaria asignados, lista para ser iniciada. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si una maquinaria se encuentra bloqueada por Mantenimiento, el sistema la muestra deshabilitada y no permite su selección. |
|  |  |  |
|  |  |  |
|  | 3 | **(Flujo de Desasignación):** Si el usuario desea quitar un recurso ya asignado, lo deselecciona de la lista y confirma; el sistema libera la asignación de esa etapa. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 1 segundo |
| **Frecuencia**  | Diaria |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La asignación unifica operarios y maquinaria en una única operación. Desasignar un recurso es un flujo de modificación dentro de este caso de uso. |  |


| CU-PROD-12 | Iniciar Orden de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor desee iniciar explícitamente la ejecución de una Orden de Producción ya asignada. |  |
| **Precondición**  | La Orden de Producción se encuentra en estado "Asignada", con operarios y maquinaria asignados. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario selecciona una OP en estado "Asignada" y solicita la opción "Iniciar Orden". |
|  | 2 | El sistema confirma que los recursos están asignados. |
|  | 3 | El usuario confirma el inicio de la orden. |
|  | 4 | El sistema cambia el estado de la OP a "En Curso" y registra la fecha de inicio. |
|  | 5 | El sistema informa al usuario que la orden fue iniciada con éxito. |
|  |  |  |
| **Postcondición**  | La Orden de Producción queda en estado "En Curso" y habilitada para el registro de Partes Diarios. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si la OP no tiene todos los recursos asignados, el sistema bloquea el inicio e informa al usuario que primero debe completar la asignación (CU-PROD-11). |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 4 | Menos de 1 segundo. |
| **Frecuencia**  | Alta (una vez por cada orden lanzada). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El inicio es explícito por decisión de negocio: la orden no arranca automáticamente al asignar recursos. |  |


| CU-PROD-13 | Cargar Parte Diario |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-02 Prevención Proactiva de Cuellos de Botella |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor registre las unidades fabricadas al finalizar su turno, delegando procesos automatizados en el sistema central y en el microservicio. |  |
| **Precondición**  | Existe al menos una Orden de Producción en estado "En Curso". |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Supervisor selecciona en su terminal una OP activa y solicita "Cargar Parte Diario". |
|  | 2 | El sistema muestra la etapa actual de la orden y la cantidad objetivo. |
|  | 3 | El Supervisor ingresa la cantidad de unidades completadas en su turno de trabajo y observaciones (opcional). |
|  | 4 | El Supervisor pulsa en confirmar. |
|  | 5 | El sistema asienta el avance, acumulando las unidades terminadas para la etapa en cuestión. |
|  | 6 | El sistema consume el stock físico de insumos (libera la reserva lógica y descuenta existencias). |
|  | 7 | El sistema incrementa el desgaste acumulado de la maquinaria utilizada. |
|  | 8 | El sistema envía una petición asíncrona al Microservicio Predictivo para evaluar la Tasa de Rendimiento Real (TRR). |
|  | 9 | El sistema notifica al usuario que el parte se registró exitosamente. |
|  |  |  |
| **Postcondición**  | El progreso de la OP se actualiza, el stock físico se consume, se incrementa el desgaste de la maquinaria y el motor predictivo recibe nueva información para evaluar desviaciones. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si la cantidad ingresada por el supervisor supera la cantidad total requerida por la OP, el sistema despliega una alerta de validación requiriendo confirmación doble antes de guardar. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5,6,7 | Menor a 2 segundos para la respuesta al usuario. |
| **Frecuencia**  | Alta |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El consumo de stock se produce aquí (reserva al crear la orden, consumo al cargar el Parte Diario). |  |


| CU-PROD-14 | Registrar Control de Calidad |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor inspeccione el lote producido y determine mermas o rechazos. |  |
| **Precondición**  | Existe un Parte Diario previamente cargado con unidades declaradas listas para su inspección. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Supervisor selecciona el registro de un Parte Diario y solicita "Registrar Inspección de Calidad". |
|  | 2 | El sistema muestra las unidades declaradas. |
|  | 3 | El Supervisor ingresa la cantidad de unidades rechazadas y selecciona el motivo de la falla (ej. error de corte, rotura). |
|  | 4 | El Supervisor confirma la operación. |
|  | 5 | El sistema registra la merma y descuenta las unidades rechazadas de unidades válidas de la OP. |
|  | 6 | El sistema emite un mensaje de éxito. |
|  |  |  |
|  |  |  |
| **Postcondición**  | La métrica de unidades reales terminadas queda corregida, asegurando que la OP no se cierre con "falsos positivos" de producción. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | Si todas las unidades están correctas, el Supervisor ingresa "0" en rechazos. El sistema asienta el lote como 100% conforme. |
|  |  |  |
|  |  |  |
|  | 5 | Si las unidades rechazadas ingresadas superan la cantidad total reportada en el Parte Diario, el sistema rechaza el guardado y exige su corrección inmediata. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 1 segundo |
| **Frecuencia**  | Media (Una vez por cada lote finalizado) |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-15 | Cerrar Orden de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor desee cerrar explícitamente una Orden de Producción al terminar la última etapa. |  |
| **Precondición**  | La Orden de Producción se encuentra en estado "En Curso". La última etapa reportó su avance completo y existe control de calidad registrado para dicha etapa. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario selecciona una OP en estado "En Curso" y solicita la opción "Cerrar Orden". |
|  | 2 | El sistema muestra el resumen de unidades producidas y valida que exista control de calidad para la última etapa. |
|  | 3 | El usuario registra las unidades producidas finales y confirma el cierre. |
|  | 4 | El sistema consolida las unidades válidas y cambia el estado de la OP a "Cerrada". |
|  | 5 | El sistema informa al usuario que la orden fue cerrada con éxito. |
|  |  |  |
| **Postcondición**  | La Orden de Producción queda en estado "Cerrada" con sus unidades válidas consolidadas. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existe control de calidad registrado para la última etapa, el sistema bloquea el cierre e informa que primero debe registrarse la inspección (CU-PROD-14). |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 4 | Menos de 1 segundo. |
| **Frecuencia**  | Alta (una vez por cada orden finalizada). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El cierre es explícito y la calidad es prerrequisito, por decisión de negocio. |  |


## Subdominio Mantenimiento

| CU-MANT-01 | Alta de Maquinaria |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee registrar una nueva maquinaria en la planta. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de maquinarias. |
|  | 2 | El sistema despliega el listado de maquinarias existentes. |
|  | 3 | El usuario selecciona la opción "Registrar nueva maquinaria". |
|  | 4 | El usuario ingresa los datos de la maquinaria: nombre y tipo. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema valida los datos y registra la nueva maquinaria con desgaste acumulado inicial en cero. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La maquinaria queda registrada y disponible para ser asignada a las etapas de las órdenes de producción (CU-PROD-11). |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si los datos obligatorios están incompletos, el sistema detiene el guardado e indica los campos erróneos. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (altas iniciales y adquisición puntual de equipos). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El umbral de desgaste crítico no se registra en el alta: se define en el Plan de Mantenimiento de la maquinaria (CU-MANT-06), que es quien dispara el bloqueo preventivo. |  |


| CU-MANT-02 | Baja de Maquinaria |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee dar de baja una maquinaria que ya no se utiliza. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. La maquinaria existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de maquinarias. |
|  | 2 | El sistema despliega el listado de maquinarias existentes. |
|  | 3 | El usuario selecciona una maquinaria y elige la opción "Dar de baja". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema verifica que la maquinaria no esté asignada a órdenes de producción en curso y procede con la baja. |
|  | 7 | El sistema informa al usuario que la baja se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La maquinaria queda dada de baja y ya no puede asignarse a nuevas órdenes. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si la maquinaria está asignada a una orden de producción "En Curso", el sistema bloquea la baja e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-03 | Modificación de Maquinaria |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee modificar los datos de una maquinaria existente. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. La maquinaria existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de maquinarias. |
|  | 2 | El sistema despliega el listado de maquinarias existentes. |
|  | 3 | El usuario selecciona una maquinaria y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos de la maquinaria en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (nombre o tipo). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | La maquinaria queda actualizada con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si los datos modificados quedan incompletos, el sistema detiene el guardado e indica el campo erróneo. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-04 | Listado de Maquinarias |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar el conjunto de maquinarias registradas en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de maquinarias. |
|  | 2 | El sistema despliega el listado de maquinarias, mostrando para cada una su nombre, tipo, desgaste acumulado y umbral crítico. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de maquinarias con su estado de desgaste. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen maquinarias registradas, el sistema muestra el mensaje "No hay maquinarias registradas". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-05 | Buscar Maquinaria |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee localizar una maquinaria específica mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de maquinarias. |
|  | 2 | El sistema despliega el listado de maquinarias y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. nombre o tipo). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra las maquinarias y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza las maquinarias que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen maquinarias que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron maquinarias para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-06 | Alta de Plan de Mantenimiento |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee definir una regla de mantenimiento preventivo por uso para una maquinaria. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. La maquinaria a la que aplicará el plan existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planes de mantenimiento. |
|  | 2 | El sistema despliega el listado de planes existentes. |
|  | 3 | El usuario selecciona la opción "Crear nuevo plan". |
|  | 4 | El usuario selecciona la maquinaria, el umbral de uso que dispara el mantenimiento y una descripción de la intervención preventiva. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema valida los datos y registra el plan de mantenimiento. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El plan de mantenimiento queda registrado y activo para disparar el bloqueo preventivo de la maquinaria al alcanzar el umbral. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el umbral de uso es cero o negativo, el sistema detiene el guardado e indica el campo erróneo. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (configuración inicial y ajustes puntuales). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El umbral de uso se expresa en volumen procesado por la maquinaria. |  |


| CU-MANT-07 | Baja de Plan de Mantenimiento |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee dar de baja un plan de mantenimiento que ya no aplica. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. El plan existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planes de mantenimiento. |
|  | 2 | El sistema despliega el listado de planes existentes. |
|  | 3 | El usuario selecciona un plan y elige la opción "Dar de baja". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema procede con la baja del plan. |
|  | 7 | El sistema informa al usuario que la baja se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El plan de mantenimiento queda dado de baja y deja de disparar bloqueos preventivos. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el plan está vinculado a un mantenimiento pendiente de ejecución, el sistema bloquea la baja e informa al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-08 | Modificación de Plan de Mantenimiento |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee modificar una regla de mantenimiento preventivo existente. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. El plan existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planes de mantenimiento. |
|  | 2 | El sistema despliega el listado de planes existentes. |
|  | 3 | El usuario selecciona un plan y elige la opción "Modificar". |
|  | 4 | El sistema carga los datos del plan en el formulario para su edición. |
|  | 5 | El usuario modifica los campos deseados (umbral de uso o descripción). |
|  | 6 | El usuario confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al usuario que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | El plan de mantenimiento queda actualizado con los nuevos valores. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si el umbral de uso queda en cero o negativo, el sistema detiene el guardado e indica el campo erróneo. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-09 | Listado de Planes de Mantenimiento |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee visualizar el conjunto de planes de mantenimiento registrados en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planes de mantenimiento. |
|  | 2 | El sistema despliega el listado de planes, mostrando para cada uno su maquinaria, umbral de uso y descripción. |
|  |  |  |
| **Postcondición**  | El usuario visualiza el listado de planes de mantenimiento. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen planes registrados, el sistema muestra el mensaje "No hay planes de mantenimiento registrados". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-10 | Buscar Plan de Mantenimiento |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee localizar un plan de mantenimiento específico mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Supervisor de Producción. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planes de mantenimiento. |
|  | 2 | El sistema despliega el listado de planes y un campo de búsqueda. |
|  | 3 | El usuario ingresa un criterio de búsqueda (ej. maquinaria asociada). |
|  | 4 | El usuario ejecuta la búsqueda. |
|  | 5 | El sistema filtra los planes y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El usuario visualiza los planes que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen planes que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron planes para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos. |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-MANT-11 | Registrar Mantenimiento |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción registre la ejecución de una intervención de mantenimiento sobre una maquinaria. |  |
| **Precondición**  | La maquinaria se encuentra bloqueada por alcanzar su umbral de desgaste, o el mantenimiento se realiza de forma programada. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario selecciona una maquinaria bloqueada (o programada) y solicita "Registrar Mantenimiento". |
|  | 2 | El sistema muestra los datos de la maquinaria y el plan de mantenimiento aplicable. |
|  | 3 | El usuario ingresa los datos de la intervención: fecha, tipo de mantenimiento y observaciones. |
|  | 4 | El usuario confirma la operación. |
|  | 5 | El sistema registra la intervención y reinicia el desgaste acumulado de la maquinaria a cero. |
|  | 6 | El sistema desbloquea la maquinaria y la deja disponible para su asignación. |
|  | 7 | El sistema informa al usuario que el mantenimiento se registró con éxito. |
|  |  |  |
| **Postcondición**  | La maquinaria queda con su desgaste reiniciado y habilitada para volver a operar en las órdenes de producción. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si el mantenimiento se registra sobre una maquinaria que no está bloqueada, el sistema igualmente asienta la intervención como mantenimiento programado y reinicia el desgaste. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 1 segundo. |
| **Frecuencia**  | Baja (por cada intervención de mantenimiento). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El bloqueo preventivo es automático (proceso del sistema) al alcanzar el umbral; este CU registra la intervención que lo resuelve. |  |


