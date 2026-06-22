<?php

use App\Models\User;

it('redirects guest users to login', function () {
    $response = $this->get('/');
    $response->assertRedirect(route('login'));
});

it('redirects authenticated users to dashboard', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $response = $this->actingAs($user)->get('/');
    $response->assertRedirect(route('dashboard'));
});
