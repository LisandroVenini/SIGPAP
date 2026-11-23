"""Model training and joblib persistence.

Training only refreshes the serialized model and its metrics; the engine still
never writes to the transactional database.
"""

from __future__ import annotations

from datetime import datetime, timezone
from typing import Any

import joblib

from .protocol import ErrorDeDominio, entero, numero
from .settings import MIN_MUESTRAS_TRR, ruta_modelo_trr
from .trr import ajustar, capacidad_estandar_diaria, construir_muestras


def entrenar(datos: dict[str, Any]) -> dict[str, Any]:
    """Fit the TRR model for one stage and persist it with its metrics."""
    etapa_id = entero(datos.get("etapa_id"), "etapa_id")
    tiempo_por_unidad = numero(datos.get("tiempo_por_unidad"), "tiempo_por_unidad")
    capacidad = capacidad_estandar_diaria(tiempo_por_unidad)
    frame = construir_muestras(datos.get("muestras", []), capacidad)

    if len(frame) < MIN_MUESTRAS_TRR:
        raise ErrorDeDominio(
            "muestras_insuficientes",
            f"at least {MIN_MUESTRAS_TRR} samples are required for training",
        )

    modelo, metricas = ajustar(frame)
    ruta = ruta_modelo_trr(etapa_id)
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
        "n_muestras": len(frame),
        "guardado": True,
    }
