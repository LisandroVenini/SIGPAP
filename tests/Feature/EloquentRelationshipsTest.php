<?php

namespace Tests\Feature;

use App\Modules\Compras\Models\Cotizacion;
use App\Modules\Compras\Models\LineaCotizacion;
use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Models\SolicitudDeCotizacion;
use App\Modules\Inventario\Models\Articulo;
use App\Modules\Inventario\Models\Reserva;
use App\Modules\Inventario\Models\UnidadDeMedida;
use App\Modules\Produccion\Models\ControlDeCalidad;
use App\Modules\Produccion\Models\Etapa;
use App\Modules\Produccion\Models\Maquinaria;
use App\Modules\Produccion\Models\Operario;
use App\Modules\Produccion\Models\OrdenDeProduccion;
use App\Modules\Produccion\Models\ParteDiario;
use App\Modules\Produccion\Models\PlanificacionDeProduccion;
use App\Modules\Produccion\Models\RegistroPrioridad;
use App\Modules\Usuarios\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EloquentRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_articulo_belongs_to_unidad_and_has_movements(): void
    {
        // Arrange
        $unidad = UnidadDeMedida::create(['nombre' => 'Unidad', 'factor_conversion' => 1]);
        $articulo = Articulo::create([
            'codigo_sku' => 'SKU-1',
            'descripcion' => 'Artículo',
            'tipo' => 'Materia Prima',
            'unidad_de_medida_id' => $unidad->id,
        ]);

        // Act / Assert
        $this->assertTrue($articulo->unidadDeMedida->is($unidad));
        $this->assertTrue($unidad->articulos->contains($articulo));
    }

    public function test_orden_de_produccion_relations(): void
    {
        // Arrange
        [$orden] = $this->createOrden();
        $operario = Operario::create(['nombre' => 'Juan', 'apellido' => 'Pérez', 'especialidad' => 'Pintado']);
        $maquinaria = Maquinaria::create(['nombre' => 'Lijadora L-03', 'tipo' => 'Lijadora']);

        // Act
        $orden->operarios()->attach($operario->id);
        $orden->maquinarias()->attach($maquinaria->id);

        // Assert
        $this->assertSame($orden->planificacion_de_produccion_id, $orden->planificacionDeProduccion->id);
        $this->assertTrue($orden->operarios->contains($operario));
        $this->assertTrue($orden->maquinarias->contains($maquinaria));
        $this->assertTrue($maquinaria->ordenesDeProduccion->contains($orden));
    }

    public function test_parte_diario_and_control_de_calidad_relations(): void
    {
        // Arrange
        [$orden] = $this->createOrden();
        $etapa = Etapa::create(['nombre' => 'Lijado']);
        $maquinaria = Maquinaria::create(['nombre' => 'Lijadora', 'tipo' => 'Lijadora']);

        // Act
        $parte = ParteDiario::create([
            'orden_de_produccion_id' => $orden->id,
            'etapa_id' => $etapa->id,
            'maquinaria_id' => $maquinaria->id,
            'fecha' => now(),
            'cantidad_conformada' => 5,
        ]);
        $control = ControlDeCalidad::create([
            'orden_de_produccion_id' => $orden->id,
            'fecha' => now(),
            'cantidad_aprobada' => 4,
            'cantidad_rechazada' => 1,
        ]);

        // Assert
        $this->assertTrue($parte->ordenDeProduccion->is($orden));
        $this->assertTrue($parte->etapa->is($etapa));
        $this->assertTrue($parte->maquinaria->is($maquinaria));
        $this->assertTrue($control->ordenDeProduccion->is($orden));
        $this->assertTrue($orden->partesDiarios->contains($parte));
        $this->assertTrue($orden->controlesDeCalidad->contains($control));
    }

    public function test_reserva_relations(): void
    {
        // Arrange
        [$orden, $articulo] = $this->createOrden();

        // Act
        $reserva = Reserva::create([
            'articulo_id' => $articulo->id,
            'orden_de_produccion_id' => $orden->id,
            'cantidad' => 5,
        ]);

        // Assert
        $this->assertTrue($reserva->articulo->is($articulo));
        $this->assertTrue($articulo->reservas->contains($reserva));
    }

    public function test_cotizacion_lineas_and_pivot_relations(): void
    {
        // Arrange
        $articulo = $this->createArticulo();
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor', 'cuit' => '20-12345678-9']);
        $solicitud = SolicitudDeCotizacion::create([
            'fecha' => now(),
            'fecha_vencimiento' => now()->addWeek(),
        ]);
        $cotizacion = Cotizacion::create([
            'solicitud_de_cotizacion_id' => $solicitud->id,
            'proveedor_id' => $proveedor->id,
            'fecha' => now(),
        ]);
        $linea = LineaCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'articulo_id' => $articulo->id,
            'precio_unitario' => 10,
            'cantidad_ofertada' => 2,
            'fecha_prometida' => now()->addWeek(),
        ]);

        // Act
        $token = (string) Str::uuid();
        $solicitud->proveedores()->attach($proveedor->id, ['token' => $token]);

        // Assert
        $this->assertTrue($linea->cotizacion->is($cotizacion));
        $this->assertTrue($cotizacion->lineas->contains($linea));
        $this->assertTrue($proveedor->solicitudesDeCotizacion->contains($solicitud));
        $this->assertSame($token, $solicitud->proveedores->first()->pivot->token);
    }

    public function test_registro_prioridad_relations(): void
    {
        // Arrange
        [$orden] = $this->createOrden();
        $usuario = Usuario::factory()->create();

        // Act
        $registro = RegistroPrioridad::create([
            'orden_de_produccion_id' => $orden->id,
            'usuario_id' => $usuario->id,
            'prioridad_nueva' => 1,
            'fecha_cambio' => now(),
            'motivo' => 'Cliente urgente',
        ]);

        // Assert
        $this->assertTrue($registro->ordenDeProduccion->is($orden));
    }

    private function createArticulo(): Articulo
    {
        $unidad = UnidadDeMedida::create(['nombre' => 'Unidad', 'factor_conversion' => 1]);

        return Articulo::create([
            'codigo_sku' => 'SKU-1',
            'descripcion' => 'Artículo',
            'tipo' => 'Materia Prima',
            'unidad_de_medida_id' => $unidad->id,
        ]);
    }

    /**
     * @return array{0: OrdenDeProduccion, 1: Articulo}
     */
    private function createOrden(): array
    {
        $articulo = $this->createArticulo();
        $planificacion = PlanificacionDeProduccion::create([
            'nombre' => 'Campaña',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addMonth(),
        ]);
        $orden = OrdenDeProduccion::create([
            'articulo_id' => $articulo->id,
            'planificacion_de_produccion_id' => $planificacion->id,
            'cantidad_requerida' => 10,
            'fecha_entrega' => now()->addWeek(),
        ]);

        return [$orden, $articulo];
    }
}
