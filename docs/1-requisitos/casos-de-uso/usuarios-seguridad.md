# Casos de Uso — Usuarios y Seguridad

| CU-USR-01 | Alta de Usuario |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador desee registrar un nuevo usuario en el sistema y asignarle un rol. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Administrador solicita al sistema acceder al gestor de usuarios. |
|  | 2 | El sistema presenta el listado de usuarios registrados. |
|  | 3 | El Administrador selecciona "Registrar nuevo usuario". |
|  | 4 | El Administrador ingresa los datos personales (Nombre, Email), define una contraseña temporal y selecciona el Nivel de Acceso/Rol. |
|  | 5 | El Administrador confirma el guardado. |
|  | 6 | El sistema valida que el email no esté en uso y crea la cuenta. |
|  | 7 | El sistema informa del éxito y actualiza la lista. |
|  |  |  |
| **Postcondición**  | El nuevo usuario queda habilitado para iniciar sesión y operar los módulos según su rol. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el correo ingresado ya pertenece a otro usuario, el sistema bloquea el registro y solicita un email diferente. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 2 segundos |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-USR-02 | Baja de Usuario |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador desee inhabilitar a un usuario del sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. El usuario a inhabilitar existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Administrador solicita al sistema acceder al gestor de usuarios. |
|  | 2 | El sistema presenta el listado de usuarios registrados. |
|  | 3 | El Administrador selecciona un usuario y elige la opción "Inhabilitar Usuario". |
|  | 4 | El sistema solicita la confirmación de la baja. |
|  | 5 | El Administrador confirma la operación. |
|  | 6 | El sistema realiza una baja lógica: el usuario ya no puede iniciar sesión, pero su historial se mantiene intacto en la auditoría. |
|  | 7 | El sistema informa al Administrador que la inhabilitación se completó con éxito y actualiza el listado. |
|  |  |  |
| **Postcondición**  | El usuario queda inhabilitado y no puede volver a iniciar sesión, conservando su historial de auditoría. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 6 | Si el Administrador intenta inhabilitarse a sí mismo, el sistema bloquea la operación e informa que no es posible. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 6 | Menos de 1 segundo |
| **Frecuencia**  | Baja (esporádica). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | La baja es lógica para conservar la trazabilidad en auditoría. |  |


| CU-USR-03 | Modificación de Usuario |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador desee modificar el rol de un usuario o forzar un blanqueo de contraseña. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. El usuario a modificar existe en el sistema. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Administrador solicita al sistema acceder al gestor de usuarios. |
|  | 2 | El sistema presenta el listado de usuarios registrados. |
|  | 3 | El Administrador selecciona un usuario y elige la opción "Editar". |
|  | 4 | El sistema carga los datos del usuario en el formulario para su edición. |
|  | 5 | El Administrador modifica el rol del usuario o fuerza un blanqueo de contraseña. |
|  | 6 | El Administrador confirma la operación. |
|  | 7 | El sistema valida los datos y actualiza el registro. |
|  | 8 | El sistema informa al Administrador que la modificación se completó con éxito. |
|  |  |  |
| **Postcondición**  | El usuario queda actualizado con su nuevo rol o contraseña. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 7 | Si el nuevo email ingresado ya pertenece a otro usuario, el sistema bloquea el guardado y solicita uno diferente. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 7 | Menos de 2 segundos |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Los cambios de rol y contraseña quedan registrados en la auditoría. |  |


