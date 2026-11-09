<?php

namespace Tests\Feature\Modules\Reportes;

use App\Modules\Reportes\Services\ExportadorReportesService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class ExportadorReportesServiceTest extends TestCase
{
    /**
     * @return array{tipo: string, titulo: string, columnas: array<int, string>, filas: array<int, array<string, mixed>>, total: int, desde: string, hasta: string}
     */
    private function reporte(): array
    {
        return [
            'tipo' => 'produccion',
            'titulo' => 'Producción',
            'columnas' => ['Orden', 'Producto', 'Estado'],
            'filas' => [
                ['orden_id' => 1, 'producto' => 'Silla de pino', 'estado' => 'Pendiente'],
                ['orden_id' => 2, 'producto' => 'Mesa de roble', 'estado' => 'En Curso'],
            ],
            'total' => 2,
            'desde' => '2026-03-01',
            'hasta' => '2026-03-31',
        ];
    }

    public function test_xlsx_writes_a_temporary_file_with_headers_and_rows(): void
    {
        // Arrange
        $service = new ExportadorReportesService;

        // Act
        $ruta = $service->xlsx($this->reporte());

        try {
            // Assert
            $this->assertFileExists($ruta);
            $this->assertGreaterThan(0, filesize($ruta));
            $this->assertSame('PK', file_get_contents($ruta, false, null, 0, 2));
        } finally {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
    }

    public function test_pdf_returns_an_application_pdf_response(): void
    {
        // Arrange
        $service = new ExportadorReportesService;

        // Act
        $response = $service->pdf($this->reporte());

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_exportar_returns_a_pdf_response_for_the_pdf_format(): void
    {
        // Arrange
        $service = new ExportadorReportesService;

        // Act
        $response = $service->exportar($this->reporte(), 'pdf');

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_exportar_returns_a_spreadsheet_download_for_the_xlsx_format(): void
    {
        // Arrange
        $service = new ExportadorReportesService;

        // Act
        $response = $service->exportar($this->reporte(), 'xlsx');

        try {
            // Assert
            $this->assertInstanceOf(BinaryFileResponse::class, $response);
            $ruta = $response->getFile()->getPathname();
            $this->assertFileExists($ruta);
            $this->assertSame('PK', file_get_contents($ruta, false, null, 0, 2));
        } finally {
            if ($response instanceof BinaryFileResponse) {
                $ruta = $response->getFile()->getPathname();
                if (is_file($ruta)) {
                    unlink($ruta);
                }
            }
        }
    }
}
