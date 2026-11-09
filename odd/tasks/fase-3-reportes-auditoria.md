# Fase 3 — Reportes y Auditoría

Objetivo: implementar CU-AUD-01 (registro de auditoría) y CU-REP-01 (reportes exportables).
Rama: `feat/reportes-auditoria` (a crear desde `main`). Runner: PHPUnit, Vitest, gga.

## Specs

- S1: Registro de auditoría (CU-AUD-01): grilla cronológica con fecha, hora, IP, usuario y acción; filtros por usuario, acción, IP y rango de fechas; detalle del estado anterior y nuevo; acceso para Administrador y Gerente; mensaje "No se encontraron registros para los criterios especificados".
- S2: Reportes (CU-REP-01): generación por tipo (Producción, Inventario, Compras), rango de fechas y parámetros, con exportación en el formato elegido y advertencia "Sin datos para generar". Pendiente de decidir el formato y sus dependencias.

## Tasks

- [ ] R1 — Registro de auditoría: permiso, Service, Query, Request, Controller, ruta, pantalla y tests.
- [ ] R2 — Reportes: tipos, consolidación, exportación y tests.

## Log

- L1: "perfecto, vamos con reportes, el modulo de analisis predictivo vamos a dejar por ultimo por ser el modulo que implementa modelos de ML"
