# Matriz de Rastreabilidad — Fase de Análisis

> **Disciplina:** Análisis · Proceso Unificado
> **Fuentes vinculantes:** `docs/2-analisis/DSS/*.md` · `docs/2-analisis/contratos/*.md` · `docs/2-analisis/Modelo-Dominio/01-conceptos.md`, `03-relaciones.md`, `04-atributos.md`
> **Propósito:** garantizar la trazabilidad vertical UP entre Casos de Uso → Operaciones del Sistema (DSS) → Contratos de Operación → Conceptos del Modelo de Dominio. Todo evento del sistema con regla de negocio debe tener contrato, y todo concepto del dominio debe quedar cubierto por las operaciones contratadas.

## Leyenda

| Marca | Significado |
| --- | --- |
| **Aplica** | La operación lleva contrato: tiene reglas de negocio que cambian el estado del dominio (altas, modificaciones, flujos críticos). |
| **Solo DSS** | La operación **no** lleva contrato: ABM trivial (baja) o consulta/listado/búsqueda sin reglas de negocio (regla de Larman, ver `contratos/_plantilla-contrato.md`). |

- Los nombres de operaciones y firmas de contrato se transcriben **tal cual** los archivos fuente (camelCase exacto).
- Donde una operación aparece en un flujo automático entre sistemas se indica el emisor/receptor entre paréntesis.
- Los conceptos del dominio usan los nombres exactos de `01-conceptos.md`.

## Inventario

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-INV-01 | Alta de Artículo | `altaArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)` | `altaArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)` | `Articulo`, `UnidadDeMedida` |
| CU-INV-02 | Baja de Artículo | `bajaArticulo(codigoSKU)` | — | `Articulo` (baja lógica: estado → Inactivo) |
| CU-INV-03 | Modificación de Artículo | `modificarArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)` | `modificarArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)` | `Articulo`, `UnidadDeMedida` |
| CU-INV-04 | Listado de Artículos | `solicitarListadoArticulos()` | — | `Articulo` (consulta) |
| CU-INV-05 | Buscar Artículo | `buscarArticulo(criterio)` | — | `Articulo` (consulta) |
| CU-INV-06 | Registrar ingreso de materiales | `registrarIngresoMateriales(numeroRemito, numeroOC, lineasDeRecepcion)` | `registrarIngresoMateriales(numeroRemito, numeroOC, lineasDeRecepcion)` | `Articulo`, `OrdenDeCompra`, `MovimientoDeStock`, `DesempenoProveedor`, `Proveedor`, `Incidencia` |
| CU-INV-07 | Registrar Ajuste de Inventario | `registrarAjusteInventario(codigoSKU, nuevoStockObservado, motivo)` | `registrarAjusteInventario(codigoSKU, nuevoStockObservado, motivo)` | `Articulo`, `MovimientoDeStock` |
| CU-INV-08 | Parametrizar Umbrales de Stock | `parametrizarUmbralesStock(codigoSKU, stockMinimo, stockMaximo)` | `parametrizarUmbralesStock(codigoSKU, stockMinimo, stockMaximo)` | `Articulo` |

