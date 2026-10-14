<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    public const QUEUED = 'queued';

    public const PROCESSING = 'processing';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    protected $fillable = [
        'user_id', 'original_name', 'path', 'status', 'total_rows', 'success_rows',
        'failed_rows', 'error_report_path', 'failure_message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
