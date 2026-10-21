<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page is rendered by Inertia with the Welcome component.
     */
    public function test_the_home_page_renders_the_welcome_inertia_component(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Welcome')
                ->where('appName', config('app.name'))
        );
    }
}
