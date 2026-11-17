<?php

namespace Tests\Feature\Modules\Auditoria;

use Database\Factories\EventoDeAuditoriaFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_audit_event_cannot_be_updated(): void
    {
        // Arrange
        $evento = EventoDeAuditoriaFactory::new()->create();

        // Act / Assert
        try {
            DB::table('eventos_de_auditoria')
                ->where('id', $evento->id)
                ->update(['accion' => 'alterado']);

            $this->fail('The audit table must reject UPDATE statements.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_an_audit_event_cannot_be_deleted(): void
    {
        // Arrange
        $evento = EventoDeAuditoriaFactory::new()->create();

        // Act / Assert
        try {
            DB::table('eventos_de_auditoria')->where('id', $evento->id)->delete();

            $this->fail('The audit table must reject DELETE statements.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
