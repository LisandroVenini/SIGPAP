<?php

namespace App\Modules\Reportes\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportadorReportesService
{
    /**
     * Export the report in the requested format.
     *
     * @param  array{titulo: string, tipo: string, columnas: array<int, string>, filas: array<int, array<string, mixed>>, total: int, desde: string, hasta: string}  $reporte
     */
    public function exportar(array $reporte, string $formato): Response|BinaryFileResponse
    {
        if ($formato === 'pdf') {
            return $this->pdf($reporte);
        }

        $ruta = $this->xlsx($reporte);

        return response()
            ->download($ruta, basename($ruta))
            ->deleteFileAfterSend();
    }

    /**
     * Write the report to a temporary XLSX file and return its absolute path.
     *
     * @param  array{columnas: array<int, string>, filas: array<int, array<string, mixed>>, tipo: string}  $reporte
     */
    public function xlsx(array $reporte): string
    {
        $directorio = storage_path('app/reportes');

        if (! is_dir($directorio) && ! mkdir($directorio, 0755, true) && ! is_dir($directorio)) {
            throw new RuntimeException("No se pudo crear el directorio {$directorio}.");
        }

        $ruta = $directorio.'/'.Str::slug($reporte['tipo']).'-'.now()->format('YmdHis').'-'.Str::random(6).'.xlsx';

        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues($reporte['columnas']));

        foreach ($reporte['filas'] as $fila) {
            $writer->addRow(Row::fromValues(array_values($fila)));
        }

        $writer->close();

        return $ruta;
    }

    /**
     * Render the report as a PDF download.
     *
     * @param  array{titulo: string, tipo: string, columnas: array<int, string>, filas: array<int, array<string, mixed>>, total: int, desde: string, hasta: string}  $reporte
     */
    public function pdf(array $reporte): Response
    {
        return Pdf::loadView('reportes.reporte', $reporte)
            ->download('reporte-'.$reporte['tipo'].'-'.now()->format('Ymd').'.pdf');
    }
}
