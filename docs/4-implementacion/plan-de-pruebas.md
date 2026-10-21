# Plan de Pruebas — SIGPAP

> **Disciplina:** Implementación · Proceso Unificado
> **Fuente vinculante:** material de cátedra "Pruebas de Software" (Caballero, 2026) — Sección 06 "Pruebas en Proyecto Software II"
> **Stack:** Laravel (Inertia + Vue 3) + Python + PostgreSQL

## 1. Alcance

El plan cubre **todos los módulos del sistema** — no solo los críticos:

| Módulo | Casos de uso | Prioridad global |
| :--- | :---: | :--- |
| Producción | 28 | Alta |
| Mantenimiento (subdominio de Producción) | 11 | Media |
| Inventario | 8 | Alta |
| Compras | 20 | Alta |
| Análisis Predictivo | 5 | Media |
| Reportes | 1 | Media |
| Auditoría | 1 | Baja |
| Usuarios y Seguridad (soporte) | 7 | Alta |
| **Total** | **81** | — |

## 2. Objetivos del plan

1. Verificar que el sistema cumple las especificaciones de diseño (verificación) y satisface los requisitos del usuario real (validación).
2. Integrar el testing como actividad transversal a todas las fases, no como paso final.
3. Asegurar que los contratos entre módulos (definidos en `docs/3-diseno/contratos-entre-modulos.md`) se cumplen en la práctica.
4. Alcanzar una cobertura de código del **70–80%** en los módulos de lógica de negocio.
5. Documentar los casos ejecutados, resultados y métricas como evidencia para el informe final.

## 3. Entregables obligatorios

La cátedra exige implementar y documentar **cinco** elementos. Este plan los define:

| # | Entregable | Qué implica en SIGPAP |
| :---: | :--- | :--- |
| 1 | **Plan de pruebas** | Este documento. |
| 2 | **Pruebas unitarias** | Tests de Services, Query Objects y reglas de negocio de cada módulo. |
| 3 | **Pruebas de integración** | Tests de los contratos entre módulos + conexión real a PostgreSQL. |
| 4 | **Pruebas de aceptación** | Casos derivados de los requisitos del usuario (81 CU) con evidencia. |
| 5 | **Documentación final** | Informe con plan, casos ejecutados, resultados y cobertura. |

## 4. Tipos de pruebas y técnicas

Para cada módulo se aplican las técnicas según su naturaleza:

| Tipo | Técnica predominante | Aplicación en SIGPAP |
| :--- | :--- | :--- |
| **Unitarias** | Caja blanca + valores límite + particiones de equivalencia | Services, Query Objects, reglas de negocio, validaciones. |
| **Integración** | Incremental (Bottom-Up) | Contratos entre módulos + persistencia real en PostgreSQL. |
| **Sistema** | Caja negra | Sistema completo en entorno staging. |
| **Aceptación** | Casos de uso + Alfa/Beta | Flujos de negocio reales con el cliente. |
| **Regresión** | Automatizada en CI | Re-ejecutar toda la suite tras cada cambio. |
| **E2E / UI** | Flujo completo por interfaz | Playwright sobre los flujos de usuario de Vue. |

## 5. Herramientas por capa

| Capa | Herramienta | Uso |
| :--- | :--- | :--- |
| Backend (Laravel) | **PHPUnit** | Pruebas unitarias e integración de Services/Query Objects. |
| Frontend (Vue 3) | **Vitest** | Pruebas unitarias de componentes Vue. |
| ML (Python) | **pytest** | Pruebas unitarias del componente predictivo (`ml-engine`). |
| E2E | **Playwright** | Flujos completos de usuario a través de la UI. |
| Análisis estático | PHP_CodeSniffer, SonarQube | Calidad de código y cobertura. |
| CI/CD | GitHub Actions | Ejecución automática en cada push. |

## 6. Criterios de entrada y salida

### Entrada (para empezar a probar un módulo)

- Los casos de uso del módulo están redactados y trazables a objetivos (matriz de rastreabilidad).
- Los contratos de operación (análisis) están cerrados.
- La estructura de carpetas del módulo (`app/Modules/<Modulo>/`) está creada.

### Salida (cuando se da por probado un módulo)

- Todos los tests unitarios del módulo pasan en verde.
- Las pruebas de integración del módulo con sus dependencias pasan.
- La cobertura del módulo alcanza el 70–80% en lógica de negocio.
- Los resultados están documentados con evidencia.

## 7. Roles

| Rol | Responsabilidad |
| :--- | :--- |
| **Desarrollador (alumno)** | Diseñar y ejecutar las pruebas, redactar el plan y el informe. |
| **Cliente (Confort y Muebles)** | Participar en las pruebas de aceptación (Beta) validando requisitos reales. |
| **Equipo de cátedra** | Evaluar el plan, las pruebas y la documentación. |

## 8. Cronograma orientativo

