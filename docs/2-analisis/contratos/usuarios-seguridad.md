# Contratos de Operación — Usuarios y Seguridad

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/2-analisis/DSS/usuarios-seguridad.md`
> **Plantilla:** `docs/2-analisis/contratos/_plantilla-contrato.md`

## Nota de alcance

Se documentan solo las operaciones del sistema con regla de negocio (altas, modificaciones y flujos críticos). Los ABM triviales (Baja de Usuario, Listado de Usuarios, Buscar Usuario — CU-USR-02, 04 y 05) no llevan contrato. El CU-USR-06 modela dos operaciones en un mismo diagrama (iniciar sesión y cerrar sesión), por lo que genera dos contratos.

## CU-USR-01 — Alta de Usuario

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaUsuario(nombre, email, claveTemporal, rol)` |
| **Responsabilidades** | Registrar un nuevo usuario en el sistema y asignarle un rol de acceso. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-USR-01 |
| **Notas** | El Modelo de Dominio denomina `claveAcceso` al atributo de contraseña; `claveTemporal` es el valor inicial que se asigna a `Usuario.claveAcceso`. El "Nivel de Acceso/Rol" del CU se modela con la asociación `Rol - Usuario`. |
| **Excepciones** | Si el `email` ingresado ya pertenece a otro `Usuario`, se bloquea el registro y se solicita un email diferente. |
| **Salida** | Mensaje de éxito (`confirmacionAlta()`) y listado de usuarios actualizado. |
| **Precondiciones** | El `Administrador` está autenticado. No existe un `Usuario` con el `email` ingresado. |
| **Postcondiciones** | Se creó una instancia de `Usuario` con `nombre` y `email` asignados, `claveAcceso` inicializado con `claveTemporal` y `estado` inicializado en "Activo". Se asoció el `Usuario` a un `Rol`. |

## CU-USR-03 — Modificación de Usuario

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarUsuario(email, rol, clave)` |
| **Responsabilidades** | Actualizar el rol de un usuario o forzar el blanqueo de su contraseña. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-USR-03 |
| **Notas** | `email` actúa como identificador y no se modifica en la secuencia normal (solo se cambian rol o contraseña). `clave` corresponde a `Usuario.claveAcceso` (blanqueo de contraseña). El cambio de rol modifica la asociación `Rol - Usuario`. |
| **Excepciones** | Si los datos modificados quedan incompletos, el sistema bloquea el guardado e informa al Administrador. |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe un `Usuario` con el `email` indicado. El `Administrador` está autenticado. |
| **Postcondiciones** | Se modificó `Usuario.claveAcceso` (si se forzó el blanqueo de contraseña). Se modificó la asociación `Rol - Usuario` (si cambió el rol). Se creó una instancia de `EventoDeAuditoria` asociada al `Usuario` (los cambios quedan registrados en auditoría). |

## CU-USR-06 — Iniciar Sesión

| Campo | Contenido |
| --- | --- |
| **Nombre** | `iniciarSesion(email, clave)` |
| **Responsabilidades** | Autenticar al usuario validando sus credenciales y cargar su rol para habilitar los módulos correspondientes. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-USR-06 |
| **Notas** | No existe un concepto `Sesion` en el Modelo de Dominio (ver hallazgo de trazabilidad): la sesión activa no es un concepto persistido. El control de acceso se resuelve con la asociación `Rol - Usuario` (RBAC). |
| **Excepciones** | Si las credenciales son inválidas, se muestra un error de autenticación y no se concede el acceso. Si el `Usuario` está inhabilitado (`Usuario.estado` = Inhabilitado), se bloquea el acceso y se informa que la cuenta fue desactivada. |
| **Salida** | `sesionIniciada(rol)` con el rol del usuario y los módulos habilitados, o error de autenticación. |
| **Precondiciones** | Existe un `Usuario` con el `email` indicado, en estado activo. |
| **Postcondiciones** | Sin cambios de estado en el Modelo de Dominio: la sesión activa no tiene concepto persistido. |

## CU-USR-06 — Cerrar Sesión

| Campo | Contenido |
| --- | --- |
| **Nombre** | `cerrarSesion()` |
| **Responsabilidades** | Finalizar la sesión activa del usuario, dejándolo desautenticado. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-USR-06 |
| **Notas** | Sin concepto `Sesion` en el Modelo de Dominio (ver hallazgo de trazabilidad); no hay datos persistidos asociados a la sesión activa. |
| **Excepciones** | — |
| **Salida** | `sesionCerrada()`. |
| **Precondiciones** | Existe una sesión activa del `Usuario`. |
| **Postcondiciones** | Sin cambios de estado en el Modelo de Dominio: la sesión activa no tiene concepto persistido. |

## CU-USR-07 — Configurar Parámetros del Sistema

| Campo | Contenido |
| --- | --- |
| **Nombre** | `configurarParametros(categoria, parametro, valor)` |
| **Responsabilidades** | Ajustar una variable de configuración global de la fábrica, agrupada por categoría. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-USR-07 |
| **Notas** | `categoria` corresponde a `ParametroGlobal.categoria` (Producción, Compras, Motor Predictivo); `parametro` a `ParametroGlobal.nombre`; `valor` a `ParametroGlobal.valor`. El ejemplo del CU (días estándar de vigencia de una `SolicitudDeCotizacion`) es un caso concreto de `ParametroGlobal`. |
| **Excepciones** | Si el `valor` ingresado tiene formato incompatible con el parámetro, se rechaza el cambio indicando el campo erróneo. |
| **Salida** | Mensaje de éxito (`confirmacionParametros()`) o error de formato. |
| **Precondiciones** | El `Administrador` está autenticado. Existe el `ParametroGlobal` que se desea modificar. |
| **Postcondiciones** | Se modificó `ParametroGlobal.valor`. |
