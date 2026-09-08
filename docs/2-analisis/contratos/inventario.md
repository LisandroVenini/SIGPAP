# Contratos de Operación — Inventario

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/2-analisis/DSS/inventario.md`
> **Plantilla:** `docs/2-analisis/contratos/_plantilla-contrato.md`

Se documentan solo las operaciones del sistema con regla de negocio (altas, modificaciones y flujos críticos). Los ABM triviales (Baja de Artículo, Listado de Artículos, Buscar Artículo) no llevan contrato.

## CU-INV-01 — Alta de Artículo

| Campo | Contenido |
| --- | --- |
| **Nombre** | `altaArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)` |
| **Responsabilidades** | Registrar un nuevo artículo en el catálogo, dejando su stock físico inicial en cero. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-INV-01 |
| **Notas** | El atributo que captura la denominación en el Modelo de Dominio es `descripcion` (el CU lo llama "Nombre"). El artículo nace en estado "Activo" (el CU-INV-01 lo deja disponible para recibir ingresos, ajustes o parametrizaciones); la transición a "Inactivo" es la baja lógica (CU-INV-02, sin contrato). Si el artículo es Producto Terminado, su hoja de ruta se define en Producción (CU-PROD-21), no aquí. |
| **Excepciones** | Si el `codigoSKU` ingresado ya pertenece a otro `Articulo`, se cancela el guardado y se informa el error al actor. |
| **Salida** | Mensaje de éxito (`confirmacionAlta()`) y listado de artículos actualizado. |
| **Precondiciones** | El `EncargadoDeStock` está autenticado. No existe un `Articulo` con el `codigoSKU` ingresado. |
| **Postcondiciones** | Se creó una instancia de `Articulo` (con `codigoSKU`, `descripcion` y `tipo` asignados). Se estableció `Articulo.stockFisico` en cero. Se inicializó `Articulo.estado` en "Activo". Se asoció el `Articulo` a una `UnidadDeMedida`. |

## CU-INV-03 — Modificación de Artículo

| Campo | Contenido |
| --- | --- |
| **Nombre** | `modificarArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)` |
| **Responsabilidades** | Actualizar los datos editables de un artículo existente sin alterar su stock ni sus reservas. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-INV-03 |
| **Notas** | `codigoSKU` actúa como identificador del artículo y no se modifica en la secuencia normal. Solo se modifican los campos editables (`descripcion`, `tipo`, `unidadDeMedida`); `Articulo.stockFisico`, las `Reserva` asociadas y `Articulo.estado` permanecen inalterados (el propio CU lo garantiza y no contempla transiciones de estado en la modificación). |
| **Excepciones** | Si el `codigoSKU` resultante pertenece a otro `Articulo`, se cancela el guardado y se informa el error al actor (la secuencia normal no contempla modificar el SKU). |
| **Salida** | Mensaje de éxito (`confirmacionModificacion()`). |
| **Precondiciones** | Existe un `Articulo` con el `codigoSKU` indicado. |
| **Postcondiciones** | Se modificó `Articulo.descripcion`. Se modificó `Articulo.tipo`. Se asoció el `Articulo` a la `UnidadDeMedida` indicada (si fue modificada). |

## CU-INV-06 — Registrar ingreso de materiales

| Campo | Contenido |
| --- | --- |
| **Nombre** | `registrarIngresoMateriales(numeroRemito, numeroOC, lineasDeRecepcion)` |
| **Responsabilidades** | Registrar la recepción física de mercancía contra una Orden de Compra, actualizar el stock, cerrar la orden y registrar el desempeño de entrega del proveedor. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-INV-06 |
| **Notas** | `numeroOC` referencia la orden con su identificador natural (`OrdenDeCompra.numeroOC`); el proveedor se deriva de la orden. `lineasDeRecepcion` agrupa, por cada artículo recibido, la cantidad real. El recálculo del tiempo de entrega real del proveedor (paso 6 del CU) se respalda en `DesempenoProveedor` (`tiempoEntregaReal` y `fechaRegistro`), que registra la entrega concreta de esta recepción. La creación de `Incidencia` es automática en esta operación: al detectarse retraso respecto de `Cotizacion.fechaPrometida` o cantidad faltante respecto de la OC, el sistema la registra sin requerir una operación ni un caso de uso adicional (el CU-INV-06 y el glosario la respaldan: "se registra automáticamente y alimenta el ranking"). |
| **Excepciones** | Si la cantidad recibida es menor que la esperada en la `OrdenDeCompra`, se emite una alerta no restrictiva y se consulta si marcar el ingreso como "Entrega Parcial". Si la cantidad excede lo solicitado, se bloquea el procesamiento y se exige confirmación del actor para asentar el excedente. |
| **Salida** | Mensaje de éxito (`confirmacionIngreso()`) y, según el caso, alerta de entrega parcial o solicitud de confirmación por excedente. |
| **Precondiciones** | Existen los `Articulo` del ingreso en el catálogo. Existe una `OrdenDeCompra` (identificada por su `numeroOC`) en estado Emitida o Confirmada, asociada a un `Proveedor`. |
| **Postcondiciones** | Se incrementó `Articulo.stockFisico` por cada artículo recibido. Se modificó `OrdenDeCompra.estado` a "Recibida" (con la variante "Entrega Parcial" cuando la recepción llega con menos cantidad). Se creó una instancia de `MovimientoDeStock` (tipo "ingreso") por cada línea de recepción, con su `numeroRemito` y `cantidad`. Se asoció cada `MovimientoDeStock` al `Articulo` correspondiente y a la `OrdenDeCompra`. Se creó una instancia de `DesempenoProveedor` con `tiempoEntregaReal` (días transcurridos entre la confirmación y la recepción) y `fechaRegistro` asignados, asociada al `Proveedor` de la `OrdenDeCompra`. Por cada línea de recepción cuya entrega llegó con retraso respecto de `Cotizacion.fechaPrometida` o con cantidad faltante respecto de la `OrdenDeCompra`, se creó una instancia de `Incidencia` con `tipo` ("Retraso" o "Faltante"), `fecha` y `detalle` asignados, asociada al `Proveedor` de la `OrdenDeCompra`. |

## CU-INV-07 — Registrar Ajuste de Inventario

| Campo | Contenido |
| --- | --- |
| **Nombre** | `registrarAjusteInventario(codigoSKU, nuevoStockObservado, motivo)` |
| **Responsabilidades** | Corregir manualmente las existencias reales de un artículo (mermas, roturas o auditoría), dejando un registro inmutable del ajuste. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-INV-07 |
| **Notas** | El `MovimientoDeStock` resultante es de tipo "ajuste" y conserva el `motivo` registrado. |
| **Excepciones** | Si el ajuste es un decremento mayor que el `Articulo.stockFisico` actual (lo que daría stock negativo), se rechaza la operación, se informa el error y se exige revisar el conteo. |
| **Salida** | Mensaje de éxito (`confirmacionAjuste()`) o error de stock negativo. |
| **Precondiciones** | Existe un `Articulo` con el `codigoSKU` indicado. |
| **Postcondiciones** | Se modificó `Articulo.stockFisico` (sobrescrito con `nuevoStockObservado`). Se creó una instancia de `MovimientoDeStock` de tipo "ajuste" con su `cantidad` (la diferencia) y su `motivo`. Se asoció el `MovimientoDeStock` al `Articulo`. |

## CU-INV-08 — Parametrizar Umbrales de Stock

| Campo | Contenido |
| --- | --- |
| **Nombre** | `parametrizarUmbralesStock(codigoSKU, stockMinimo, stockMaximo)` |
| **Responsabilidades** | Definir o modificar los límites mínimos y máximos tolerables de stock de un artículo. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | CU-INV-08 |
| **Notas** | Estos umbrales son el insumo de las alertas automáticas de reposición (proceso automático, no caso de uso). |
| **Excepciones** | Si el `stockMinimo` ingresado es mayor que el `stockMaximo`, se bloquea el guardado y se informa la incoherencia lógica, exigiendo corregirla. |
| **Salida** | Mensaje de éxito (`confirmacionUmbrales()`) o error de incoherencia entre umbrales. |
| **Precondiciones** | Existe un `Articulo` con el `codigoSKU` indicado. |
| **Postcondiciones** | Se modificó `Articulo.stockMinimo`. Se modificó `Articulo.stockMaximo`. |
