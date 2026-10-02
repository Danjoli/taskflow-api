<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication to update notification preferences', function () {
    $this->patchJson('/api/me/preferences', ['timezone' => 'America/Sao_Paulo'])
        ->assertUnauthorized();
});

it('updates timezone and deadline notification preference', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/me/preferences', [
        'timezone' => 'America/Sao_Paulo',
        'deadline_notifications_enabled' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.user.timezone', 'America/Sao_Paulo')
        ->assertJsonPath('data.user.deadline_notifications_enabled', false);
});

it('validates notification preferences', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson('/api/me/preferences', [
        'timezone' => 'Invalid/Zone',
        'deadline_notifications_enabled' => 'maybe',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'timezone',
            'deadline_notifications_enabled',
        ]);
});
