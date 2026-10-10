# Contratos de Operación — Compras

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/2-analisis/DSS/compras.md`
> **Plantilla:** `docs/2-analisis/contratos/_plantilla-contrato.md`

## Nota de alcance

Se documentan solo las operaciones del sistema con regla de negocio (altas, modificaciones y flujos críticos). Las bajas, listados, búsquedas y la consulta de ranking (CU-COM-02, 04, 05, 09, 10, 14, 15, 18) no llevan contrato. Las anulaciones de Solicitud de Cotización y de Orden de Compra (CU-COM-07 y CU-COM-12) sí llevan contrato: transicionan `estado` a "Anulada" y su ejecución depende de una regla de bloqueo según el estado previo.

## CU-COM-01 — Alta de Proveedor

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaProveedor(razonSocial, cuit, email, telefono)` |
| **Responsabilidades** | Registrar un nuevo proveedor para habilitarlo a participar en futuras Solicitudes de Cotización y Órdenes de Compra. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-01 |
| **Notas** | El dominio modela `Proveedor.estado`; el proveedor nace "Activo" y queda habilitado para participar en futuras Solicitudes de Cotización y Órdenes de Compra. La transición a "Inactivo" es la baja lógica (CU-COM-02, sin contrato). |
| **Excepciones** | Si el `cuit` o el `email` ya existen en otro `Proveedor`, se bloquea el guardado y se informa el conflicto al actor. |
| **Salida** | Mensaje de éxito (`confirmacionAlta()`) y listado de proveedores actualizado. |
| **Precondiciones** | El `EncargadoDeCompras` está autenticado. No existe un `Proveedor` con el `cuit` ni el `email` ingresados. |
| **Postcondiciones** | Se creó una instancia de `Proveedor` con `razonSocial`, `cuit`, `email` y `telefono` asignados, y `estado` en "Activo". |

## CU-COM-03 — Modificación de Proveedor

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarProveedor(cuit, razonSocial, email, telefono)` |
| **Responsabilidades** | Actualizar los datos editables de un proveedor existente. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-03 |
| **Notas** | `cuit` actúa como identificador y no se modifica en la secuencia normal (el CU solo permite modificar Razón Social, Email o Teléfono). Esta operación no altera `Proveedor.estado`: el paso a "Inactivo" es la baja lógica (CU-COM-02). |
| **Excepciones** | Si el `email` resultante ya existe en otro `Proveedor`, se bloquea el guardado y se informa el conflicto. (Aplica también a `cuit` si llegara a modificarse). |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe un `Proveedor` con el `cuit` indicado. |
| **Postcondiciones** | Se modificó `Proveedor.razonSocial`. Se modificó `Proveedor.email`. Se modificó `Proveedor.telefono`. |

## CU-COM-06 — Alta de Solicitud de Cotización

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaSolicitudCotizacion(lineas, proveedores, fechaVencimiento)` |
| **Responsabilidades** | Consolidar una solicitud de cotización, asignarle fecha de vencimiento y distribuirla a los proveedores seleccionados mediante enlaces únicos. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-06 |
| **Notas** | `lineas` agrupa, por artículo a cotizar, la cantidad solicitada; cada línea se materializa en una `LineaSolicitudCotizacion` (clase de asociación `SolicitudDeCotizacion`-`Articulo`). La solicitud se crea en estado "Enviada" (el CU-COM-06 aprueba y envía la solicitud consolidada); el estado "Borrador" corresponde al borrador predictivo que el motor puede generar previo a este CU. Los enlaces web/token por proveedor son el mecanismo de distribución de la solicitud, sin concepto propio en el dominio. |
| **Excepciones** | Si el usuario descarta o modifica el borrador, el alta se ejecuta con los datos ingresados manualmente. |
| **Salida** | Mensaje de éxito (`confirmacionEnvio()`). |
| **Precondiciones** | Existen los `Articulo` a cotizar en el catálogo. Existen `Proveedor` activos. |
| **Postcondiciones** | Se creó una instancia de `SolicitudDeCotizacion` con `fecha` y `fechaVencimiento` asignadas, y `estado` en "Enviada". Se creó una instancia de `LineaSolicitudCotizacion` por cada línea (con `cantidadSolicitada` asignada), asociada a la `SolicitudDeCotizacion` y al `Articulo` correspondiente. Se asoció la `SolicitudDeCotizacion` a los `Proveedor` seleccionados. |

