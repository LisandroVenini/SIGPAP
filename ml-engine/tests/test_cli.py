from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent


def ejecutar(payload: dict) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [sys.executable, str(RAIZ / "main.py")],
        input=json.dumps(payload),
        capture_output=True,
        text=True,
        cwd=RAIZ,
        timeout=60,
        check=False,
    )


def test_cli_writes_one_ok_json_response():
    proceso = ejecutar(
        {
            "operacion": "predecir_trr",
            "datos": {
                "etapa_id": 1,
                "tiempo_por_unidad": 10,
                "cantidad_pendiente": 48,
                "fecha_base": "2026-11-09",
                "muestras": [],
            },
        }
    )

    assert proceso.returncode == 0

    respuesta = json.loads(proceso.stdout)

    assert respuesta["estado"] == "ok"
    assert respuesta["resultado"]["valor"] == 1.0
    assert respuesta["resultado"]["origen"] == "estandar"


def test_cli_reports_a_domain_error_with_exit_zero():
    proceso = ejecutar({"operacion": "operacion_inexistente", "datos": {}})

    assert proceso.returncode == 0

    respuesta = json.loads(proceso.stdout)

    assert respuesta["estado"] == "error"
    assert respuesta["codigo"] == "operacion_desconocida"


def test_cli_reports_invalid_json_with_exit_zero():
    proceso = subprocess.run(
        [sys.executable, str(RAIZ / "main.py")],
        input="{not json",
        capture_output=True,
        text=True,
        cwd=RAIZ,
        timeout=60,
        check=False,
    )

    assert proceso.returncode == 0
    assert json.loads(proceso.stdout)["codigo"] == "json_invalido"
