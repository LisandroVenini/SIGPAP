"""Model training and joblib persistence.

Training only refreshes the serialized model and its metrics; the engine still
never writes to the transactional database.
"""

from __future__ import annotations

from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import joblib

from .protocol import ErrorDeDominio, entero, numero
from .settings import HORAS_JORNADA, MIN_MUESTRAS_TRR, ruta_modelo_trr
from .trr import ajustar, construir_muestras


def entrenar(datos: dict[str, Any]) -> dict[str, Any]:
    """Fit the TRR model for one stage and persist it with its metrics."""
    etapa_id = entero(datos.get("etapa_id"), "etapa_id")
    tiempo_por_unidad = numero(datos.get("tiempo_por_unidad"), "tiempo_por_unidad")
    minutos_por_defecto = numero(
        datos.get("minutos_habiles_por_dia", HORAS_JORNADA * 60),
        "minutos_habiles_por_dia",
    )
    frame = construir_muestras(datos.get("muestras", []), tiempo_por_unidad, minutos_por_defecto)

    if len(frame) < MIN_MUESTRAS_TRR:
        raise ErrorDeDominio(
            "muestras_insuficientes",
            f"at least {MIN_MUESTRAS_TRR} samples are required for training",
        )

    ruta = ruta_modelo_trr(etapa_id)
    anteriores = _metricas_anteriores(ruta)
    modelo, metricas = ajustar(frame)
    ruta.parent.mkdir(parents=True, exist_ok=True)

    joblib.dump(
        {
            "modelo": modelo,
            "origen_fecha": frame["fecha"].min().date(),
            "metricas": metricas,
            "entrenado_en": datetime.now(timezone.utc).isoformat(),
        },
        ruta,
    )

    return {
        "etapa_id": etapa_id,
        "metricas": metricas,
        "metricas_anteriores": anteriores,
        "delta": _delta(metricas, anteriores),
        "n_muestras": len(frame),
        "guardado": True,
    }


def _metricas_anteriores(ruta: Path) -> dict[str, Any] | None:
    """Metrics of the previous artifact, when it exists and is readable."""
    if not ruta.is_file():
        return None

    try:
        artefacto = joblib.load(ruta)
    except Exception:  # noqa: BLE001 - a corrupt artifact must not block training
        return None

    metricas = artefacto.get("metricas") if isinstance(artefacto, dict) else None

    return metricas if isinstance(metricas, dict) else None


def _delta(metricas: dict[str, Any], anteriores: dict[str, Any] | None) -> dict[str, Any]:
    """Change in metrics against the previous training, when comparable."""
    if anteriores is None:
        return {"r2": None, "mae": None}

    def diferencia(actual: Any, previo: Any) -> float | None:
        if actual is None or previo is None:
            return None

        return round(float(actual) - float(previo), 4)

    return {
        "r2": diferencia(metricas.get("r2"), anteriores.get("r2")),
        "mae": diferencia(metricas.get("mae"), anteriores.get("mae")),
    }
