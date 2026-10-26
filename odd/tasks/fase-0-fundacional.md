# Fase 0 — Fundacional (Usuarios/Seguridad + Auditoría + Alerta + Shell)

> Objetivo: sentar las bases transversales antes de los módulos operativos.
> Rama: `feat/fase-0-fundacional` · Runner: `php artisan test` (PHPUnit + PostgreSQL).
> CUs relacionados: CU-USR-06 (Iniciar/Cerrar Sesión, issue #89), CU-USR-07 (Configurar Parámetros, issue #90). El `Closes` se decide al completar cada CU.

## Specs

- S1: Login/logout usando `clave_acceso`; rutas `login` (GET/POST) y `logout` (POST); `auth` redirige a login; usuario autenticado va a `/dashboard`.
- S2: `UsuariosService::autorizar(Usuario, string $operacion): bool` vía spatie `can`.
- S3: `ParametroGlobalService::parametro(string $clave): ?string` (lectura transversal; devuelve el valor, no el Model) y `definirParametro(Usuario $actor, string $clave, string $valor, string $categoria = 'General')` (solo Administrador; lanza `AuthorizationException`).
- S4: `AuditoriaService::registrarEvento(int $usuarioId, string $accion, ?array $estadoAnterior, ?array $estadoNuevo, ?string $ip): EventoDeAuditoria` (el actor va por id para no cruzar Models entre módulos, Regla 5).
- S5: `AlertaService::emitirAlerta(string $tipo, array $contexto, string $origen): Alerta` (shared kernel, único punto que persiste `Alerta`).
- S6: Seed de roles (Administrador, Gerente, Supervisor de Producción, Encargado de Stock, Encargado de Compras), usuario admin (local) y parámetros base.
- S7: App shell (sidebar + topbar + user chip + logout) y `Dashboard` placeholder con el design system Salvia & Teja.
- S8: Tests para auth, autorizar, parámetros, auditoría, alertas y dashboard.

## Tasks

- [x] T1 — Backend Usuarios/Seguridad: `UsuariosService`, `ParametroGlobalService`, seeders (roles/admin/parámetros) + tests. Arreglar `DatabaseSeeder`.
- [x] T2 — `AuditoriaService` + `AlertaService` + tests.
- [ ] T3 — Auth: controlador, rutas, página `Auth/Login` + tests.
- [ ] T4 — Layout/App shell + `Dashboard` + tests.

## Log

- L1: "vamos con la fase 0. te adjunto la skill de arquitectura que hicimos para que recurdes lo que definimos y podamos seguir esos lineamientos".
- L2: orden acordado: Fase 0 fundacional (Usuarios + Auditoría + Alerta + shell), luego Inventario → Producción, Compras → Predictivo, Reportes. Front+back en vertical slices por módulo.
- L3: T1 implementado: `UsuariosService::autorizar`, `ParametroGlobalService::{parametro,definirParametro}`, seeders de roles/admin/parámetros, `DatabaseSeeder` corregido. 25 tests / 140 assertions verdes.
- L4: T2 implementado: `AuditoriaService::registrarEvento` (actor por id) y `AlertaService::emitirAlerta` (shared kernel). Tests de ambos.
