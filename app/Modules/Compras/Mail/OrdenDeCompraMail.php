<?php

namespace App\Modules\Compras\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrdenDeCompraMail extends Mailable
{
    use Queueable;

    /**
     * @param  array<int, array{articulo: string, cantidad: float, precio_pactado: float}>  $lineas
     */
    public function __construct(
        public readonly string $numeroOc,
        public readonly string $proveedor,
        public readonly array $lineas,
        public readonly float $total,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orden de compra SIGPAP '.$this->numeroOc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orden-de-compra',
        );
    }
}
