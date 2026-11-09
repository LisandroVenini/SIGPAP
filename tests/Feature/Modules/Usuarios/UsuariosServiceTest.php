<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Services\UsuariosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UsuariosServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_autorizar_returns_true_when_the_user_holds_the_permission(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $permission = Permission::create(['name' => 'inventario.ver', 'guard_name' => 'web']);
        $usuario->givePermissionTo($permission);
        $service = new UsuariosService;

        // Act
        $autorizado = $service->autorizar($usuario, 'inventario.ver');

        // Assert
        $this->assertTrue($autorizado);
    }

    public function test_autorizar_returns_false_when_the_user_lacks_the_permission(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $service = new UsuariosService;

        // Act
        $autorizado = $service->autorizar($usuario, 'compras.aprobar');

        // Assert
        $this->assertFalse($autorizado);
    }

    public function test_resumenes_de_usuarios_returns_id_and_name_in_a_single_query(): void
    {
        // Arrange
        $laura = Usuario::factory()->create(['nombre' => 'Laura Cáceres']);
        $pedro = Usuario::factory()->create(['nombre' => 'Pedro Gómez']);
        $service = new UsuariosService;

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $resumenes = $service->resumenesDeUsuarios([$laura->id, $pedro->id, $laura->id]);

        // Assert
        $consultas = collect(DB::getQueryLog())
            ->filter(fn (array $entry): bool => str_contains($entry['query'], 'from "usuarios"'))
            ->count();

        DB::disableQueryLog();

        $this->assertSame(1, $consultas);
        $this->assertSame([
            $laura->id => ['id' => $laura->id, 'nombre' => 'Laura Cáceres'],
            $pedro->id => ['id' => $pedro->id, 'nombre' => 'Pedro Gómez'],
        ], collect($resumenes)->sortKeys()->all());
    }

    public function test_resumenes_de_usuarios_returns_an_empty_array_without_ids(): void
    {
        // Arrange
        $service = new UsuariosService;

        // Act
        $resumenes = $service->resumenesDeUsuarios([]);

        // Assert
        $this->assertSame([], $resumenes);
    }
}
