# Contratos de Operación — Análisis Predictivo

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/2-analisis/DSS/analisis-predictivo.md`
> **Plantilla:** `docs/2-analisis/contratos/_plantilla-contrato.md`

## Nota de alcance

Se documentan las 4 operaciones del sistema con regla de negocio y flujo crítico del módulo Análisis Predictivo (CU-PRED-01, 02, 03 y 04). CU-PRED-05 (Consultar Tablero Predictivo) no lleva contrato por ser una consulta de solo lectura: no crea, modifica ni asocia instancias del Modelo de Dominio, siguiendo la regla de la plantilla (los listados y consultas no se contratan).

## CU-PRED-01 — iniciarReentrenamiento()

| Campo | Contenido |
| --- | --- |
| **Nombre** | `iniciarReentrenamiento()` |
| **Responsabilidades** | Disparar el ciclo de reentrenamiento del modelo de Machine Learning, delegando la ejecución en el Componente Predictivo y registrando el resultado en la auditoría del sistema. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PRED-01 |
| **Notas** | Hallazgo: el CU menciona "ModeloPredictivo" y "fecha de último entrenamiento", pero ninguno tiene respaldo en el Modelo de Dominio. `ModeloPredictivo` está excluido explícitamente como componente de software (`01-conceptos.md` → "Excluidos"), y la "fecha de último entrenamiento" (paso 5) es estado interno del componente predictivo/Scheduler, no un atributo de ningún concepto. El único efecto persistible en el dominio es la instancia de `EventoDeAuditoria`. |
| **Excepciones** | Si el `ComponentePredictivo` no responde (Timeout), la tarea se reprograma para el siguiente ciclo horario y se envía una alerta técnica al administrador (tipo "Técnica", con respaldo en `Alerta.tipo`). |
| **Salida** | `entrenamientoCompletado(estado, metricas)`. |
| **Precondiciones** | El `RelojSistema` alcanzó el intervalo de reentrenamiento configurado. Existen datos históricos consolidados (Partes Diarios cerrados y Órdenes de Producción cerradas) en el sistema. |
| **Postcondiciones** | En el flujo normal no se crearon ni modificaron instancias del dominio transaccional: el entrenamiento ocurre dentro del `ComponentePredictivo` (componente de software externo, fuera del Modelo de Dominio). Se creó una instancia de `EventoDeAuditoria` con `fechaHora` y `accion` ("Reentrenamiento del Modelo") asignados. Se asoció el `EventoDeAuditoria` al `Usuario` administrador que ejecutó el proceso. |

## CU-PRED-02 — recalcularTiempoProduccion(datosAvance)

| Campo | Contenido |
| --- | --- |
| **Nombre** | `recalcularTiempoProduccion(datosAvance)` |
| **Responsabilidades** | Recalcular la proyección de finalización de una Orden de Producción a partir del avance real, actualizando la Tasa de Rendimiento y emitiendo la alerta de cuello de botella si la fecha proyectada supera la fecha límite. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PRED-02 (reactivo a CU-PROD-13) |
| **Notas** | El evento que entra al sistema proviene del `ComponentePredictivo` (actor reactivo), decisión de trazabilidad registrada en el DSS. Hallazgo: la postcondición del CU ("el cronograma general — Gantt gerencial — se actualiza") refiere a una vista excluida del dominio (`01-conceptos.md` → "Gantt (Cronograma), Dashboard, Reporte | Vistas / salidas"); el efecto persistible es la creación de `TasaDeRendimiento` y `Alerta`. El cronograma como funcionalidad está cubierto por CU-PROD-23 / F12 (Gestionar Cronograma de Producción); la brecha conceptual del cronograma/prioridad está registrada como hallazgo en el registro de hallazgos del proyecto (H-029). |
| **Excepciones** | Si la comunicación con el `ComponentePredictivo` falla, la petición se encola y se realizan reintentos automáticos sin interrumpir el trabajo del operario en planta. |
| **Salida** | `resultadoRecalculo(tasaRendimiento, fechaProyectada)`. |
| **Precondiciones** | Existe una `OrdenDeProduccion` en curso. Existe al menos un `ParteDiario` cerrado con avance registrado (CU-PROD-13). |
| **Postcondiciones** | Se creó una instancia de `TasaDeRendimiento` con `valor`, `desviacion` y `fechaCalculo` asignados. Se asoció la `TasaDeRendimiento` a la `Etapa` correspondiente al avance recibido. Si la fecha proyectada supera `OrdenDeProduccion.fechaEntrega`, se creó una instancia de `Alerta` con `tipo` "Cuello de Botella Predictivo", `fecha` y `descripcion` asignados, asociada a la `OrdenDeProduccion`. |

## CU-PRED-03 — evaluarRiesgoSuministro()

| Campo | Contenido |
| --- | --- |
| **Nombre** | `evaluarRiesgoSuministro()` |
| **Responsabilidades** | Evaluar periódicamente el riesgo de quiebre de stock de los insumos en consumo, generando borradores de Solicitudes de Cotización y alertas para los insumos críticos. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PRED-03 |
| **Notas** | El carácter "borrador" de las solicitudes generadas por el proceso automático tiene respaldo en `SolicitudDeCotizacion.estado` (Borrador, Enviada, Adjudicada, Anulada); el `EncargadoDeCompras` las revisa/aprueba en el módulo Compras (CU-COM-06). El tiempo de entrega real no se guarda como atributo de `Proveedor`: se deriva de `DesempenoProveedor.tiempoEntregaReal` (registro de entregas reales) y de `Cotizacion.fechaPrometida`. El "Cronograma de Fabricación" enviado al componente predictivo es la vista Gantt excluida del dominio. |
| **Excepciones** | Si un insumo es nuevo y no posee historial de consumo ni de proveedores, se utiliza el umbral estático (`Articulo.stockMinimo` / `Articulo.stockMaximo`) en lugar de la predicción. Si ningún insumo está en riesgo de quiebre, el proceso finaliza sin generar alertas ni borradores. |
| **Salida** | `borradoresCotizacion(solicitudes)`. |
| **Precondiciones** | Existen `OrdenDeProduccion` activas consumiendo insumos (con `Reserva`s asociadas). Existen `DesempenoProveedor` con entregas reales registradas para los `Proveedor`. Existen `Articulo` con `stockFisico` y `stockMinimo` registrados. |
| **Postcondiciones** | Por cada insumo crítico de la lista de criticidad, se creó una instancia de `SolicitudDeCotizacion` con `fecha` asignada y `estado` inicializado en "Borrador". Se asoció cada `SolicitudDeCotizacion` al `Proveedor` correspondiente y se creó una instancia de `LineaSolicitudCotizacion` con `cantidadSolicitada` asignada, asociada a la `SolicitudDeCotizacion` y al `Articulo` en riesgo. Se creó una instancia de `Alerta` con `tipo` "Quiebre de Stock", `fecha` y `descripcion` asignados, asociada al `Articulo` crítico. Si no existían insumos en riesgo, no se creó ninguna instancia. |

## CU-PRED-04 — simularEscenarioProduccion(articulo, cantidad, fechaLimite)

| Campo | Contenido |
| --- | --- |
| **Nombre** | `simularEscenarioProduccion(articulo, cantidad, fechaLimite)` |
| **Responsabilidades** | Evaluar el impacto logístico de un pedido urgente sobre las órdenes de producción en curso, sin modificar el cronograma real. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-PRED-04 |
| **Notas** | Respaldo verificado: `EscenarioDeSimulacion` existe en el dominio con `cantidadFicticia`, `fechaLimite`, `prioridad` e `impactoProyectado` (`04-atributos.md`) y se asocia a `Articulo` (`03-relaciones.md` → "A se calcula/emite sobre B"). El CU no explicita la persistencia del escenario, pero IRQ-04 exige "parámetros de escenarios de simulación guardados" (`05-requisitos-informacion.md`), lo que respalda la postcondición de creación. `prioridad` se fija en 1 según el CU (pedido urgente). La alerta "Escenario Inviable" de la excepción es una salida derivada del retorno del componente predictivo, no una instancia de `Alerta` persistida. |
| **Excepciones** | Si el `ComponentePredictivo` determina que es matemáticamente imposible cumplir la fecha de entrega, devuelve "Escenario Inviable" y el sistema presenta la alerta al Gerente sin mostrar el dashboard de impacto. |
| **Salida** | `dashboardResultados(resultados)` con las órdenes preexistentes que se retrasarían y los días de demora. |
| **Precondiciones** | El `Gerente` está autenticado con el rol Gerente. Existe el `Articulo` consultado en el catálogo. Existen `OrdenDeProduccion` en curso. |
| **Postcondiciones** | Se creó una instancia de `EscenarioDeSimulacion` con `cantidadFicticia`, `fechaLimite`, `prioridad` e `impactoProyectado` asignados. Se asoció el `EscenarioDeSimulacion` al `Articulo` simulado. No se modificó ninguna `OrdenDeProduccion` ni se alteró el cronograma real (la simulación es no destructiva). |