<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'admin'])->get('/_admin-probe', fn () => 'ok');
});

it('defaults new users to the buyer role', function () {
    expect(User::factory()->create()->role)->toBe(UserRole::Buyer);
});

it('does not allow role to be mass assigned', function () {
    $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret123', 'role' => 'admin']);

    expect($user->fresh()->role)->toBe(UserRole::Buyer);
});

it('lets admins through the admin middleware', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/_admin-probe')->assertOk();
});

it('forbids buyers from the admin middleware', function () {
    $this->actingAs(User::factory()->create())->get('/_admin-probe')->assertForbidden();
});

it('redirects guests to login', function () {
    $this->get('/_admin-probe')->assertRedirect('/login');
});
