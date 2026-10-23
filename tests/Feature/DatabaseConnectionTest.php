<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify the application connects to the configured PostgreSQL test database
     * and that migrations created the expected schema.
     */
    public function test_it_uses_postgresql_and_has_usuarios_table(): void
    {
        // Arrange
        $connectionName = DB::connection()->getDriverName();

        // Act
        $hasUsuariosTable = Schema::hasTable('usuarios');

        // Assert
        $this->assertSame('pgsql', $connectionName);
        $this->assertTrue($hasUsuariosTable);
    }

    public function test_it_has_the_security_and_permissions_schema(): void
    {
        // Arrange / Act / Assert
        $this->assertTrue(Schema::hasColumn('usuarios', 'nombre'));
        $this->assertTrue(Schema::hasColumn('usuarios', 'clave_acceso'));
        $this->assertTrue(Schema::hasColumn('usuarios', 'estado'));

        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('model_has_roles'));
        $this->assertTrue(Schema::hasTable('model_has_permissions'));
        $this->assertTrue(Schema::hasTable('role_has_permissions'));

        $this->assertTrue(Schema::hasTable('parametros_globales'));
    }
}