| CU-USR-04 | Listado de Usuarios |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador desee visualizar el conjunto de usuarios registrados en el sistema. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Administrador solicita al sistema acceder al gestor de usuarios. |
|  | 2 | El sistema presenta el listado de usuarios, mostrando para cada uno su nombre, email, rol y estado (activo/inhabilitado). |
|  |  |  |
| **Postcondición**  | El Administrador visualiza el listado de usuarios del sistema. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 2 | Si no existen usuarios registrados, el sistema muestra el mensaje "No hay usuarios registrados". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 2 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-USR-05 | Buscar Usuario |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador desee localizar un usuario específico mediante criterios de búsqueda. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El Administrador solicita al sistema acceder al gestor de usuarios. |
|  | 2 | El sistema presenta el listado de usuarios y un campo de búsqueda. |
|  | 3 | El Administrador ingresa un criterio de búsqueda (ej. nombre, email o rol). |
|  | 4 | El Administrador ejecuta la búsqueda. |
|  | 5 | El sistema filtra los usuarios y muestra los resultados coincidentes. |
|  |  |  |
| **Postcondición**  | El Administrador visualiza los usuarios que coinciden con el criterio ingresado. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 5 | Si no existen usuarios que coincidan con el criterio, el sistema muestra el mensaje "No se encontraron usuarios para el criterio especificado". |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 2 segundos |
| **Frecuencia**  | Media (consultas periódicas). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |


| CU-USR-06 | Iniciar/Cerrar Sesión |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando un usuario del sistema inicie sesión con sus credenciales y, al terminar, cierre su sesión. |  |
| **Precondición**  | El usuario existe en el sistema y se encuentra en estado activo. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario solicita al sistema iniciar sesión. |
|  | 2 | El usuario ingresa su email y contraseña. |
|  | 3 | El sistema valida las credenciales y carga el rol del usuario. |
|  | 4 | El sistema habilita los módulos correspondientes a su rol y muestra la pantalla principal. |
|  | 5 | Al finalizar, el usuario solicita cerrar sesión. |
|  | 6 | El sistema finaliza la sesión activa del usuario. |
|  |  |  |
| **Postcondición**  | El usuario queda autenticado (al iniciar) y desautenticado (al cerrar), con acceso limitado según su rol. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 3 | Si las credenciales son inválidas, el sistema muestra un error de autenticación y no concede el acceso. |
|  |  |  |
|  |  |  |
|  | 3 | Si el usuario está inhabilitado, el sistema bloquea el acceso y muestra un mensaje informando que la cuenta fue desactivada. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 3 | Menos de 1 segundo |
| **Frecuencia**  | Alta (varias veces por día). |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | El control de acceso se basa en RBAC: cada rol habilita únicamente los módulos pertinentes a su función. |  |


| CU-USR-07 | Configurar Parámetros del Sistema |  |
| :---- | :---- | :---- |
| **Objetivos**  **asociados**  | OBJ-01 Digitalización e Integración Operativa |  |
| **Requisitos**  **asociados**  | RF-07 Seguridad, Accesos y Auditoría del Sistema |  |
| **Descripción**  | El sistema deberá comportarse tal como se describe en el siguiente caso de uso cuando el Administrador necesite ajustar las variables estáticas y reglas de negocio globales de la fábrica. |  |
| **Precondición**  | El usuario ha iniciado sesión con el rol de Administrador. |  |
| **Secuencia**  **normal**  | **Paso**  | **Acción**  |
|  | 1 | El usuario ingresa a la vista de "Configuración del Sistema". |
|  | 2 | El sistema muestra los parámetros globales agrupados por categoría (Producción, Compras, Motor Predictivo). |
|  | 3 | El usuario modifica un parámetro (ej. cambiar los días estándar de vigencia de una Solicitud de Cotización de 7 a 5 días) y presiona "Guardar Cambios". |
|  | 4 | El sistema valida el formato de los datos ingresados. |
|  | 5 | El sistema sobrescribe los parámetros y notifica que los cambios se aplicaron correctamente. |
|  |  |  |
| **Postcondición**  | El sistema adopta las nuevas variables de configuración para su funcionamiento. |  |
| **Excepciones**  | **Paso**  | **Acción**  |
|  | 4 | Si el usuario ingresa valores incompatibles, el sistema rechaza el cambio indicando el campo erróneo. |
| **Rendimiento**  | **Paso**  | **Cota de tiempo**  |
|  | 5 | Menos de 1 segundo |
| **Frecuencia**  | Baja |  |
| **Estabilidad**  | Alta |  |
| **Comentarios**  | Ninguno |  |
