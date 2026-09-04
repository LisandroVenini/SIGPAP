# Módulo Producción

## Operatoria

El módulo de Producción centraliza el ciclo de fabricación orientándolo a la planificación y el control gerencial. Desde la planificación de campaña hasta el cierre de cada orden, el sistema registra los avances mediante cargas centralizadas (Parte Diario), eliminando la fricción operativa en la planta de máquinas y garantizando datos limpios para el análisis predictivo.

## Jerarquía del dominio

```
Planificación de Producción (campaña, ej. "sept-nov 2026")
   └── Órdenes de Producción (OP)           ← una planificación contiene N OPs
          └── Productos                      ← cada OP define sus prod y cant
                 └── Etapas de producción    ← predefinidas por producto, con tiempo estimado por unidad
                        └── Parte Diario     ← registro de avance por etapa
```

### Etapas de producción

Cada producto define sus **propias etapas específicas** con su **tiempo estimado por unidad**. Dos productos pueden compartir el nombre de una etapa (ej. "lijado"), pero cada uno tiene su propio tiempo: una silla puede tardar 5 minutos en lijado y una mesa 20. No existe un catálogo genérico centralizado de etapas.

El tiempo estimado por unidad de cada etapa es el insumo de:
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
4. **Etapas por producto con tiempos propios**: cada producto define sus etapas y su tiempo por unidad; no hay catálogo genérico.
5. **Hoja de ruta en Producción**: el alta del Producto Terminado (en Inventario) no captura etapas; se definen aquí en Producción (F2), donde el Supervisor conoce los tiempos.

## Funcionalidades y pasos

### F1 — Planificación de producción (campaña)

1. El Supervisor crea una planificación marco: nombre de campaña, fechas estimadas de inicio y fin, observaciones.
2. El sistema valida que la fecha de fin sea posterior a la de inicio.
3. La planificación queda disponible para contener órdenes de producción.

### F2 — Definir hoja de ruta del producto (nuevo)

1. El Supervisor selecciona un Producto Terminado del catálogo (alta en Inventario) que aún no tenga hoja de ruta.
2. El sistema lista los productos terminados sin etapas definidas, para que no queden colgados.
3. El Supervisor asocia las etapas de producción del producto (ej. armado, lijado, pintado, embalado) y el tiempo estimado por unidad de cada una.
4. El sistema valida los tiempos y guarda la hoja de ruta.
5. La hoja de ruta queda disponible para que la creación de órdenes de producción (F3) la instancie.

### F3 — Creación de orden de producción

1. El Supervisor selecciona el producto, la cantidad requerida y la fecha de entrega esperada.
2. Vincula la orden a una planificación activa.
3. El sistema instancia la hoja de ruta (etapas predefinidas del producto) y calcula los tiempos estimados iniciales a partir del tiempo por unidad de cada etapa.
4. El sistema verifica el stock disponible (descontando reservas previas):
   - Si alcanza → estado `Pendiente`, insumos comprometidos (reserva lógica).
   - Si no alcanza → estado `Pendiente por Insumos` + alerta al módulo de Compras.

### F4 — Asignación de recursos

1. El Supervisor asigna operarios responsables y maquinaria para cada etapa de la hoja de ruta.
2. El sistema valida la disponibilidad: una maquinaria bloqueada por mantenimiento no puede seleccionarse.
3. La orden pasa a estado `Asignada`.

### F5 — Iniciar orden (nuevo)

1. El Supervisor inicia explícitamente una orden en estado `Asignada`.
2. El sistema confirma que los recursos están asignados y pasa la orden a `En Curso`.

### F6 — Carga de Parte Diario

1. Al finalizar el turno, el Supervisor registra la cantidad de unidades completadas por etapa.
2. El sistema acumula el avance de la orden y consume el stock físico de insumos.
3. El sistema incrementa el desgaste acumulado de la maquinaria utilizada.
4. El sistema dispara una petición asíncrona al Microservicio Predictivo para recalcular la Tasa de Rendimiento Real (TRR).

### F7 — Control de calidad

1. El Supervisor inspecciona el lote producido y registra la cantidad de unidades rechazadas y el motivo.
2. El sistema descuenta las mermas de las unidades válidas de la orden.

### F8 — Cerrar orden (nuevo)

1. Al terminar la última etapa, el Supervisor registra las unidades producidas finales.
2. El sistema valida que exista control de calidad registrado para la última etapa (prerrequisito).
3. El sistema consolida las unidades válidas y pasa la orden a `Cerrada`.

### F9 — Gestionar Cronograma de Producción (Gantt gerencial — nuevo)

1. La Gerencia accede al cronograma dinámico de producción.
2. El sistema muestra las órdenes de producción y sus etapas ordenadas en el tiempo, calculado a partir de los tiempos por unidad de cada etapa.
3. La Gerencia visualiza qué orden de producción y qué etapas corresponden a cada día.
4. La Gerencia puede asignar y reordenar las órdenes dentro del cronograma.
5. El cronograma se actualiza dinámicamente con los datos reales de avance (Parte Diario) y las predicciones del Microservicio.

### F10 — Mantenimiento preventivo por uso (subdominio)

- Gestión de maquinarias (alta, baja, modificación).
- Configuración de planes de mantenimiento preventivo por umbrales de uso (volumen procesado).
- Registro de intervenciones de mantenimiento.
- El sistema acumula desgaste por volumen procesado y bloquea preventivamente la máquina al alcanzar el umbral crítico.

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Reestructuración de ABM**: cada "Gestionar [Entidad]" se desglosa en cinco casos de uso independientes: Alta, Baja, Modificación, Listado y Buscar.
2. **Nomenclatura consistente**: nombres descriptivos y uniformes en todo el sistema (ej. "Alta de Orden", "Baja de Orden").
3. **Gestionar Cronograma de Producción**: incorporado como caso de uso (F9).

## Decisiones de diseño (validadas)

- **Producto Terminado en Inventario, hoja de ruta en Producción**: el catálogo de artículos vive en Inventario; la hoja de ruta de etapas (relación producto→etapas con tiempos por unidad) pertenece a Producción. El ABM de Producto Terminado será de Inventario, y la gestión de etapas será de Producción.

## Pendientes de diseño

- Gantt integrado para toma de decisiones gerencial (depende de Análisis Predictivo y Reportes).
- Ver si implementar: evolución en base a inflación interanual.