## Compras

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-COM-01 | Alta de Proveedor | `altaProveedor(razonSocial, cuit, email, telefono)` | `altaProveedor(razonSocial, cuit, email, telefono)` | `Proveedor` |
| CU-COM-02 | Baja de Proveedor | `bajaProveedor(cuit)` | — | `Proveedor` (baja lógica: estado → Inactivo) |
| CU-COM-03 | Modificación de Proveedor | `modificarProveedor(cuit, razonSocial, email, telefono)` | `modificarProveedor(cuit, razonSocial, email, telefono)` | `Proveedor` |
| CU-COM-04 | Listado de Proveedores | `solicitarListadoProveedores()` | — | `Proveedor` (consulta) |
| CU-COM-05 | Buscar Proveedor | `buscarProveedor(criterio)` | — | `Proveedor` (consulta) |
| CU-COM-06 | Alta de Solicitud de Cotización | `solicitarBorradorCotizacion(); altaSolicitudCotizacion(lineas, proveedores, fechaVencimiento)` | `altaSolicitudCotizacion(lineas, proveedores, fechaVencimiento)` | `SolicitudDeCotizacion`, `LineaSolicitudCotizacion`, `Articulo`, `Proveedor` |
| CU-COM-07 | Baja de Solicitud de Cotización | `anularSolicitudCotizacion(idSolicitudCotizacion, motivo)` | `anularSolicitudCotizacion(idSolicitudCotizacion, motivo)` | `SolicitudDeCotizacion` (estado → Anulada) |
| CU-COM-08 | Modificación de Solicitud de Cotización | `modificarSolicitudCotizacion(idSolicitudCotizacion, lineas, proveedores, fechaVencimiento)` | `modificarSolicitudCotizacion(idSolicitudCotizacion, lineas, proveedores, fechaVencimiento)` | `SolicitudDeCotizacion`, `LineaSolicitudCotizacion`, `Articulo`, `Proveedor` |
| CU-COM-09 | Listado de Solicitudes de Cotización | `solicitarListadoSolicitudesCotizacion()` | — | `SolicitudDeCotizacion` (consulta) |
| CU-COM-10 | Buscar Solicitud de Cotización | `buscarSolicitudCotizacion(criterio)` | — | `SolicitudDeCotizacion` (consulta) |
| CU-COM-11 | Alta de Orden de Compra | `solicitarInformeMulticriterio(idSolicitudCotizacion); emitirOrdenCompra(idSolicitudCotizacion, proveedor, lineas)` | `emitirOrdenCompra(idSolicitudCotizacion, proveedor, lineas)` | `OrdenDeCompra`, `Proveedor`, `Cotizacion`, `LineaOrdenCompra`, `Articulo`, `SolicitudDeCotizacion` (estado → Adjudicada) |
| CU-COM-12 | Baja de Orden de Compra | `anularOrdenCompra(idOrdenCompra, motivo)` | `anularOrdenCompra(idOrdenCompra, motivo)` | `OrdenDeCompra` (estado → Anulada) |
| CU-COM-13 | Modificación de Orden de Compra | `modificarOrdenCompra(idOrdenCompra, lineas)` | `modificarOrdenCompra(idOrdenCompra, lineas)` | `OrdenDeCompra`, `LineaOrdenCompra`, `Articulo` |
| CU-COM-14 | Listado de Órdenes de Compra | `solicitarListadoOrdenesCompra()` | — | `OrdenDeCompra` (consulta) |
| CU-COM-15 | Buscar Orden de Compra | `buscarOrdenCompra(criterio)` | — | `OrdenDeCompra` (consulta) |
| CU-COM-16 | Enviar Cotización | `enviarCotizacion(token, precioUnitario, fechaPrometida, cantidadOfertada)` | `enviarCotizacion(token, precioUnitario, fechaPrometida, cantidadOfertada)` | `Cotizacion`, `Proveedor`, `Articulo`, `SolicitudDeCotizacion` (Flujo B), `SolicitudDeDisponibilidad` (Flujo A) |
| CU-COM-17 | Confirmar Orden de Compra | `confirmarOrdenCompra(token)` | `confirmarOrdenCompra(token)` | `OrdenDeCompra`, `Proveedor` |
| CU-COM-18 | Consultar Ranking de Proveedores | `consultarRankingProveedores(); consultarDetalleProveedor(cuit)` | — | `Proveedor`, `DesempenoProveedor`, `Incidencia` (ranking: valor derivado, consulta) |
| CU-COM-19 | Solicitar Disponibilidad a Proveedor | `solicitarDisponibilidad(articulo, cantidad, proveedor)` | `solicitarDisponibilidad(articulo, cantidad, proveedor)` | `SolicitudDeDisponibilidad`, `Proveedor`, `Articulo` |
| CU-COM-20 | Resolver Disponibilidad de Compra | `resolverDisponibilidad(respuestaProveedor, umbralTolerancia)` (Sistema→ComponentePredictivo, flujo automático) | `resolverDisponibilidad(respuestaProveedor, umbralTolerancia)` | `SolicitudDeDisponibilidad` (estado → Respondida), `Cotizacion`, `Articulo`, `Alerta` (escenario "Variación Fuerte"), `ParametroGlobal` (umbral de tolerancia) |

