"""Tasa de Rendimiento Real (TRR) estimation.

The daily TRR is the produced quantity over the standard daily capacity of the
stage. A linear regression over the day index projects the next TRR, which turns
the pending quantity into projected days.

Only the time trend is used. Weekday seasonality is deliberately out of scope:
with a handful of samples it is collinear with the day index and makes the
extrapolation worse. It stays in the documented future work (Prophet).
"""

from __future__ import annotations

import math
from datetime import date
from typing import Any

import joblib
import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression

from .protocol import ErrorDeDominio, entero, fecha_iso, lista, numero
from .settings import HORAS_JORNADA, MIN_MUESTRAS_TRR, ruta_modelo_trr

ORIGEN_MODELO = "modelo"
ORIGEN_MODELO_AJUSTADO = "modelo_ajustado"
ORIGEN_PROMEDIO = "promedio"
ORIGEN_ESTANDAR = "estandar"

# Avoids dividing by zero when the regression projects a non-positive rate.
TASA_MINIMA = 0.0001


def capacidad_estandar_diaria(tiempo_por_unidad: float, minutos_habiles: float) -> float:
    """Standard units for a stage, from its minutes per unit and the working minutes."""
    if tiempo_por_unidad <= 0:
        raise ErrorDeDominio(
            "tiempo_por_unidad_invalido",
            "tiempo_por_unidad must be greater than zero",
        )

    if minutos_habiles <= 0:
        raise ErrorDeDominio(
            "sin_capacidad_diaria",
            "the day has no working minutes",
        )

    return minutos_habiles / tiempo_por_unidad


def construir_muestras(
    muestras: Any,
    tiempo_por_unidad: float,
    minutos_habiles_por_defecto: float,
) -> pd.DataFrame:
    """Normalize the raw daily samples into fecha, cantidad, indice and tasa.

    Each sample carries the working minutes of its own date, so a Saturday
    half-day is compared against its real capacity instead of a full day. A
    sample registered on a non-working day carries no rate information and is
    skipped.
    """
    if tiempo_por_unidad <= 0:
        raise ErrorDeDominio(
            "tiempo_por_unidad_invalido",
            "tiempo_por_unidad must be greater than zero",
        )

    registros: list[dict[str, Any]] = []

    for muestra in lista(muestras, "muestras"):
        if not isinstance(muestra, dict):
            raise ErrorDeDominio("muestra_invalida", "each sample must be a JSON object")

        minutos = numero(
            muestra.get("minutos_habiles", minutos_habiles_por_defecto),
            "muestra.minutos_habiles",
        )

        if minutos <= 0:
            continue

        registros.append(
            {
                "fecha": fecha_iso(muestra.get("fecha"), "muestra.fecha"),
                "cantidad_conformada": numero(
                    muestra.get("cantidad_conformada"), "muestra.cantidad_conformada"
                ),
                "minutos_habiles": minutos,
            }
        )

    columnas = ["fecha", "cantidad_conformada", "minutos_habiles", "indice_dia", "tasa"]
    frame = pd.DataFrame(registros, columns=["fecha", "cantidad_conformada", "minutos_habiles"])

    if frame.empty:
        return pd.DataFrame(columns=columnas)

    frame["fecha"] = pd.to_datetime(frame["fecha"])
    frame = frame.sort_values("fecha").reset_index(drop=True)
    frame["indice_dia"] = (frame["fecha"] - frame["fecha"].min()).dt.days
    frame["tasa"] = frame["cantidad_conformada"] / (frame["minutos_habiles"] / tiempo_por_unidad)

    return frame


def ajustar(frame: pd.DataFrame) -> tuple[LinearRegression, dict[str, Any]]:
    """Fit the regression and report its in-sample metrics."""
    caracteristicas = frame[["indice_dia"]].to_numpy(dtype=float)
    objetivo = frame["tasa"].to_numpy(dtype=float)

    modelo = LinearRegression().fit(caracteristicas, objetivo)
    predicciones = modelo.predict(caracteristicas)

    return modelo, _metricas(objetivo, predicciones, len(frame))


def _metricas(objetivo: np.ndarray, predicciones: np.ndarray, n: int) -> dict[str, Any]:
    error_absoluto_medio = round(float(np.mean(np.abs(objetivo - predicciones))), 4)
    suma_cuadrados_residual = float(np.sum((objetivo - predicciones) ** 2))
    suma_cuadrados_total = float(np.sum((objetivo - np.mean(objetivo)) ** 2))

    r2 = None

    if suma_cuadrados_total > 0:
        valor_r2 = 1 - (suma_cuadrados_residual / suma_cuadrados_total)
        r2 = round(valor_r2, 4) if math.isfinite(valor_r2) else None

    return {"r2": r2, "mae": error_absoluto_medio, "n_muestras": n}


def _metricas_de_promedio(frame: pd.DataFrame) -> dict[str, Any]:
    objetivo = frame["tasa"].to_numpy(dtype=float)
    predicciones = np.full_like(objetivo, float(np.mean(objetivo)))

    return _metricas(objetivo, predicciones, len(frame))


def _valor_finito(valor: float) -> float:
    if not math.isfinite(valor):
        return 1.0

    return max(valor, TASA_MINIMA)


def predecir(datos: dict[str, Any]) -> dict[str, Any]:
    """Project the TRR and the days needed to finish the pending quantity."""
    etapa_id = entero(datos.get("etapa_id"), "etapa_id")
    tiempo_por_unidad = numero(datos.get("tiempo_por_unidad"), "tiempo_por_unidad")
    cantidad_pendiente = numero(datos.get("cantidad_pendiente"), "cantidad_pendiente")
    fecha_base = fecha_iso(datos.get("fecha_base"), "fecha_base")

    if cantidad_pendiente < 0:
        raise ErrorDeDominio("cantidad_invalida", "cantidad_pendiente cannot be negative")

    minutos_base = numero(
        datos.get("minutos_habiles_por_dia", HORAS_JORNADA * 60),
        "minutos_habiles_por_dia",
    )
    capacidad = capacidad_estandar_diaria(tiempo_por_unidad, minutos_base)
    frame = construir_muestras(datos.get("muestras", []), tiempo_por_unidad, minutos_base)
    ruta = ruta_modelo_trr(etapa_id)

    if ruta.is_file():
        artefacto = joblib.load(ruta)
        modelo = artefacto["modelo"]
        origen = ORIGEN_MODELO
        metricas = artefacto["metricas"]
        indice = (fecha_base - artefacto["origen_fecha"]).days
        valor = float(modelo.predict([[indice]])[0])
    elif len(frame) >= MIN_MUESTRAS_TRR:
        modelo, metricas = ajustar(frame)
        origen = ORIGEN_MODELO_AJUSTADO
        indice = (fecha_base - frame["fecha"].min().date()).days
        valor = float(modelo.predict([[indice]])[0])
    elif len(frame) > 0:
        origen = ORIGEN_PROMEDIO
        metricas = _metricas_de_promedio(frame)
        valor = float(frame["tasa"].mean())
    else:
        origen = ORIGEN_ESTANDAR
        metricas = {"r2": None, "mae": None, "n_muestras": 0}
        valor = 1.0

    valor = _valor_finito(valor)
    dias_proyectados = 0.0

    if cantidad_pendiente > 0:
        dias_proyectados = cantidad_pendiente / (capacidad * valor)

    return {
        "etapa_id": etapa_id,
        "valor": round(valor, 4),
        "desviacion": round(valor - 1, 4),
        "dias_proyectados": round(dias_proyectados, 2),
        "origen": origen,
        "metricas": metricas,
    }
