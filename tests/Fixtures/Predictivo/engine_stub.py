#!/usr/bin/env python3
"""Contract stub for the PHP process tests.

It never imports scikit-learn, so the PHP suite runs without the engine's
virtualenv. The response depends on datos["escenario"].
"""

from __future__ import annotations

import json
import sys
import time


def main() -> int:
    payload = json.loads(sys.stdin.read() or "{}")
    datos = payload.get("datos", {})
    escenario = datos.get("escenario", "ok")

    if escenario == "falla":
        sys.stderr.write("stub failure\n")

        return 1

    if escenario == "basura":
        sys.stdout.write("this is not json")

        return 0

    if escenario == "error":
        sys.stdout.write(
            json.dumps(
                {
                    "estado": "error",
                    "codigo": "datos_invalidos",
                    "mensaje": "stub domain error",
                }
            )
        )

        return 0

    if escenario == "lento":
        time.sleep(5)

    sys.stdout.write(
        json.dumps(
            {
                "estado": "ok",
                "resultado": {
                    "operacion": payload.get("operacion"),
                    "datos": datos,
                },
            }
        )
    )

    return 0


if __name__ == "__main__":
    sys.exit(main())
