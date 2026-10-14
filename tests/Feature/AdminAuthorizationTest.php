<?php

use App\Models\Domain;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->domain = Domain::factory()->pending()->create();
    $this->batch = ImportBatch::create(['user_id' => User::factory()->admin()->create()->id, 'original_name' => 'a.csv', 'path' => 'imports/a.csv', 'status' => 'completed']);
});

dataset('admin routes', [
    'dashboard' => ['get', fn () => '/admin'],
    'domains' => ['get', fn () => '/admin/domains'],
    'status change' => ['patch', fn ($t) => "/admin/domains/{$t->domain->id}/status"],
    'review queue' => ['get', fn () => '/admin/review'],
    'approve' => ['post', fn ($t) => "/admin/review/{$t->domain->id}/approve"],
    'reject' => ['post', fn ($t) => "/admin/review/{$t->domain->id}/reject"],
    'imports' => ['get', fn () => '/admin/imports'],
    'import upload' => ['post', fn () => '/admin/imports'],
    'error report' => ['get', fn ($t) => "/admin/imports/{$t->batch->id}/errors"],
    'audit log' => ['get', fn () => '/admin/audit-log'],
]);

it('redirects guests away from admin routes', function (string $method, Closure $uri) {
    $this->{$method}($uri($this))->assertRedirect('/login');
})->with('admin routes');

it('forbids buyers from every admin route', function (string $method, Closure $uri) {
    $this->actingAs(User::factory()->create())->{$method}($uri($this))->assertForbidden();
})->with('admin routes');

it('lets admins open the admin pages', function () {
    $admin = User::factory()->admin()->create();

    foreach (['/admin', '/admin/domains', '/admin/review', '/admin/imports', '/admin/audit-log'] as $uri) {
        $this->actingAs($admin)->get($uri)->assertOk();
    }
});

it('keeps buyers from seeing non-public listings', function () {
    $this->actingAs(User::factory()->create())->get("/domains/{$this->domain->id}")->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get("/domains/{$this->domain->id}")->assertOk();
});
