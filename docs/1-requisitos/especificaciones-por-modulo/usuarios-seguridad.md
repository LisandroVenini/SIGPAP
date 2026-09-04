# Módulo Usuarios y Seguridad (soporte)

## Operatoria

Módulo de soporte que materializa el requisito no funcional "Seguridad y control de Acceso". Garantiza la integridad, confidencialidad y trazabilidad de la información mediante un modelo de Control de Acceso Basado en Roles (RBAC), y centraliza la administración general del sistema.

## Rol del módulo

Es un módulo de **soporte/administración general**: no participa del flujo productivo de la fábrica, pero habilita y controla el acceso de todos los demás actores.

## Alcance (validado)

1. **Autenticación**: iniciar/cerrar sesión con validación de rol.
2. **Gestión de usuarios y roles**: alta, modificación, baja (lógica) y asignación de roles.
3. **Configuración de parámetros globales**: variables de la fábrica (turnos, feriados, métricas base, umbrales de negocio).

## Entidades del dominio

- **Usuario**: persona con acceso al sistema (nombre, email, contraseña).
- **Rol**: nivel de acceso que determina qué módulos y operaciones puede usar (Supervisor, Encargado de Stock, Encargado de Compras, Gerente, Administrador).
- **Parámetro global**: variable de configuración de la fábrica agrupada por categoría.

## Modelo de acceso (RBAC)

```
Usuario ──tiene──▶ Rol ──habilita──▶ Módulos y operaciones
```

Cada actor interactúa únicamente con los módulos pertinentes a su función. Los actores externos (proveedores) acceden mediante enlaces seguros temporales, sin cuenta de usuario.

## Decisiones de negocio (validadas)

1. **Login como caso de uso**: la autenticación se modela como CU (estaba implícita en las precondiciones de todos los demás CU).
2. **Módulo = administración general**: usuarios + roles + login + configuración de parámetros.

## Funcionalidades y pasos

### F1 — Iniciar/Cerrar sesión (nuevo — CU-USR-03)

1. El usuario ingresa email y contraseña.
2. El sistema valida las credenciales y carga el rol del usuario.
3. El sistema habilita los módulos correspondientes a su rol.
4. Excepción: credenciales inválidas → error de autenticación.
5. El usuario puede cerrar sesión, terminando su sesión activa.

### F2 — Gestionar usuarios y roles (CU-USR-01)

1. El Administrador da de alta un usuario: nombre, email, contraseña temporal y rol.
2. El sistema valida que el email no esté en uso y crea la cuenta.
3. Modificación: cambiar rol o forzar blanqueo de contraseña.
4. Baja: inhabilitación (baja lógica) conservando el historial de auditoría.

### F3 — Configurar parámetros del sistema (CU-USR-02)

1. El Administrador o Gerente accede a la configuración del sistema.
2. El sistema muestra los parámetros globales agrupados por categoría (Producción, Compras, Motor Predictivo).
3. El usuario modifica un parámetro y guarda.
4. El sistema valida el formato y aplica el cambio.
5. Excepción: valores incompatibles → rechaza indicando el campo erróneo.

## Cruces con otros módulos

- **Todos los módulos**: el RBAC controla el acceso de cada actor a su módulo.
- **Auditoría**: los cambios de rol y configuración se registran en el log de auditoría.
- **Análisis Predictivo**: parámetros del motor predictivo (umbrales de tolerancia) se configuran aquí.
- **Compras**: parámetros de licitación (ej. vigencia de solicitud de cotización) se configuran aquí.

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Reestructuración de ABM**: "Gestionar Usuarios y Roles" (CU-USR-01) se desglosa en Alta, Baja, Modificación, Listado y Buscar.
2. **Nomenclatura consistente**: nombres descriptivos y uniformes.
