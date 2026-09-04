# Casos de Uso — Compras

| CU-COM-01 | Gestionar Proveedores |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Compras necesite dar de alta, modificar, listar o inhabilitar a un proveedor. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Compras. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión de proveedores. |
|  | 2 | El sistema despliega el listado de proveedores existentes |
|  | 3 | El usuario selecciona la opción "Registrar nuevo proveedor". |
|  | 4 | El usuario ingresa los datos correspondientes: Razón Social, CUIT, Email y Teléfono. |
|  | 5 | El usuario confirma la operación de guardado. |
|  | 6 | El sistema valida que el CUIT no esté duplicado y que el formato del correo electrónico sea válido. |
|  | 7 | El sistema informa que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El proveedor queda habilitado para ser incluido en futuras Solicitudes de Cotización y Órdenes de Compra. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | **(Flujo de Modificación):** Si el usuario selecciona un proveedor y elige "Modificar", el sistema carga sus datos. Al confirmar (Paso 5), se actualiza el registro. |
|  |  |  |
|  |  |  |
|  | 3 | **(Flujo de Baja):** Si el usuario elige "Dar de Baja", el sistema inactiva al proveedor (baja lógica), impidiendo futuras comunicaciones pero manteniendo el historial intacto. |
|  | 5 | Si el sistema detecta que el CUIT o el Email ya existen en otro registro, bloquea el guardado e informa del conflicto al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-02 | Gestionar Solicitud de Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el usuario genere una solicitud de cotización para uno o varios artículos y lo distribuya a una lista de proveedores seleccionados. |  |
| **Precondición**  | Los artículos a cotizar existen en el catálogo y hay proveedores activos en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario ingresa a la gestión de Solicitudes de Cotización. |
|  | 2 | El sistema resalta un "Borrador de Solicitud" generado automáticamente por el motor predictivo. |
|  | 3 | El usuario selecciona el borrador para revisarlo. |
|  | 4 | El sistema muestra los artículos sugeridos, las cantidades y los proveedores preseleccionados. |
|  | 5 | El usuario hace clic en "Aprobar y Enviar Solicitud". |
|  | 6 | El sistema consolida la solicitud y genera los enlaces web únicos por cada proveedor. |
|  | 7 | El sistema envía los correos electrónicos automáticamente e informa del éxito al usuario. |
|  |  |  |
| **Postcondición**  | Las solicitudes son enviadas y el sistema queda a la espera de las respuestas de los proveedores. |  |
| **Excepciones** | **Paso**  | **Acción**  |
|  | 3 | **(Flujo Manual):** El usuario selecciona "Crear Solicitud de Cotización", ingresa los artículos, las cantidades, la fecha de vencimiento de la licitación, elige a los proveedores deseados y confirma el envío. |
|  | 5 | Si el usuario no está de acuerdo con el borrador predictivo, puede elegir "Modificar Borrador" (agregando/quitando ítems) o "Descartar", anulando el procedimiento. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Hasta 3 segundos |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Este es un pilar del DSS: El sistema trabaja por el usuario armando el borrador, y el usuario solo aplica su criterio para aprobar la solicitud. |  |


| CU-COM-03 | Gestionar Orden de Compra |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-02 Prevención Proactiva de Cuellos de Botella OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información de Compras IRQ-04 Información Analítica y Predictiva RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el usuario desee crear órdenes de compra manuales o visualizar el análisis multicriterio del sistema para aprobar la mejor cotización recibida, transformándola en una Orden de Compra oficial. |  |
| **Precondición**  | Existe una Solicitud de Cotización con respuestas cargadas por los proveedores. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario ingresa a la gestión de Órdenes de Compra y selecciona una Solicitud de Cotización cerrada. |
|  | 2 | El sistema presenta el "Informe Multicriterio", destacando visualmente la oferta ganadora según la fórmula matemática (relación precio / tiempo de entrega / ranking del proveedor). |
|  | 3 | El usuario revisa el informe y selecciona "Aprobar Sugerencia y Emitir Orden". |
|  | 4 | El sistema genera la Orden de Compra oficial para el proveedor ganador. |
|  | 5 | El sistema envía un correo electrónico al proveedor para que confirme la recepción de la orden. |
|  | 6 | El sistema actualiza el estado de la OC a "Emitida" e informa el éxito de la operación. |
|  |  |  |
|  |  |  |
| **Postcondición**  | La Orden de Compra entra en estado "Pendiente de Confirmación" por parte del proveedor. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 1 | **(Flujo Manual):** El usuario selecciona "Crear Orden de Compra", selecciona proveedor, artículos, precios pactados y emite la orden sin pasar por una licitación previa |
|  |  |  |
|  |  |  |
|  | 3 | **(Elección Manual):** Si el usuario decide no hacer caso a la sugerencia del sistema por motivos externos, selecciona manualmente a otro proveedor de la lista y emite la orden para este. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Hasta 3 segundos |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-04 | Enviar Cotización |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-03 Gestión de Abastecimiento y Licitaciones |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando un Proveedor Externo acceda a la plataforma para ofertar sus precios y tiempos. |  |
| **Precondición**  | El Proveedor ha recibido un correo electrónico con un Link válido y la solicitud de cotización sigue abierta. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Proveedor Externo hace clic en el enlace recibido en su correo. |
|  | 2 | El sistema valida el token de seguridad del enlace y despliega una pantalla web con la lista de insumos solicitados. |
|  | 3 | El Proveedor ingresa su Precio Unitario y el Tiempo Estimado de Entrega para cada artículo. |
|  | 4 | El Proveedor hace clic en "Enviar Cotización". |
|  | 5 | El sistema almacena los datos, los vincula a la solicitud original y cierra el token para evitar re-envíos. |
|  | 6 | El sistema muestra un mensaje de confirmación al proveedor y notifica internamente al Encargado de Compras. |
|  |  |  |
|  |  |  |
| **Postcondición**  | La oferta comercial del proveedor queda registrada para ser evaluada por el motor multicriterio del sistema. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si el enlace ya fue utilizado, o la fecha límite de la solicitud de cotización ya expiró, el sistema deniega el acceso y muestra un mensaje informando que la licitación está cerrada. |
|  |  |  |
|  |  |  |
|  | 4 | Si el proveedor intenta enviar el formulario dejando precios en blanco (o en valor 0\) para artículos marcados como obligatorios, el sistema le solicita que complete todos los campos antes de proceder. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 1 segundo |
| **Frecuencia**  | Alta |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-COM-05 | Confirmar orden de compra |  |
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
| **Postcondición**  | La Orden de Compra queda en estado “Confirmada” y entra formalmente en curso |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | Si el proveedor presiona "Rechazar Pedido" (ej. por falta de stock), el sistema cambia el estado a "Rechazada", anula el pedido y dispara una alerta urgente al Encargado de Compras. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 4 | 1 segundo |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


