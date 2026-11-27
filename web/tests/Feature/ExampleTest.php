<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL sends guests to the login page.
     */
    public function test_the_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
    }
}
