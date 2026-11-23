# Documentación SIGPAP

Sistema de Gestión de Producción y Análisis Predictivo (DSS) para la empresa "Confort y Muebles".

Documentación del trabajo final organizada por fases del proceso unificado (UP). Cada carpeta agrupa un artefacto; cada archivo es una unidad consultable de forma independiente para no releer documentos completos.

## Estructura

```
docs/
├── 0-propuesta/                  ← Documento vinculante (punto de partida)
├── 1-requisitos/                 ← Requisitos del sistema + casos de uso
├── 2-analisis/                   ← Modelo de dominio, DSS, contratos de operación
├── 3-diseno/                     ← Arquitectura, contratos entre módulos, despliegue
└── 4-implementacion/             ← base de datos y plan de pruebas
```

## 0. Propuesta

| Archivo | Contenido |
| --- | --- |
| [propuesta-de-sistema-sigpap.md](0-propuesta/propuesta-de-sistema-sigpap.md) | Propuesta definitiva aprobada. Documento vinculante. |

## 1. Requisitos

| Archivo | Contenido |
| --- | --- |
| [README.md](1-requisitos/README.md) | Portada, lista de cambios e índice |
| [01-presentacion.md](1-requisitos/01-presentacion.md) | Presentación general, situación, características y participantes |
| [02-objetivos.md](1-requisitos/02-objetivos.md) | Objetivos del sistema (OBJ-01..05) |
| [03-casos-de-uso-resumen.md](1-requisitos/03-casos-de-uso-resumen.md) | Diagrama de CU y tabla resumen |
| [04-subsistemas.md](1-requisitos/04-subsistemas.md) | Subsistemas del proyecto |
| [05-requisitos-informacion.md](1-requisitos/05-requisitos-informacion.md) | Requisitos de información (IRQ-01..05) |
| [06-requisitos-funcionales.md](1-requisitos/06-requisitos-funcionales.md) | Requisitos funcionales (RF-01..07) |
| [07-requisitos-no-funcionales.md](1-requisitos/07-requisitos-no-funcionales.md) | Requisitos no funcionales (NFR-01..03) |
| [08-actores.md](1-requisitos/08-actores.md) | Definición de actores (ACT-01..07) |
| [09-matriz-rastreabilidad.md](1-requisitos/09-matriz-rastreabilidad.md) | Matriz de rastreabilidad objetivo/requisitos |
| [10-glosario.md](1-requisitos/10-glosario.md) | Glosario de términos |

### Casos de uso extendidos (por módulo)

| Archivo | Módulo |
| --- | --- |
| [casos-de-uso/produccion.md](1-requisitos/casos-de-uso/produccion.md) | Producción (CU-PROD) |
| [casos-de-uso/inventario.md](1-requisitos/casos-de-uso/inventario.md) | Inventario (CU-INV) |
| [casos-de-uso/compras.md](1-requisitos/casos-de-uso/compras.md) | Compras (CU-COM) |
| [casos-de-uso/analisis-predictivo.md](1-requisitos/casos-de-uso/analisis-predictivo.md) | Análisis Predictivo (CU-PRED) |
| [casos-de-uso/reportes.md](1-requisitos/casos-de-uso/reportes.md) | Reportes (CU-REP) |
| [casos-de-uso/auditoria.md](1-requisitos/casos-de-uso/auditoria.md) | Auditoría (CU-AUD) |
| [casos-de-uso/usuarios-seguridad.md](1-requisitos/casos-de-uso/usuarios-seguridad.md) | Usuarios y Seguridad (CU-USR) |

### Especificaciones funcionales por módulo (material de trabajo propio)

Estas especificaciones no son un entregable formal de la cátedra: son un artefacto propio para tener el panorama de cada módulo y usarlo como base al redactar los casos de uso.

| Archivo | Módulo |
| --- | --- |
| [especificaciones-por-modulo/produccion.md](1-requisitos/especificaciones-por-modulo/produccion.md) | Producción |
| [especificaciones-por-modulo/inventario.md](1-requisitos/especificaciones-por-modulo/inventario.md) | Inventario |
| [especificaciones-por-modulo/compras.md](1-requisitos/especificaciones-por-modulo/compras.md) | Compras |
| [especificaciones-por-modulo/analisis-predictivo.md](1-requisitos/especificaciones-por-modulo/analisis-predictivo.md) | Análisis Predictivo |
| [especificaciones-por-modulo/reportes.md](1-requisitos/especificaciones-por-modulo/reportes.md) | Reportes |
| [especificaciones-por-modulo/auditoria.md](1-requisitos/especificaciones-por-modulo/auditoria.md) | Auditoría |
| [especificaciones-por-modulo/usuarios-seguridad.md](1-requisitos/especificaciones-por-modulo/usuarios-seguridad.md) | Usuarios y Seguridad |

## 2. Análisis (entregables formales de la cátedra)

| Archivo | Contenido |
| --- | --- |
| [Modelo-Dominio/01-conceptos.md](2-analisis/Modelo-Dominio/01-conceptos.md) | Conceptos, descripciones, relaciones y atributos del modelo de dominio |
| [DSS/](2-analisis/DSS/produccion.md) | Diagramas de Secuencia del Sistema por módulo |
| [contratos/](2-analisis/contratos/produccion.md) | Contratos de operación por módulo |
| [matriz-de-rastreabilidad.md](2-analisis/matriz-de-rastreabilidad.md) | Matriz de rastreabilidad de análisis |

## 3. Diseño

| Archivo | Contenido |
| --- | --- |
| [DESIGN.md](3-diseno/DESIGN.md) | Design system (Salvia & Teja) |
| [contratos-entre-modulos.md](3-diseno/contratos-entre-modulos.md) | Contratos y dependencias entre módulos |
| [diagrama-despliegue.mmd.md](3-diseno/diagrama-despliegue.mmd.md) | Diagrama de despliegue (monolito + componente ML + PostgreSQL) |

## 4. Implementación

| Archivo | Contenido |
| --- | --- |
| [diseno-base-de-datos.md](4-implementacion/diseno-base-de-datos.md) | Diseño de la base de datos (tablas, claves y relaciones) |
| [plan-de-pruebas.md](4-implementacion/plan-de-pruebas.md) | Plan de pruebas del sistema |

## Estado de implementación

El repositorio implementa el sistema descrito en `0-propuesta` y `1-requisitos` con Laravel 13 + Inertia/Vue 3 + PostgreSQL. Estado al cierre del PR #110:

| Alcance | Estado | Evidencia |
| --- | --- | --- |
| Usuarios, seguridad y auditoría | Implementado | PRs #93, #99, #104 |
| Inventario | Implementado | PRs #94, #97 |
| Producción y mantenimiento | Implementado | PR #95 |
| Compras (cotizaciones, OC, ranking, informe multicriterio) | Implementado salvo CU-COM-19/20 | PRs #96, #98 |
| Reportes y tablero | Implementado | PRs #100, #101 |
| Alertas por rol (shared kernel) | Implementado | PRs #109, #110 |
| Análisis predictivo (CU-PRED-01…05) | **No implementado** | Módulo vacío; issues #2, #5, #6, #7, #9 |
| Disponibilidad de compra (CU-COM-19/20) | **No implementado** | Solo esquema de datos; issues #28, #31 |

Los módulos operativos cubren RF-01, RF-02, RF-03, RF-06 y RF-07. RF-04 queda parcial (alertas operativas, cálculo predictivo pendiente) y RF-05 no está implementado.
