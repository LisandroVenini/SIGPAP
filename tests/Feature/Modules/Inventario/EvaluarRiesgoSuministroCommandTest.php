<?php

namespace Tests\Feature\Modules\Inventario;

use App\Modules\Inventario\Jobs\EvaluarRiesgoSuministroJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EvaluarRiesgoSuministroCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_enqueues_the_evaluation_job(): void
    {
        Queue::fake();

        $this->artisan('riesgo:evaluar')->assertSuccessful();

        Queue::assertPushed(EvaluarRiesgoSuministroJob::class);
    }
}
