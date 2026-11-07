<?php

namespace Tests\Feature\Modules\Compras;

use App\Modules\Compras\Models\Proveedor;
use App\Modules\Compras\Services\GestionProveedoresService;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use Database\Factories\OrdenDeCompraFactory;
use Database\Factories\SolicitudDeCotizacionFactory;
use Database\Factories\SolicitudDeDisponibilidadFactory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GestionProveedoresServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Usuario
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $usuario = Usuario::factory()->create();
        $usuario->assignRole(Rol::EncargadoCompras->value);

        return $usuario;
    }

    private function service(): GestionProveedoresService
    {
        return app(GestionProveedoresService::class);
    }

    public function test_creates_a_supplier_as_active_and_audits_it(): void
    {
        // Arrange
        $actor = $this->actor();

        // Act
        $proveedor = $this->service()->crear($actor, [
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '30-71234567-8',
            'email' => 'compras@acerosdelsur.test',
            'telefono' => '11-4444-5555',
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Activo', $proveedor->estado);
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '30-71234567-8',
            'email' => 'compras@acerosdelsur.test',
            'telefono' => '11-4444-5555',
            'estado' => 'Activo',
        ]);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'crear_proveedor',
            'usuario_id' => $actor->id,
            'auditable_type' => Proveedor::class,
            'auditable_id' => $proveedor->id,
        ]);
    }

    public function test_rejects_a_duplicate_cuit(): void
    {
        // Arrange
        $actor = $this->actor();
        Proveedor::factory()->create([
            'cuit' => '30-71234567-8',
            'email' => 'existente@example.test',
        ]);

        // Act / Assert
        try {
            $this->service()->crear($actor, [
                'razon_social' => 'Otra empresa',
                'cuit' => '30-71234567-8',
                'email' => null,
                'telefono' => null,
            ]);

            $this->fail('A duplicate CUIT should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cuit', $exception->errors());
        }

        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_rejects_a_duplicate_email_on_create(): void
    {
        // Arrange
        $actor = $this->actor();
        Proveedor::factory()->create([
            'cuit' => '20-99999999-9',
            'email' => 'proveedor@example.test',
        ]);

        // Act / Assert
        try {
            $this->service()->crear($actor, [
                'razon_social' => 'Otra empresa',
                'cuit' => '20-11111111-2',
                'email' => 'proveedor@example.test',
                'telefono' => null,
            ]);

            $this->fail('A duplicate email should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_rejects_a_duplicate_email_on_update(): void
    {
        // Arrange
        $actor = $this->actor();
        Proveedor::factory()->create([
            'cuit' => '20-11111111-2',
            'email' => 'existente@example.test',
        ]);
        $proveedor = Proveedor::factory()->create([
            'cuit' => '20-22222222-3',
            'email' => 'propio@example.test',
        ]);

        // Act / Assert
        try {
            $this->service()->actualizar($actor, $proveedor, [
                'razon_social' => $proveedor->razon_social,
                'email' => 'existente@example.test',
                'telefono' => null,
            ]);

            $this->fail('A duplicate email should be rejected on update.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'email' => 'propio@example.test',
        ]);
    }

    public function test_lists_suppliers_with_related_counts(): void
    {
        // Arrange
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Aceros del Sur']);
        $solicitud = SolicitudDeCotizacionFactory::new()->create();
        $proveedor->solicitudesDeCotizacion()->attach($solicitud->id, ['token' => null]);
        $disponibilidad = SolicitudDeDisponibilidadFactory::new()->create(['proveedor_id' => $proveedor->id]);
        OrdenDeCompraFactory::new()->create([
            'proveedor_id' => $proveedor->id,
            'solicitud_de_disponibilidad_id' => $disponibilidad->id,
        ]);

        // Act
        $resultados = $this->service()->listar([]);

        // Assert
        $this->assertCount(1, $resultados->items());
        $fila = $resultados->items()[0];
        $this->assertSame([
            'id',
            'razon_social',
            'cuit',
            'email',
            'telefono',
            'estado',
            'solicitudes',
            'ordenes',
        ], array_keys($fila));
        $this->assertSame(1, $fila['solicitudes']);
        $this->assertSame(1, $fila['ordenes']);
    }

    public function test_lists_suppliers_ordered_by_business_name_with_fifteen_per_page(): void
    {
        // Arrange
        Proveedor::factory()
            ->count(16)
            ->sequence(fn ($sequence) => ['razon_social' => sprintf('Zeta %02d', $sequence->index + 1)])
            ->create();
        Proveedor::factory()->create(['razon_social' => 'Alfa Metales']);

        // Act
        $resultados = $this->service()->listar([]);

        // Assert
        $this->assertSame(15, $resultados->perPage());
        $this->assertSame(17, $resultados->total());
        $this->assertSame('Alfa Metales', $resultados->items()[0]['razon_social']);
    }

    public function test_searches_suppliers_by_reason_social(): void
    {
        // Arrange
        Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'cuit' => '30-00000001-1',
            'email' => 'uno@example.test',
        ]);
        Proveedor::factory()->create([
            'razon_social' => 'Plásticos Norte',
            'cuit' => '30-00000002-2',
            'email' => 'dos@example.test',
        ]);

        // Act
        $resultados = $this->service()->listar(['buscar' => 'acero']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Aceros del Sur', $resultados->items()[0]['razon_social']);
    }

    public function test_searches_suppliers_by_cuit(): void
    {
        // Arrange
        Proveedor::factory()->create([
            'razon_social' => 'Uno',
            'cuit' => '30-71234567-8',
            'email' => 'uno@example.test',
        ]);
        Proveedor::factory()->create([
            'razon_social' => 'Dos',
            'cuit' => '20-11111111-2',
            'email' => 'dos@example.test',
        ]);

        // Act
        $resultados = $this->service()->listar(['buscar' => '71234567']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('30-71234567-8', $resultados->items()[0]['cuit']);
    }

    public function test_searches_suppliers_by_email(): void
    {
        // Arrange
        Proveedor::factory()->create([
            'razon_social' => 'Uno',
            'cuit' => '30-00000001-1',
            'email' => 'ventas@aceros.test',
        ]);
        Proveedor::factory()->create([
            'razon_social' => 'Dos',
            'cuit' => '30-00000002-2',
            'email' => 'contacto@plasticos.test',
        ]);

        // Act
        $resultados = $this->service()->listar(['buscar' => 'aceros']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('ventas@aceros.test', $resultados->items()[0]['email']);
    }

    public function test_search_is_case_insensitive(): void
    {
        // Arrange
        Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'cuit' => '30-00000001-1',
            'email' => 'uno@example.test',
        ]);
        Proveedor::factory()->create([
            'razon_social' => 'Plásticos Norte',
            'cuit' => '30-00000002-2',
            'email' => 'dos@example.test',
        ]);

        // Act
        $resultados = $this->service()->listar(['buscar' => 'ACEROS']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Aceros del Sur', $resultados->items()[0]['razon_social']);
    }

    public function test_search_escapes_wildcard_characters(): void
    {
        // Arrange
        Proveedor::factory()->create([
            'razon_social' => 'Metales 100% puro',
            'cuit' => '30-00000001-1',
            'email' => 'uno@example.test',
        ]);
        Proveedor::factory()->create([
            'razon_social' => 'Metales 100X puro',
            'cuit' => '30-00000002-2',
            'email' => 'dos@example.test',
        ]);

        // Act
        $resultados = $this->service()->listar(['buscar' => '100%']);

        // Assert
        $this->assertCount(1, $resultados->items());
        $this->assertSame('Metales 100% puro', $resultados->items()[0]['razon_social']);
    }

    public function test_updates_only_the_allowed_fields_and_ignores_the_cuit(): void
    {
        // Arrange
        $actor = $this->actor();
        $proveedor = Proveedor::factory()->create([
            'razon_social' => 'Aceros del Sur',
            'cuit' => '30-71234567-8',
            'email' => 'viejo@aceros.test',
            'telefono' => '11-1111-1111',
        ]);

        // Act
        $actualizado = $this->service()->actualizar($actor, $proveedor, [
            'razon_social' => 'Aceros del Sur S.A.',
            'cuit' => '20-99999999-9',
            'email' => 'nuevo@aceros.test',
            'telefono' => '11-2222-2222',
        ], '127.0.0.1');

        // Assert
        $this->assertSame('Aceros del Sur S.A.', $actualizado->razon_social);
        $this->assertSame('nuevo@aceros.test', $actualizado->email);
        $this->assertSame('11-2222-2222', $actualizado->telefono);
        $this->assertSame('30-71234567-8', $actualizado->cuit);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'actualizar_proveedor',
            'usuario_id' => $actor->id,
            'auditable_type' => Proveedor::class,
            'auditable_id' => $proveedor->id,
        ]);
    }

    public function test_deactivates_a_supplier_and_audits_it(): void
    {
        // Arrange
        $actor = $this->actor();
        $proveedor = Proveedor::factory()->create(['estado' => 'Activo']);

        // Act
        $this->service()->darDeBaja($actor, $proveedor, '127.0.0.1');

        // Assert
        $this->assertSame('Inactivo', $proveedor->refresh()->estado);
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'estado' => 'Inactivo']);
        $this->assertDatabaseHas('eventos_de_auditoria', [
            'accion' => 'dar_de_baja_proveedor',
            'usuario_id' => $actor->id,
            'auditable_type' => Proveedor::class,
            'auditable_id' => $proveedor->id,
        ]);
    }

    public function test_deactivating_a_supplier_twice_is_idempotent(): void
    {
        // Arrange
        $actor = $this->actor();
        $proveedor = Proveedor::factory()->create(['estado' => 'Activo']);

        // Act
        $this->service()->darDeBaja($actor, $proveedor, '127.0.0.1');
        $this->service()->darDeBaja($actor, $proveedor, '127.0.0.1');

        // Assert
        $this->assertSame('Inactivo', $proveedor->refresh()->estado);
        $this->assertDatabaseCount('eventos_de_auditoria', 1);
    }
}
