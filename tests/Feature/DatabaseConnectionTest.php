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
    public function test_it_uses_postgresql_and_has_users_table(): void
    {
        // Arrange
        $connectionName = DB::connection()->getDriverName();

        // Act
        $hasUsersTable = Schema::hasTable('users');

        // Assert
        $this->assertSame('pgsql', $connectionName);
        $this->assertTrue($hasUsersTable);
    }
}