| Fase | Actividad | Momento |
| :--- | :--- | :--- |
| 1 | Definir plan de pruebas y alcance por módulo | Inicio de implementación |
| 2 | Pruebas unitarias por módulo (conforme se codifica) | Durante codificación |
| 3 | Pruebas de integración (contratos entre módulos + BD) | Tras cada integración |
| 4 | Pruebas de sistema y E2E | Previo a despliegue |
| 5 | Pruebas de aceptación (Alfa/Beta) | Con el cliente |
| 6 | Documentación final + métricas de cobertura | Cierre |

## 9. Matriz módulo → técnica de prueba

La matriz cruza cada módulo con las técnicas aplicables y sus casos de prueba prioritarios.

| Módulo | Unitarias (qué testear) | Integración (qué verificar) | Aceptación (flujo clave) |
| :--- | :--- | :--- | :--- |
| **Producción** | Transiciones de estado de `OrdenDeProduccion`; validaciones de `ParteDiario` (unidades, superación de cantidad); cálculo de desgaste de `Maquinaria`; `ControlDeCalidad`. | → Inventario (reserva de stock al crear orden, `reservar`/`consumir`); → Predictivo (cierre de Parte Diario dispara recálculo). | Supervisor carga un Parte Diario y el sistema actualiza avance + desgaste. |
| **Mantenimiento** | Cálculo de umbral de desgaste; bloqueo preventivo de maquinaria; `PlanDeMantenimiento`. | → Producción (maquinaria asignada a órdenes). | Registrar intervención libera el bloqueo y resetea desgaste. |
| **Inventario** | `StockQuery::disponible()` (físico − reservado); `Reserva`; `MovimientoDeStock`; umbrales mín/máx. | → Compras (`registrarIngreso`); → Predictivo (`evaluarRiesgoSuministro`). | Crear reserva lógica y verificar que el stock disponible baja sin tocar el físico. |
| **Compras** | Ranking de proveedores; regla de cota de compra directa; licitación multicriterio. | → Inventario (`registrarIngreso` de OC); → Predictivo (`resolverDisponibilidad` Flujo A). | Licitación completa: RFQ → cotizaciones → adjudicación → OC. |
| **Análisis Predictivo** | Cálculo de TRR; riesgo de suministro; simulación de escenarios (pytest en `ml-engine`). | `MLEngine` invocado por Laravel (stdin/stdout JSON); Laravel persiste el resultado. | Cierre de Parte Diario recalcula TRR y actualiza Gantt. |
| **Reportes** | — (vista, no lógica propia). | Lectura de todos los módulos (`datosDeProduccion`, `datosDeInventario`, etc.). | Dashboard gerencial muestra KPIs + Gantt + alertas. |
| **Auditoría** | `EventoDeAuditoria` (usuario, IP, estado previo/nuevo); borrado lógico. | Registro de eventos desde todos los módulos (`registrarEvento`). | Consultar registro y ver trazabilidad de una operación crítica. |
| **Usuarios y Seguridad** | RBAC (`autorizar`); validación de credenciales; `ParametroGlobal`. | Autorización aplicada en todos los módulos. | Iniciar sesión por rol y verificar permisos de acceso. |

## 10. Estrategia de integración (Bottom-Up incremental)

Se integra de abajo hacia arriba, pieza por pieza, priorizando los módulos base:

```text
1. Usuarios/Seguridad  (base: RBAC + ParámetroGlobal)
2. Inventario          (base: catálogo, stock, reservas)
3. Producción + Mantenimiento   (depende de Inventario)
4. Compras             (depende de Inventario + Proveedores)
5. Análisis Predictivo (depende de datos de Producción/Inventario/Compras)
6. Reportes            (depende de todos, solo lectura)
7. Auditoría           (transversal, se integra con cada módulo)
```

Cada integración verifica que el módulo que se agrega interactúa correctamente con los ya integrados **y** con la conexión real a PostgreSQL.

## 11. Estrategia de datos de prueba

- **Entorno de test:** PostgreSQL dedicada (o SQLite en memoria para unitarias de Laravel con `RefreshDatabase`).
- **Seeders:** datos base de la fábrica (artículos, etapas, proveedores de ejemplo con nombres reales — "Maderera del Alto Paraná", "Ferretería Industrial Posadas").
- **Fixtures aisladas:** cada test prepara y limpia sus propios datos (independencia del orden de ejecución).

## 12. Métricas y meta

| Métrica | Meta |
| :--- | :--- |
| Cobertura de código (módulos de negocio) | 70–80% |
| Tiempo de ejecución unitaria | milisegundos |
| Tests de regresión | 100% automatizados en CI |

## 13. Testing asistido por IA (metodología de la cátedra)

Se aplica la metodología de 5 pasos del material:

1. **Definir alcance** — este plan define módulos y prioridades.
2. **Generar casos con IA** — LLM genera casos desde los 81 CU; *revisión crítica manual de cada caso*.
3. **Implementar y automatizar** — PHPUnit/pytest/Vitest, integrados al repositorio.
4. **Ejecutar en CI/CD** — GitHub Actions en cada push, monitoreo de cobertura.
5. **Iterar y mantener** — actualizar tests ante cambios de requisitos; detectar obsoletos.

> **Regla de oro:** la IA asiste, no reemplaza el criterio del ingeniero para definir *qué* y *cómo* probar.
