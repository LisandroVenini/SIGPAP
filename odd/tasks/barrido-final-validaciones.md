# Barrido final de validaciones

Objetivo: cerrar los 16 hallazgos del último barrido transversal (A1, M1–M6, B1–B9). Rama `feat/validaciones-fechas`. Runner: PHPUnit (`php artisan test --compact`) + Vitest (`npm run test:js`).

## Specs

- **S1 (A1)**: un artículo con stock o historial no puede cambiar de unidad de medida; sin historial sí puede.
- **S2 (M1)**: un ajuste de stock no puede dejar el stock físico por debajo de lo reservado.
- **S3 (M2)**: el token del portal de órdenes expira a los 30 días de la emisión (confirmar/rechazar devuelve 404 después).
- **S4 (M3)**: la cantidad rechazada acumulada de todos los controles de calidad no puede superar el conformado base.
- **S5 (M4)**: el cronograma proyecta una orden Pospuesta desde su `fecha_prevista_inicio`, no desde hoy.
- **S6 (M5)**: los drawers de Órdenes de Producción (nueva, modificar, anular, asignar) muestran los errores de validación; iniciar y reanudar no permiten doble envío y muestran sus errores.
- **S7 (M6)**: una recepción con faltantes no cierra la OC: queda Confirmada con `entrega_parcial = true` y sigue recepcionable para el remanente. El cierre explícito con faltantes sigue disponible y registra desempeño e incidencias.
- **S8 (B1)**: actualizar un parámetro global inexistente devuelve error de validación, no 500.
- **S9 (B2)**: se elimina `factor_conversion` de unidades de medida (dato sin uso ni cálculo asociado).
- **S10 (B3)**: la tabla de auditoría rechaza UPDATE y DELETE a nivel de base de datos.
- **S11 (B4)**: la exportación de reportes rechaza más de 50.000 filas con un mensaje claro.
- **S12 (B5)**: las acciones destructivas con confirmación no se pueden disparar dos veces mientras están en vuelo.
- **S13 (B6)**: el throttle del portal público está cubierto por un test (429 tras 20 peticiones).
- **S14 (B7)**: un parámetro global inválido que cae a su default deja un warning en el log.
- **S15 (B8)**: las ediciones inline de Hoja de Ruta muestran sus errores de validación.
- **S16 (B9)**: `fecha_entrega` puede superar `fecha_fin` de la planificación: es una decisión de dominio documentada, sin cambio de código.

## Tasks

| ID | Specs | Ruta | Estado |
|---|---|---|---|
| T1 | S1 | inline | pendiente |
| T2 | S2 | inline | pendiente |
| T3 | S3 | inline | pendiente |
| T4 | S4 | inline | pendiente |
| T5 | S5 | inline | pendiente |
| T6 | S6 | inline | pendiente |
| T7 | S7 | inline | pendiente |
| T8 | S8 | inline | pendiente |
| T9 | S9 | inline | pendiente |
| T10 | S10 | inline | pendiente |
| T11 | S11 | inline | pendiente |
| T12 | S12 | inline | pendiente |
| T13 | S13 | inline | pendiente |
| T14 | S14 | inline | pendiente |
| T15 | S15 | inline | pendiente |
| T16 | S16 | — | decisión documentada |

## Log

- **L1**: "perfecto, vamos con A1, m1, ..., M6, y los 9 hallazgos de bajo riesgo".
- **L2**: decisiones tomadas: M2 usa una ventana fija de 30 días; M6 no cierra con faltantes salvo pedido explícito del usuario (checkbox "cerrar recepción"); B2 elimina el campo en vez de simular una conversión inexistente; B3 usa un trigger en PostgreSQL; B9 no cambia código (el período de planificación no acota la entrega).