## Usuarios y Seguridad

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-USR-01 | Alta de Usuario | `altaUsuario(nombre, email, claveTemporal, rol)` | `altaUsuario(nombre, email, claveTemporal, rol)` | `Usuario`, `Rol` |
| CU-USR-02 | Baja de Usuario | `inhabilitarUsuario(email)` | — | `Usuario` (baja lógica: estado → Inhabilitado) |
| CU-USR-03 | Modificación de Usuario | `modificarUsuario(email, rol, clave)` | `modificarUsuario(email, rol, clave)` | `Usuario`, `Rol`, `EventoDeAuditoria` |
| CU-USR-04 | Listado de Usuarios | `solicitarListadoUsuarios()` | — | `Usuario` (consulta) |
| CU-USR-05 | Buscar Usuario | `buscarUsuario(criterio)` | — | `Usuario` (consulta) |
| CU-USR-06 | Iniciar/Cerrar Sesión | `iniciarSesion(email, clave); cerrarSesion()` | `iniciarSesion(email, clave)`; `cerrarSesion()` (dos contratos) | `Usuario`, `Rol` (iniciarSesion); `Usuario` (cerrarSesion, sin cambios de estado en el dominio) |
| CU-USR-07 | Configurar Parámetros del Sistema | `configurarParametros(categoria, parametro, valor)` | `configurarParametros(categoria, parametro, valor)` | `ParametroGlobal` |

