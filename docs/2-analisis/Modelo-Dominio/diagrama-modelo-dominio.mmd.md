# Modelo de Dominio — Diagrama Mermaid (30 conceptos)

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente:** `01-conceptos.md`, `02-descripciones.md`, `03-relaciones.md`, `04-atributos.md`
> **Nota de uso:** Renderizar con https://mermaid.live o bloque Mermaid en Obsidian (plugin mermaid).

```mermaid
classDiagram
    %% ===================== Producción =====================
    class PlanificacionDeProduccion {
        nombre
        fechaInicio
        fechaFin
        observaciones
    }
    class OrdenDeProduccion {
        estado
        cantidadRequerida
        fechaEntrega
        fechaInicio
        cantidadProducida
    }
    class Etapa {
        nombre
        descripcion
    }
    class AsignacionDeEtapa {
        tiempoPorUnidad
    }
    class ParteDiario {
        fecha
        cantidadConformada
        observaciones
    }
    class Operario {
        nombre
        apellido
        especialidad
    }
    class Maquinaria {
        nombre
        tipo
        estadoOperativo
        desgasteAcumulado
    }
    class ControlDeCalidad {
        fecha
        cantidadAprobada
        cantidadRechazada
        motivoRechazo
    }
    class PlanDeMantenimiento {
        umbralUso
        descripcion
    }
    class IntervencionDeMantenimiento {
        fecha
        tipo
        responsable
        observaciones
    }

    %% ===================== Inventario =====================
    class Articulo {
        codigoSKU
        descripcion
        tipo
        stockFisico
        stockMinimo
        stockMaximo
        fechaVencimiento
        estado
    }
    class Reserva {
        cantidad
    }
    class MovimientoDeStock {
        tipo
        cantidad
        fecha
        motivo
        numeroRemito
    }
    class UnidadDeMedida {
        nombre
        factorConversion
    }

    %% ===================== Proveedores y Compras =====================
    class Proveedor {
        razonSocial
        cuit
        email
        telefono
        estado
    }
    class SolicitudDeDisponibilidad {
        fecha
        cantidadSolicitada
        estado
    }
    class SolicitudDeCotizacion {
        fecha
        fechaVencimiento
        estado
    }
    class Cotizacion {
        precioUnitario
        cantidadOfertada
        fechaPrometida
        adjudicada
    }
    class OrdenDeCompra {
        numeroOC
        estado
        fechaEmision
    }
    class Incidencia {
        tipo
        fecha
        detalle
    }
    class LineaSolicitudCotizacion {
        cantidadSolicitada
    }
    class LineaOrdenCompra {
        cantidad
        precioPactado
    }
    class DesempenoProveedor {
        tiempoEntregaReal
        fechaRegistro
    }

    %% ===================== Análisis Predictivo =====================
    class TasaDeRendimiento {
        valor
        desviacion
        fechaCalculo
    }
    class Alerta {
        tipo
        fecha
        descripcion
    }
    class EscenarioDeSimulacion {
        cantidadFicticia
        fechaLimite
        prioridad
        impactoProyectado
    }

    %% ===================== Auditoría =====================
    class EventoDeAuditoria {
        fechaHora
        direccionIP
        accion
        estadoAnterior
        estadoNuevo
    }

    %% ===================== Usuarios y Seguridad =====================
    class Usuario {
        nombre
        email
        claveAcceso
        estado
    }
    class Rol {
        nombre
    }
    class ParametroGlobal {
        nombre
        valor
        categoria
    }

    %% ===================== Producción =====================
    PlanificacionDeProduccion "1" --> "0..*" OrdenDeProduccion : agrupa
    Articulo "1" --> "0..*" AsignacionDeEtapa : incluye
    Etapa "1" --> "0..*" AsignacionDeEtapa : define
    OrdenDeProduccion "1" --> "0..*" ParteDiario : registra
    OrdenDeProduccion "1" --> "0..*" ControlDeCalidad : inspecciona
    Maquinaria "1" --> "0..*" IntervencionDeMantenimiento : registra
    Operario "0..*" --> "0..*" OrdenDeProduccion : asignado
    Maquinaria "0..*" --> "0..*" OrdenDeProduccion : asignada
    OrdenDeProduccion "0..*" --> "0..*" Articulo : involucra
    Etapa "1" --> "0..*" ParteDiario : conforma
    Maquinaria "0..1" --> "0..*" ParteDiario : procesa

    %% ===================== Inventario =====================
    Articulo "1" --> "0..*" MovimientoDeStock : registra
    UnidadDeMedida "1" --> "0..*" Articulo : mide
    Reserva "0..*" --> "1" Articulo : compromete
    Reserva "0..*" --> "1" OrdenDeProduccion : compromete

    %% ===================== Proveedores y Compras =====================
    Proveedor "1" --> "0..*" Incidencia : acumula
    Proveedor "1" --> "0..*" DesempenoProveedor : registra
    SolicitudDeDisponibilidad "0..*" --> "1" Proveedor : se origina sobre
    SolicitudDeDisponibilidad "0..*" --> "1" Articulo : se origina sobre
    SolicitudDeCotizacion "0..*" --> "0..*" Proveedor : se origina sobre
    SolicitudDeCotizacion "1" *-- "0..*" LineaSolicitudCotizacion : contiene
    LineaSolicitudCotizacion "0..*" --> "1" Articulo : cotiza
    Cotizacion "0..*" --> "1" SolicitudDeCotizacion : responde a
    Cotizacion "0..*" --> "0..1" SolicitudDeDisponibilidad : responde a
    Cotizacion "0..*" --> "1" Proveedor : es emitida por
    Cotizacion "0..*" --> "1" Articulo : cotiza
    OrdenDeCompra "0..*" --> "1" Proveedor : se emite a
    OrdenDeCompra "0..*" --> "0..1" Cotizacion : deriva de
    OrdenDeCompra "0..*" --> "0..1" SolicitudDeDisponibilidad : deriva de
    OrdenDeCompra "1" *-- "0..*" LineaOrdenCompra : contiene
    LineaOrdenCompra "0..*" --> "1" Articulo : compra
    MovimientoDeStock "0..*" --> "0..1" OrdenDeCompra : se origina sobre

    %% ===================== Análisis Predictivo =====================
    TasaDeRendimiento "0..*" --> "1" Etapa : se calcula sobre
    Alerta "0..*" --> "0..1" OrdenDeProduccion : se origina sobre
    Alerta "0..*" --> "0..1" Articulo : se origina sobre
    Alerta "0..*" --> "0..1" Maquinaria : se origina sobre
    EscenarioDeSimulacion "0..*" --> "1" Articulo : simula

    %% ===================== Auditoría =====================
    Usuario "1" --> "0..*" EventoDeAuditoria : genera

    %% ===================== Usuarios y Seguridad =====================
    Rol "1" --> "0..*" Usuario : asigna

    %% ===================== Mantenimiento =====================
    PlanDeMantenimiento "0..*" --> "1" Maquinaria : rige
```

