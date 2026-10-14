<?php

namespace App\Services\Admin;

use App\Enums\DomainStatus;
use App\Enums\OrderStatus;
use App\Models\Domain;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DashboardStats
{
    /** @return array{by_status: array<string, int>, revenue_cents: int, orders_paid: int, top_niches: list<array{niche: string, sales: int, revenue_cents: int}>} */
    public function all(): array
    {
        return [
            'by_status' => $this->listingsByStatus(),
            'revenue_cents' => (int) Order::where('status', OrderStatus::Paid)->sum('total_cents'),
            'orders_paid' => Order::where('status', OrderStatus::Paid)->count(),
            'top_niches' => $this->topNiches(),
        ];
    }

    /** @return array<string, int> every status is present, even at zero */
    private function listingsByStatus(): array
    {
        $counts = Domain::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(DomainStatus::cases())
            ->mapWithKeys(fn (DomainStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)])
            ->all();
    }

    /** Niches ranked by revenue from paid orders. */
    private function topNiches(int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('domains', 'domains.id', '=', 'order_items.domain_id')
            ->where('orders.status', OrderStatus::Paid->value)
            ->groupBy('domains.niche')
            ->orderByDesc('revenue_cents')
            ->limit($limit)
            ->selectRaw('domains.niche as niche, count(*) as sales, sum(order_items.price_cents) as revenue_cents')
            ->get()
            ->map(fn ($r) => ['niche' => $r->niche, 'sales' => (int) $r->sales, 'revenue_cents' => (int) $r->revenue_cents])
            ->all();
    }
}
