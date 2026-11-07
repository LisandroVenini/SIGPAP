<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Mail\OrdenDeCompraMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdenDeCompraMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_lines_total_and_portal_link(): void
    {
        // Arrange
        $token = (string) Str::uuid();

        // Act
        $mail = new OrdenDeCompraMail(
            numeroOc: 'OC-2026-0007',
            proveedor: 'Aceros del Sur',
            lineas: [
                ['articulo' => 'Tabla de pino', 'cantidad' => 8.0, 'precio_pactado' => 120.5],
            ],
            total: 964.0,
            token: $token,
        );
        $html = $mail->render();

        // Assert
        $this->assertSame('Orden de compra SIGPAP OC-2026-0007', $mail->envelope()->subject);
        $this->assertStringContainsString('Aceros del Sur', $html);
        $this->assertStringContainsString('Tabla de pino', $html);
        $this->assertStringContainsString('964', $html);
        $this->assertStringContainsString(
            route('portal.ordenes.show', ['token' => $token]),
            $html,
        );
    }
}