## CU-COM-08 — Modificación de Solicitud de Cotización

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarSolicitudCotizacion(idSolicitudCotizacion, lineas, proveedores, fechaVencimiento)` |
| **Responsabilidades** | Actualizar los datos de una solicitud de cotización aún no respondida por los proveedores. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-08 |
| **Notas** | Solo se modifican líneas (artículo con su cantidad), proveedores o fecha de vencimiento de una solicitud aún editable. `lineas` agrupa, por artículo, la cantidad solicitada editada. `idSolicitudCotizacion` es identificador operativo del evento; en el Modelo de Dominio la solicitud se identifica por su identidad de concepto, sin requerir un atributo explícito. La modificación no altera `SolicitudDeCotizacion.estado`. |
| **Excepciones** | Si la solicitud ya recibió respuestas de proveedores, se bloquea la modificación y se informa al actor. |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe una `SolicitudDeCotizacion` en estado editable (Borrador o Enviada) sin `Cotizacion`es cargadas. |
| **Postcondiciones** | Se modificó `SolicitudDeCotizacion.fechaVencimiento` (si fue editada). Se crearon, modificaron o eliminaron `LineaSolicitudCotizacion` según lo editado, con `cantidadSolicitada` asignada y asociadas a la `SolicitudDeCotizacion` y al `Articulo` correspondiente. Se modificaron las asociaciones de la `SolicitudDeCotizacion` con `Proveedor` según lo editado. |

## CU-COM-11 — Alta de Orden de Compra

| Campo | Contenido |
| --- | --- |
| **Nombre** | `emitirOrdenCompra(idSolicitudCotizacion, proveedor, lineas)` |
| **Responsabilidades** | Emitir la Orden de Compra oficial para el proveedor ganador, a partir de la adjudicación de una cotización. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-11 |
| **Notas** | El "Informe Multicriterio" es una salida derivada (retorno `informeMulticriterio()`), no un concepto del dominio. La fórmula (precio / tiempo de entrega / ranking) se apoya en `Cotizacion.precioUnitario`, `Cotizacion.fechaPrometida`, el desempeño histórico (`DesempenoProveedor`) y las `Incidencia`. `lineas` agrupa, por artículo, la cantidad y el `precioPactado` de la orden; en el flujo de adjudicación se derivan de la `Cotizacion` ganadora y en el flujo manual (OC sin licitación, `idSolicitudCotizacion` no aplica) las ingresa el usuario. `numeroOC` es el identificador natural que se asigna a la orden emitida. |
| **Excepciones** | Si el usuario no acepta la sugerencia, selecciona manualmente otro proveedor y la `OrdenDeCompra` se emite para el proveedor elegido. |
| **Salida** | Mensaje de éxito (`confirmacionEmision()`) y envío del correo de confirmación al proveedor. |
| **Precondiciones** | Existe una `SolicitudDeCotizacion` cerrada con `Cotizacion`es cargadas (Flujo B). Existe un `Proveedor` activo. |
| **Postcondiciones** | Se creó una instancia de `OrdenDeCompra` con `numeroOC` y `fechaEmision` asignados, y `estado` en "Emitida". Se asoció la `OrdenDeCompra` al `Proveedor` ganador y a la `Cotizacion` adjudicada. Se marcó `Cotizacion.adjudicada` en verdadero en la cotización ganadora. Se creó una instancia de `LineaOrdenCompra` por cada línea (con `cantidad` y `precioPactado` asignados), asociada a la `OrdenDeCompra` y al `Articulo` correspondiente. Si la orden se emite adjudicando una licitación, se modificó `SolicitudDeCotizacion.estado` a "Adjudicada". |

## CU-COM-13 — Modificación de Orden de Compra

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarOrdenCompra(idOrdenCompra, lineas)` |
| **Responsabilidades** | Actualizar las líneas (artículos, cantidades y, si se renegocia, precios pactados) de una Orden de Compra aún no confirmada por el proveedor. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-13 |
| **Notas** | Solo se modifican líneas de la orden (el CU-COM-13 contempla artículos o cantidades). `lineas` agrupa, por artículo, la cantidad a adquirir (y el `precioPactado` cuando se renegocia). `idOrdenCompra` es identificador operativo del evento; la identidad natural del concepto es `OrdenDeCompra.numeroOC`, que no se modifica en esta operación. El respaldo de la asociación orden-artículo es `LineaOrdenCompra` (clase de asociación `OrdenDeCompra`-`Articulo`). |
| **Excepciones** | Si la Orden de Compra ya fue confirmada por el proveedor, se bloquea la modificación y se informa al actor. |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe una `OrdenDeCompra` en estado "Emitida" (no confirmada), identificada por su `numeroOC`. |
| **Postcondiciones** | Se modificaron las `LineaOrdenCompra` de la `OrdenDeCompra` según lo editado (artículo, `cantidad` y/o `precioPactado`), manteniendo su asociación a la `OrdenDeCompra` y al `Articulo` correspondiente. Se eliminaron las `LineaOrdenCompra` de artículos quitados y se crearon las de artículos agregados. Se mantuvo `OrdenDeCompra.numeroOC`. |

