# Contratos entre Módulos

> **Disciplina:** Diseño · Proceso Unificado
> **Fuente vinculante:** `docs/2-analisis/Modelo-Dominio/` (31 conceptos) y CAM-001 (vault `8-Gestion-de-Cambios/`)
> **Complementa:** `docs/0-propuesta/propuesta-de-sistema-sigpap.md`

## Principio rector

**Un módulo es dueño de su lógica y sus datos.** Ningún módulo accede a los modelos/`Eloquent` internos de otro. La única puerta de entrada a un módulo es su **interfaz pública** (el Service expuesto). En términos del análisis: un módulo dice *qué puede hacer*, nunca *cómo lo hace internamente*.

Corolario del CAM-001: **Laravel es dueño del estado y de la base de datos.** El componente de ML (Python) es un *calculador*: recibe datos, calcula y devuelve resultados. Nunca persiste ni modifica conceptos del dominio por su cuenta.

## Matriz de dependencias entre módulos

Cada celda nombra la operación que cruza la frontera. Fila = consumidor, columna = proveedor. `—` = sin dependencia directa.

| Consumidor \ Proveedor | Producción | Inventario | Compras | Predictivo | Reportes | Auditoría | Usuarios |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **Producción** | — | `reservar`, `liberarReserva`, `consumir`, `verificarDisponibilidad` | — | `recalcularTiempoProduccion` | — | `registrarEvento` | `autorizar`, `parametro` |
| **Inventario** | — | — | — | `evaluarRiesgoSuministro` | — | `registrarEvento` | `autorizar`, `parametro` |
| **Compras** | — | `registrarIngreso` | — | `resolverDisponibilidad`, `evaluarRiesgoProveedor` | — | `registrarEvento` | `autorizar`, `parametro` |
| **Predictivo** | `datosDeProduccion` | `datosDeInventario` | `datosDeProveedores` | — | — | `registrarEvento` | `autorizar`, `parametro` |
| **Reportes** | `datosDeProduccion` | `datosDeInventario` | `datosDeCompras` | `predicciones` | — | — | `autorizar` |
| **Auditoría** | — | — | — | — | — | — | — |
| **Usuarios** | — | — | — | — | — | — | — |

> **Nota de dirección:** el módulo **Predictivo** consume *datos* de Producción/Inventario/Compras para calcular, y **devuelve resultados** que el módulo de origen aplica. La flecha de datos es de ida (entran datos) y la de decisión es de vuelta (sale predicción). El dueño de cada concepto sigue siendo su módulo.

## Contratos por módulo

---

### Producción

**Posee:** `PlanificacionDeProduccion`, `OrdenDeProduccion`, `RegistroPrioridad`, `Etapa`, `AsignacionDeEtapa`, `ParteDiario`, `Operario`, `Maquinaria`, `ControlDeCalidad`, `PlanDeMantenimiento`, `IntervencionDeMantenimiento`.

**Responsabilidad:** ciclo de fabricación completo — emisión de órdenes, planificación, seguimiento por Parte Diario, mantenimiento preventivo y cierre con control de calidad.

**Interfaz pública (expone a otros módulos):**

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `datosDeProduccion` | — (lectura) | Órdenes en curso, partes diarios, etapas con su estándar, maquinaria con desgaste. |

**Dependencias (consume de otros):**

| Operación consumida | Proveedor | Uso |
| --- | --- | --- |
| `verificarDisponibilidad(articulo, cantidad)` | Inventario | Antes de confirmar una orden (descontando reservas previas). |
| `reservar(articulo, cantidad, orden)` | Inventario | Al lanzar una orden: reserva lógica de insumos. |
| `liberarReserva(reserva)` | Inventario | Al anular o cerrar una orden sin consumo total. |
| `consumir(articulo, cantidad, orden)` | Inventario | Al cerrar una etapa/orden: descuenta stock físico. |
| `recalcularTiempoProduccion(datosAvance)` | Predictivo | Al cerrar un Parte Diario: recalcula TRR y fecha proyectada. |
| `registrarEvento(...)` | Auditoría | Cierres, cambios de prioridad, controles de calidad. |

---

### Inventario

**Posee:** `Articulo`, `Reserva`, `MovimientoDeStock`, `UnidadDeMedida`.

**Responsabilidad:** catálogo, stock físico vs. reservado, movimientos, alertas de reposición/caducidad.

**Interfaz pública:**

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `verificarDisponibilidad(articulo, cantidad)` | Artículo y cantidad requerida | Stock disponible (físico − reservado). |
| `reservar(articulo, cantidad, orden)` | Artículo, cantidad, orden de producción | `Reserva` creada. |
| `liberarReserva(reserva)` | Reserva | Reserva liberada. |
| `consumir(articulo, cantidad, orden)` | Artículo, cantidad, orden | `MovimientoDeStock` de consumo. |
| `registrarIngreso(articulo, cantidad, ordenCompra)` | Artículo, cantidad, OC | `MovimientoDeStock` de ingreso. |
| `datosDeInventario` | — (lectura) | Stock físico, reservas lógicas, velocidad de consumo. |

