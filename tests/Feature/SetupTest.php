<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_setup_when_no_admin_exists(): void
    {
        $this->get('/pos/login')->assertRedirect('/pos/setup');
        $this->get('/pos/setup')->assertOk();
    }

    public function test_setup_creates_the_first_admin_once(): void
    {
        $this->post('/pos/setup', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/pos/login');

        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'role' => 'admin']);

        $this->get('/pos/setup')->assertNotFound();
        $this->post('/pos/setup', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
        $this->get('/pos/login')->assertOk();
    }

    public function test_login_page_shows_when_admin_exists(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->get('/pos/login')->assertOk();
    }
}
