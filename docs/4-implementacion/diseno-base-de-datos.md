# Diseño de Base de Datos — SIGPAP

> **Estado:** borrador revisado (post Judgment Day round 1). No es código todavía.
> **Fuente de verdad:** `docs/2-analisis/Modelo-Dominio/` (31 conceptos), `docs/3-diseno/contratos-entre-modulos.md`, `docs/0-propuesta/propuesta-de-sistema-sigpap.md`, `AGENTS.md`.

## Contexto

SIGPAP (Sistema Integrado de Gestión y Análisis Predictivo) para la empresa manufacturera "Confort y Muebles". Monolito modular Laravel 13 + Inertia/Vue 3 + PostgreSQL. Sin multi-tenancy (un solo negocio). Módulos: Produccion, Inventario, Compras, Predictivo, Reportes (vista), Auditoria, Usuarios + shared kernel `Alerta`.

## Convenciones

- Tablas en `snake_case` plural, conceptos de dominio en español. Todo Model declara `$table` explícito (el inflector de Laravel es inglés y pluraliza mal el español).
- Claves primarias `id` (bigint unsigned, auto-incremento).
- Claves foráneas `foreignId(...)->constrained()` con `ON DELETE RESTRICT` (ver "Integridad referencial").
- Enums como `string` + PHP backed enum en la capa de aplicación (no enums nativos de Postgres). **Excepción:** `alertas.tipo` es `string` libre (decisión del usuario).
- Invariantes de "exclusión de orígenes" con **CHECK** a nivel de BD (ver `cotizaciones` y `ordenes_de_compra`).
- Cantidades/precios: `decimal(12,2)`; factores de conversión: `decimal(12,6)`; tiempo por unidad: `decimal(8,2)`; métricas de rendimiento: `decimal(12,4)`.
- Estados previo/nuevo de auditoría: `jsonb`.
- Enlaces seguros del portal de proveedores: columna `token` (uuid) en los puntos de emisión (ver Proveedores).

## 1) Usuarios y Seguridad (spatie/laravel-permission)

| Tabla | Campos | Notas |
| --- | --- | --- |
| `usuarios` | id, nombre, email (unique), clave_acceso, email_verified_at?, remember_token?, estado (Activo/Inhabilitado), timestamps | editada in place: `name→nombre`, `password→clave_acceso`, + `estado`. Sin `rol_id` (spatie por pivote). |
| `roles` | id, name, guard_name, timestamps | estándar spatie (concepto `Rol`) |
| `permissions` | id, name, guard_name, timestamps | estándar spatie |
| `model_has_roles` | role_id, model_type, model_id | morph → `Usuario` |
| `model_has_permissions` | permission_id, model_type, model_id | morph → `Usuario` |
| `role_has_permissions` | role_id, permission_id | pivote rol↔permiso |
| `parametros_globales` | id, nombre (unique), valor (text), categoria | |

Notas spatie: guard único `web`, sin teams. Configurar morph map (`'usuario' => App\Modules\Usuarios\Models\Usuario::class`). `Usuario` usa el trait `HasRoles`.

## 2) Inventario

| Tabla | Campos | FK |
| --- | --- | --- |
| `unidades_de_medida` | id, nombre (unique), factor_conversion | |
| `articulos` | id, codigo_sku (unique), descripcion, tipo (Materia Prima/Insumo Consumible/Producto Terminado), origen (Fabricado/Tercero, nullable), stock_fisico, stock_minimo?, stock_maximo?, fecha_vencimiento?, estado (Activo/Inactivo), unidad_de_medida_id | `unidad_de_medida_id → unidades_de_medida` |
| `reservas` | id, articulo_id, orden_de_produccion_id, cantidad, estado (Vigente/Consumida/Liberada) | 2 FK |
| `movimientos_de_stock` | id, articulo_id, orden_de_produccion_id?, orden_de_compra_id?, tipo (Ingreso/Consumo/Salida/Ajuste), cantidad (con signo), fecha, motivo?, numero_remito? | `articulo_id`; `orden_de_produccion_id` y `orden_de_compra_id` nullable |

Notas Inventario:
- `stock_minimo`/`stock_maximo` son nullable a propósito: solo aplican a materias primas/insumos; el producto terminado no tiene umbral.
- `movimientos_de_stock` es **append-only** (inmutable): las correcciones se registran con un `MovimientoDeStock` de tipo `Ajuste`, nunca editando/borrando el original.

## 3) Producción

