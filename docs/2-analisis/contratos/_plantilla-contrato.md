# Plantilla de Contrato de Operación

> **Disciplina:** Análisis · Proceso Unificado
> **Fuente:** Larman, *Applying UML and Patterns* — adaptada con campos extendidos por la cátedra.
> **Uso:** un contrato por **operación del sistema** descubierta en el DSS, solo para operaciones con reglas de negocio (altas, modificaciones y flujos críticos). Los ABM triviales (Baja, Listado, Buscar) no llevan contrato.

## Estructura

| Campo | Contenido |
| --- | --- |
| **Nombre** | Firma de la operación del sistema: `nombreOperacion(param1 tipo1, param2 tipo2)`. Debe coincidir **exactamente** con el mensaje del DSS (trazabilidad vertical). |
| **Responsabilidades** | Qué hace la operación, en lenguaje de negocio (una frase). |
| **Tipo** | `Sistema`. |
| **Referencias cruzadas** | Código(s) de caso de uso que originan la operación (ej. `CU-INV-06`). |
| **Notas** | Aclaraciones que no encajan en otro campo (opcional, `—` si no aplica). |
| **Excepciones** | Situaciones de error o de flujo alternativo y cómo se informan al actor. |
| **Salida** | Mensajes o resultados mostrados al actor como respuesta. |
| **Precondiciones** | Estado del Modelo de Dominio que se asume verdadero antes de ejecutar. |
| **Postcondiciones** | Cambios de estado **declarativos** en el Modelo de Dominio, en tiempo pasado. Solo tres tipos válidos (Larman): **creación/eliminación de instancias**, **modificación de atributos**, **formación/rotura de asociaciones**. |

## Reglas obligatorias

1. **Trazabilidad vertical estricta.** El `Nombre` es exactamente la firma del mensaje del DSS, que a su vez es la operación pública del sistema que luego recibirá un controlador en las secuencias de diseño.
2. **Caja negra.** No se describe *cómo* se implementa (sin `if/else`, sin "el sistema valida y luego guarda"): se declara *qué cambió*.
3. **Postcondiciones en pasado, declarativas.** Ejemplos válidos: "Se creó una instancia de `MovimientoDeStock`", "Se modificó `Articulo.stockFisico`", "Se asoció el movimiento al artículo". Ejemplo inválido: "El sistema valida los datos y guarda en la base de datos".
4. **Nombres de dominio.** Las postcondiciones referencian conceptos y atributos del Modelo de Dominio con su nombre exacto (`Articulo.stockFisico`, no "stock físico" suelto salvo aclaración).
5. **Excepciones ≠ implementación.** Se declara la condición de negocio y el efecto visible para el actor, no el mecanismo interno.

## Ejemplo de referencia

| Campo | Contenido |
| --- | --- |
| **Nombre** | `extraer(unMonto moneda, unaNroCuenta cadena)` |
| **Responsabilidades** | Realizar una extracción, actualizando el saldo y la disponibilidad del cajero. |
| **Tipo** | Sistema |
| **Referencias cruzadas** | Caso de uso *Extraer*. |
| **Notas** | — |
| **Excepciones** | En caso de no tener disponibilidad el cajero, indicar los valores posibles para la operación. En el caso de que el banco no confirme la operación, indicar el error. |
| **Salida** | Desplegar los mensajes provocados por las excepciones. |
| **Precondiciones** | El sistema conoce la tarjeta. |
| **Postcondiciones** | Se creó una operación. Se decrementó `Cuenta.saldo` en `unMonto`. Se creó una instancia de `Movimiento`. Se asoció el movimiento a la cuenta. |
