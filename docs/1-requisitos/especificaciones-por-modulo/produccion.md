# Módulo Producción

## Operatoria

El módulo de Producción centraliza el ciclo de fabricación orientándolo a la planificación y el control gerencial. Desde la planificación de campaña hasta el cierre de cada orden, el sistema registra los avances mediante cargas centralizadas (Parte Diario), eliminando la fricción operativa en la planta de máquinas y garantizando datos limpios para el análisis predictivo.

## Jerarquía del dominio

```
Planificación de Producción (campaña, ej. "sept-nov 2026")
   └── Órdenes de Producción (OP)           ← una planificación contiene N OPs
          └── Productos                      ← cada OP define sus productos y cantidades
                 └── Asignación Etapa↔Producto ← qué etapas tiene cada producto y su tiempo por unidad
                        └── Parte Diario     ← registro de avance por etapa
```

### Etapas de producción

Existe un **catálogo de Etapas de Producción** (entidad con ABM): Lijado, Pintado, Armado, Embalado, etc.

Cada producto define su hoja de ruta **asignando** etapas del catálogo y, por cada asignación, su **tiempo estimado por unidad**. Dos productos pueden compartir la misma etapa con distinto tiempo: la silla asigna "Lijado" con 5 minutos, la mesa asigna "Lijado" con 20.

El tiempo estimado por unidad de cada asignación etapa↔producto es el insumo de:
- El **Cronograma de Producción (Gantt gerencial)**.
- Los **procesos automatizados** de la propuesta (ajuste predictivo de tasa de rendimiento, simulador de pedidos urgentes).

## Flujo de estados de la Orden de Producción

```
Pendiente ──asignar recursos──▶ Asignada ──iniciar──▶ En Curso ──cerrar──▶Cerrada
     │                            │
     ├─▶ Pendiente por Insumos    └─▶ (Partes Diarios recurrentes)
     └─▶ Anulada
```

| Estado                | Transición que lo produce                                   |
| --------------------- | ----------------------------------------------------------- |
| Pendiente             | Creación de la orden (F2)                                   |
| Pendiente por Insumos | Creación de la orden con stock insuficiente (F2, excepción) |
| Asignada              | Asignación de recursos (F3)                                 |
| En Curso              | Inicio explícito de la orden (F4)                           |
| Cerrada               | Cierre explícito de la orden (F7)                           |
| Anulada               | Anulación de la orden (F2, excepción)                       |

## Decisiones de negocio (validadas)

1. **Inicio y cierre explícitos**: el supervisor inicia la orden (`Asignada → En Curso`) y la cierra (`En Curso → Cerrada`) con acciones manuales.
2. **Consumo de stock en dos momentos**: al crear la orden el stock se *compromete* (reserva lógica); al cargar cada Parte Diario se *consume* del stock físico (libera reserva + baja existencias).
3. **Calidad como prerrequisito del cierre**: no se puede cerrar sin la inspección de calidad de la última etapa.
4. **Catálogo de etapas + asignación con tiempo**: existe un catálogo de Etapas de Producción (entidad con ABM); cada producto asigna etapas del catálogo definiendo su tiempo por unidad. La etapa es genérica, el tiempo es por asignación producto↔etapa.
5. **Hoja de ruta en Producción**: el alta del Producto Terminado (en Inventario) no captura etapas; se definen aquí en Producción (F2), donde el Supervisor conoce los tiempos.
6. **Asignación de recursos unificada**: el Supervisor asigna operarios y maquinaria en una única operación. La desasignación de un recurso es un flujo de modificación dentro de esa misma operación, no un caso de uso aparte.
7. **Gestión de operarios en Producción**: los operarios son una entidad propia (ABM en Producción); las maquinarias, en cambio, se gestionan en el subdominio de Mantenimiento.

## Funcionalidades y pasos

### F1 — Gestión de planificaciones (ABM)

El Supervisor gestiona las planificaciones de producción (campañas). Incluye alta, baja, modificación, listado y búsqueda. La fecha de fin debe ser posterior a la de inicio.

### F2 — Gestión del catálogo de etapas de producción (ABM, nuevo)

El Supervisor gestiona las etapas de producción del catálogo (nombre, descripción). Incluye alta, baja, modificación, listado y búsqueda.

### F3 — Gestión de operarios (ABM, nuevo)

El Supervisor gestiona a los operarios de la planta (nombre, datos de contacto, especialidad). Incluye alta, baja, modificación, listado y búsqueda. Los operarios son necesarios para la asignación de recursos de las órdenes.

### F4 — Asignar etapa a producto (nuevo)

