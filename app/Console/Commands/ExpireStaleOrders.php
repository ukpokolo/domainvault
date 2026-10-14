<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

class ExpireStaleOrders extends Command
{
    protected $signature = 'orders:expire-stale {--hours=24 : Age after which an unpaid order is abandoned}';

    protected $description = 'Mark pending orders that were never paid as failed';

    public function handle(): int
    {
        $count = Order::where('status', OrderStatus::Pending)
            ->where('created_at', '<', now()->subHours((int) $this->option('hours')))
            ->update(['status' => OrderStatus::Failed, 'failure_reason' => 'Abandoned: payment not completed']);

        $this->info("Expired {$count} stale order(s).");

        return self::SUCCESS;
    }
}
