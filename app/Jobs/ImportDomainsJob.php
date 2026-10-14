<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Import\DomainCsvImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ImportDomainsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public readonly ImportBatch $batch) {}

    public function handle(DomainCsvImporter $importer): void
    {
        $importer->import($this->batch);
    }

    public function failed(Throwable $e): void
    {
        $this->batch->update([
            'status' => ImportBatch::FAILED,
            'failure_message' => 'Import crashed: '.str($e->getMessage())->limit(200),
        ]);
    }
}
