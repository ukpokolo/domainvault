<?php

use App\Jobs\ImportDomainsJob;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
});

function csv(string $content, string $name = 'domains.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

const HEADER = "name,registered_year,authority_score,niche,price,backlinks,referring_domains,status,notes\n";

it('queues a job and records an audit entry on upload', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post('/admin/imports', ['file' => csv(HEADER."a.com,2010,50,Fitness,500\n")])
        ->assertSessionHas('success');

    Queue::assertPushed(ImportDomainsJob::class);
    expect(ImportBatch::sole()->status)->toBe('queued')
        ->and(AuditLog::sole()->action)->toBe('import.started');
});

it('imports valid rows and defaults status to pending', function () {
    $this->actingAs($this->admin)->post('/admin/imports', ['file' => csv(
        HEADER."Alpha.com,2008,60,Fitness,1500.50,1000,80,,Great domain\nbeta.io,2015,35,Pets,300,,,live,\n"
    )]);

    $batch = ImportBatch::sole();
    expect($batch->status)->toBe('completed')
        ->and([$batch->total_rows, $batch->success_rows, $batch->failed_rows])->toBe([2, 2, 0])
        ->and($batch->error_report_path)->toBeNull();

    $alpha = Domain::firstWhere('name', 'alpha.com');
    expect($alpha->tld)->toBe('com')
        ->and($alpha->price_cents)->toBe(150_050)
        ->and($alpha->status->value)->toBe('pending')
        ->and($alpha->notes)->toBe('Great domain')
        ->and(Domain::firstWhere('name', 'beta.io')->status->value)->toBe('live');
});

it('validates row by row, imports the good ones and builds an error report', function () {
    Domain::factory()->create(['name' => 'taken.com', 'tld' => 'com']);

    $rows = [
        'good.com,2010,50,Fitness,500',
        'not a domain,2010,50,Fitness,500',
        'taken.com,2010,50,Fitness,500',
        'future.com,'.(now()->year + 5).',50,Fitness,500',
        'strong.com,2010,150,Fitness,500',
        'free.com,2010,50,Fitness,0',
        'good.com,2011,50,Fitness,600',
        'nostatus.com,2010,50,Fitness,500,,,sold',
        '=cmd.com,2010,50,Fitness,500',
    ];

    $this->actingAs($this->admin)->post('/admin/imports', ['file' => csv(HEADER.implode("\n", $rows)."\n")]);

    $batch = ImportBatch::sole();
    expect([$batch->total_rows, $batch->success_rows, $batch->failed_rows])->toBe([9, 1, 8])
        ->and(Domain::where('name', 'good.com')->count())->toBe(1);

    $report = Storage::disk('local')->get($batch->error_report_path);
    expect($report)->toStartWith("line,name,errors\n")
        ->toContain('3,"not a domain"')
        ->toContain('4,taken.com')->toContain('already exists')
        ->toContain('Duplicate of row 2')
        ->toContain('between 0 and 100');
    // formula-leading cells are neutralised
    expect($report)->toContain("'=cmd.com");
});

it('lets admins download the error report and 404s when there is none', function () {
    $this->actingAs($this->admin)->post('/admin/imports', ['file' => csv(HEADER."bad,2010,50,Fitness,500\n")]);
    $batch = ImportBatch::sole();

    $this->actingAs($this->admin)->get("/admin/imports/{$batch->id}/errors")
        ->assertOk()->assertDownload("import-{$batch->id}-errors.csv");

    $clean = ImportBatch::create(['user_id' => $this->admin->id, 'original_name' => 'x', 'path' => 'x', 'status' => 'completed']);
    $this->actingAs($this->admin)->get("/admin/imports/{$clean->id}/errors")->assertNotFound();
});

it('fails the batch when required columns are missing', function () {
    $this->actingAs($this->admin)->post('/admin/imports', ['file' => csv("name,price\na.com,10\n")]);

    $batch = ImportBatch::sole();
    expect($batch->status)->toBe('failed')
        ->and($batch->failure_message)->toContain('registered_year')->toContain('authority_score')
        ->and(Domain::count())->toBe(0);
});

it('handles a UTF-8 BOM and blank lines', function () {
    $this->actingAs($this->admin)->post('/admin/imports', ['file' => csv("\xEF\xBB\xBF".HEADER."\na.com,2010,50,Fitness,500\n\n")]);

    expect(ImportBatch::sole()->success_rows)->toBe(1);
});

it('rejects uploads that are not CSV or are missing', function () {
    $this->actingAs($this->admin)->post('/admin/imports', ['file' => UploadedFile::fake()->image('x.png')])->assertSessionHasErrors('file');
    $this->actingAs($this->admin)->post('/admin/imports', [])->assertSessionHasErrors('file');

    expect(ImportBatch::count())->toBe(0);
});

it('marks the batch failed if the job crashes', function () {
    $batch = ImportBatch::create(['user_id' => $this->admin->id, 'original_name' => 'x', 'path' => 'missing.csv', 'status' => 'queued']);

    (new ImportDomainsJob($batch))->failed(new RuntimeException('disk exploded'));

    expect($batch->fresh()->status)->toBe('failed')->and($batch->fresh()->failure_message)->toContain('disk exploded');
});
