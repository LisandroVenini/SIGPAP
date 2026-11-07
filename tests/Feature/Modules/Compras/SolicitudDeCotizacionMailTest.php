<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Mail\SolicitudDeCotizacionMail;
use App\Modules\Compras\Models\Proveedor;
use Database\Factories\SolicitudDeCotizacionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SolicitudDeCotizacionMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_lines_and_the_portal_link(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'email' => 'ventas@aceros.test',
        ]);
        $solicitud = SolicitudDeCotizacionFactory::new()->create([
            'fecha_vencimiento' => '2026-12-31',
        ]);
        $token = (string) Str::uuid();

        // Act
        $mail = new SolicitudDeCotizacionMail($solicitud, $proveedor, $token, [
            ['articulo' => 'Tabla de pino', 'cantidad_solicitada' => 10.0],
        ]);
        $html = $mail->render();

        // Assert
        $this->assertSame('Solicitud de cotización SIGPAP', $mail->envelope()->subject);
        $this->assertStringContainsString('Aceros del Sur', $html);
        $this->assertStringContainsString('Tabla de pino', $html);
        $this->assertStringContainsString('31/12/2026', $html);
        $this->assertStringContainsString(
            route('portal.cotizaciones.show', ['token' => $token]),
            $html,
        );
    }
}
