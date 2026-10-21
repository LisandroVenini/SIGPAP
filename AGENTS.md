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

## Reglas de testing (obligatorias)

1. **Todo cambio de comportamiento debe incluir sus tests.** Si se modifica o agrega un Service, un Query Object o una regla de negocio, el commit debe incluir el test unitario o de integración correspondiente. No hay excepción.
2. **Tests unitarios:** Services, Query Objects y reglas de negocio con PHPUnit (Laravel), pytest (componente ML en Python) o Vitest (componentes Vue).
3. **Tests de integración:** verificar los contratos entre módulos y la conexión a PostgreSQL.
4. **Estructura AAA** (Arrange-Act-Assert) en todo test.
5. **No borrar tests existentes** salvo que el comportamiento que cubrían deje de existir.

## Reglas generales

- Convenciones de commit: Conventional Commits, sin "Co-Authored-By" ni atribución de IA.
- Código, comentarios e identificadores en inglés (salvo conceptos del dominio, que siguen el modelo en PascalCase español).
- No código muerto, no `var_dump`/`dd`/`console.log` residuales, no secretos en el código.
