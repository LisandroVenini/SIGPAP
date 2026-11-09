<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>{{ $titulo }}</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: DejaVu Sans, sans-serif; color: #1f2933; font-size: 11px; margin: 24px; }
            h1 { font-size: 18px; margin: 0 0 4px; }
            .rango { color: #52606d; margin: 0 0 16px; }
            .total { margin-top: 12px; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #cbd2d9; padding: 5px 6px; text-align: left; }
            th { background: #eef2f5; font-size: 10px; text-transform: uppercase; }
            td { vertical-align: top; }
        </style>
    </head>
    <body>
        <h1>{{ $titulo }}</h1>
        <p class="rango">Período: {{ $desde }} al {{ $hasta }}</p>

        <table>
            <thead>
                <tr>
                    @foreach ($columnas as $columna)
                        <th>{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr>
                        @foreach (array_values($fila) as $valor)
                            <td>{{ $valor }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columnas) }}">Sin datos para generar</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="total">Total de filas: {{ $total }}</p>
    </body>
</html>
