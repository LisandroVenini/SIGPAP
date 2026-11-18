# Robustez backend

Objetivo: cerrar los hallazgos ALTO y MEDIO de backend de la cuarta auditoría. Rama `fix/robustez-backend`. Runner: `php artisan test --compact`.

## Specs

- **S1**: el email de usuario se normaliza a minúsculas al crear, actualizar y autenticar; dos emails que solo difieren en mayúsculas no pueden coexistir; el índice único funcional `lower(email)` existe en la base.
- **S2**: las claves foráneas y las columnas de filtro usadas por la aplicación tienen índice en PostgreSQL.
- **S3**: todo campo numérico que se persiste en `decimal(12,2)` declara `max:9999999999.99`; valores mayores devuelven error de validación y no 500.
- **S4**: un fallo de SMTP al invitar proveedores o enviar una OC se registra en el log y no revierte la operación ya confirmada.
- **S5**: un CUIT duplicado se rechaza en el Form Request y una violación de unicidad concurrente se traduce a error de validación.
- **S6**: el umbral de intentos fallidos de login y la expiración de sesión por inactividad se leen de `ParametroGlobal` (`seguridad_intentos_fallidos`, `seguridad_expiracion_sesion_min`) con defaults 5 y 30.

## Tasks

| ID | Specs | Estado |
|---|---|---|
| T1 | S1 | hecho |
| T2 | S2 | hecho |
| T3 | S3 | hecho |
| T4 | S4 | hecho |
| T5 | S5 | hecho |
| T6 | S6 | hecho |

## Log

- **L1**: el usuario pidió empezar por robustez backend y luego completar el resto de los hallazgos.
- **L2**: se incluyeron los dos ALTO de backend (email y FKs) por ser del mismo dominio y no requerir decisiones de producto.
- **L3**: el scheduler mínimo se difiere a la tanda de Alertas: sin emisores de alerta no tiene consumidor real.
- **L4**: `diffInMinutes` de Carbon 3 devuelve valor con signo; la expiración se compara con `ultima_actividad + limite <= now()`.
