"""Supply risk: which inputs will run out before a replacement can arrive.

The calculation is statistical, not a regression: projected daily consumption
against the supplier's real lead time. Inputs without history fall back to the
static minimum/maximum thresholds, as the use case requires.
"""

from __future__ import annotations

from datetime import date, timedelta
from typing import Any

from .protocol import ErrorDeDominio, entero, fecha_iso, lista, numero


def evaluar(datos: dict[str, Any]) -> dict[str, Any]:
    """Return the criticality list of the inputs at risk of a stock break."""
    fecha_base = fecha_iso(datos.get("fecha_base"), "fecha_base")
    dias_aviso = entero(datos.get("dias_aviso", 7), "dias_aviso")
    insumos = lista(datos.get("insumos", []), "insumos")

    if dias_aviso < 0:
        raise ErrorDeDominio("campo_invalido", "dias_aviso cannot be negative")

    criticidad: list[dict[str, Any]] = []
    evaluados = 0

    for insumo in insumos:
        if not isinstance(insumo, dict):
            raise ErrorDeDominio("insumo_invalido", "each input must be a JSON object")

        evaluados += 1

        articulo_id = entero(insumo.get("articulo_id"), "insumo.articulo_id")
        stock = numero(insumo.get("stock_fisico"), "insumo.stock_fisico")
        comprometido = numero(insumo.get("comprometido", 0), "insumo.comprometido")
        consumo = numero(insumo.get("consumo_diario", 0), "insumo.consumo_diario")
        lead = numero(insumo.get("lead_time_dias", 0), "insumo.lead_time_dias")
        minimo = numero(insumo.get("stock_minimo", 0), "insumo.stock_minimo")
        maximo = numero(insumo.get("stock_maximo", 0), "insumo.stock_maximo")

        disponible = max(0.0, stock - comprometido)
        cantidad = round(max(0.0, maximo - disponible), 2)

        if consumo <= 0 or lead <= 0:
            # No history: the documented fallback is the static threshold.
            if disponible <= minimo:
                criticidad.append(
                    {
                        "articulo_id": articulo_id,
                        "disponible": round(disponible, 2),
                        "consumo_diario": round(consumo, 4),
                        "dias_hasta_quiebre": None,
                        "fecha_quiebre": None,
                        "lead_time_dias": None,
                        "fecha_limite_pedido": None,
                        "riesgo": "alto",
                        "motivo": "umbral_estatico",
                        "cantidad_sugerida": cantidad,
                    }
                )

            continue

        dias_quiebre = disponible / consumo
        fecha_quiebre = fecha_base + timedelta(days=dias_quiebre)
        fecha_limite = fecha_quiebre - timedelta(days=lead)
        dias_hasta_limite = (fecha_limite - fecha_base).days

        if dias_hasta_limite < 0:
            riesgo = "alto"
        elif dias_hasta_limite <= dias_aviso:
            riesgo = "medio"
        else:
            continue

        criticidad.append(
            {
                "articulo_id": articulo_id,
                "disponible": round(disponible, 2),
                "consumo_diario": round(consumo, 4),
                "dias_hasta_quiebre": round(dias_quiebre, 2),
                "fecha_quiebre": fecha_quiebre.isoformat(),
                "lead_time_dias": round(lead, 2),
                "fecha_limite_pedido": fecha_limite.isoformat(),
                "riesgo": riesgo,
                "motivo": "quiebre_antes_de_reposicion",
                "cantidad_sugerida": cantidad,
            }
        )

    criticidad.sort(key=lambda fila: (0 if fila["riesgo"] == "alto" else 1, fila["fecha_limite_pedido"] or ""))

    return {"criticidad": criticidad, "evaluados": evaluados}
