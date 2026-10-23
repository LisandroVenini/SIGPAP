## Resumen

<!-- Qué hace este PR y por qué. 1-3 bullets. -->

## Tipo de cambio

- [ ] Feature (`type:feature`)
- [ ] Bug fix (`type:bug`)
- [ ] Documentación (`type:docs`)
- [ ] Refactor (`type:refactor`)
- [ ] Mantenimiento/tooling (`type:chore`)
- [ ] Breaking change (`type:breaking-change`)

## Issue relacionado

<!--
`Closes #N`  → el PR completa la issue (se cierra al mergear y el tablero pasa a Done).
`Refs #N`    → el PR es parte de una issue más grande (no la cierra).
Omitir si es un cambio fundacional sin issue.
-->

Closes #
Refs #

## Cambios

| Archivo | Cambio |
| --- | --- |
| `path` | Qué cambió |

## Checklist de arquitectura (AGENTS.md)

- [ ] Monolito modular (`app/Modules/<Modulo>/`), sin microservicios
- [ ] Sin capa Repository ni Interface de repositorio
- [ ] Sin API JSON pública ni SPA separada (Inertia + Vue 3)
- [ ] Comunicación entre módulos solo por el Service público
- [ ] `Alerta` emitida por el subsistema transversal
- [ ] `ParametroGlobal`: lectura transversal, escritura solo Administrador
- [ ] Sin `dd` / `var_dump` / `console.log` / secretos residuales

## Checklist de base de datos (si aplica)

- [ ] Migraciones nuevas (no se editan migraciones ya aplicadas)
- [ ] Tablas `snake_case` plural en español y `$table` explícito
- [ ] FKs con `ON DELETE RESTRICT`
- [ ] Enums como `string` + PHP backed enum

## Checklist de pruebas (AGENTS.md)

- [ ] Todo cambio de comportamiento incluye tests (PHPUnit / pytest / Vitest)
- [ ] Estructura AAA (Arrange-Act-Assert)
- [ ] Tests de integración para contratos entre módulos
- [ ] No se borraron tests existentes

## Plan de pruebas

- [ ] `./vendor/bin/pint --test`
- [ ] `php artisan test`
- [ ] `npm run typecheck`
- [ ] `npm run lint:js`
- [ ] `npm run test:js`
- [ ] `npm run build`

## Notas / Riesgos

<!-- Riesgos, migraciones pendientes, breaking changes, etc. -->
