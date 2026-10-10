# Módulo Auditoría

## Operatoria

Garantiza la seguridad, transparencia y trazabilidad de los datos sensibles manipulados por los usuarios del sistema.

## Alcance (validado)

Auditoría tiene un único caso de uso de interacción (consulta). El resto de sus responsabilidades son **procesos automáticos y transversales** del sistema, no casos de uso.

## Funcionalidades y pasos

### F1 — Consultar registro de auditoría (CU-AUD-01)

1. El Administrador o Gerente solicita acceder al registro de auditoría (log del sistema).
2. El sistema despliega una grilla con los eventos críticos ordenados cronológicamente (Fecha, Hora, IP, Usuario, Acción).
3. El usuario ingresa parámetros de búsqueda (ej. "acciones del Encargado de Stock" entre dos fechas).
4. El sistema procesa los filtros y actualiza el listado.
5. Excepción: sin resultados → "No se encontraron registros para los criterios especificados".

### Procesos automáticos y transversales (no son casos de uso)

- **Registro de Eventos Críticos**: captura de usuario, fecha, hora e IP en operaciones clave (aprobación de compras, cierres de producción). Se dispara automáticamente; no hay actor que "registre" la auditoría.
- **Trazabilidad de Estados (Old vs. New)**: almacenamiento del estado anterior y el nuevo valor de un registro modificado para investigar alteraciones.
- **Borrado Lógico (Soft Deletes)**: política transversal — ninguna entidad se elimina físicamente; las bajas (artículo, proveedor, usuario, etc.) conservan el historial recuperable.

## Implementación

Se implementa mediante la librería [Laravel-activitylog (Spatie)](https://spatie.be/docs/laravel-activitylog/v5/introduction).

## Cruces con otros módulos

- **Todos los módulos**: el registro de eventos se dispara desde las operaciones clave de Producción (cierre de OP), Compras (adjudicación, resoluciones DSS), Usuarios (cambios de rol), etc.
- **Componente Predictivo**: sus decisiones automáticas (resoluciones DSS) también se registran y son visibles en la auditoría.
- **Usuarios/Seguridad**: el módulo soporte gestiona los roles, y Auditoría registra quién cambió qué.

## Observaciones de la facultad (a aplicar en los casos de uso)

1. **Nomenclatura consistente**: "Consultar Registro de Auditoría" ya es descriptivo y consistente.
2. No aplica desglose ABM (no hay entidad "Gestionar" en este módulo; solo consulta).
