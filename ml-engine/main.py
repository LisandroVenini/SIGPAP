#!/usr/bin/env python3
"""Entry point of the predictive engine.

Reads one JSON request from stdin, dispatches it, and writes one JSON response
to stdout. Unexpected crashes exit with a non-zero status so Laravel can retry.
"""

from __future__ import annotations

import json
import sys
from typing import Any, Callable

from engine.entrenamiento import entrenar
from engine.protocol import (
    ErrorDeDominio,
    ErrorDeProtocolo,
    leer_solicitud,
    respuesta_error,
    respuesta_ok,
)
from engine.riesgo import evaluar
from engine.trr import predecir

Operacion = Callable[[dict[str, Any]], dict[str, Any]]

OPERACIONES: dict[str, Operacion] = {
    "entrenar": entrenar,
    "predecir_trr": predecir,
    "evaluar_riesgo": evaluar,
}


def _escribir(payload: dict[str, Any]) -> None:
    sys.stdout.write(json.dumps(payload, ensure_ascii=False))


def main() -> int:
    try:
        operacion, datos = leer_solicitud(sys.stdin.read())

        if operacion not in OPERACIONES:
            raise ErrorDeProtocolo(
                "operacion_desconocida",
                f"unknown operation: {operacion}",
            )

        _escribir(respuesta_ok(OPERACIONES[operacion](datos)))

        return 0
    except ErrorDeDominio as error:
        _escribir(respuesta_error(error))

        return 0
    except Exception as error:  # noqa: BLE001 - crashes must surface as retries
        sys.stderr.write(f"{type(error).__name__}: {error}\n")

        return 1


if __name__ == "__main__":
    sys.exit(main())
