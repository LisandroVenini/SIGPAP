# Diagramas de Secuencia del Sistema (DSS) — Mantenimiento

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente vinculante:** `docs/1-requisitos/casos-de-uso/produccion.md` (sección "Subdominio Mantenimiento")

## Criterio de modelado

- **Caja negra inviolable:** el sistema se representa como una única línea de vida `:Sistema`. No se exponen bases de datos, controladores, clases internas ni componentes.
- **Actor único:** `SupervisorDeProduccion` (PascalCase), único actor del subdominio.
- **Eventos del sistema:** operaciones públicas con intención de negocio (camelCase), derivadas de la secuencia normal de cada caso de uso. No se modelan eventos de interfaz (`presionarBoton()`, `clicEnCrear()`) ni navegación.
- **Retornos:** solo se dibujan cuando aportan datos relevantes (listados, resultados de búsqueda); en los flujos de escritura se usa un retorno descriptivo (`confirmacionAlta()`, etc.).

## CU-MANT-01 — Alta de Maquinaria

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: altaMaquinaria(nombre, tipo)
    Sistema-->>Supervisor: confirmacionAlta()
```

## CU-MANT-02 — Baja de Maquinaria

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: bajaMaquinaria(maquinaria)
    Sistema-->>Supervisor: confirmacionBaja()
```

## CU-MANT-03 — Modificación de Maquinaria

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: modificarMaquinaria(maquinaria, nombre, tipo)
    Sistema-->>Supervisor: confirmacionModificacion()
```

## CU-MANT-04 — Listado de Maquinarias

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: solicitarListadoMaquinarias()
    Sistema-->>Supervisor: listadoMaquinarias(maquinarias)
```

## CU-MANT-05 — Buscar Maquinaria

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: buscarMaquinaria(criterio)
    Sistema-->>Supervisor: resultadosBusqueda(maquinarias)
```

## CU-MANT-06 — Alta de Plan de Mantenimiento

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: altaPlanMantenimiento(maquinaria, umbralUso, descripcion)
    Sistema-->>Supervisor: confirmacionAlta()
```

## CU-MANT-07 — Baja de Plan de Mantenimiento

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: bajaPlanMantenimiento(plan)
    Sistema-->>Supervisor: confirmacionBaja()
```

## CU-MANT-08 — Modificación de Plan de Mantenimiento

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: modificarPlanMantenimiento(plan, umbralUso, descripcion)
    Sistema-->>Supervisor: confirmacionModificacion()
```

## CU-MANT-09 — Listado de Planes de Mantenimiento

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: solicitarListadoPlanesMantenimiento()
    Sistema-->>Supervisor: listadoPlanesMantenimiento(planes)
```

## CU-MANT-10 — Buscar Plan de Mantenimiento

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: buscarPlanMantenimiento(criterio)
    Sistema-->>Supervisor: resultadosBusqueda(planes)
```

## CU-MANT-11 — Registrar Mantenimiento

```mermaid
sequenceDiagram
    actor Supervisor as SupervisorDeProduccion
    participant Sistema as :Sistema
    Supervisor->>Sistema: registrarMantenimiento(maquinaria, fecha, tipo, observaciones)
    Sistema-->>Supervisor: confirmacionMantenimiento()
```
