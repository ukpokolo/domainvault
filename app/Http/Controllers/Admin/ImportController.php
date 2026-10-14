<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportDomainsRequest;
use App\Jobs\ImportDomainsJob;
use App\Models\ImportBatch;
use App\Services\Admin\AuditLogger;
use App\Services\Import\DomainCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ImportBatch::class);

        return Inertia::render('Admin/Imports', [
            'batches' => ImportBatch::with('user:id,name')->latest('id')->paginate(10)->through(fn (ImportBatch $b) => [
                'id' => $b->id,
                'original_name' => $b->original_name,
                'status' => $b->status,
                'total_rows' => $b->total_rows,
                'success_rows' => $b->success_rows,
                'failed_rows' => $b->failed_rows,
                'failure_message' => $b->failure_message,
                'has_error_report' => $b->error_report_path !== null,
                'user' => $b->user->name,
                'created_at' => $b->created_at->toIso8601String(),
            ]),
            'columns' => ['required' => DomainCsvImporter::REQUIRED, 'optional' => DomainCsvImporter::OPTIONAL],
        ]);
    }

    public function store(ImportDomainsRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);

        $file = $request->file('file');
        $batch = ImportBatch::create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $file->store('imports', 'local'),
            'status' => ImportBatch::QUEUED,
        ]);

        $audit->log($request->user(), 'import.started', $batch, ['file' => $batch->original_name]);
        ImportDomainsJob::dispatch($batch);

        return back()->with('success', 'Import queued. Refresh in a moment to see the result.');
    }

    public function errors(ImportBatch $batch): StreamedResponse
    {
        $this->authorize('view', $batch);
        abort_unless($batch->error_report_path && Storage::disk('local')->exists($batch->error_report_path), 404);

        return Storage::disk('local')->download($batch->error_report_path, "import-{$batch->id}-errors.csv");
    }
}
