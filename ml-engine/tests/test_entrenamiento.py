from __future__ import annotations

import pytest

from engine.entrenamiento import entrenar
from engine.protocol import ErrorDeDominio

from tests.test_trr import TIEMPO_POR_UNIDAD, muestras


def test_training_persists_the_model_and_returns_its_metrics():
    resultado = entrenar(
        {
            "etapa_id": 3,
            "tiempo_por_unidad": TIEMPO_POR_UNIDAD,
            "muestras": muestras(48, 43.2, 38.4, 33.6),
        }
    )

    assert resultado == {
        "etapa_id": 3,
        "metricas": {
            "r2": resultado["metricas"]["r2"],
            "mae": resultado["metricas"]["mae"],
            "n_muestras": 4,
        },
        "n_muestras": 4,
        "guardado": True,
    }

    from engine.settings import ruta_modelo_trr

    assert ruta_modelo_trr(3).is_file()


def test_training_requires_enough_samples():
    with pytest.raises(ErrorDeDominio) as error:
        entrenar(
            {
                "etapa_id": 3,
                "tiempo_por_unidad": TIEMPO_POR_UNIDAD,
                "muestras": muestras(48, 40),
            }
        )

    assert error.value.codigo == "muestras_insuficientes"


def test_training_rejects_a_missing_stage():
    with pytest.raises(ErrorDeDominio):
        entrenar({"tiempo_por_unidad": TIEMPO_POR_UNIDAD, "muestras": muestras(48, 40, 30)})
