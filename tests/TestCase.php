<?php

namespace Tests;

use App\Modules\Predictivo\Services\MLEngine;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakeMLEngine;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // The suite never runs the real Python engine: it would depend on the
        // virtualenv and on wall-clock time. Tests that need the process
        // engine forget this instance explicitly.
        $this->app->instance(MLEngine::class, new FakeMLEngine);
    }
}
