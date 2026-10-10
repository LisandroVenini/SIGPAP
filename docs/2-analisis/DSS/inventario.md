# Diagramas de Secuencia del Sistema (DSS) — Inventario

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/1-requisitos/casos-de-uso/inventario.md`

## Criterio de modelado

- **Caja negra inviolable:** el sistema se representa como una única línea de vida `:Sistema`. No se exponen bases de datos, controladores, clases internas ni componentes.
- **Actor:** `EncargadoDeStock` (PascalCase), único actor del módulo.
- **Eventos del sistema:** operaciones públicas con intención de negocio (camelCase), derivadas de la secuencia normal de cada caso de uso. No se modelan eventos de interfaz (`presionarBotonGuardar()`, `clicEnCrear()`) ni navegación.
- **Retornos:** solo se dibujan cuando aportan datos relevantes (listados, resultados, datos de un artículo); en los flujos de escritura se usa un retorno descriptivo (`confirmacionAlta()`, etc.).

## CU-INV-01 — Alta de Artículo

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: altaArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)
    Sistema-->>Encargado: confirmacionAlta()
```

## CU-INV-02 — Baja de Artículo

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: bajaArticulo(codigoSKU)
    Sistema-->>Encargado: confirmacionBaja()
```

## CU-INV-03 — Modificación de Artículo

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: modificarArticulo(codigoSKU, descripcion, tipo, unidadDeMedida)
    Sistema-->>Encargado: confirmacionModificacion()
```

## CU-INV-04 — Listado de Artículos

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: solicitarListadoArticulos()
    Sistema-->>Encargado: listadoArticulos(articulos)
```

## CU-INV-05 — Buscar Artículo

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: buscarArticulo(criterio)
    Sistema-->>Encargado: resultadosBusqueda(articulos)
```

## CU-INV-06 — Registrar ingreso de materiales

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: registrarIngresoMateriales(numeroRemito, numeroOC, lineasDeRecepcion)
    Sistema-->>Encargado: confirmacionIngreso()
```

## CU-INV-07 — Registrar Ajuste de Inventario

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: registrarAjusteInventario(codigoSKU, nuevoStockObservado, motivo)
    Sistema-->>Encargado: confirmacionAjuste()
```

## CU-INV-08 — Parametrizar Umbrales de Stock

```mermaid
sequenceDiagram
    actor Encargado as EncargadoDeStock
    participant Sistema as :Sistema
    Encargado->>Sistema: parametrizarUmbralesStock(codigoSKU, stockMinimo, stockMaximo)
    Sistema-->>Encargado: confirmacionUmbrales()
```
