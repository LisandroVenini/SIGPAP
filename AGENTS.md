# Reglas de Revisión — SIGPAP

> **Propósito:** este archivo es la fuente de reglas que usa `gga` (Gentleman Guardian Angel) para revisar el código antes de cada commit. También sirve como contrato de implementación para cualquier agente de IA que trabaje en el proyecto.

> **Contrato de respuesta del revisor (gga):** iniciá SIEMPRE la respuesta con `STATUS: PASSED` o `STATUS: FAILED` en la primera línea, antes de cualquier explicación. Con `STRICT_MODE=true`, una respuesta cuyo estado no aparezca en las primeras 15 líneas se rechaza como ambigua.

## Reglas de arquitectura (no negociables)

1. **Monolito modular, NO microservicios.** Todo el código vive en un único proceso Laravel, organizado en `app/Modules/<Modulo>/`. No crear servicios HTTP separados.
2. **Frontend con Inertia.js + Vue 3, NO SPA separado.** Páginas Vue en `resources/js/Pages/`, renderizadas vía Inertia desde el monolito. Sin API JSON pública, sin tokens, sin CORS, sin doble deploy.
3. **Sin capa Repository ni Interface de repositorio.** Eloquent (Active Record) ya provee persistencia. Un `Repository` que delega CRUD es un pasamanos prohibido.
4. **Sin Query Object para filtros simples.** Un filtro de una línea va en un `scope` del Model. Un Query Object solo para consultas multi-tabla o con significado de negocio.
5. **Encapsulamiento por Service público.** Un módulo llama al Service público de otro módulo. NUNCA accede al Model/Eloquent interno de otro módulo.
6. **Laravel es dueño del estado.** El componente ML (Python) solo `recibe datos → calcula → devuelve resultados`. Nunca persiste ni modifica conceptos del dominio por su cuenta.
7. **`Alerta` es shared kernel transversal.** Se persiste vía un único subsistema de alertas. Ningún módulo la crea por su cuenta.
8. **`ParametroGlobal`:** lectura transversal, escritura solo Administrador.

### Contrato de capas (enforcement obligatorio)

Cada cambio debe vivir en la capa correcta. El revisor DEBE marcar `FAILED` si no se cumple. Fuente detallada: skill `sigpap-arquitectura`.

| Necesitás… | Va en… | Prohibido en… |
| --- | --- | --- |
| Recibir HTTP, delegar y responder | `Controllers/` (thin) | lógica de negocio |
| Lógica de negocio / caso de uso | `Services/` | Controller o Model |
| Validación de entrada | `Requests/` (Form Request) | Controller |
| Persistencia / relaciones | `Models/` (Eloquent Active Record) | Repository |
| Filtro simple (una línea) | `scope` del Model | Query Object |
| Consulta multi-tabla / agregación | `Queries/` | Controller o Service |
| Calcular con ML | `MLEngine` (Python) | FastAPI / Guzzle / SQLAlchemy |
| Emitir notificación | subsistema transversal de alertas | lógica del emisor |
| Renderizar vista | página Vue en `resources/js/Pages/` vía Inertia | Blade / SPA / API pública |

**Regla del Controller thin (crítica):** un `Controller` SOLO recibe el request, delega en un `Service` y devuelve la respuesta (Inertia/redirect). NO puede contener:
- lógica de negocio ni reglas de decisión,
- transacciones (`DB::transaction`) ni transacciones implícitas,
- consultas Eloquent (`Model::where`, `firstOrFail`, `update`, `create`, `insert`),
- auditoría (`AuditoriaService`),
- validación compleja (debe ir en un Form Request).

Si el Controller tiene un `foreach` de negocio, una transacción, una query o una llamada de auditoría, es `FAILED`.

**Estructura por módulo:**
```
app/Modules/<Modulo>/
├── Controllers/   # thin: recibe HTTP, delega
├── Services/      # lógica de negocio (casos de uso)
├── Models/        # Eloquent (Active Record)
├── Queries/       # solo consultas complejas
├── Requests/      # Form Requests (validación)
├── DTOs/          # transferencia
├── Events/        # eventos de dominio (si aplica)
└── Jobs/          # background (si aplica)
```
Prohibido crear `Repositories/` o `Interfaces/` de repositorio.

**Anti-patrones (FAILED):**
- Repository pasamanos (delegar CRUD a un Repository).
- "Service hell": `findAll`/`findById`/`create`/`update`/`delete` disfrazados de Service.
- Query Object para un filtro de una línea.
- Lógica de negocio, queries, transacciones o auditoría en el Controller.
- Acceder al Model/Eloquent de otro módulo (solo su Service público).

**Comunicación entre módulos:** solo por el Service público del módulo dueño. Prohibido `ModeloDeOtroModulo::create(...)` o relaciones Eloquent cruzadas entre módulos.

**Checklist del revisor (obligatoria):** si el diff toca un `Controller`, verificá el contrato de capas anterior; si toca un `Service`, verificá que la lógica y el acceso a datos estén ahí; si hay una escritura, verificá que la validación esté en un Form Request.

## Reglas de testing (obligatorias)

1. **Todo cambio de comportamiento debe incluir sus tests.** Si se modifica o agrega un Service, un Query Object o una regla de negocio, el commit debe incluir el test unitario o de integración correspondiente. No hay excepción.
2. **Tests unitarios:** Services, Query Objects y reglas de negocio con PHPUnit (Laravel), pytest (componente ML en Python) o Vitest (componentes Vue).
3. **Tests de integración:** verificar los contratos entre módulos y la conexión a PostgreSQL.
4. **Estructura AAA** (Arrange-Act-Assert) en todo test.
5. **No borrar tests existentes** salvo que el comportamiento que cubrían deje de existir.

## Reglas generales

- Convenciones de commit: Conventional Commits, sin "Co-Authored-By" ni atribución de IA.
- Código, comentarios e identificadores en inglés. Los conceptos del dominio van en español: clases de modelo en PascalCase (`Usuario`, `OrdenDeProduccion`) y tablas en snake_case plural (`usuarios`, `ordenes_de_produccion`). El inflector de Laravel es inglés y pluraliza mal el español (`proveedors`, `cotizacions`), así que **todo model con nombre español declara `$table` explícito**; no confiar en la pluralización automática. Las migraciones ya aplicadas no se editan: todo cambio de esquema va en una migración nueva.
- No código muerto, no `var_dump`/`dd`/`console.log` residuales, no secretos en el código.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
