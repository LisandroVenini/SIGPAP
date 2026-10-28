<?php

namespace Tests\Feature\Modules\Usuarios;

use App\Modules\Usuarios\Enums\EstadoUsuario;
use App\Modules\Usuarios\Enums\Rol;
use App\Modules\Usuarios\Models\Usuario;
use App\Modules\Usuarios\Queries\UsuariosQuery;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_filters_by_search_role_and_state(): void
    {
        // Arrange
        $this->seed(RoleSeeder::class);

        $laura = Usuario::factory()->create(['nombre' => 'Laura Cáceres', 'email' => 'laura@confort.test']);
        $laura->assignRole(Rol::Gerente->value);

        $sofia = Usuario::factory()->create(['nombre' => 'Sofía Benítez', 'email' => 'sofia@confort.test']);
        $sofia->estado = EstadoUsuario::Inhabilitado->value;
        $sofia->save();
        $sofia->assignRole(Rol::EncargadoStock->value);

        Usuario::factory()->create(['nombre' => 'Pedro Gómez', 'email' => 'pedro@confort.test']);

        $query = new UsuariosQuery;

        // Act / Assert
        $this->assertSame(1, $query->listar(['buscar' => 'Laura'])->total());
        $this->assertSame(1, $query->listar(['rol' => Rol::EncargadoStock->value])->total());
        $this->assertSame(1, $query->listar(['estado' => EstadoUsuario::Inhabilitado->value])->total());
        $this->assertSame(3, $query->listar()->total());
    }

    public function test_resumen_aggregates_active_and_disabled_usuarios(): void
    {
        // Arrange
        Usuario::factory()->count(2)->create();

        $inhabilitado = Usuario::factory()->create();
        $inhabilitado->estado = EstadoUsuario::Inhabilitado->value;
        $inhabilitado->save();

        // Act
        $resumen = (new UsuariosQuery)->resumen();

        // Assert
        $this->assertSame(3, $resumen['total']);
        $this->assertSame(2, $resumen['activos']);
        $this->assertSame(1, $resumen['inhabilitados']);
    }
}
