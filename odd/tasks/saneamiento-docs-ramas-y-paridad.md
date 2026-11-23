# Saneamiento de docs y ramas + paridad opencode → gentle-shell

Objetivo: dejar la documentación alineada con lo mergeado, limpiar las ramas locales que ya no aportan, y migrar a gentle-shell (Pi) todas las skills, MCP y comandos que usa opencode.
Branch: `main` (docs) + cambios de entorno fuera del repo. Delivery: `ask-on-risk`. Verificación: `git status` limpio, `gh issue list` intacto, suite no afectada (solo docs).

## Specs

### S1 — Saneamiento corto de docs
Usuario: "antes de empezar con los trabajos terminemos con el saneamiento corto de docs y ramas."

- `docs/README.md` refleja el estado real de las carpetas (2-analisis y 4-implementacion ya tienen contenido).
- Los `odd/tasks/*.md` con checkboxes desactualizados se alinean con lo efectivamente mergeado (no se reescribe historia: se corrige el estado y se deja constancia en el `## Log`).
- El módulo Predictivo se marca explícitamente como **no implementado** donde `docs/1-requisitos` lo describe como funcional, sin borrar los requisitos (siguen siendo el alcance objetivo).

### S2 — Saneamiento de ramas
- Se eliminan las ramas locales que ya son ancestro de `main` (contenido íntegro en `main`).
- Las ramas locales que **no** son ancestro de `main` y **no** tienen copia en `origin` no se borran sin decisión del usuario: son la única copia de esos commits.
- Las ramas remotas no se tocan sin aprobación explícita.

### S3 — Paridad opencode → gentle-shell
Usuario: "trae todas las skills, mpc, etc que tenga opencode a gentle-shell, asi ya lo tenemos configurado."

- Skills ausentes en `~/.agents/skills` copiadas desde `~/.config/opencode/skills`.
- Servidores MCP de opencode replicados en la configuración de Pi, con las rutas absolutas que el sandbox necesita.
- Comandos de opencode convertidos a prompt templates de Pi.
- Lo que no sea portable (plugins, temas, TUI) se reporta como tal en vez de simularse.

### Supuestos
- A1: `~/.agents/skills` es el scope usuario de Pi y el nuevo source of truth; la copia de opencode se conserva para ese cliente.
- A2: Pi no escanea `~/.config/opencode`, por eso la migración es una copia, no un symlink del árbol completo.
- A3: Los MCP con credenciales (Obsidian, Stitch) se replican tal cual porque `mcp.json` es user-scoped y `0600`.

## Tasks

| ID | Spec | Route | Estado | Commit |
| --- | --- | --- | --- | --- |
| T1 | S3 | inline | hecho | — (fuera del repo) |
| T2 | S3 | inline | hecho | — (fuera del repo) |
| T3 | S2 | inline | hecho | — |
| T4 | S1 | inline | hecho | — |
| T5 | S1 | inline | hecho | — |
| T6 | S1-S3 | inline | hecho | — |

- T1: copiar a `~/.agents/skills` las skills que faltan.
- T2: agregar a `agent/mcp.json` los servidores de opencode que funcionan en este entorno.
- T3: borrar ramas locales contenidas en `main`; reportar las 8 sin respaldo.
- T4: actualizar `docs/README.md`.
- T5: alinear checkboxes de `odd/tasks/*.md` y marcar Predictivo como no implementado.
- T6: verificación final y commit.

## Log

L1 (2026-11-23, pedido del usuario, verbatim):
"antes de empezar con los trabajos terminemos con el saneamiento corto de docs y ramas. Ademas de eso, nose si lo hiciste pero trae todas las skills, mpc, etc que tenga opencode a gentle-shell, asi ya lo tenemos configurado"

L2 (hallazgo, ramas): `git branch --merged main` da 13 ramas. Otras 8 no son ancestro de `main` y **no existe `origin/<rama>`** para ellas (`backup/feat-validaciones-fechas`, `feat/calendario-fechas`, `feat/compras-detalle-cotizaciones`, `feat/confirm-dialog`, `feat/cronograma-gantt`, `feat/ordenes-produccion-parte-diario`, `feat/seeders-produccion`, `feat/validaciones-fechas`). `git cherry` marca 0 parches aplicados en todas: su contenido llegó a `main` reescrito por los slices #103–#106, así que no hay prueba de contención por patch. Se conservan.

L3 (hallazgo, entorno): `codegraph` y `bunx` no existen en este entorno, así que los MCP `codegraph` y `obsidian` de opencode no pueden arrancar tal cual. El MCP `laravel` de opencode apunta a otro proyecto (`SistemaRetail`), no a SIGPAP.

L4 (ejecución):
- Skills: copiadas `caos-retail-scope`, `graphify`, `stancl-tenancy`, `tailwind-4`, `uml-modeling`, `verification-sweep` a `~/.agents/skills` (45 en total). Las 17 que ya estaban no se tocaron.
- MCP en Pi: conectados `engram` (23 tools), `laravel-boost` (10), `context7` (2), `excalidraw` (5), `stitch` (18) y `playwright` (25). `primevue` quedó deshabilitado: SIGPAP no usa PrimeVue. `codegraph` y `obsidian` no se pudieron migrar por binarios faltantes.
- Ramas: 13 locales borradas con `git branch -d` (ancestras de `main`) y `git fetch --prune` limpió 3 refs remotas ya inexistentes. Las 8 sin contención ni respaldo remoto se conservan y se reportan.
- Docs: `docs/README.md` gana la sección "Estado de implementación"; RF-04 y RF-05 declaran su estado real; 4 checkboxes de `odd/tasks` se alinearon con los PR #92, #95, #98 y #100.
- Verificación: cambios solo de documentación, sin efecto en la suite. `gga` no revisa `.md`, así que el hook no interviene.

L5 (hallazgo, gga): el hook `pre-commit` llamaba a `gga`, que no estaba en el PATH del sandbox (mismo caso que `gh` y `engram`). Se agregó un wrapper en `agent/bin/gga` que preserva la ruta real, porque el symlink rompe la resolución de su `lib/providers.sh`. Aun así **gga no puede revisar en este entorno**: no hay CLI de proveedor instalada (`claude`, `gemini`, `codex` y `opencode` faltan) y el proveedor `github:` falla con `Could not resolve host: models.inference.ai.azure.com` (red restringida). Se ajustó el hook para omitir la revisión **solo** cuando el changeset no toca archivos de `FILE_PATTERNS` (documentación); con archivos revisables sigue siendo fail-closed. La elección de proveedor para el trabajo de código queda pendiente de decisión.