## Producción

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-PROD-01 | Alta de Planificación de Producción | `altaPlanificacion(nombre, fechaInicio, fechaFin, observaciones)` | `altaPlanificacion(nombre, fechaInicio, fechaFin, observaciones)` | `PlanificacionDeProduccion` |
| CU-PROD-02 | Baja de Planificación de Producción | `bajaPlanificacion(planificacion)` | — | `PlanificacionDeProduccion` |
| CU-PROD-03 | Modificación de Planificación de Producción | `modificarPlanificacion(planificacion, nombre, fechaInicio, fechaFin, observaciones)` | `modificarPlanificacion(planificacion, nombre, fechaInicio, fechaFin, observaciones)` | `PlanificacionDeProduccion` |
| CU-PROD-04 | Listado de Planificaciones de Producción | `solicitarListadoPlanificaciones()` | — | `PlanificacionDeProduccion` (consulta) |
| CU-PROD-05 | Buscar Planificación de Producción | `buscarPlanificacion(criterio)` | — | `PlanificacionDeProduccion` (consulta) |
| CU-PROD-06 | Alta de Orden de Producción | `altaOrdenProduccion(producto, cantidad, fechaEntrega, planificacion)` | `altaOrdenProduccion(producto, cantidad, fechaEntrega, planificacion)` | `OrdenDeProduccion`, `PlanificacionDeProduccion`, `Articulo`, `Reserva` |
| CU-PROD-07 | Baja de Orden de Producción | `anularOrdenProduccion(orden, motivo)` | — | `OrdenDeProduccion` (estado → Anulada) |
| CU-PROD-08 | Modificación de Orden de Producción | `modificarOrdenProduccion(orden, cantidad, fechaEntrega)` | `modificarOrdenProduccion(orden, cantidad, fechaEntrega)` | `OrdenDeProduccion`, `Reserva` |
| CU-PROD-09 | Listado de Órdenes de Producción | `solicitarListadoOrdenesProduccion()` | — | `OrdenDeProduccion` (consulta) |
| CU-PROD-10 | Buscar Orden de Producción | `buscarOrdenProduccion(criterio)` | — | `OrdenDeProduccion` (consulta) |
| CU-PROD-11 | Asignar Recursos a Orden | `asignarRecursosOrden(orden, operarios, maquinaria)` | `asignarRecursosOrden(orden, operarios, maquinaria)` | `OrdenDeProduccion`, `Operario`, `Maquinaria` |
| CU-PROD-12 | Iniciar Orden de Producción | `iniciarOrdenProduccion(orden)` | `iniciarOrdenProduccion(orden)` | `OrdenDeProduccion` |
| CU-PROD-13 | Cargar Parte Diario | `cargarParteDiario(orden, cantidad, observaciones)` | `cargarParteDiario(orden, cantidad, observaciones)` | `ParteDiario`, `OrdenDeProduccion`, `Etapa`, `Maquinaria`, `MovimientoDeStock`, `Articulo`, `Reserva` |
| CU-PROD-14 | Registrar Control de Calidad | `registrarControlCalidad(parteDiario, unidadesRechazadas, motivo)` | `registrarControlCalidad(parteDiario, unidadesRechazadas, motivo)` | `ControlDeCalidad`, `OrdenDeProduccion` |
| CU-PROD-15 | Cerrar Orden de Producción | `cerrarOrdenProduccion(orden, unidadesFinales)` | `cerrarOrdenProduccion(orden, unidadesFinales)` | `OrdenDeProduccion` |
| CU-PROD-16 | Alta de Etapa de Producción | `altaEtapa(nombre, descripcion)` | `altaEtapa(nombre, descripcion)` | `Etapa` |
| CU-PROD-17 | Baja de Etapa de Producción | `bajaEtapa(etapa)` | — | `Etapa` |
| CU-PROD-18 | Modificación de Etapa de Producción | `modificarEtapa(etapa, nombre, descripcion)` | `modificarEtapa(etapa, nombre, descripcion)` | `Etapa` |
| CU-PROD-19 | Listado de Etapas de Producción | `solicitarListadoEtapas()` | — | `Etapa` (consulta) |
| CU-PROD-20 | Buscar Etapa de Producción | `buscarEtapa(criterio)` | — | `Etapa` (consulta) |
| CU-PROD-21 | Asignar Etapa a Producto | `asignarEtapaProducto(producto, etapa, tiempoPorUnidad)` | `asignarEtapaProducto(producto, etapa, tiempoPorUnidad)` | `AsignacionDeEtapa`, `Articulo`, `Etapa` |
| CU-PROD-22 | Desasignar Etapa de Producto | `desasignarEtapaProducto(producto, etapa)` | `desasignarEtapaProducto(producto, etapa)` | `AsignacionDeEtapa`, `Articulo`, `Etapa` |
| CU-PROD-23 | Gestionar Cronograma de Producción | `gestionarCronograma(orden, nuevaPosicion)` | `gestionarCronograma(orden, nuevaPosicion)` | `OrdenDeProduccion` (ordenamiento; vista Gantt excluida del dominio) |
| CU-PROD-24 | Alta de Operario | `altaOperario(nombre, apellido, especialidad)` | `altaOperario(nombre, apellido, especialidad)` | `Operario` |
| CU-PROD-25 | Baja de Operario | `bajaOperario(operario)` | — | `Operario` |
| CU-PROD-26 | Modificación de Operario | `modificarOperario(operario, nombre, apellido, especialidad)` | `modificarOperario(operario, nombre, apellido, especialidad)` | `Operario` |
| CU-PROD-27 | Listado de Operarios | `solicitarListadoOperarios()` | — | `Operario` (consulta) |
| CU-PROD-28 | Buscar Operario | `buscarOperario(criterio)` | — | `Operario` (consulta) |

