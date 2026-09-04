# Requisitos No Funcionales

| NFR–01  | Seguridad y Control de Acceso |
| :---- | :---- |
| **Objetivos asociados**  | OBJ-01 Digitalización e Integración Operativa |
| **Requisitos asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |
| **Descripción**  | El sistema deberá implementar un esquema robusto de seguridad y control de acceso. Se utilizará un modelo de Control de Acceso Basado en Roles para garantizar que cada usuario interactúe únicamente con los módulos pertinentes a su función operativa o gerencial. Además, los accesos para actores externos (proveedores) se realizarán mediante enlaces seguros y temporales  |
|  |  |
| **Comentarios**  | Ninguno  |

| NFR–02 | Arquitectura Híbrida y Escalabilidad |
| :---- | :---- |
| **Objetivos asociados**  | OBJ–02 Prevención Proactiva de Cuellos de Botella OBJ-04 Soporte Gerencial mediante Simulación de Escenarios |
| **Requisitos asociados**  | RF-04 Análisis Predictivo y Monitoreo de Alertas RF-05 Simulación Gerencial de Escenarios |
| **Descripción**  | El sistema deberá asegurar su escalabilidad mediante una arquitectura híbrida que separe el monolito transaccional (desarrollado en Laravel) del microservicio especializado en Machine Learning (desarrollado en FastAPI). La comunicación entre ambos entornos deberá ser asíncrona mediante trabajos en segundo plano |
|  |  |
| **Comentarios**  | Ninguno  |

| NFR–03 | Usabilidad y Baja Fricción Operativa |
| :---- | :---- |
| **Objetivos asociados**  | OBJ–01 Digitalización e Integración Operativa |
| **Requisitos asociados**  | RF-01 Gestión de la Producción y Parte Diario |
| **Descripción**  | El sistema deberá garantizar una excelente usabilidad y experiencia de usuario, particularmente en las interfaces de recolección de datos en planta. Deberá implementar un mecanismo de captura de datos de baja fricción, minimizando la cantidad de interacciones (clics) requeridas para que el supervisor reporte avances sin interrumpir el flujo productivo. |
|  |  |
| **Comentarios**  | Ninguno  |