## Leyenda

- **Clases:** rectángulo con dos compartimentos (Nombre PascalCase + atributos camelCase). Sin métodos, sin visibilidad, sin claves foráneas.
- **Composición (rombo relleno `*--`):** solo para las líneas de detalle con ciclo de vida acoplado a su cabecera — `LineaSolicitudCotizacion` (parte de `SolicitudDeCotizacion`) y `LineaOrdenCompra` (parte de `OrdenDeCompra`). La línea muere con su cabecera.
- **Asociación simple (`-->`):** el resto de los vínculos. Sin agregación (rombo blanco) en ningún caso.
- **Clases de asociación** (`AsignacionDeEtapa`, `LineaSolicitudCotizacion`, `LineaOrdenCompra`, `DesempenoProveedor`): se dibujan como clases normales con sus atributos, conectadas a ambos extremos que vinculan (no como rombo).
- **Multiplicidades derivadas de `03-relaciones.md`** (propuesta de lectura del modelador, no cambios al dominio):
  - *"A es un registro de B"* → B `1`, A `0..*`.
  - *"A se emite/origina sobre B"* → B `1` (o `0..1` cuando el origen es opcional, ej. `Cotizacion`→`SolicitudDeDisponibilidad`, `OrdenDeCompra`→`Cotizacion`), A `0..*`.
  - *"A se calcula/emite sobre B"* → B `1` (o `0..1` para los destinos opcionales de `Alerta`), A `0..*`.
  - *"A se vincula a B"* → el lado "catálogo" (`Etapa`, `Articulo`, `UnidadDeMedida`, `Maquinaria`) `1`, el lado transaccional `0..*`.
  - *"A es asignado a B"* → N:M (`0..*`/`0..*`) para `Operario` y `Maquinaria`; `Rol`→`Usuario` se lee como un rol `1` que cubre `0..*` usuarios.
- **`ParametroGlobal`** es un concepto autónomo (sin relaciones), como indica la nota 1 de `03-relaciones.md`.