## Mantenimiento

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-MANT-01 | Alta de Maquinaria | `altaMaquinaria(nombre, tipo)` | `altaMaquinaria(nombre, tipo)` | `Maquinaria` |
| CU-MANT-02 | Baja de Maquinaria | `bajaMaquinaria(maquinaria)` | — | `Maquinaria` |
| CU-MANT-03 | Modificación de Maquinaria | `modificarMaquinaria(maquinaria, nombre, tipo)` | `modificarMaquinaria(maquinaria, nombre, tipo)` | `Maquinaria` |
| CU-MANT-04 | Listado de Maquinarias | `solicitarListadoMaquinarias()` | — | `Maquinaria` (consulta) |
| CU-MANT-05 | Buscar Maquinaria | `buscarMaquinaria(criterio)` | — | `Maquinaria` (consulta) |
| CU-MANT-06 | Alta de Plan de Mantenimiento | `altaPlanMantenimiento(maquinaria, umbralUso, descripcion)` | `altaPlanMantenimiento(maquinaria, umbralUso, descripcion)` | `PlanDeMantenimiento`, `Maquinaria` |
| CU-MANT-07 | Baja de Plan de Mantenimiento | `bajaPlanMantenimiento(plan)` | — | `PlanDeMantenimiento` |
| CU-MANT-08 | Modificación de Plan de Mantenimiento | `modificarPlanMantenimiento(plan, umbralUso, descripcion)` | `modificarPlanMantenimiento(plan, umbralUso, descripcion)` | `PlanDeMantenimiento` |
| CU-MANT-09 | Listado de Planes de Mantenimiento | `solicitarListadoPlanesMantenimiento()` | — | `PlanDeMantenimiento` (consulta) |
| CU-MANT-10 | Buscar Plan de Mantenimiento | `buscarPlanMantenimiento(criterio)` | — | `PlanDeMantenimiento` (consulta) |
| CU-MANT-11 | Registrar Mantenimiento | `registrarMantenimiento(maquinaria, fecha, tipo, observaciones)` | `registrarMantenimiento(maquinaria, fecha, tipo, observaciones)` | `IntervencionDeMantenimiento`, `Maquinaria` |

## Análisis Predictivo

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-PRED-01 | Ejecutar Reentrenamiento del Modelo | `iniciarReentrenamiento()` (RelojSistema→Sistema, flujo automático) | `iniciarReentrenamiento()` | `EventoDeAuditoria`, `Usuario` (alerta técnica por timeout en excepción: `Alerta`) |
| CU-PRED-02 | Recalcular Tiempo de Producción | `recalcularTiempoProduccion(datosAvance)` (ComponentePredictivo→Sistema, flujo automático reactivo a CU-PROD-13) | `recalcularTiempoProduccion(datosAvance)` | `TasaDeRendimiento`, `Etapa`, `Alerta`, `OrdenDeProduccion` |
| CU-PRED-03 | Evaluar Riesgo de Suministro | `evaluarRiesgoSuministro()` (RelojSistema→Sistema, flujo automático) | `evaluarRiesgoSuministro()` | `SolicitudDeCotizacion` (estado → Borrador), `LineaSolicitudCotizacion`, `Proveedor`, `Articulo`, `Alerta` |
| CU-PRED-04 | Simular escenario de producción | `simularEscenarioProduccion(articulo, cantidad, fechaLimite)` | `simularEscenarioProduccion(articulo, cantidad, fechaLimite)` | `EscenarioDeSimulacion`, `Articulo` |
| CU-PRED-05 | Consultar Tablero Predictivo | `consultarTableroPredictivo(); filtrarTableroPredictivo(criterio)` | — | `Alerta`, `TasaDeRendimiento` (indicadores y alertas del tablero); cronograma proyectado: vista excluida del dominio |

## Reportes y Auditoría

| CU | Nombre del CU | Operación(es) del sistema (del DSS) | Contrato (firma exacta o "—") | Conceptos de dominio afectados |
| --- | --- | --- | --- | --- |
| CU-REP-01 | Generar Reportes | `generarReporte(tipoReporte, rangoFechas, parametros)` | — | — (Reporte: vista/salida excluida del dominio) |
| CU-AUD-01 | Consultar Registro de Auditoría | `consultarRegistroAuditoria(criterios, rangoFechas)` | — | `EventoDeAuditoria` (consulta) |

## Resumen