**Dependencias:**

| Operación consumida | Proveedor | Uso |
| --- | --- | --- |
| `evaluarRiesgoSuministro(stock, reservas, cronograma)` | Predictivo | Abastecimiento inteligente: proyección de quiebre de stock. |
| `registrarEvento(...)` | Auditoría | Movimientos y ajustes críticos. |

---

### Proveedores y Compras

**Posee:** `Proveedor`, `SolicitudDeDisponibilidad`, `SolicitudDeCotizacion`, `Cotizacion`, `OrdenDeCompra`, `Incidencia`, `LineaSolicitudCotizacion`, `LineaOrdenCompra`, `DesempenoProveedor`.

**Responsabilidad:** registro/ranking de proveedores, borradores de compra, licitación multicriterio, seguimiento de entregas.

**Interfaz pública:**

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `datosDeProveedores` | — (lectura) | Desempeño histórico, incidencias, cotizaciones, tiempos de entrega. |
| `datosDeCompras` | — (lectura) | Órdenes de compra, solicitudes, estados de abastecimiento. |

**Dependencias:**

| Operación consumida | Proveedor | Uso |
| --- | --- | --- |
| `registrarIngreso(...)` | Inventario | Recepción de mercadería (total o parcial). |
| `resolverDisponibilidad(respuesta, umbral)` | Predictivo | Flujo A: evalúa la respuesta del proveedor y recomienda escenario. |
| `evaluarRiesgoProveedor(...)` | Predictivo | Riesgo histórico de retraso (alimenta la licitación). |
| `registrarEvento(...)` | Auditoría | Aprobaciones y emisión de órdenes de compra. |

---

### Análisis Predictivo

**Posee:** `TasaDeRendimiento`, `EscenarioDeSimulacion`.

**Responsabilidad:** núcleo del DSS. Calcular TRR, evaluar riesgo de suministro, resolver disponibilidad (Flujo A), simular escenarios. **No es dueño de los datos que procesa ni persiste resultados fuera de sus propios conceptos** — CAM-001.

**Implementación:** el cálculo concreto vive detrás de la abstracción `MLEngine`, inicialmente un componente Python invocado por Laravel (queue job → proceso → JSON). FastAPI queda como evolución futura documentada.

**Interfaz pública:**

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `recalcularTiempoProduccion(datosAvance)` | Avance del Parte Diario por etapa | `TasaDeRendimiento` + fecha proyectada. |
| `evaluarRiesgoSuministro(stock, reservas, cronograma)` | Estado de inventario + cronograma | Lista de criticidad (insumos en riesgo de quiebre). |
| `resolverDisponibilidad(respuesta, umbral)` | Respuesta del proveedor + umbral de tolerancia | Recomendación de escenario (Ideal / Sin Stock / Variación Fuerte). |
| `simularEscenario(parametros, cronograma)` | Pedido ficticio + cronograma real | Impacto proyectado (qué órdenes se retrasan y cuánto). |
| `predicciones` | — (lectura) | Resultados persistidos para el tablero. |

**Dependencias (datos de entrada):**

| Operación consumida | Proveedor | Uso |
| --- | --- | --- |
| `datosDeProduccion` | Producción | Partes diarios, etapas, estándares. |
| `datosDeInventario` | Inventario | Stock, reservas, consumo. |
| `datosDeProveedores` | Compras | Desempeño, tiempos de entrega. |
| `registrarEvento(...)` | Auditoría | Reentrenamientos y decisiones automáticas. |

> **Regla de ownership:** el componente ML **devuelve** la predicción; el módulo de origen (`Producción`, `Inventario`, `Compras`) es quien **aplica** el resultado (actualiza fechas, emite la `Alerta` vía el subsistema transversal, crea el borrador de cotización). El ML jamás modifica órdenes ni escribe en tablas transaccionales ajenas.

---

### Reportes

**Posee:** (ningún concepto del dominio — es una **vista** sobre los demás módulos).

**Responsabilidad:** dashboards, Gantt predictivo, simulador UI, exportación (PDF/Excel).

**Dependencias (solo lectura):**

| Operación consumida | Proveedor | Uso |
| --- | --- | --- |
| `datosDeProduccion` | Producción | KPIs, cronograma. |
| `datosDeInventario` | Inventario | Stock crítico. |
| `datosDeCompras` | Compras | Estado de abastecimiento. |
| `predicciones` | Predictivo | TRR, fechas proyectadas, alertas. |

> Reportes **nunca escribe** en el dominio de otros módulos. Si una vista dispara una acción (ej. simular), la delega en el módulo dueño.

---

### Auditoría

**Posee:** `EventoDeAuditoria`.

