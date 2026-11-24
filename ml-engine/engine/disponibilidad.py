"""Availability resolution (flow A).

Compares the supplier answer against the tolerance threshold and the date the
input is needed. It recommends; it never decides.
"""

from __future__ import annotations

from typing import Any

from .protocol import ErrorDeDominio, fecha_iso, numero

ESCENARIO_IDEAL = "Ideal"
ESCENARIO_SIN_STOCK = "Sin Stock"
ESCENARIO_VARIACION_FUERTE = "Variación Fuerte"


def resolver(datos: dict[str, Any]) -> dict[str, Any]:
    cantidad = numero(datos.get("cantidad_disponible"), "cantidad_disponible")
    precio = numero(datos.get("precio_unitario"), "precio_unitario")
    umbral = numero(datos.get("umbral", 0.15), "umbral")

    if cantidad < 0:
        raise ErrorDeDominio("cantidad_invalida", "cantidad_disponible cannot be negative")

    if precio <= 0:
        raise ErrorDeDominio("precio_invalido", "precio_unitario must be greater than zero")

    if umbral < 0:
        raise ErrorDeDominio("umbral_invalido", "umbral cannot be negative")

    fecha_prometida = fecha_iso(datos.get("fecha_prometida"), "fecha_prometida")
    requerida_cruda = datos.get("fecha_requerida")
    fecha_requerida = (
        fecha_iso(requerida_cruda, "fecha_requerida") if requerida_cruda else None
    )
    referencia_cruda = datos.get("precio_referencia")
    precio_referencia = (
        numero(referencia_cruda, "precio_referencia")
        if referencia_cruda is not None
        else None
    )

    if cantidad <= 0:
        return {
            "escenario": ESCENARIO_SIN_STOCK,
            "motivos": ["sin_stock"],
            "desviacion_precio": None,
            "dias_de_atraso": None,
        }

    motivos: list[str] = []
    desviacion = None

    if precio_referencia is not None and precio_referencia > 0:
        desviacion = round((precio - precio_referencia) / precio_referencia, 4)

        if desviacion > umbral:
            motivos.append("precio_sobre_umbral")

    dias_de_atraso = None

    if fecha_requerida is not None:
        dias_de_atraso = (fecha_prometida - fecha_requerida).days

        if dias_de_atraso > 0:
            motivos.append("fecha_fuera_del_cronograma")

    return {
        "escenario": ESCENARIO_VARIACION_FUERTE if motivos else ESCENARIO_IDEAL,
        "motivos": motivos,
        "desviacion_precio": desviacion,
        "dias_de_atraso": dias_de_atraso,
    }
