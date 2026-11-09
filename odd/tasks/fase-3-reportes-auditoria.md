# Fase 3 — Reportes y Auditoría

Objetivo: implementar CU-AUD-01 (registro de auditoría) y CU-REP-01 (reportes exportables).
Rama: `feat/reportes-auditoria` (a crear desde `main`). Runner: PHPUnit, Vitest, gga.

## Specs

- S1: Registro de auditoría (CU-AUD-01): grilla cronológica con fecha, hora, IP, usuario y acción; filtros por usuario, acción, IP y rango de fechas; detalle del estado anterior y nuevo; acceso para Administrador y Gerente; mensaje "No se encontraron registros para los criterios especificados".
- S2: Reportes (CU-REP-01): tipos Producción (órdenes por fecha de entrega), Inventario (movimientos de stock por fecha) y Compras (órdenes por fecha de emisión); rango de fechas y filtro de estado en Producción; vista previa en pantalla con total de filas; exportación PDF (`barryvdh/laravel-dompdf`) y XLSX (`openspout/openspout`); advertencia "Sin datos para generar"; cada tipo requiere el permiso de su área.

## Tasks

- [x] R1 — Registro de auditoría. Commit `a218a12`.
- [ ] R2 — Reportes: tipos, consolidación, exportación y tests.

## Log

- L1: "perfecto, vamos con reportes, el modulo de analisis predictivo vamos a dejar por ultimo por ser el modulo que implementa modelos de ML"
- L2: Dependencias de exportación: `maatwebsite/excel` resolvió a v1.1.5 (Laravel 4) y `phpoffice/phpspreadsheet` exige `ext-gd` que el entorno no tiene. Se instaló `openspout/openspout` v5.3 (XLSX sin gd) y `barryvdh/laravel-dompdf` v3.1. Smoke test de ambos: OK.