| Tabla | Campos | FK |
| --- | --- | --- |
| `etapas` | id, nombre (unique), descripcion? | |
| `operarios` | id, nombre, apellido, especialidad | |
| `maquinarias` | id, nombre, tipo, estado_operativo (Disponible/En Mantenimiento/Bloqueada), desgaste_acumulado | |
| `planificaciones_de_produccion` | id, nombre, fecha_inicio, fecha_fin, observaciones? | |
| `ordenes_de_produccion` | id, articulo_id, planificacion_de_produccion_id (NOT NULL), estado (Pendiente/Pendiente por Insumos/Asignada/En Curso/Cerrada/Anulada), cantidad_requerida, fecha_entrega, fecha_inicio?, fecha_fin?, cantidad_producida, prioridad_actual | `articulo_id`, `planificacion_de_produccion_id` (obligatorio) |
| `orden_de_produccion_operario` | orden_de_produccion_id, operario_id (PK compuesta) | pivote N:M |
| `orden_de_produccion_maquinaria` | orden_de_produccion_id, maquinaria_id (PK compuesta) | pivote N:M |
| `asignaciones_de_etapa` | id, articulo_id, etapa_id, tiempo_por_unidad | unique(articulo_id, etapa_id) |
| `partes_diarios` | id, orden_de_produccion_id, etapa_id, maquinaria_id?, fecha, cantidad_conformada, observaciones? | 3 FK; unique(orden_de_produccion_id, etapa_id, fecha) |
| `controles_de_calidad` | id, orden_de_produccion_id, fecha, cantidad_aprobada, cantidad_rechazada, motivo_rechazo? | |
| `planes_de_mantenimiento` | id, maquinaria_id, umbral_uso, descripcion | |
| `intervenciones_de_mantenimiento` | id, maquinaria_id, fecha, tipo, responsable, observaciones? | |
| `registros_de_prioridad` | id, orden_de_produccion_id, usuario_id, prioridad_anterior?, prioridad_nueva, fecha_cambio, motivo (catálogo), detalle? | `usuario_id → usuarios` |

Notas Producción:
- `articulo_id` = producto a fabricar (N:1). El diagrama dibuja "involucra" como N:M porque `Articulo` puede ser producto o insumo; los insumos de la orden se vinculan vía `Reserva`. Decisión explícita: la orden fabrica **un** producto.
- `unique(orden_de_produccion_id, etapa_id, fecha)` en `partes_diarios` evita duplicar avance del mismo día. **Pendiente:** si hay más de un turno por día y se necesita distinguirlos, se agrega un discriminador `turno`.

## 4) Proveedores y Compras

| Tabla | Campos | FK |
| --- | --- | --- |
| `proveedores` | id, razon_social, cuit (unique), email?, telefono?, estado (Activo/Inactivo) | |
| `solicitudes_de_disponibilidad` | id, proveedor_id, articulo_id, fecha, cantidad_solicitada, estado (Enviada/En Espera/Respondida), token? | Flujo A; `token` = enlace seguro |
| `solicitudes_de_cotizacion` | id, fecha, fecha_vencimiento, estado (Borrador/Enviada/Adjudicada/Anulada) | Flujo B (RFQ) |
| `solicitud_de_cotizacion_proveedor` | solicitud_de_cotizacion_id, proveedor_id, token? (PK compuesta) | pivote N:M; `token` por destinatario |
| `lineas_de_solicitud_de_cotizacion` | id, solicitud_de_cotizacion_id, articulo_id, cantidad_solicitada | |
| `cotizaciones` | id, solicitud_de_cotizacion_id?, solicitud_de_disponibilidad_id?, proveedor_id, fecha | CHECK: exactamente uno de los dos orígenes |
| `lineas_de_cotizacion` | id, cotizacion_id, articulo_id, precio_unitario, cantidad_ofertada, fecha_prometida, adjudicada | adjudicación por artículo |
| `ordenes_de_compra` | id, numero_oc (unique), proveedor_id, cotizacion_id?, solicitud_de_disponibilidad_id?, estado (Emitida/Confirmada/Recibida/Anulada/Rechazada), entrega_parcial, fecha_emision | CHECK: exactamente uno de los dos orígenes |
| `lineas_de_orden_de_compra` | id, orden_de_compra_id, articulo_id, cantidad, precio_pactado | |
| `incidencias` | id, proveedor_id, orden_de_compra_id?, tipo (Retraso/Faltante/Rotura), fecha, detalle | |
| `desempenos_de_proveedor` | id, proveedor_id, orden_de_compra_id?, tiempo_entrega_real, fecha_registro | |

**CHECK de exclusión de orígenes** (en `cotizaciones` y en `ordenes_de_compra`):
`(solicitud_de_cotizacion_id IS NOT NULL) != (solicitud_de_disponibilidad_id IS NOT NULL)` — exactamente uno de los dos, nunca ambos, nunca ninguno.

Notas Compras:
- `cotizaciones.fecha` = fecha de recepción de la oferta (agregada por utilidad; el dominio no la explicitaba).
- `lineas_de_cotizacion.fecha_prometida` vive **por línea** (cada artículo puede prometerse en distinta fecha).
- `adjudicada` vive en `lineas_de_cotizacion` (por artículo): permite adjudicar cada insumo de una RFQ multi-artículo al mejor oferente, coherente con la licitación multicriterio.
- `incidencias.orden_de_compra_id` y `desempenos_de_proveedor.orden_de_compra_id` (nullable) trazan cada evento a la entrega/OC concreta que miden.

