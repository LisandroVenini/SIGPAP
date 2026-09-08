# Diagramas de Secuencia del Sistema (DSS) — Compras

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/1-requisitos/casos-de-uso/compras.md`

## Criterio de modelado

- **Caja negra inviolable:** el sistema se representa como una única línea de vida `:Sistema`. No se exponen bases de datos, controladores, clases internas ni el motor predictivo como lifeline.
- **Actores del módulo (PascalCase):**
  - `EncargadoDeCompras` — actor humano principal.
  - `Proveedor` — actor humano externo (CU-COM-16 y CU-COM-17).
  - `MicroservicioPredictivo` — actor de sistema externo, secundario y no humano (CU-COM-20). Se modela como actor externo, nunca como lifeline interna.
- **Orden de líneas de vida:** humano(s) primero, luego el sistema externo, luego `:Sistema`.
- **Eventos del sistema:** operaciones públicas con intención de negocio (camelCase), derivadas de la secuencia normal de cada caso de uso. No se modelan eventos de interfaz (`presionarBoton()`, `clicEnEnviar()`) ni navegación.
- **Retornos:** solo se dibujan cuando aportan datos relevantes (listados, resultados, borrador predictivo, informe multicriterio, recomendación). En los flujos de escritura se usa un retorno descriptivo (`confirmacionAlta()`, `ordenConfirmada()`, etc.).
- **Datos internos como retornos:** el borrador predictivo (CU-COM-06), el informe multicriterio (CU-COM-11) y la recomendación (CU-COM-20) son RETORNOS del sistema, no eventos.

## CU-COM-01 — Alta de Proveedor

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: altaProveedor(razonSocial, cuit, email, telefono)
    Sistema-->>Encargado: confirmacionAlta()
```

## CU-COM-02 — Baja de Proveedor

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: bajaProveedor(cuit)
    Sistema-->>Encargado: confirmacionBaja()
```

## CU-COM-03 — Modificación de Proveedor

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: modificarProveedor(cuit, razonSocial, email, telefono)
    Sistema-->>Encargado: confirmacionModificacion()
```

## CU-COM-04 — Listado de Proveedores

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarListadoProveedores()
    Sistema-->>Encargado: listadoProveedores(proveedores)
```

## CU-COM-05 — Buscar Proveedor

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: buscarProveedor(criterio)
    Sistema-->>Encargado: resultadosBusqueda(proveedores)
```

## CU-COM-06 — Alta de Solicitud de Cotización

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarBorradorCotizacion()
    Sistema-->>Encargado: borradorCotizacion(articulosSugeridos, cantidades, proveedoresSugeridos)
    Encargado->>Sistema: altaSolicitudCotizacion(lineas, proveedores, fechaVencimiento)
    Sistema-->>Encargado: confirmacionEnvio()
```

## CU-COM-07 — Baja de Solicitud de Cotización

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: anularSolicitudCotizacion(idSolicitudCotizacion, motivo)
    Sistema-->>Encargado: confirmacionAnulacion()
```

## CU-COM-08 — Modificación de Solicitud de Cotización

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: modificarSolicitudCotizacion(idSolicitudCotizacion, lineas, proveedores, fechaVencimiento)
    Sistema-->>Encargado: confirmacionModificacion()
```

## CU-COM-09 — Listado de Solicitudes de Cotización

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarListadoSolicitudesCotizacion()
    Sistema-->>Encargado: listadoSolicitudesCotizacion(solicitudes)
```

## CU-COM-10 — Buscar Solicitud de Cotización

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: buscarSolicitudCotizacion(criterio)
    Sistema-->>Encargado: resultadosBusqueda(solicitudes)
```

## CU-COM-11 — Alta de Orden de Compra

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarInformeMulticriterio(idSolicitudCotizacion)
    Sistema-->>Encargado: informeMulticriterio(ofertaGanadora, alternativas)
    Encargado->>Sistema: emitirOrdenCompra(idSolicitudCotizacion, proveedor, lineas)
    Sistema-->>Encargado: confirmacionEmision()
```

## CU-COM-12 — Baja de Orden de Compra

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: anularOrdenCompra(idOrdenCompra, motivo)
    Sistema-->>Encargado: confirmacionAnulacion()
```

## CU-COM-13 — Modificación de Orden de Compra

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: modificarOrdenCompra(idOrdenCompra, lineas)
    Sistema-->>Encargado: confirmacionModificacion()
```

## CU-COM-14 — Listado de Órdenes de Compra

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarListadoOrdenesCompra()
    Sistema-->>Encargado: listadoOrdenesCompra(ordenes)
```

## CU-COM-15 — Buscar Orden de Compra

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: buscarOrdenCompra(criterio)
    Sistema-->>Encargado: resultadosBusqueda(ordenes)
```

## CU-COM-16 — Enviar Cotización

```mermaid
sequenceDiagram
    actor Proveedor as Proveedor
    participant Sistema as :Sistema
    Proveedor->>Sistema: enviarCotizacion(token, precioUnitario, fechaPrometida, cantidadOfertada)
    Sistema-->>Proveedor: confirmacionRecepcion()
```

## CU-COM-17 — Confirmar Orden de Compra

```mermaid
sequenceDiagram
    actor Proveedor as Proveedor
    participant Sistema as :Sistema
    Proveedor->>Sistema: confirmarOrdenCompra(token)
    Sistema-->>Proveedor: ordenConfirmada()
```

## CU-COM-18 — Consultar Ranking de Proveedores

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: consultarRankingProveedores()
    Sistema-->>Encargado: rankingProveedores(posiciones, metricas)
    Encargado->>Sistema: consultarDetalleProveedor(cuit)
    Sistema-->>Encargado: detalleProveedor(incidencias, puntajeRiesgo)
```

## CU-COM-19 — Solicitar Disponibilidad a Proveedor

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarDisponibilidad(articulo, cantidad, proveedor)
    Sistema-->>Encargado: confirmacionEnvio()
```

## CU-COM-20 — Resolver Disponibilidad de Compra

> **Flujo automático:** se dispara cuando el `Proveedor` carga su respuesta de disponibilidad (CU-COM-16). No hay intervención humana que lo inicie; el `EncargadoDeCompras` solo recibe la recomendación. La colaboración `:Sistema → MicroservicioPredictivo` es una interacción entre sistemas: el microservicio es un actor externo (sistema secundario), no una clase interna, por lo que la caja negra del monolito permanece intacta.

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeCompras
    actor Microservicio as MicroservicioPredictivo
    participant Sistema as :Sistema
    Sistema->>Microservicio: resolverDisponibilidad(respuestaProveedor, umbralTolerancia)
    Microservicio-->>Sistema: recomendacion(escenario)
    Sistema-->>Encargado: recomendacion(escenario)
```
