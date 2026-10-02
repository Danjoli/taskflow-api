<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_by_email_and_ip(): void
    {
        config(['taskflow.rate_limits.login_per_minute' => 2]);

        $payload = [
            'email' => 'rate-limit@example.com',
            'password' => 'invalid-password',
        ];

        $this->postJson('/api/login', $payload)->assertUnauthorized();
        $this->postJson('/api/login', $payload)->assertUnauthorized();

        $this->postJson('/api/login', $payload)
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }

    public function test_registration_is_rate_limited_by_ip(): void
    {
        config(['taskflow.rate_limits.register_per_hour' => 2]);

        $this->postJson('/api/register', [])->assertUnprocessable();
        $this->postJson('/api/register', [])->assertUnprocessable();

        $this->postJson('/api/register', [])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }

    public function test_authenticated_api_is_rate_limited_by_user(): void
    {
        config(['taskflow.rate_limits.api_per_minute' => 2]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/me')->assertOk();
        $this->getJson('/api/me')->assertOk();

        $this->getJson('/api/me')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }

    public function test_authenticated_users_have_independent_rate_limits(): void
    {
        config(['taskflow.rate_limits.api_per_minute' => 1]);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Sanctum::actingAs($firstUser);
        $this->getJson('/api/me')->assertOk();
        $this->getJson('/api/me')->assertTooManyRequests();

        Sanctum::actingAs($secondUser);
        $this->getJson('/api/me')->assertOk();
    }
}
