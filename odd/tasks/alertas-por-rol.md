# Alertas por rol

Objetivo: subsistema de alertas configurable por reglas, con destinatarios por rol, lectura por usuario y auto-resolución. Rama `feat/alertas-por-rol`.

## Specs

- **S1**: una **regla de alerta** define tipo, condiciones, severidad, roles destinatarios, mensaje y estado activo. El CRUD es de reglas; las alertas emitidas no se editan.
- **S2**: una regla puede apuntar a uno o varios roles; cada usuario ve las alertas de sus roles y el Administrador ve todas.
- **S3**: la lectura es por usuario (`alerta_lecturas`); marcar leída no afecta a otros usuarios del mismo rol.
- **S4**: una regla no duplica una condición abierta (dedupe por regla + referencia, índice único parcial); puede volver a emitir si se resuelve y reaparece.
- **S5**: las alertas de condición se resuelven cuando el vigilante deja de verlas; las de evento se archivan al leerse o a los 90 días.
- **S6**: el catálogo de tipos es cerrado y declara schema de condiciones, variables de plantilla, severidad y roles por defecto.
- **S7**: la gestión de reglas respeta el módulo: Admin/Gerente todo, cada encargado su módulo; el creador o Admin/Gerente puede borrar.
- **S8**: toda regla es libre (crear, editar, desactivar, borrar); el borrado es lógico para preservar el historial.

## Tasks

| ID | Specs | Estado |
|---|---|---|
| T1 | S1, S2, S3, S4 | hecho (kernel backend) |
| T2 | S6 | hecho (catálogo) |
| T3 | S1, S7, S8 | pendiente (CRUD + permisos) |
| T4 | S3, S5 | pendiente (bandeja + lectura) |
| T5 | S2, S4 | pendiente (emisores de eventos) |
| T6 | S5 | pendiente (vigilantes + scheduler) |
| T7 | S5 | pendiente (seeder + retención) |

## Log

- **L1**: el usuario pidió un módulo de alertas por rol; varias alertas pueden pertenecer a más de un rol.
- **L2**: decisiones: CRUD de reglas configurables (no alertas manuales), gestión por módulo, reglas totalmente libres (borrado lógico).
- **L3**: se separa regla (configuración) de alerta (instancia); el catálogo de tipos es cerrado y validado.
