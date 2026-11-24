from __future__ import annotations

import pytest

from engine.protocol import ErrorDeDominio
from engine.riesgo import evaluar


def insumo(**overrides) -> dict:
    base = {
        "articulo_id": 1,
        "stock_fisico": 100,
        "comprometido": 20,
        "consumo_diario": 5,
        "lead_time_dias": 10,
        "stock_minimo": 30,
        "stock_maximo": 200,
    }
    base.update(overrides)

    return base


def datos(**overrides) -> dict:
    base = {"fecha_base": "2026-11-24", "dias_aviso": 7, "insumos": []}
    base.update(overrides)

    return base


def test_an_input_that_breaks_before_replenishment_is_high_risk():
    # 80 available / 5 per day = 16 days → breaks 2026-12-10.
    # 17-day lead time → the order was already due on 2026-11-23.
    resultado = evaluar(datos(insumos=[insumo(lead_time_dias=17)]))

    fila = resultado["criticidad"][0]

    assert fila["riesgo"] == "alto"
    assert fila["motivo"] == "quiebre_antes_de_reposicion"
    assert fila["disponible"] == 80.0
    assert fila["fecha_quiebre"] == "2026-12-10"
    assert fila["fecha_limite_pedido"] == "2026-11-23"
    assert fila["cantidad_sugerida"] == 120.0


def test_an_input_due_inside_the_warning_window_is_medium_risk():
    # Order-by date 2026-11-30, six days after the base date.
    resultado = evaluar(datos(insumos=[insumo(lead_time_dias=10)]))

    assert resultado["criticidad"][0]["riesgo"] == "medio"


def test_a_safe_input_is_not_listed():
    # Order-by date 2026-12-08, fourteen days after the base date.
    resultado = evaluar(datos(insumos=[insumo(lead_time_dias=2)]))

    assert resultado["criticidad"] == []
    assert resultado["evaluados"] == 1


def test_an_input_without_history_falls_back_to_the_static_threshold():
    resultado = evaluar(datos(insumos=[
        insumo(consumo_diario=0, lead_time_dias=0, stock_fisico=30),
        insumo(articulo_id=2, consumo_diario=0, lead_time_dias=0, stock_fisico=500),
    ]))

    assert len(resultado["criticidad"]) == 1

    fila = resultado["criticidad"][0]

    assert fila["articulo_id"] == 1
    assert fila["motivo"] == "umbral_estatico"
    assert fila["riesgo"] == "alto"
    assert fila["fecha_limite_pedido"] is None


def test_the_reservation_reduces_the_available_stock():
    resultado = evaluar(datos(insumos=[insumo(comprometido=95, lead_time_dias=3)]))

    # Only 5 units available: one day of consumption.
    assert resultado["criticidad"][0]["disponible"] == 5.0
    assert resultado["criticidad"][0]["fecha_quiebre"] == "2026-11-25"


def test_results_are_sorted_by_severity_then_deadline():
    resultado = evaluar(datos(insumos=[
        insumo(articulo_id=10, lead_time_dias=10),
        insumo(articulo_id=20, lead_time_dias=17),
        insumo(articulo_id=30, lead_time_dias=12),
    ]))

    assert [fila["articulo_id"] for fila in resultado["criticidad"]] == [20, 30, 10]


def test_a_negative_warning_window_is_rejected():
    with pytest.raises(ErrorDeDominio) as error:
        evaluar(datos(dias_aviso=-1, insumos=[]))

    assert error.value.codigo == "campo_invalido"


def test_a_non_object_input_is_rejected():
    with pytest.raises(ErrorDeDominio) as error:
        evaluar(datos(insumos=["no soy un objeto"]))

    assert error.value.codigo == "insumo_invalido"
