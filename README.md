# SIGPAP

Sistema de Gestión de Producción y Análisis Predictivo (Confort y Muebles, Posadas).

## Stack

- Backend: Laravel 13 (PHP 8.3)
- Frontend: Inertia.js + Vue 3 + TypeScript
- Estilos: Tailwind CSS 4
- Base de datos: PostgreSQL 16
- Análisis predictivo: componente ML en Python (previsto)

## Requisitos

- PHP 8.3 o superior
- Composer
- Node.js 22 o superior
- PostgreSQL 16 o superior

## Puesta en marcha

```bash
cp .env.example .env
composer install
php artisan key:generate
# Crear la base de datos PostgreSQL (por ejemplo, sigpap_db)
php artisan migrate
npm install
npm run build
```

La suite de PHPUnit corre contra una base PostgreSQL separada llamada `sigpap_test`; creala antes de ejecutar los tests:

```bash
createdb -h 127.0.0.1 -U postgres sigpap_test
```

## Comandos

| Comando | Descripción |
| --- | --- |
| `composer dev` | Levanta el entorno de desarrollo de Laravel |
| `composer test` | Ejecuta la suite de PHPUnit |
| `composer lint` | Verifica el estilo PHP con Pint |
| `composer lint:fix` | Corrige el estilo PHP con Pint |
| `npm run dev` | Servidor de desarrollo de Vite |
| `npm run build` | Compila los assets para producción |
| `npm run typecheck` | Verifica los tipos de TypeScript |
| `npm run test:js` | Ejecuta los tests de Vue con Vitest |
| `npm run lint:js` | Ejecuta ESLint sobre `resources/js` |
| `npm run format` | Formatea `resources/js` y `resources/css` con Prettier |

## Estructura

- `app/Modules/<Modulo>`: módulos del monolito (Produccion, Inventario, Compras, Predictivo, Reportes, Auditoria, Usuarios)
- `app/Shared/Alertas`: shared kernel transversal de alertas
- `resources/js/Pages/<Modulo>`: páginas Vue renderizadas por Inertia

Reglas arquitectónicas clave:

- Un módulo expone su funcionalidad a través de un Service público; nunca accede al Model/Eloquent interno de otro módulo.
- No hay capa Repository ni interfaces de repositorio; Eloquent (Active Record) cubre la persistencia.

## Verificación

- `gga`: el hook pre-commit ya está instalado. Las reglas de revisión viven en `AGENTS.md`; la configuración del proveedor `opencode` está en `.gga`.
- CI: GitHub Actions ejecuta Pint, PHPUnit contra PostgreSQL, typecheck, ESLint, Vitest y build en cada push a `main` y en cada pull request.

## Documentación

- `docs/0-propuesta`
- `docs/1-requisitos`
- `docs/2-analisis`
- `docs/3-diseno`
- `docs/4-implementacion`
