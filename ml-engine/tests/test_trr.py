from __future__ import annotations

import pytest

from engine.entrenamiento import entrenar
from engine.protocol import ErrorDeDominio
from engine.trr import capacidad_estandar_diaria, construir_muestras, predecir

# 480 standard minutes per day / 10 minutes per unit = 48 units per day.
TIEMPO_POR_UNIDAD = 10.0
CAPACIDAD_DIARIA = 48.0


def muestras(*cantidades: float, desde: str = "2026-11-02") -> list[dict[str, float | str]]:
    from datetime import date, timedelta

    inicio = date.fromisoformat(desde)

    return [
        {
            "fecha": (inicio + timedelta(days=indice)).isoformat(),
            "cantidad_conformada": cantidad,
        }
        for indice, cantidad in enumerate(cantidades)
    ]


def datos_prediccion(**overrides) -> dict:
    base = {
        "etapa_id": 7,
        "tiempo_por_unidad": TIEMPO_POR_UNIDAD,
        "cantidad_pendiente": 96.0,
        "fecha_base": "2026-11-09",
        "muestras": [],
    }
    base.update(overrides)

    return base


def test_capacity_comes_from_the_working_minutes_of_the_day():
    assert capacidad_estandar_diaria(TIEMPO_POR_UNIDAD, 480) == CAPACIDAD_DIARIA
    assert capacidad_estandar_diaria(TIEMPO_POR_UNIDAD, 240) == 24.0

    with pytest.raises(ErrorDeDominio):
        capacidad_estandar_diaria(0, 480)

    with pytest.raises(ErrorDeDominio) as error:
        capacidad_estandar_diaria(TIEMPO_POR_UNIDAD, 0)

    assert error.value.codigo == "sin_capacidad_diaria"


def test_samples_use_the_working_minutes_of_their_own_date():
    frame = construir_muestras(
        [
            {"fecha": "2026-11-02", "cantidad_conformada": 48, "minutos_habiles": 480},
            {"fecha": "2026-11-07", "cantidad_conformada": 12, "minutos_habiles": 240},
            {"fecha": "2026-11-08", "cantidad_conformada": 10, "minutos_habiles": 0},
        ],
        TIEMPO_POR_UNIDAD,
        480,
    )

    # A Saturday half-day is measured against its real capacity (24 units),
    # and a sample on a non-working day is skipped.
    assert list(frame["tasa"]) == [1.0, 0.5]
    assert len(frame) == 2


def test_the_projection_uses_the_working_minutes_of_the_base_day():
    # 96 pending at 240 working minutes (24 units/day) → 4 days.
    resultado = predecir(datos_prediccion(minutos_habiles_por_dia=240, cantidad_pendiente=96))

    assert resultado["origen"] == "estandar"
    assert resultado["dias_proyectados"] == 4.0


def test_a_day_without_working_minutes_is_rejected():
    with pytest.raises(ErrorDeDominio) as error:
        predecir(datos_prediccion(minutos_habiles_por_dia=0))

    assert error.value.codigo == "sin_capacidad_diaria"


def test_without_history_the_standard_rate_is_used():
    resultado = predecir(datos_prediccion())

    assert resultado["origen"] == "estandar"
    assert resultado["valor"] == 1.0
    assert resultado["desviacion"] == 0.0
    assert resultado["dias_proyectados"] == 2.0


def test_with_two_samples_the_average_is_used():
    resultado = predecir(datos_prediccion(muestras=muestras(48, 24)))

    assert resultado["origen"] == "promedio"
    assert resultado["valor"] == 0.75
    assert resultado["desviacion"] == -0.25


def test_with_enough_samples_the_model_is_fitted_on_the_fly():
    resultado = predecir(datos_prediccion(muestras=muestras(48, 43.2, 38.4, 33.6, 28.8)))

    assert resultado["origen"] == "modelo_ajustado"
    assert resultado["valor"] < 0.6
    assert resultado["desviacion"] < 0
    assert resultado["metricas"]["n_muestras"] == 5
    assert resultado["metricas"]["mae"] is not None


def test_the_trained_model_wins_over_the_historical_samples():
    entrenar(
        {
            "etapa_id": 7,
            "tiempo_por_unidad": TIEMPO_POR_UNIDAD,
            "muestras": muestras(48, 43.2, 38.4, 33.6, 28.8),
        }
    )

    resultado = predecir(datos_prediccion(muestras=muestras(48, 48, 48)))

    assert resultado["origen"] == "modelo"
    assert resultado["valor"] < 0.6


def test_a_flat_series_reports_no_r2_instead_of_nan():
    resultado = predecir(datos_prediccion(muestras=muestras(48, 48, 48, 48)))

    assert resultado["origen"] == "modelo_ajustado"
    assert resultado["valor"] == pytest.approx(1.0, abs=0.01)
    assert resultado["metricas"]["r2"] is None


def test_pending_quantity_zero_projects_zero_days():
    resultado = predecir(datos_prediccion(cantidad_pendiente=0.0))

    assert resultado["dias_proyectados"] == 0.0


def test_a_negative_pending_quantity_is_rejected():
    with pytest.raises(ErrorDeDominio) as error:
        predecir(datos_prediccion(cantidad_pendiente=-1.0))

    assert error.value.codigo == "cantidad_invalida"


def test_an_invalid_stage_is_rejected():
    with pytest.raises(ErrorDeDominio):
        predecir(datos_prediccion(etapa_id="uno"))


def test_an_invalid_sample_is_rejected():
    with pytest.raises(ErrorDeDominio):
        predecir(datos_prediccion(muestras=[{"fecha": "ayer", "cantidad_conformada": 1}]))
