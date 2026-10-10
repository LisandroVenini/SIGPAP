# Requisitos Funcionales

Debe tener una lista de los requisitos funcionales, expresado en forma tradicional o mediante casos de usos 

| RF-01 | Gestión de la Producción y Parte Diario |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-01 Digitalización e Integración Operativa OBJ-02 Prevención Proactiva de Cuellos de Botella** |  |
| **Requisitos asociados** | CU-PROD-01…CU-PROD-28 CU-MANT-01…CU-MANT-11 IRQ-02 Información sobre Órdenes de Producción y Avance IRQ-05 Información sobre Maquinarias y Desgaste Operativo |  |
| **Descripción**  | El sistema deberá permitir la planificación de campañas, la creación de órdenes de producción y la asignación de recursos (personal y maquinaria). A nivel operativo, deberá facilitar una interfaz ágil para que el supervisor cargue el "Parte Diario", registrando los avances reales que luego servirán para recalcular las desviaciones de tiempo de producción. |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Es el núcleo transaccional del sistema, indispensable para generar la información que necesita el algoritmo predictivo. |  |

| RF-02 | Gestión de Inventario |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-01 Digitalización e Integración Operativa OBJ-05 Gestión de Inventario mediante Reservas Lógicas** |  |
| **Requisitos asociados** | CU-INV-01…CU-INV-08 IRQ-01 Información sobre Artículos e Inventario |  |
| **Descripción**  | El sistema deberá gestionar el catálogo de artículos y el ingreso de materiales, implementando estrictamente un modelo dual de stock. Debe calcular en tiempo real la diferencia entre el stock físico (disponible en depósito) y el stock comprometido (reservado para órdenes en curso). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  |  |  |

| RF-03 | Gestión de Abastecimiento y Licitaciones |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-01 Digitalización e Integración Operativa OBJ-03 Sincronización Inteligente de Abastecimiento** |  |
| **Requisitos asociados** | CU-COM-01…CU-COM-20 IRQ-03 Información sobre Proveedores y Trazabilidad de Compras |  |
| **Descripción**  | El sistema deberá soportar el ciclo de vida completo de compras, permitiendo gestionar solicitudes de cotización y enviarlas a los proveedores mediante correos electrónicos. Deberá permitir al proveedor externo ingresar sus ofertas temporalmente en la plataforma para que la gerencia pueda evaluar (junto con el motor predictivo) y adjudicar la Orden de Compra oficial. |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  |  |  |

| RF-04 | Análisis Predictivo y Monitoreo de Alertas |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-02 Prevención Proactiva de Cuellos de Botella OBJ-03 Sincronización Inteligente de Abastecimiento** |  |
| **Requisitos asociados** | CU-PRED-01…CU-PRED-05 IRQ-04 Información Analítica, Predictiva y de Simulación |  |
| **Descripción**  | El sistema principal deberá delegar asíncronamente al componente predictivo (Motor ML) el procesamiento matemático de la Tasa de Rendimiento Real y el consumo logístico. El sistema deberá recibir estos recálculos y presentarlos a la gerencia a través de Tableros y Alertas, indicando posibles cuellos de botella o sugiriendo la anticipación de compras. |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  |  |  |

| RF-05 | Simulación Gerencial de Escenarios |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-04 Soporte Gerencial mediante Simulación de Escenarios** |  |
| **Requisitos asociados** | CU-PRED-04 IRQ-04 Información Analítica, Predictiva y de Simulación |  |
| **Descripción**  | El sistema deberá proveer un entorno seguro donde un usuario gerencial pueda ingresar los parámetros de un pedido urgente. El sistema proyectará el impacto operativo, listando qué órdenes preexistentes sufrirían retrasos y evaluando la viabilidad de la inserción sin alterar los datos productivos reales. |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  |  |  |

| RF-06 | Gestión de Mantenimiento Preventivo de Maquinarias |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-01 Digitalización e Integración Operativa** |  |
| **Requisitos asociados** | CU-MANT-01…CU-MANT-11 IRQ-05 Información sobre Maquinarias y Desgaste Operativo |  |
| **Descripción**  | El sistema deberá gestionar las maquinarias y permitir la configuración de planes de mantenimiento basados en el uso (volumen procesado). Deberá calcular el desgaste en base a los partes diarios cerrados y bloquear preventivamente la asignación de máquinas que hayan superado sus umbrales operativos, hasta que se registre el mantenimiento. |  |
| **Estabilidad**  | Media |  |
| **Comentarios**  |  |  |

| RF-07 | Seguridad, Accesos y Auditoría del Sistema |  |
| :---- | :---- | ----- |
| **Objetivos asociados**  | **OBJ-01 Digitalización e Integración Operativa** |  |
| **Requisitos asociados** | CU-USR-01…CU-USR-07 CU-AUD-01 CU-REP-01 |  |
| **Descripción**  | El sistema deberá garantizar un acceso seguro mediante autenticación y un modelo de Control de Acceso Basado en Roles (RBAC), controlando la visibilidad de los módulos. Adicionalmente, deberá contar con registros de auditoría y generación de reportes operativos y gerenciales para asegurar la trazabilidad de todos los actores. |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Fundamental para asegurar la integridad de la base de datos y rastrear tanto las decisiones humanas como las acciones del componente predictivo. |  |

