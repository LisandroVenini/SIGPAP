<?php

namespace Tests\Feature;

use Database\Factories\AlertaFactory;
use Database\Factories\ArticuloFactory;
use Database\Factories\AsignacionDeEtapaFactory;
use Database\Factories\ControlDeCalidadFactory;
use Database\Factories\CotizacionFactory;
use Database\Factories\DesempenoProveedorFactory;
use Database\Factories\EscenarioDeSimulacionFactory;
use Database\Factories\EtapaFactory;
use Database\Factories\EventoDeAuditoriaFactory;
use Database\Factories\IncidenciaFactory;
use Database\Factories\IntervencionDeMantenimientoFactory;
use Database\Factories\LineaCotizacionFactory;
use Database\Factories\LineaOrdenCompraFactory;
use Database\Factories\LineaSolicitudCotizacionFactory;
use Database\Factories\MaquinariaFactory;
use Database\Factories\MovimientoDeStockFactory;
use Database\Factories\OperarioFactory;
use Database\Factories\OrdenDeCompraFactory;
use Database\Factories\OrdenDeProduccionFactory;
use Database\Factories\ParametroGlobalFactory;
use Database\Factories\ParteDiarioFactory;
use Database\Factories\PlanDeMantenimientoFactory;
use Database\Factories\PlanificacionDeProduccionFactory;
use Database\Factories\ProveedorFactory;
use Database\Factories\RegistroPrioridadFactory;
use Database\Factories\ReservaFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Database\Factories\TasaDeRendimientoFactory;
use Database\Factories\UnidadDeMedidaFactory;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_factories_create_a_valid_record(): void
    {
        // Arrange
        $factories = [
            ParametroGlobalFactory::class,
            UsuarioFactory::class,
            UnidadDeMedidaFactory::class,
            ArticuloFactory::class,
            ReservaFactory::class,
            MovimientoDeStockFactory::class,
            PlanificacionDeProduccionFactory::class,
            OrdenDeProduccionFactory::class,
            EtapaFactory::class,
            OperarioFactory::class,
            MaquinariaFactory::class,
            AsignacionDeEtapaFactory::class,
            ParteDiarioFactory::class,
            ControlDeCalidadFactory::class,
            PlanDeMantenimientoFactory::class,
            IntervencionDeMantenimientoFactory::class,
            RegistroPrioridadFactory::class,
            ProveedorFactory::class,
            SolicitudDeDisponibilidadFactory::class,
            SolicitudDeCotizacionFactory::class,
            CotizacionFactory::class,
            LineaCotizacionFactory::class,
            OrdenDeCompraFactory::class,
            LineaOrdenCompraFactory::class,
            LineaSolicitudCotizacionFactory::class,
            IncidenciaFactory::class,
            DesempenoProveedorFactory::class,
            TasaDeRendimientoFactory::class,
            EscenarioDeSimulacionFactory::class,
            EventoDeAuditoriaFactory::class,
            AlertaFactory::class,
        ];

        // Act / Assert
        foreach ($factories as $factoryClass) {
            $model = $factoryClass::new()->create();

            $this->assertTrue(
                $model->exists,
                "Factory {$factoryClass} failed to persist a record."
            );
        }
    }
}
