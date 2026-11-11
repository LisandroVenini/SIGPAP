# Seeders de datos — módulo Producción

Objetivo: poblar la base con un dataset demo realista del módulo Producción, idempotente y aditivo, para ejercitar todas las pantallas (dashboard, órdenes, parte diario, cronograma, mantenimiento).
Branch: `feat/seeders-produccion`. Delivery: `ask-on-risk`. Tests: `php artisan test --compact`.

## Specs

### S1 — Alcanzar el módulo con datos realistas
Usuario: "ahora lo que quiero es que migremos la base de datos por modulos... Empecemos por el modulo de produccion, quiero que nos aseguremos de cubrir al menos 3 o 4 frentes de cada seccion."

- `InventarioBaseSeeder`: insumos con stock (maderas, cola, barniz, herrajes, tela, espuma) y los cuatro productos terminados fabricados que tienen hoja de ruta.
- `ProduccionSeeder`: etapas, operarios, maquinarias, planificaciones, hoja de ruta con recetas, órdenes, recursos, partes, calidad, prioridades y mantenimiento.
- Los datos demo se crean una sola vez y no se pisan: los artículos conservan el stock que producción ya consumió.

### S2 — Cobertura por sección (4 frentes)
- Etapas: seis etapas reales, una con descripción y una eliminada lógica.
- Operarios: cinco activos con especialidad y uno eliminado lógico.
- Maquinarias: disponibles, en mantenimiento y bloqueada, con desgaste alto y bajo.
- Planificaciones: semana pasada, actual, próxima y una sin órdenes.
- Órdenes: los siete estados presentes, con y sin recursos, con entrega vencida y holgada, con y sin partes.
- Hoja de ruta y recetas: rutas de 2, 3, 4 y 5 etapas; recetas de 4-5 componentes.
- Parte diario: varias fechas, con y sin máquina, con observaciones, progreso real por etapa.
- Calidad y cierre: control con unidades rechazadas, orden lista para cerrar y dos órdenes cerradas.
- Cronograma y prioridad: prioridades 1..N, reordenamientos registrados y al menos un conflicto de entrega.
- Mantenimiento: tres planes (uno por máquina, único) y cuatro intervenciones históricas.

### S3 — Idempotencia y seguridad de datos
- Nunca se trunca ni se borra: solo se crea lo que falta.
- Etapas se resuelven por `lower(nombre)` porque el índice único es case-insensitive y la base puede tener `lijado` en minúsculas.
- Órdenes se resuelven por `(articulo_id, cantidad_requerida)`, único por diseño en este dataset.
- Partes, reservas y calidad se generan a través de los servicios del módulo, respetando estado En Curso, etapa de la ruta, máquina asignada y tope de cantidad.
- Guard `app()->environment(['local', 'testing'])`: en producción no hace nada.

### Supuestos
- A1: los seeders son on-demand (`db:seed --class=...`), no se enganchan al `DatabaseSeeder`.
- A2: las fechas son relativas a `now()` para que el cronograma y el "hoy" sigan teniendo sentido.
- A3: el cierre y los cambios de prioridad se hacen a través de `GestionCierreService` y `GestionCronogramaService`, igual que la UI, para que queden auditados y consistentes; la semilla solo registra partes y calidad antes.

## Tasks

| ID | Spec | Route | Status | Commit |
| --- | --- | --- | --- | --- |
| T1 | S1, S3 | inline | done | — |
| T2 | S1, S2 | inline | done | — |
| T3 | S2, S3 | inline | done | — |
| T4 | S2 | inline | done | — |

- T1: `InventarioBaseSeeder` (catálogo mínimo con stock y productos fabricados).
- T2: `ProduccionSeeder` (catálogos, hoja de ruta, órdenes y recursos).
- T3: `ProduccionSeeder` (partes, calidad, prioridades y mantenimiento) con servicios.
- T4: `ProduccionSeederTest` (idempotencia, estados, invariantes y conflicto de entrega).

## Log

L1 (2026-11-11, pedido del usuario, verbatim):
"ahora lo que quiero es que migremos la base de datos por modulos, asi nose aseguramos de hacer bien las cosas.
Empecemos por el modulo de produccion, quiero que nos aseguremos de cubrir al menos 3 o 4 frentes de cada seccion. Presentame el plan"

L2 (corrección del alcance): "Me confundi en mi pedido. Me referia a que queria que hagamos seeders para llenar la base de datos".

L3 (decisión del usuario): "On-demand, con guard local (Recomendado)". No se engancha al `DatabaseSeeder`.

L4 (cierre): implementado. `php artisan db:seed --class="App\Modules\Produccion\Database\Seeders\ProduccionSeeder"` crea 15 órdenes nuevas (18 en total con las previas), 6 etapas, 5 operarios, 4 máquinas, 4 planificaciones, 4 rutas, 18 partes, 5 controles y 9 registros de prioridad. Segunda corrida con conteos idénticos (18/18/10/9). Tests: 4 nuevos, suite completa en verde.

L5 (cierre tras revisión): el hook `gga` marcó violaciones de contrato entre módulos y de capas. Se corrigió: `ProduccionSeeder` ya no toca `Articulo`, `Reserva` ni `Usuario`; usa `GestionArticulosService::porSku`, `GestionReservasService::cubierta` y `UsuariosService::buscarPorEmail`. El usuario demo vive en `Usuarios/Database/Seeders/SupervisorDemoSeeder`. El cierre usa `GestionCierreService::cerrar` y las prioridades `GestionCronogramaService::reordenar` (con guarda de idempotencia). Los catálogos son create-only para no pisar datos reales. Se agregaron tests propios para `porSku` y `buscarPorEmail`. Suite completa: 625/625.
