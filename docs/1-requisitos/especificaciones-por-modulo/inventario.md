# Módulo Inventario

## Operatoria

Este módulo garantiza que la información del capital inmovilizado esté siempre actualizada, diferenciando claramente el inventario físico del inventario comprometido para la producción, y alertando sobre riesgos de abastecimiento.

## Entidades del dominio

- **Artículo**: unidad central del catálogo. Puede ser Materia Prima, Insumo Consumible o Producto Terminado. Soporta múltiples unidades de medida y factores de conversión.
- **Stock físico real**: existencias efectivamente en depósito.
- **Stock comprometido (reserva lógica)**: cantidad asignada a órdenes de producción en curso.
- **Stock disponible**: stock físico − stock comprometido. Es el que se usa para decidir si una nueva orden puede lanzarse.

## Modelo de stock

```
Stock Disponible = Stock Físico Real − Stock Comprometido (Reservas Lógicas)
```

| Momento | Efecto en el stock |
| --- | --- |
| Alta de artículo | Stock físico inicial en cero |
| Ingreso de mercancía (F2) | Incrementa stock físico real |
| Lanzamiento de orden (Producción) | Incrementa stock comprometido (reserva) |
| Carga de Parte Diario (Producción) | Decrementa stock físico + libera reserva |
| Ajuste de inventario (F3) | Sobrescribe stock físico real |
| Baja de artículo | Baja lógica, conserva historial |

## Decisiones de negocio (validadas)

1. **Alertas de reposición y caducidad no son casos de uso**: son procesos automáticos del sistema (sin actor que los dispare). Se documentan como requisito funcional/regla de negocio, no como CU.
2. **Las reservas lógicas son automáticas**: las genera Producción al lanzar una orden. Inventario no tiene un CU que "genere reservas", pero sí debe mostrar el stock disponible vs comprometido en sus consultas.
3. **Producto Terminado en Inventario**: el catálogo de artículos (incluido Producto Terminado) vive en Inventario; la hoja de ruta de etapas pertenece a Producción.
4. **Alta de producto sin etapas**: al dar de alta un Producto Terminado no se capturan etapas; se definen después en Producción (CU "Definir Hoja de Ruta del Producto"), porque el dato lo conoce el Supervisor de Producción, no el Encargado de Stock.

## Funcionalidades y pasos

### F1 — Gestión del catálogo de artículos (CU-INV-01)

1. El Encargado de Stock da de alta un artículo: Código SKU, nombre, tipo (Materia Prima, Insumo, Producto Terminado) y unidad de medida.
2. El sistema valida que el SKU sea único y registra con stock físico inicial en cero.
3. El listado de artículos muestra el stock físico, el stock comprometido y el stock disponible de cada artículo.
4. Permite modificar y dar de baja lógica (conservando historial).
5. Si el artículo es un Producto Terminado, el alta **no** captura etapas de producción: la hoja de ruta (etapas + tiempos) se define luego en Producción mediante el CU "Definir Hoja de Ruta del Producto".

### F2 — Registrar ingreso de materiales (CU-INV-02)

1. El Encargado de Stock recibe mercancía y selecciona la Orden de Compra correspondiente.
2. Ingresa el número de remito y la cantidad real recibida por artículo.
3. El sistema valida contra la OC, incrementa el stock físico y actualiza la OC a "Recibida".
4. El sistema recalcula la métrica de tiempo de entrega real del proveedor (insumo para Predictivo).
5. Excepciones: entrega parcial (alerta no restrictiva) y excedente (requiere confirmación).

### F3 — Registrar ajuste de inventario (CU-INV-03)

1. El Encargado de Stock registra una corrección manual: artículo, nuevo stock observado y motivo (rotura, auditoría, merma).
2. El sistema calcula la diferencia, sobrescribe el stock físico y guarda un registro inmutable del ajuste.
3. Excepción: no permite stock negativo (rechaza decrementos mayores al físico disponible).

### F4 — Parametrizar umbrales de stock (CU-INV-04)

1. El Encargado de Stock define el stock mínimo y máximo de un artículo.
2. El sistema valida que mínimo ≤ máximo.
3. Estos umbrales son el insumo de las alertas automáticas de reposición.

### Procesos automáticos (no son casos de uso)

- **Alerta de reposición**: cuando el stock disponible desciende por debajo del mínimo, el sistema notifica proactivamente al Encargado de Stock.
- **Alerta de caducidad**: el sistema notifica insumos próximos a su fecha de vencimiento.
- **Generación de reservas**: al lanzar una orden de producción, el sistema reserva lógicamente los insumos (stock comprometido).

## Cruces con otros módulos

- **Producción**: consume stock físico y libera reservas al cargar Partes Diarios; la hoja de ruta de etapas de los productos vive en Producción.
- **Compras**: el ingreso de materiales (F2) referencia Órdenes de Compra y actualiza las métricas del proveedor.
- **Análisis Predictivo**: el stock físico, las reservas y los tiempos de entrega reales alimentan la evaluación de riesgo de suministro (CU-PRED-03).

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Reestructuración de ABM**: "Gestionar Catálogo de Artículos" (CU-INV-01) se desglosa en Alta, Baja, Modificación, Listado y Buscar.
2. **Nomenclatura consistente**: nombres descriptivos y uniformes.
