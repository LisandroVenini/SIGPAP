<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta de disponibilidad SIGPAP</title>
</head>
<body style="margin: 0; padding: 0; background-color: #ebf0e9; font-family: Arial, Helvetica, sans-serif; color: #22281f;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ebf0e9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; background-color: #ffffff; border: 1px solid #d8e0d6; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background-color: #22281f; padding: 20px 24px; color: #ffffff;">
                            <span style="font-size: 18px; font-weight: bold; letter-spacing: -0.02em;">SIGPAP</span>
                            <span style="font-size: 12px; color: #c9d2c7; margin-left: 8px;">Confort y Muebles</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px;">
                            <h1 style="margin: 0 0 12px; font-size: 20px; color: #22281f;">Consulta de disponibilidad</h1>

                            <p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6;">
                                Estimado/a <strong>{{ $proveedor->razon_social }}</strong>,
                            </p>
                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6;">
                                Confort y Muebles desea pedirte <strong>{{ $solicitud->cantidad_solicitada }}</strong>
                                unidades de <strong>{{ $articulo }}</strong>. Confirmá disponibilidad, precio actualizado
                                y fecha de entrega.
                            </p>

                            @if ($solicitud->fecha_requerida !== null)
                                <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6;">
                                    La necesitamos antes del
                                    <strong>{{ $solicitud->fecha_requerida->format('d/m/Y') }}</strong>.
                                </p>
                            @endif

                            <p style="margin: 0 0 24px;">
                                <a href="{{ route('portal.disponibilidad.show', ['token' => $token]) }}"
                                   style="display: inline-block; background-color: #b4491f; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: bold; padding: 12px 20px; border-radius: 8px;">
                                    Responder consulta
                                </a>
                            </p>

                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #5b6258;">
                                Si el botón no funciona, copie y pegue el siguiente enlace en su navegador:<br>
                                <span style="word-break: break-all;">{{ route('portal.disponibilidad.show', ['token' => $token]) }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f6f8f5; padding: 16px 24px; font-size: 11px; color: #9ba39a;">
                            Este enlace es único y personal. Una vez respondida la consulta quedará deshabilitado.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