**Responsabilidad:** trazabilidad de eventos críticos (usuario, fecha, IP, estado previo/nuevo) y soporte de borrado lógico.

**Interfaz pública:**

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `registrarEvento(usuario, accion, estadoAnterior, estadoNuevo, ip)` | Datos del evento | `EventoDeAuditoria` persistido. |

> **Actor técnico sintético:** las decisiones automáticas del componente ML se auditan con un usuario técnico (`Microservicio` → `ComponentePredictivo` tras CAM-001), sin alterar la relación `EventoDeAuditoria - Usuario`.

---

### Usuarios y Seguridad

**Posee:** `Usuario`, `Rol`, `ParametroGlobal`.

**Responsabilidad:** autenticación, RBAC, configuración global de negocio (turnos, feriados, umbrales).

**Interfaz pública:**

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `autorizar(usuario, operacion)` | Usuario + operación solicitada | Permiso concedido/denegado. |
| `parametro(clave)` | Clave de configuración | `ParametroGlobal` (ej. umbral de tolerancia). |
| `definirParametro(clave, valor)` | Clave + valor, **solo Administrador (ACT-07)** | `ParametroGlobal` actualizado. |

> **Regla de ownership de `ParametroGlobal`:** lectura transversal (`parametro(clave)` desde cualquier módulo), **escritura exclusiva del Administrador** vía `definirParametro`. Es un shared kernel: pequeño, estable y con un único dueño de mutación.

---

## Conceptos transversales (shared kernel)

### `Alerta`

`Alerta` es un **concepto transversal**, no propiedad del módulo Análisis Predictivo. Su decisión de modelado (unificar todas las notificaciones en un único concepto con atributo `tipo`) es correcta, pero de ella **no** se deduce un ownership en Predictivo: las relaciones del modelo ya la muestran repartida (`Alerta - OrdenDeProduccion`, `Alerta - Articulo`, `Alerta - Maquinaria`) y sus tipos (reposición, caducidad, mantenimiento preventivo, falla técnica) no tienen relación con ML.

**Subsistema de alertas** — interfaz pública transversal:

| Operación | Datos de entrada | Resultado |
| --- | --- | --- |
| `emitirAlerta(tipo, contexto, origen)` | Tipo de alerta + concepto asociado + módulo emisor | `Alerta` persistida. |

**Quién emite qué:**

| Tipo de alerta | Emitida por |
| --- | --- |
| Cuello de botella predictivo | `Producción` (al aplicar el resultado del recálculo de TRR) |
| Reposición / caducidad | `Inventario` |
| Quiebre de stock proyectado | `Compras` (al aplicar la criticidad de suministro) |
| Mantenimiento preventivo | `Producción` |
| Técnica (falla/timeout del sistema) | Cualquier módulo que detecte la falla |

El subsistema de alertas es el **único** punto que persiste `Alerta`; ningún módulo la crea por su cuenta.

---

## Dependencias cruzadas y flujo de los procesos automatizados

| Proceso | Secuencia de contratos |
| --- | --- |
| **P1 — Ajuste de TRR** | `Producción.cerrarParteDiario` → `Predictivo.recalcularTiempoProduccion` → devuelve TRR → `Producción` actualiza fecha proyectada → `Alerta` (cuello de botella) si supera fecha límite. |
| **P2 — Abastecimiento inteligente** | `Inventario` monitorea consumo → `Predictivo.evaluarRiesgoSuministro` → lista de criticidad → `Compras` genera borrador de `SolicitudDeCotizacion` → licitación → `Predictivo.resolverDisponibilidad` (Flujo A) / algoritmo multicriterio (Flujo B). |
| **P3 — Simulador** | `Gerente` ingresa parámetros → `Reportes` delega → `Predictivo.simularEscenario` → impacto → `Reportes` renderiza (sin alterar el cronograma real). |

## Decisiones de diseño registradas

1. **Encapsulamiento por Service público.** La regla "llamar al Service, nunca al Model" es una decisión de diseño que se codifica, no una red: en un monolito los módulos comparten el mismo Eloquent, y el límite se sostiene por convención + revisión.
2. **`Alerta` es un shared kernel transversal, no propiedad de Predictivo.** La decisión de modelado #6 (unificar notificaciones con `tipo`) es correcta, pero no asigna ownership en Predictivo. Un único subsistema de alertas persiste `Alerta`; cada módulo la emite con el `tipo` de su contexto (Producción → cuello de botella, Inventario → reposición/caducidad, etc.).
3. **Reportes y Auditoría son módulos transversales.** Reportes es solo lectura; Auditoría es solo escritura de eventos. Ninguno posee lógica de negocio del dominio operativo.
4. **`ParametroGlobal` es un shared kernel con escritura restringida.** Lectura transversal (`parametro(clave)`) desde cualquier módulo; escritura exclusiva del Administrador (`definirParametro`). No se asocia a los demás conceptos del dominio.
