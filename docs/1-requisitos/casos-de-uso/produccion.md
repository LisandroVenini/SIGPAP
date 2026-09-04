# Casos de Uso — Producción

| CU-PROD-01 | Gestionar Planificación de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor de Producción desee registrar, modificar, listar o dar de baja una planificación marco que agrupará múltiples órdenes de producción. |  |
| **Precondición**  | El usuario ha iniciado sesión con rol de Supervisor de Producción |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de planificaciones. |
|  | 2 | El sistema despliega el listado de planificaciones existentes. |
|  | 3 | El usuario selecciona la opción de "Crear nueva planificación". |
|  | 4 | El usuario selecciona crear una nueva planificación e ingresa los datos (Nombre de campaña, Fechas estimadas de inicio y fin, Observaciones). |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema valida los datos y registra la nueva planificación en la base de datos. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | La planificación de producción queda registrada en el sistema y disponible para vincularle futuras órdenes de producción |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | **(Flujo de Modificación):** Si el usuario selecciona una planificación del listado y elige "Modificar", el sistema carga los datos en el formulario para su edición. Tras confirmar (Paso 5), el sistema actualiza el registro. |
|  |  |  |
|  |  |  |
|  | 3 | **(Flujo de Baja):** Si el usuario selecciona "Eliminar", el sistema verifica que la planificación no contenga Órdenes de Producción asociadas. Si está vacía, procede con la baja y finaliza el caso de uso. |
|  | 5 | Si el sistema detecta que la Fecha de Fin es anterior a la Fecha de Inicio, emite una advertencia al usuario y detiene el guardado hasta que se corrija. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 2 segundos. |
| **Frecuencia**  | Media (1 a 2 veces por mes). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-02 | Gestionar Órdenes de Producción |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el usuario requiera dar de alta, modificar o anular Órdenes de Producción (OP). |  |
| **Precondición**  | Existe al menos una Planificación activa. El artículo a fabricar existe en el catálogo de productos terminados. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de Órdenes de Producción. |
|  | 2 | El sistema muestra el listado de las OP actuales. |
|  | 3 | El usuario selecciona la opción "Crear nueva Orden". |
|  | 4 | El usuario selecciona el Producto, ingresa la Cantidad requerida, la Fecha de entrega esperada y vincula la orden a una Planificación. |
|  | 5 | El usuario confirma la operación. |
|  | 6 | El sistema genera la OP, le asigna el estado "Pendiente" y carga internamente la hoja de ruta estándar para ese producto. |
|  | 7 | El sistema informa al usuario el éxito de la operación. |
|  |  |  |
| **Postcondición**  | La Orden de Producción es creada en estado "Pendiente" y los materiales necesarios quedan "Comprometidos" en el inventario. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | **(Flujo de Modificación):** Si el usuario elige "Modificar" una orden "Pendiente", el sistema permite alterar cantidades o fechas. Si la OP ya está en ejecución, el sistema bloquea la modificación. |
|  |  |  |
|  |  |  |
|  | 3 | **(Flujo de Anulación):** Si el usuario elige "Anular", el sistema solicita motivo, cambia el estado a "Anulada" y libera las reservas lógicas de inventario asociadas. |
|  | 5 | Si el stock físico no alcanza para cubrir la reserva lógica, el sistema alerta al usuario del quiebre inminente y deja la OP en estado "Pendiente por Insumos", disparando una alerta para el módulo de compras. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 2 segundos. |
| **Frecuencia**  | Alta (Múltiples veces por semana). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-03 | Asignar Recursos a Orden |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-05 Información sobre Maquinarias y Desgaste RF-06 Gestión de Mantenimiento Preventivo de Maquinarias |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor necesite asignar el personal y la maquinaria a una Orden de Producción específica antes de iniciarla. |  |
| **Precondición**  | La Orden de Producción se encuentra en estado "Pendiente" o “Pendiente por Insumos”. Existen operarios y máquinas activos en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario selecciona una OP Pendiente y solicita la opción "Asignar Recursos". |
|  | 2 | El sistema despliega las etapas de la hoja de ruta de la OP seleccionada. |
|  | 3 | El usuario selecciona de las listas desplegables a los operarios responsables y la maquinaria para las etapas. |
|  | 4 | El usuario confirma la asignación. |
|  | 5 | El sistema valida la disponibilidad de los recursos seleccionados. |
|  | 6 | El sistema registra la asignación y cambia el estado de la OP a "Lista para iniciar" (o "Asignada"). |
|  | 7 | El sistema confirma el éxito de la operación. |
|  |  |  |
| **Postcondición**  | La OP queda habilitada para que el piso de planta comience a reportar el avance en los Partes Diarios. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si una maquinaria se encuentra bloqueada por Mantenimiento,el sistema la muestra deshabilitada y no permite su selección. |
|  |  |  |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 1 segundo |
| **Frecuencia**  | Diaria |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-04 | Cargar Parte Diario |  |
| :---- | ----- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-02 Prevención Proactiva de Cuellos de Botella |  |
| **Requisitos**  **asociados**  | IRQ-01Información sobre Artículos e Inventario IRQ-02 Información sobre Órdenes de Producción y Avance RF-01 Gestión de la Producción y Parte Diario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Supervisor registre las unidades fabricadas al finalizar su turno, delegando procesos automatizados en el sistema central y en el microservicio. |  |
| **Precondición**  | Existe al menos una Orden de Producción en estado "En Curso". |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Supervisor selecciona en su terminal una OP activa y solicita "Cargar Parte Diario". |
|  | 2 | El sistema muestra la etapa actual de la orden y la cantidad objetivo. |
|  | 3 | El Supervisor ingresa la cantidad de unidades completadas en su turno de trabajo y observaciones (opcional). |
|  | 4 | El Supervisor pulsa en confirmar. |
|  | 5 | El sistema asienta el avance, acumulando las unidades terminadas para la etapa en cuestión. |
|  | 6 | El sistema incrementa el desgaste acumulado de la maquinaria utilizada. |
|  | 7 | El sistema envía una petición asíncrona al Microservicio Predictivo para evaluar la Tasa de Rendimiento Real |
|  | 8 | El sistema notifica al usuario que el parte se registró exitosamente. |
|  |  |  |
| **Postcondición**  | El progreso de la OP se actualiza, el stock físico se descuenta, se incrementa las horas de uso de cada maquinaria y el motor predictivo recibe nueva información para evaluar desviaciones. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si la cantidad ingresada por el supervisor supera la cantidad total requerida por la OP, el sistema despliega una alerta de validación requiriendo confirmación doble antes de guardar. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 4,5,6 | Menor a 2 segundos para la respuesta al usuario |
| **Frecuencia**  | Alta |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-PROD-05 | Registrar Control de Calidad |  |
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


