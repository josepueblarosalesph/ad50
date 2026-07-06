<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('the legacy dashboard redirects authenticated users to their role destination', function () {
    $user = User::factory()->create(['role' => 'postulante']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('postulante.panel'));
});