## 5) Análisis Predictivo

| Tabla | Campos | FK |
| --- | --- | --- |
| `tasas_de_rendimiento` | id, etapa_id, valor, desviacion, fecha_calculo | |
| `escenarios_de_simulacion` | id, articulo_id, cantidad_ficticia, fecha_limite, prioridad, impacto_proyectado | |

## 6) Shared kernel y Auditoría

| Tabla | Campos | FK |
| --- | --- | --- |
| `alertas` | id, tipo (string libre, sin enum), fecha, descripcion, origen?, orden_de_produccion_id?, articulo_id?, maquinaria_id? | 3 FK nullable |
| `eventos_de_auditoria` | id, usuario_id, auditable_type?, auditable_id?, fecha_hora, direccion_ip, accion, estado_anterior? (jsonb), estado_nuevo? (jsonb) | `usuario_id`; polymorphic `auditable` |

Notas:
- `eventos_de_auditoria` lleva referencia polymorphic (`auditable_type`/`auditable_id`) a la entidad afectada, para que la investigación de incidentes sea consultable (no depender de parsear `accion`).
- El actor técnico sintético (`ComponentePredictivo`) se materializa como una fila de `usuarios` vía **seeder obligatorio**; `usuario_id` no es nullable.
- `alertas.tipo` libre: decisión explícita del usuario (alertas manuales sin catálogo harcodeado). Tradeoff asumido: filtrar/agrupar por tipo deja de ser confiable.

## Total

**39 tablas** = 30 conceptos con tabla propia + `roles` (spatie, concepto `Rol`) + 4 tablas spatie (`permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) + 3 pivotes N:M (`orden_de_produccion_operario`, `orden_de_produccion_maquinaria`, `solicitud_de_cotizacion_proveedor`) + `lineas_de_cotizacion`.

## Integridad referencial (regla global)

Un padre con hijos no se puede borrar. Todas las FK con `ON DELETE RESTRICT`. El borrado se rige por **5 buckets**:

| Bucket | Tablas | Comportamiento |
| --- | --- | --- |
| **Transaccional con `estado`** | `ordenes_de_produccion`, `planificaciones_de_produccion`, `solicitudes_de_disponibilidad`, `solicitudes_de_cotizacion`, `cotizaciones`, `ordenes_de_compra`, `lineas_*` | Ciclo de vida con `estado` (incl. `Anulada`) **+** `SoftDeletes` técnico. Borrado lógico de cabecera **propaga a sus líneas** (composición). |
| **Catálogo con `estado`** | `articulos`, `proveedores`, `usuarios` | Baja lógica vía `estado` (Activo/Inactivo, Inhabilitado). Sin borrado físico. |
| **Catálogo sin `estado`** | `unidades_de_medida`, `etapas`, `maquinarias`, `operarios`, `planes_de_mantenimiento` | `RESTRICT` + el Service valida que no haya hijos antes de la baja. |
| **Inmutable (append-only)** | `movimientos_de_stock`, `registros_de_prioridad`, `eventos_de_auditoria` | Sin delete de ningún tipo. Las correcciones usan movimientos de `Ajuste` / nuevos registros. |
| **Registro operativo** | `partes_diarios`, `controles_de_calidad`, `intervenciones_de_mantenimiento`, `incidencias`, `desempenos_de_proveedor`, `tasas_de_rendimiento`, `escenarios_de_simulacion`, `alertas` | `SoftDeletes` para corrección (sin `estado` de negocio). |

**Doble anulación aclarada:** `estado = Anulada` es el estado de negocio (el registro queda **visible** en reportes y Gantt); `SoftDeletes` es solo limpieza técnica (invisible). Nunca se usa SoftDeletes para anular un negocio.

**spatie:** `model_has_roles` y `role_has_permissions` en RESTRICT (no cascade) para que rol/permiso en uso no se borre.

## Decisiones resueltas

1. **`usuarios` migra in place** (renombre `name→nombre`, `password→clave_acceso`, + `estado`), sin migración nueva. **Excepción única y documentada** a la regla AGENTS.md:29 ("migraciones aplicadas no se editan"), aceptada por el usuario; requiere `migrate:fresh` (o dropear la DB local) para resincronizar el batch 1.
2. `stock_fisico` desnormalizado (contador actual) actualizado en la misma transacción que cada `MovimientoDeStock`; `stock_disponible` derivado (físico − reservado). Al ser los movimientos append-only, contador y ledger nunca divergen.
3. `cotizaciones` y `ordenes_de_compra` con doble origen **excluyente** (CHECK XOR).
4. Regla global de no-borrar-padre-con-hijos + borrado lógico por bucket (5 buckets).
5. spatie/permission para roles y permisos.
6. `articulo_id` en órdenes es N:1 (producto a fabricar); los insumos van vía `Reserva`.
7. `adjudicada` vive en `lineas_de_cotizacion` (por artículo), no en la cabecera: adjudicación granular coherente con la licitación multicriterio por insumo.