| Módulo | CU | Operaciones DSS | Contratos | Cobertura (contratos/CU) |
| --- | --- | --- | --- | --- |
| Inventario | 8 | 8 | 4 | 50,0 % |
| Compras | 20 | 23 | 12 | 60,0 % |
| Usuarios y Seguridad | 7 | 8 | 5 | 71,4 % |
| Producción | 28 | 28 | 16 | 57,1 % |
| Mantenimiento | 11 | 11 | 5 | 45,5 % |
| Análisis Predictivo | 5 | 6 | 4 | 80,0 % |
| Reportes y Auditoría | 2 | 2 | 0 | 0,0 % |
| **Total** | **81** | **86** | **46** | **56,8 %** |

## Hallazgos de trazabilidad (con resolución)

1. **`Incidencia` sin operación que la cree (resuelto, H-039).** Ningún contrato creaba una instancia de `Incidencia`; solo aparecía como insumo en las Notas del contrato CU-COM-11 (informe multicriterio) y como concepto consultado en CU-COM-18 (ranking, sin contrato), dejando al ranking sin fuente de datos contratada. Se resolvió declarando la creación automática de `Incidencia` en la postcondición del contrato CU-INV-06 (`registrarIngresoMateriales`): por cada línea cuya entrega llegó con retraso respecto de `Cotizacion.fechaPrometida` o con cantidad faltante respecto de la OC, se crea la instancia (tipo "Retraso" o "Faltante", con `fecha` y `detalle`) asociada al `Proveedor` de la orden (ver Notas del contrato).
2. **Anulaciones con regla de negocio sin contrato (resuelto, H-040).** `anularSolicitudCotizacion` (CU-COM-07) y `anularOrdenCompra` (CU-COM-12) transicionan estado del dominio ("Anulada") y la regla "la anulación se bloquea si fue adjudicada" (`SolicitudDeCotizacion.estado`, `04-atributos.md`) quedaba sin formalizar en ningún contrato. Se resolvió con los contratos `anularSolicitudCotizacion(idSolicitudCotizacion, motivo)` y `anularOrdenCompra(idOrdenCompra, motivo)`, que declaran la precondición de estado y la postcondición de transición.
3. **CU-PROD-22 (Desasignar Etapa de Producto) sin contrato aunque rompe una asociación (resuelto, H-041).** La operación `desasignarEtapaProducto(producto, etapa)` destruye la instancia `AsignacionDeEtapa` (rotura de asociación, postcondición contractual según la plantilla). Se resolvió con el contrato `desasignarEtapaProducto(producto, etapa)`, incluyendo el bloqueo cuando existen órdenes en curso que usan esa hoja de ruta.
4. **`ParametroGlobal` sin operación que lo cree (resuelto por decisión, H-042).** CU-USR-07 solo modifica `ParametroGlobal.valor`; ninguna operación contratada crea el parámetro (su precondición exige que ya exista). Se documentó en la nota de alcance de `contratos/usuarios-seguridad.md` que la creación inicial se realiza como carga inicial de configuración en la puesta en marcha del sistema (fuera de los casos de uso); CU-USR-07 solo ajusta sus valores.
5. **Bajas lógicas que transicionan estado sin contrato (informativo, decisión de plantilla).** `bajaArticulo` (INV-02), `bajaProveedor` (COM-02), `inhabilitarUsuario` (USR-02), `anularOrdenProduccion` (PROD-07), `bajaPlanificacion` (PROD-02), `bajaEtapa` (PROD-17), `bajaOperario` (PROD-25), `bajaMaquinaria` (MANT-02) y `bajaPlanMantenimiento` (MANT-07) modifican el estado del dominio sin contrato; coherente con la regla "bajas triviales sin contrato" de `_plantilla-contrato.md`, pero el cambio de estado queda declarado solo en Notas de otros contratos.
6. **Verificación positiva de trazabilidad vertical.** Los 46 contratos tienen su firma exacta en el DSS correspondiente (0 contratos sin DSS). Las operaciones sin contrato son consultas/listados/búsquedas o bajas (Solo DSS, regla Larman); ninguna operación de alta, modificación o anulación con regla de negocio quedó sin contratar.