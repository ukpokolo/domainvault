<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DomainStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeDomainStatusRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Services\Admin\ListingReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    public function index(Request $request): Response
    {
        $status = DomainStatus::tryFrom((string) $request->query('status'));

        $domains = Domain::query()
            ->when($status, fn ($q) => $q->withStatus($status))
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', '%'.addcslashes((string) $term, '%_\\').'%'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Domains', [
            'domains' => DomainResource::collection($domains),
            'filters' => ['status' => $status?->value, 'q' => $request->query('q')],
            'transitions' => collect(DomainStatus::cases())->mapWithKeys(
                fn (DomainStatus $s) => [$s->value => array_map(fn ($t) => $t->value, $s->allowedTransitions())]
            ),
        ]);
    }

    public function updateStatus(ChangeDomainStatusRequest $request, Domain $domain, ListingReviewService $review): RedirectResponse
    {
        $this->authorize('updateStatus', $domain);

        try {
            $review->changeStatus($request->user(), $domain, $request->status());
        } catch (InvalidStatusTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$domain->name} is now {$request->status()->value}.");
    }
}