## CU-COM-16 — Enviar Cotización

| Campo | Contenido |
| --- | --- |
| **Nombre** | `enviarCotizacion(token, precioUnitario, fechaPrometida, cantidadOfertada)` |
| **Responsabilidades** | Registrar la oferta comercial del proveedor (precio, fecha prometida y cantidad) y vincularla a la solicitud original, cerrando el enlace. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-16 |
| **Notas** | Se reutiliza para Flujo A (disponibilidad directa) y Flujo B (cotización). En Flujo A la `Cotizacion` se origina sobre una `SolicitudDeDisponibilidad`; en Flujo B sobre una `SolicitudDeCotizacion` (ambas asociaciones están modeladas en el dominio). "Tiempo Estimado de Entrega" se modela como `Cotizacion.fechaPrometida`; la "disponibilidad" informada en Flujo A se respalda en `Cotizacion.cantidadOfertada`. Cada `Cotizacion` refiere el `Articulo` concreto cotizado (asociación `Cotizacion`-`Articulo`). |
| **Excepciones** | Si el token ya fue utilizado o la fecha límite expiró, se deniega el acceso. Si se omiten precios de artículos obligatorios, se exige completar el formulario. |
| **Salida** | Mensaje de confirmación (`confirmacionRecepcion()`) y notificación interna al `EncargadoDeCompras`. |
| **Precondiciones** | El `Proveedor` accede con un enlace válido. La `SolicitudDeCotizacion` o `SolicitudDeDisponibilidad` sigue abierta. |
| **Postcondiciones** | Se creó una instancia de `Cotizacion` con `precioUnitario`, `fechaPrometida` y `cantidadOfertada` asignados. Se asoció la `Cotizacion` al `Proveedor` y al `Articulo` cotizado. Se asoció la `Cotizacion` a la `SolicitudDeCotizacion` (Flujo B) o a la `SolicitudDeDisponibilidad` (Flujo A). |

## CU-COM-17 — Confirmar Orden de Compra

| Campo | Contenido |
| --- | --- |
| **Nombre** | `confirmarOrdenCompra(token)` |
| **Responsabilidades** | Registrar la aceptación formal del proveedor y poner la Orden de Compra en curso. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-17 |
| **Notas** | El "reloj de entrega" (fecha de confirmación) marca el inicio del tiempo de entrega real; el registro completo se materializa en `DesempenoProveedor` al recibir la mercancía (CU-INV-06), con `tiempoEntregaReal` (días entre la confirmación y la recepción) y `fechaRegistro`. En esta operación solo se registra la confirmación de la orden y el estado resultante. |
| **Excepciones** | Si el proveedor rechaza el pedido, `OrdenDeCompra.estado` cambia a "Rechazada" y se dispara una alerta urgente al `EncargadoDeCompras`. |
| **Salida** | Mensaje de éxito (`ordenConfirmada()`). |
| **Precondiciones** | Existe una `OrdenDeCompra` en estado "Emitida" asociada a un `Proveedor`. |
| **Postcondiciones** | Se modificó `OrdenDeCompra.estado` a "Confirmada". |

## CU-COM-19 — Solicitar Disponibilidad a Proveedor

