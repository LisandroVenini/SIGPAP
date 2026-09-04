# Casos de Uso — Inventario

| CU-INV-01 | Gestionar Catálogo de Artículos |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario RF-01 Gestión de Inventario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Stock requiera dar de alta, modificar, listar o inhabilitar un artículo del catálogo de articulos. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Encargado de Stock. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema acceder a la gestión del catálogo de artículos. |
|  | 2 | El sistema despliega el listado de artículos existentes. |
|  | 3 | El usuario selecciona la opción de "Crear nuevo artículo". |
|  | 4 | El usuario ingresa los datos del artículo: Código SKU, Nombre, Tipo (Materia Prima, Insumo, Producto Terminado) y Unidad de Medida (ej. Kg, Unidad, Litros). |
|  | 5 | El usuario confirma la operación de guardado. |
|  | 6 | El sistema valida que el Código SKU sea único y registra el nuevo artículo con stock físico inicial en cero. |
|  | 7 | El sistema informa al usuario que el proceso ha terminado con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El artículo queda disponible en el sistema para recibir ingresos, ajustes o parametrizaciones. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | **(Flujo de Modificación):** Si el usuario selecciona un artículo de la lista y elige "Modificar", el sistema carga los datos. Tras confirmar (Paso 5), el sistema actualiza el registro sin alterar su stock. |
|  |  |  |
|  |  |  |
|  | 3 | **(Flujo de Baja):** Si el usuario selecciona "Dar de Baja", el sistema realiza una baja lógica (lo inactiva) para conservar el historial en las órdenes pasadas. Si el artículo aún tiene stock físico disponible, el sistema advierte de la situación y pide doble confirmación. |
|  | 5 | Si el Código SKU ingresado ya pertenece a otro artículo, el sistema cancela el guardado e informa el error al usuario. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 2 segundos |
| **Frecuencia**  | Baja (Alta inicial y mantenimientos puntuales). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-INV-02 | Registrar ingreso de materiales |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario IRQ-03 Información sobre Proveedores y Trazabilidad de Compras RF-01 Gestión de Inventario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Stock reciba físicamente mercancía en el depósito proveniente de un proveedor, impactando directamente en el stock real y en las métricas predictivas. |  |
| **Precondición**  | El/Los artículo existen en el catálogo y existe una Orden de Compra (OC) oficial emitida previamente. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema registrar un nuevo ingreso de mercancía. |
|  | 2 | El sistema despliega el formulario de recepción. |
|  | 3 | El usuario selecciona la OC correspondiente e ingresa los datos del remito físico (Número de remito, cantidad real recibida de cada artículo). |
|  | 4 | El usuario confirma la operación. |
|  | 5 | El sistema valida que las cantidades concuerden con la OC, incrementa el Stock Físico Real en depósito y actualiza el estado de la Orden de Compra a "Recibida". |
|  | 6 | El sistema calcula la diferencia entre la fecha de recepción actual y la fecha en que se emitió la OC para recalcular automáticamente la métrica estadística del tiempo de entrega real del proveedor. |
|  | 7 | El sistema informa al usuario que el stock ha sido actualizado con éxito. |
|  |  |  |
| **Postcondición**  | El stock físico del inventario se incrementa y se actualiza el historial estadístico del proveedor |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si la cantidad ingresada es menor a la esperada en la Orden de Compra vinculada, el sistema emite una alerta no restrictiva preguntando si se debe marcar el ingreso como "Entrega Parcial". |
|  |  |  |
|  |  |  |
|  | 5 | Si la cantidad ingresada excede lo solicitado en la OC, el sistema bloquea el procesamiento automático y exige una confirmación del usuario para asentar el excedente. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5,6 | Menor a 2 segundos |
| **Frecuencia**  | Media/Alta (Dependiendo del volumen de entregas semanales). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-INV-03 | Registrar Ajuste de Inventario |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario RF-01 Gestión de Inventario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Stock necesite realizar correcciones manuales sobre las existencias reales en el sistema debido a mermas o roturas |  |
| **Precondición**  | El artículo seleccionado existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita realizar un ajuste manual de inventario. |
|  | 2 | El sistema muestra un buscador de artículos. |
|  | 3 | El usuario selecciona el artículo, ingresa el "Nuevo Stock Observado" y selecciona un motivo (ej. Rotura, Auditoría, Merma). |
|  | 4 | El usuario confirma la operación. |
|  | 5 | El sistema calcula la diferencia (Positiva o Negativa), sobrescribe el stock físico real y guarda un registro inmutable del ajuste. |
|  | 6 | El sistema notifica el éxito del ajuste. |
|  |  |  |
|  |  |  |
| **Postcondición**  | Las existencias del sistema quedan alineadas con el stock real del depósito. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si el usuario intenta registrar un decremento manual cuya cantidad es mayor al Stock Físico Real disponible (lo que daría stock negativo), el sistema rechaza la operación, muestra un error y exige revisar el conteo. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 1 segundo |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-INV-04 | Parametrizar Umbrales de Stock |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-03 Sincronización Inteligente de Abastecimiento OBJ-05 Gestión de Inventario mediante Reservas Lógicas |  |
| **Requisitos**  **asociados**  | IRQ-01 Información sobre Artículos e Inventario RF-01 Gestión de Inventario |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Encargado de Stock defina o modifique los límites mínimos y máximos tolerables de un artículo en particular. |  |
| **Precondición**  | El artículo seleccionado se encuentra registrado en el catálogo. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Encargado de Stock selecciona un artículo específico del catálogo y solicita modificar sus umbrales de Stock. |
|  | 2 | El sistema muestra los umbrales de Stock Mínimo y Stock Máximo que se encuentran configurados actualmente en la ficha técnica del producto. |
|  | 3 | El usuario ingresa los nuevos valores deseados para ambos umbrales. |
|  | 4 | El usuario confirma la actualización. |
|  | 5 | El sistema valida los datos y guarda la configuración. |
|  | 6 | El sistema emite un mensaje de éxito. |
|  |  |  |
|  |  |  |
| **Postcondición**  | Los nuevos límites de stock quedan establecidos. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si el usuario ingresa un "Stock Mínimo" que es mayor al "Stock Máximo" (ej. Mínimo 100, Máximo 50), el sistema bloquea el guardado y exige corregir la incoherencia lógica. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | 1 segundo |
| **Frecuencia**  | Media |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


