from __future__ import annotations

import pytest

from engine.protocol import (
    ErrorDeDominio,
    ErrorDeProtocolo,
    entero,
    fecha_iso,
    leer_solicitud,
    lista,
    numero,
    respuesta_error,
    respuesta_ok,
)


def test_reads_an_operation_and_its_data():
    operacion, datos = leer_solicitud('{"operacion": "predecir_trr", "datos": {"etapa_id": 1}}')

    assert operacion == "predecir_trr"
    assert datos == {"etapa_id": 1}


def test_defaults_the_data_payload_to_an_empty_object():
    operacion, datos = leer_solicitud('{"operacion": "entrenar"}')

    assert operacion == "entrenar"
    assert datos == {}


def test_rejects_invalid_json():
    with pytest.raises(ErrorDeProtocolo) as error:
        leer_solicitud("{not json")

    assert error.value.codigo == "json_invalido"


def test_rejects_a_request_without_operation():
    with pytest.raises(ErrorDeProtocolo) as error:
        leer_solicitud('{"datos": {}}')

    assert error.value.codigo == "operacion_invalida"


def test_rejects_a_non_object_data_payload():
    with pytest.raises(ErrorDeProtocolo) as error:
        leer_solicitud('{"operacion": "entrenar", "datos": [1, 2]}')

    assert error.value.codigo == "datos_invalidos"


def test_builds_ok_and_error_responses():
    assert respuesta_ok({"valor": 1.0}) == {"estado": "ok", "resultado": {"valor": 1.0}}
    assert respuesta_error(ErrorDeDominio("x", "boom")) == {
        "estado": "error",
        "codigo": "x",
        "mensaje": "boom",
    }


def test_reads_finite_numbers_only():
    assert numero(3, "campo") == 3.0
    assert numero(1.5, "campo") == 1.5

    for invalid in (True, "3", None, float("inf"), float("nan")):
        with pytest.raises(ErrorDeDominio):
            numero(invalid, "campo")


def test_reads_integers():
    assert entero(4, "campo") == 4

    with pytest.raises(ErrorDeDominio):
        entero(4.5, "campo")


def test_reads_iso_dates():
    assert fecha_iso("2026-11-02", "campo").isoformat() == "2026-11-02"

    with pytest.raises(ErrorDeDominio):
        fecha_iso("02/11/2026", "campo")


def test_reads_lists():
    assert lista([1], "campo") == [1]

    with pytest.raises(ErrorDeDominio):
        lista({"a": 1}, "campo")
