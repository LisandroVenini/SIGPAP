from __future__ import annotations

import pytest

from engine.disponibilidad import (
    ESCENARIO_IDEAL,
    ESCENARIO_SIN_STOCK,
    ESCENARIO_VARIACION_FUERTE,
    resolver,
)
from engine.protocol import ErrorDeDominio


def datos(**overrides) -> dict:
    base = {
        "cantidad_disponible": 50,
        "precio_unitario": 100,
        "precio_referencia": 100,
        "umbral": 0.15,
        "fecha_prometida": "2026-11-28",
        "fecha_requerida": "2026-12-01",
    }
    base.update(overrides)

    return base


def test_a_zero_answer_is_sin_stock():
    resultado = resolver(datos(cantidad_disponible=0))

    assert resultado["escenario"] == ESCENARIO_SIN_STOCK
    assert resultado["motivos"] == ["sin_stock"]


def test_an_answer_within_the_threshold_and_date_is_ideal():
    resultado = resolver(datos())

    assert resultado["escenario"] == ESCENARIO_IDEAL
    assert resultado["motivos"] == []
    assert resultado["desviacion_precio"] == 0.0
    assert resultado["dias_de_atraso"] == -3


def test_a_price_above_the_threshold_is_a_strong_variation():
    resultado = resolver(datos(precio_unitario=120))

    assert resultado["escenario"] == ESCENARIO_VARIACION_FUERTE
    assert "precio_sobre_umbral" in resultado["motivos"]


def test_a_price_inside_the_threshold_is_not_flagged():
    resultado = resolver(datos(precio_unitario=114))

    assert resultado["escenario"] == ESCENARIO_IDEAL


def test_a_date_after_the_needed_date_breaks_the_schedule():
    resultado = resolver(datos(fecha_prometida="2026-12-05"))

    assert resultado["escenario"] == ESCENARIO_VARIACION_FUERTE
    assert "fecha_fuera_del_cronograma" in resultado["motivos"]
    assert resultado["dias_de_atraso"] == 4


def test_both_reasons_are_reported():
    resultado = resolver(datos(precio_unitario=200, fecha_prometida="2026-12-10"))

    assert set(resultado["motivos"]) == {"precio_sobre_umbral", "fecha_fuera_del_cronograma"}


def test_without_a_reference_price_the_price_check_is_skipped():
    resultado = resolver(datos(precio_unitario=500, precio_referencia=None))

    assert resultado["escenario"] == ESCENARIO_IDEAL
    assert resultado["desviacion_precio"] is None


def test_without_a_needed_date_the_schedule_check_is_skipped():
    resultado = resolver(datos(fecha_prometida="2030-01-01", fecha_requerida=None))

    assert resultado["escenario"] == ESCENARIO_IDEAL
    assert resultado["dias_de_atraso"] is None


def test_a_negative_threshold_is_rejected():
    with pytest.raises(ErrorDeDominio) as error:
        resolver(datos(umbral=-0.1))

    assert error.value.codigo == "umbral_invalido"


def test_a_zero_price_is_rejected():
    with pytest.raises(ErrorDeDominio) as error:
        resolver(datos(precio_unitario=0))

    assert error.value.codigo == "precio_invalido"
