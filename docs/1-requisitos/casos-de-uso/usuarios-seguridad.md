# Casos de Uso — Usuarios y Seguridad

| CU-USR-01 | Gestionar Usuarios y Roles |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador necesite dar de alta personal nuevo, modificar sus datos o asignarle los permisos correspondientes |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. |  |
| **Secuencia**  **normal**  | **Paso**  |  |
|  | 1 | El Administrador solicita acceder al gestor de usuarios. |
|  | 2 | El sistema presenta el listado de usuarios registrados. |
|  | 3 | El Administrador selecciona "Registrar nuevo usuario". |
|  | 4 | Ingresa los datos personales (Nombre, Email), define una contraseña temporal y selecciona el Nivel de Acceso/Rol. |
|  | 5 | El Administrador confirma el guardado. |
|  | 6 | El sistema valida que el email no esté en uso y crea la cuenta. |
|  | 7 | El sistema informa del éxito y actualiza la lista. |
|  |  |  |
| **Postcondición**  | El nuevo usuario queda habilitado para iniciar sesión y operar los módulos según su rol. |  |
| **Excepciones**  | **Paso**  |  |
|  | 3 | **(Flujo de Modificación):** Si selecciona un usuario existente y elige "Editar", puede cambiar su rol o forzar un blanqueo de contraseña. |
|  | 3 | **(Flujo de Baja):** Si elige "Inhabilitar Usuario", el sistema realiza una baja lógica para que el empleado no pueda volver a ingresar, pero manteniendo intacto su historial en la auditoría. |
|  |  |  |
|  |  |  |
|  | 5 | Si el correo ingresado ya pertenece a otro usuario, el sistema bloquea el registro y solicita un email diferente. |
| **Rendimiento**  | **Paso**  |  |
|  | 6 | Menos de 2 segundos |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |

| CU-USR-02 | Configurar Parámetros del Sistema |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando la dirección necesite ajustar las variables estáticas y reglas de negocio globales de la fábrica |  |
| **Precondición**  | El usuario ha iniciado sesión con rol de Administrador o Gerente. |  |
| **Secuencia**  **normal**  | **Paso**  |  |
|  | 1 | El usuario ingresa a la vista de "Configuración del Sistema". |
|  | 2 | El sistema muestra los parámetros globales agrupados por categoría (Producción, Compras, Motor Predictivo). |
|  | 3 | El usuario modifica un parámetro (ej. Cambiar los días estándar de vigencia de una Solicitud de Cotización de 7 a 5 días) y presiona "Guardar Cambios". |
|  | 4 | El sistema valida el formato de los datos ingresados. |
|  | 5 | El sistema sobrescribe los parámetros y notifica que los cambios se aplicaron correctamente. |
|  |  |  |
|  |  |  |
|  |  |  |
| **Postcondición**  | El sistema adopta las nuevas variables de configuración para su funcionamiento. |  |
| **Excepciones**  | **Paso**  |  |
|  | 4 | Si el usuario ingresa valores incompatibles, el sistema rechaza el cambio indicando el campo erróneo. |
|  |  |  |
|  |  |  |
| **Rendimiento**  | **Paso**  |  |
|  | 5 | Menos de 1 segundo |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


