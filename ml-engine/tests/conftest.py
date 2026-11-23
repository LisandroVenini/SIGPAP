from __future__ import annotations

import pytest


@pytest.fixture(autouse=True)
def isolated_model_dir(tmp_path, monkeypatch):
    """Keep every test away from the real model directory."""
    monkeypatch.setenv("PREDICTIVO_MODEL_DIR", str(tmp_path / "models"))
