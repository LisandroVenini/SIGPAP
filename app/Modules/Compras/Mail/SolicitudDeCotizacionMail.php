<?php

namespace App\Modules\Compras\Mail;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SolicitudDeCotizacionMail extends Mailable
{
    use Queueable;

    /**
     * @param  array<int, array{articulo: string, cantidad_solicitada: float}>  $lineas
     */
    public function __construct(
        public readonly SolicitudDeCotizacion $solicitud,
        public readonly Proveedor $proveedor,
        public readonly string $token,
        public readonly array $lineas,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de cotización SIGPAP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-de-cotizacion',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