1. El Supervisor selecciona un Producto Terminado del catálogo.
2. El sistema muestra las etapas ya asignadas al producto y su tiempo por unidad.
3. El Supervisor asigna una etapa del catálogo y define su tiempo estimado por unidad.
4. El sistema valida los tiempos y guarda la asignación.

### F5 — Desasignar etapa de producto (nuevo)

1. El Supervisor selecciona un Producto Terminado.
2. El sistema muestra las etapas asignadas.
3. El Supervisor desasigna una etapa del producto.
4. El sistema valida que no haya órdenes en curso usando esa hoja de ruta y quita la etapa.

### F6 — Creación de orden de producción

1. El Supervisor selecciona el producto, la cantidad requerida y la fecha de entrega esperada.
2. Vincula la orden a una planificación activa.
3. El sistema instancia la hoja de ruta (etapas asignadas al producto) y calcula los tiempos estimados iniciales a partir del tiempo por unidad de cada etapa.
4. El sistema verifica el stock disponible (descontando reservas previas):
   - Si alcanza → estado `Pendiente`, insumos comprometidos (reserva lógica).
   - Si no alcanza → estado `Pendiente por Insumos` + alerta al módulo de Compras.

### F7 — Asignación de recursos

1. El Supervisor asigna operarios responsables y maquinaria para cada etapa de la hoja de ruta.
2. El sistema valida la disponibilidad: una maquinaria bloqueada por mantenimiento no puede seleccionarse.
3. La orden pasa a estado `Asignada`.

### F8 — Iniciar orden (nuevo)

1. El Supervisor inicia explícitamente una orden en estado `Asignada`.
2. El sistema confirma que los recursos están asignados y pasa la orden a `En Curso`.

### F9 — Carga de Parte Diario

1. Al finalizar el turno, el Supervisor registra la cantidad de unidades completadas por etapa.
2. El sistema acumula el avance de la orden y consume el stock físico de insumos.
3. El sistema incrementa el desgaste acumulado de la maquinaria utilizada.
4. El sistema dispara una petición asíncrona al Microservicio Predictivo para recalcular la Tasa de Rendimiento Real (TRR).

### F10 — Control de calidad

1. El Supervisor inspecciona el lote producido y registra la cantidad de unidades rechazadas y el motivo.
2. El sistema descuenta las mermas de las unidades válidas de la orden.

### F11 — Cerrar orden (nuevo)

1. Al terminar la última etapa, el Supervisor registra las unidades producidas finales.
2. El sistema valida que exista control de calidad registrado para la última etapa (prerrequisito).
3. El sistema consolida las unidades válidas y pasa la orden a `Cerrada`.

### F12 — Gestionar Cronograma de Producción (Gantt gerencial — nuevo)

1. La Gerencia accede al cronograma dinámico de producción.
2. El sistema muestra las órdenes de producción y sus etapas ordenadas en el tiempo, calculado a partir de los tiempos por unidad de cada etapa.
3. La Gerencia visualiza qué orden de producción y qué etapas corresponden a cada día.
4. La Gerencia puede asignar y reordenar las órdenes dentro del cronograma.
5. El cronograma se actualiza dinámicamente con los datos reales de avance (Parte Diario) y las predicciones del Microservicio.

### F13 — Mantenimiento preventivo por uso (subdominio)

- Gestión de maquinarias (ABM: alta, baja, modificación, listado, búsqueda).
- Gestión de planes de mantenimiento preventivo por umbrales de uso (ABM).
- Registro de intervenciones de mantenimiento.
- El sistema acumula desgaste por volumen procesado y bloquea preventivamente la máquina al alcanzar el umbral crítico.

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Reestructuración de ABM**: cada "Gestionar [Entidad]" se desglosa en cinco casos de uso independientes: Alta, Baja, Modificación, Listado y Buscar.
2. **Nomenclatura consistente**: nombres descriptivos y uniformes en todo el sistema (ej. "Alta de Orden", "Baja de Orden").
3. **Gestionar Cronograma de Producción**: incorporado como caso de uso (F11).

## Decisiones de diseño (validadas)

- **Producto Terminado en Inventario, hoja de ruta en Producción**: el catálogo de artículos vive en Inventario; la hoja de ruta de etapas (relación producto→etapas con tiempos por unidad) pertenece a Producción. El ABM de Producto Terminado será de Inventario, y la gestión de etapas será de Producción.

## Pendientes de diseño

- Gantt integrado para toma de decisiones gerencial (depende de Análisis Predictivo y Reportes).
- Ver si implementar: evolución en base a inflación interanual.