| Campo | Contenido |
| --- | --- |
| **Nombre** | `solicitarDisponibilidad(articulo, cantidad, proveedor)` |
| **Responsabilidades** | Generar y enviar al proveedor una consulta de disponibilidad directa (Flujo A). |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-19 |
| **Notas** | La detección de stock bajo es un proceso automático del sistema, no un caso de uso; este CU lo acciona el actor. La consulta nace en estado "Enviada" y queda a la espera de la respuesta del proveedor (CU-COM-16); el "estado En espera de respuesta" del CU-COM-19 describe ese estado inicial. |
| **Excepciones** | Si el `Proveedor` seleccionado no tiene `email` registrado, se bloquea el envío y se informa al actor. Si el usuario elige otro proveedor del ranking, se emite para ese. |
| **Salida** | Mensaje de éxito (`confirmacionEnvio()`) y notificación "En espera de respuesta". |
| **Precondiciones** | Existe un `Articulo` con stock bajo (`stockFisico` por debajo de `stockMinimo`). Existe un `Proveedor` con `email` registrado. |
| **Postcondiciones** | Se creó una instancia de `SolicitudDeDisponibilidad` con `fecha`, `cantidadSolicitada` y `estado` ("Enviada") asignados. Se asoció la `SolicitudDeDisponibilidad` al `Proveedor` y al `Articulo`. |

## CU-COM-20 — Resolver Disponibilidad de Compra

| Campo | Contenido |
| --- | --- |
| **Nombre** | `resolverDisponibilidad(respuestaProveedor, umbralTolerancia)` |
| **Responsabilidades** | Evaluar la respuesta del proveedor contra el umbral de tolerancia y el cronograma, y recomendar una acción al Encargado de Compras. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-20 |
| **Notas** | El `ComponentePredictivo` evalúa y recomienda, nunca emite la Orden de Compra (la emite el `EncargadoDeCompras` vía CU-COM-11). El "escenario" resultante (Ideal, Sin Stock, Variación Fuerte) es una salida derivada, no un concepto persistido. `umbralTolerancia` corresponde a un `ParametroGlobal`. La respuesta evaluada es la `Cotizacion` que el proveedor originó sobre la `SolicitudDeDisponibilidad` (Flujo A, CU-COM-16), asociación modelada en el dominio. Al evaluarla, la consulta pasa a "Respondida". |
| **Excepciones** | Escenario "Sin Stock": se descarta al proveedor para esta compra y se dispara la consulta al siguiente proveedor del ranking. Escenario "Variación Fuerte": se frena el proceso y se alerta al `EncargadoDeCompras`. |
| **Salida** | `recomendacion(escenario)` presentada al `EncargadoDeCompras`. |
| **Precondiciones** | Existe una `SolicitudDeDisponibilidad` con respuesta de disponibilidad cargada por el `Proveedor` (CU-COM-16). |
| **Postcondiciones** | Se modificó `SolicitudDeDisponibilidad.estado` a "Respondida". En el flujo normal (escenario Ideal) no se crea ni modifica ninguna otra instancia persistida: la recomendación es una salida derivada presentada al actor. En el escenario "Variación Fuerte" se creó una instancia de `Alerta` asociada al `Articulo` consultado. |

## CU-COM-07 — Anular Solicitud de Cotización

| Campo | Contenido |
| --- | --- |
| **Nombre** | `anularSolicitudCotizacion(idSolicitudCotizacion, motivo)` |
| **Responsabilidades** | Anular una solicitud de cotización que aún no fue adjudicada, conservando su historial. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-07 |
| **Notas** | La regla de negocio "la anulación se bloquea si fue adjudicada" está declarada en `SolicitudDeCotizacion.estado` (04-atributos.md). La anulación no elimina el registro: solo transiciona el estado. |
| **Excepciones** | Si la solicitud ya fue adjudicada, el sistema bloquea la anulación e informa al actor. |
| **Salida** | `confirmacionAnulacion()`. |
| **Precondiciones** | Existe una `SolicitudDeCotizacion` y no fue adjudicada (estado ≠ "Adjudicada"). |
| **Postcondiciones** | Se modificó `SolicitudDeCotizacion.estado` a "Anulada". |

## CU-COM-12 — Anular Orden de Compra

| Campo | Contenido |
| --- | --- |
| **Nombre** | `anularOrdenCompra(idOrdenCompra, motivo)` |
| **Responsabilidades** | Anular una Orden de Compra que aún no fue confirmada por el proveedor. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-COM-12 |
| **Notas** | La anulación aplica solo mientras la OC está en estado "Emitida"; una vez confirmada (CU-COM-17) ya no es anulable, se rechaza o se gestiona la entrega. |
| **Excepciones** | Si la OC ya fue confirmada por el proveedor, el sistema bloquea la anulación e informa al actor. |
| **Salida** | `confirmacionAnulacion()`. |
| **Precondiciones** | Existe una `OrdenDeCompra` en estado "Emitida" (identificada por `numeroOC`). |
| **Postcondiciones** | Se modificó `OrdenDeCompra.estado` a "Anulada". |
