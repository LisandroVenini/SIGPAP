<?php

namespace App\Modules\Compras\Mail;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeDisponibilidad;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SolicitudDeDisponibilidadMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly SolicitudDeDisponibilidad $solicitud,
        public readonly Proveedor $proveedor,
        public readonly string $token,
        public readonly string $articulo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Consulta de disponibilidad SIGPAP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-de-disponibilidad',
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
