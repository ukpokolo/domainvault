<?php

namespace App\Services\Import;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Streams a CSV of domains, validates every row independently, inserts the
 * good ones in chunks and writes an error report for the rest.
 */
class DomainCsvImporter
{
    public const REQUIRED = ['name', 'registered_year', 'authority_score', 'niche', 'price'];

    public const OPTIONAL = ['backlinks', 'referring_domains', 'status', 'notes'];

    private const CHUNK = 200;

    public function import(ImportBatch $batch): void
    {
        $batch->update(['status' => ImportBatch::PROCESSING]);

        $handle = fopen(Storage::disk('local')->path($batch->path), 'r');
        if ($handle === false) {
            throw new RuntimeException('Uploaded file could not be opened.');
        }

        try {
            $header = $this->readHeader($handle);
            $missing = array_diff(self::REQUIRED, $header);
            if ($missing) {
                $batch->update([
                    'status' => ImportBatch::FAILED,
                    'failure_message' => 'Missing required column(s): '.implode(', ', $missing),
                ]);

                return;
            }

            $this->process($batch, $handle, $header);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle @param list<string> $header */
    private function process(ImportBatch $batch, $handle, array $header): void
    {
        $errors = [];
        $buffer = [];
        $seen = [];
        $total = $success = 0;
        $line = 1; // header is line 1

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            if ($cells === [null]) {
                continue; // blank line
            }
            $total++;

            $row = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), null));
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            $row['name'] = $name;

            $messages = $this->validate($row);
            if ($name !== '' && isset($seen[$name])) {
                $messages[] = "Duplicate of row {$seen[$name]} in this file.";
            }

            if ($messages) {
                $errors[] = [$line, $row['name'], implode(' ', $messages)];

                continue;
            }

            $seen[$name] = $line;
            $buffer[] = $this->toRecord($row);
            $success++;

            if (count($buffer) >= self::CHUNK) {
                $this->flush($buffer);
                $buffer = [];
            }
        }
        $this->flush($buffer);

        $batch->update([
            'status' => ImportBatch::COMPLETED,
            'total_rows' => $total,
            'success_rows' => $success,
            'failed_rows' => count($errors),
            'error_report_path' => $errors ? $this->writeReport($batch, $errors) : null,
        ]);
    }

    /** @param resource $handle @return list<string> */
    private function readHeader($handle): array
    {
        $cells = fgetcsv($handle) ?: [];

        // Strip a UTF-8 BOM that Excel adds, and normalise names.
        return array_map(fn ($c) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c))), $cells);
    }

    /** @param array<string, mixed> $row @return list<string> */
    private function validate(array $row): array
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/', 'unique:domains,name'],
            'registered_year' => ['required', 'integer', 'between:1985,'.now()->year],
            'authority_score' => ['required', 'integer', 'between:0,100'],
            'niche' => ['required', 'string', 'max:60'],
            'price' => ['required', 'numeric', 'gt:0', 'max:10000000'],
            'backlinks' => ['nullable', 'integer', 'min:0'],
            'referring_domains' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:draft,pending,live'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.regex' => 'name is not a valid domain name.',
            'name.unique' => 'name already exists.',
        ]);

        return $validator->errors()->all();
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function toRecord(array $row): array
    {
        $now = now();

        return [
            'name' => $row['name'],
            'tld' => substr(strrchr($row['name'], '.'), 1),
            'registered_year' => (int) $row['registered_year'],
            'backlinks' => (int) ($row['backlinks'] ?? 0),
            'referring_domains' => (int) ($row['referring_domains'] ?? 0),
            'authority_score' => (int) $row['authority_score'],
            'niche' => trim($row['niche']),
            'price_cents' => (int) round((float) $row['price'] * 100),
            'status' => ($row['status'] ?? '') !== '' ? $row['status'] : DomainStatus::Pending->value,
            'notes' => ($row['notes'] ?? '') !== '' ? $row['notes'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @param list<array<string, mixed>> $records */
    private function flush(array $records): void
    {
        if ($records) {
            Domain::insert($records);
        }
    }

    /** @param list<array{0: int, 1: string, 2: string}> $errors */
    private function writeReport(ImportBatch $batch, array $errors): string
    {
        $path = "imports/errors-{$batch->id}.csv";
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['line', 'name', 'errors']);
        foreach ($errors as $e) {
            // Prefix formula-leading cells so the report is safe to open in Excel.
            fputcsv($stream, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'$v" : $v, $e));
        }
        rewind($stream);
        Storage::disk('local')->put($path, stream_get_contents($stream));
        fclose($stream);

        return $path;
    }
}
