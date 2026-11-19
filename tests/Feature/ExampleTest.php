<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('app')
            ->assertSee('id="app"', false)
            ->assertDontSee('Laravel');
    }

    public function test_application_pages_use_the_vue_shell_instead_of_a_default_welcome_page(): void
    {
        $this->get('/students')
            ->assertOk()
            ->assertViewIs('app')
            ->assertSee('id="app"', false)
            ->assertDontSee('Laravel');

        $this->assertFileDoesNotExist(resource_path('views/welcome.blade.php'));
    }
}
