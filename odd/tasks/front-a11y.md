# Frontend y accesibilidad

Objetivo: cerrar los hallazgos F1–F14 de la cuarta auditoría. Rama `fix/robustez-backend` (continúa la misma rama). Runner: `npx vitest run`.

## Specs

- **S1**: todo panel lateral se anuncia como diálogo modal, toma foco al abrir, se cierra con Escape y atrapa Tab.
- **S2**: los inputs de filas repetidas tienen nombre accesible; el botón de reordenar del Gantt también.
- **S3**: las acciones inline de Hoja de Ruta no se disparan dos veces; los submits deshabilitan mientras procesan.
- **S4**: Reportes usa un select de estado, muestra errores del backend y feedback de carga.
- **S5**: los empty states distinguen "sin datos" de "sin resultados para el filtro".
- **S6**: la paginación marca la página actual y no navega en los extremos.
- **S7**: autocompletado de contraseña nueva, teclados numéricos para CUIT/teléfono, cantidades con formato local y límites nativos donde el backend los exige.

## Tasks

| ID | Specs | Estado |
|---|---|---|
| T1 | S1 | hecho |
| T2 | S2, S3 | hecho |
| T3 | S4, S5 | hecho |
| T4 | S6 | hecho |
| T5 | S7 | hecho |

## Log

- **L1**: el usuario pidió continuar con frontend y accesibilidad tras cerrar robustez backend.
- **L2**: S1 se resolvió con la directiva `v-dialog` (role, aria-modal, nombre desde el heading, foco inicial, Escape y focus trap) aplicada a los 18 paneles, en lugar de reescribir cada drawer.
- **L3**: el filtro de estado de Reportes pasó de texto libre a select con los estados reales de producción.
