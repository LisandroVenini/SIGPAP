"""Paths and constants resolved at call time.

Model artifacts live beside the engine, never in PostgreSQL. The directory is
read from the environment on every call so tests can isolate it.
"""

from __future__ import annotations

import os
from datetime import date
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent

# Working day length in hours. Kept in sync with GestionCronogramaService.
HORAS_JORNADA = 8

# Below this many samples the engine falls back to the plain average instead of
# fitting a regression.
MIN_MUESTRAS_TRR = 3


def directorio_modelos() -> Path:
    """Directory that holds the serialized models."""
    return Path(os.environ.get("PREDICTIVO_MODEL_DIR", BASE_DIR / "models"))


def ruta_modelo_trr(etapa_id: int) -> Path:
    """Serialized Tasa de Rendimiento model for one stage."""
    return directorio_modelos() / f"trr_{etapa_id}.joblib"


def hoy() -> date:
    """Today, isolated for tests."""
    return date.today()
