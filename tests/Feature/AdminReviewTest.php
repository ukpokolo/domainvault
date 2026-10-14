<?php

use App\Enums\DomainStatus;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('approves a pending listing and writes an audit entry', function () {
    $domain = Domain::factory()->pending()->create();

    $this->actingAs($this->admin)->post("/admin/review/{$domain->id}/approve")->assertSessionHas('success');

    expect($domain->fresh()->status)->toBe(DomainStatus::Live);
    $log = AuditLog::sole();
    expect($log->action)->toBe('domain.approved')
        ->and($log->user_id)->toBe($this->admin->id)
        ->and($log->auditable_id)->toBe($domain->id)
        ->and($log->meta)->toBe(['from' => 'pending', 'to' => 'live']);
});

it('rejects a pending listing back to draft and records the reason', function () {
    $domain = Domain::factory()->pending()->create();

    $this->actingAs($this->admin)->post("/admin/review/{$domain->id}/reject", ['reason' => 'Spammy backlinks'])->assertSessionHas('success');

    expect($domain->fresh()->status)->toBe(DomainStatus::Draft)
        ->and(AuditLog::sole()->meta['reason'])->toBe('Spammy backlinks');
});

it('lists only pending domains in the review queue', function () {
    Domain::factory()->pending()->count(2)->create();
    Domain::factory()->live()->create();

    $this->actingAs($this->admin)->get('/admin/review')
        ->assertInertia(fn ($p) => $p->component('Admin/Review')->has('domains.data', 2));
});

it('changes status within allowed transitions', function () {
    $domain = Domain::factory()->live()->create();

    $this->actingAs($this->admin)->patch("/admin/domains/{$domain->id}/status", ['status' => 'draft'])->assertSessionHas('success');

    expect($domain->fresh()->status)->toBe(DomainStatus::Draft)
        ->and(AuditLog::sole()->action)->toBe('domain.status_changed');
});

it('refuses to move a sold domain or to set sold by hand', function () {
    $sold = Domain::factory()->sold()->create();
    $live = Domain::factory()->live()->create();

    $this->actingAs($this->admin)->patch("/admin/domains/{$sold->id}/status", ['status' => 'live'])->assertSessionHas('error');
    $this->actingAs($this->admin)->patch("/admin/domains/{$live->id}/status", ['status' => 'sold'])->assertSessionHasErrors('status');

    expect($sold->fresh()->status)->toBe(DomainStatus::Sold)
        ->and($live->fresh()->status)->toBe(DomainStatus::Live)
        ->and(AuditLog::count())->toBe(0);
});

it('refuses to approve a listing that is not pending', function () {
    $sold = Domain::factory()->sold()->create();

    $this->actingAs($this->admin)->post("/admin/review/{$sold->id}/approve")->assertSessionHas('error');

    expect($sold->fresh()->status)->toBe(DomainStatus::Sold);
});

it('filters the admin domain list by status and name', function () {
    Domain::factory()->live()->create(['name' => 'alpha.com']);
    Domain::factory()->pending()->create(['name' => 'beta.com']);

    $this->actingAs($this->admin)->get('/admin/domains?status=pending')
        ->assertInertia(fn ($p) => $p->has('domains.data', 1)->where('domains.data.0.name', 'beta.com'));
    $this->actingAs($this->admin)->get('/admin/domains?q=alph')
        ->assertInertia(fn ($p) => $p->has('domains.data', 1)->where('domains.data.0.name', 'alpha.com'));
});

it('shows the audit log newest first', function () {
    $a = Domain::factory()->pending()->create();
    $b = Domain::factory()->pending()->create();
    $this->actingAs($this->admin)->post("/admin/review/{$a->id}/approve");
    $this->actingAs($this->admin)->post("/admin/review/{$b->id}/reject");

    $this->actingAs($this->admin)->get('/admin/audit-log')->assertInertia(fn ($p) => $p
        ->has('logs.data', 2)->where('logs.data.0.action', 'domain.rejected')->where('logs.data.0.actor', $this->admin->name));
});

it('serialises every paginated admin prop with a meta block the UI relies on', function () {
    $this->actingAs($this->admin)->get('/admin/audit-log')->assertInertia(fn ($p) => $p->has('logs.meta.last_page')->has('logs.meta.links'));
    $this->actingAs($this->admin)->get('/admin/imports')->assertInertia(fn ($p) => $p->has('batches.meta.last_page')->has('batches.meta.links'));
    $this->actingAs($this->admin)->get('/admin/review')->assertInertia(fn ($p) => $p->has('domains.meta.last_page'));
});
