"""JSON stdin/stdout protocol between Laravel and the engine."""

from __future__ import annotations

import json
import math
from datetime import date
from typing import Any


class ErrorDeDominio(Exception):
    """A business or validation error the caller can report without retrying."""

    def __init__(self, codigo: str, mensaje: str) -> None:
        super().__init__(mensaje)
        self.codigo = codigo
        self.mensaje = mensaje


class ErrorDeProtocolo(ErrorDeDominio):
    """The request itself is malformed."""


def leer_solicitud(texto: str) -> tuple[str, dict[str, Any]]:
    """Parse the raw stdin payload into an operation name and its data."""
    try:
        payload = json.loads(texto)
    except json.JSONDecodeError as exc:
        raise ErrorDeProtocolo("json_invalido", "the request is not valid JSON") from exc

    if not isinstance(payload, dict):
        raise ErrorDeProtocolo("solicitud_invalida", "the request must be a JSON object")

    operacion = payload.get("operacion")
    if not isinstance(operacion, str) or not operacion.strip():
        raise ErrorDeProtocolo("operacion_invalida", "the operation name is required")

    datos = payload.get("datos", {})
    if not isinstance(datos, dict):
        raise ErrorDeProtocolo("datos_invalidos", "the data payload must be a JSON object")

    return operacion.strip(), datos


def respuesta_ok(resultado: dict[str, Any]) -> dict[str, Any]:
    return {"estado": "ok", "resultado": resultado}


def respuesta_error(error: ErrorDeDominio) -> dict[str, Any]:
    return {"estado": "error", "codigo": error.codigo, "mensaje": error.mensaje}


def numero(valor: Any, campo: str) -> float:
    """Read a finite float, rejecting booleans and free text."""
    if isinstance(valor, bool) or not isinstance(valor, (int, float)):
        raise ErrorDeDominio("campo_invalido", f"{campo} must be a number")

    numero_convertido = float(valor)

    if not math.isfinite(numero_convertido):
        raise ErrorDeDominio("campo_invalido", f"{campo} must be a finite number")

    return numero_convertido


def entero(valor: Any, campo: str) -> int:
    numero_convertido = numero(valor, campo)

    if not numero_convertido.is_integer():
        raise ErrorDeDominio("campo_invalido", f"{campo} must be an integer")

    return int(numero_convertido)


def fecha_iso(valor: Any, campo: str) -> date:
    try:
        return date.fromisoformat(str(valor))
    except (TypeError, ValueError) as exc:
        raise ErrorDeDominio("campo_invalido", f"{campo} must be an ISO date") from exc


def lista(valor: Any, campo: str) -> list[Any]:
    if not isinstance(valor, list):
        raise ErrorDeDominio("campo_invalido", f"{campo} must be a list")

    return valor
