<?php

namespace App\Http\Controllers;

use App\Http\Requests\DomainIndexRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Services\Search\DomainSearchService;
use App\Services\Search\FilterChips;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function __construct(
        private readonly DomainSearchService $search,
        private readonly FilterChips $chips,
    ) {}

    public function index(DomainIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Marketplace/Index', [
            'domains' => DomainResource::collection(
                $this->search->paginate($filters)->withQueryString()
            ),
            'filters' => $filters->toArray() + ['sort' => $filters->sort],
            'facets' => $this->search->facets(),
            'chips' => $this->chips->for($filters, $request->validated('ai')),
            'ai_query' => $request->validated('ai'),
        ]);
    }

    public function show(Domain $domain): Response
    {
        $this->authorize('view', $domain);

        return Inertia::render('Marketplace/Show', [
            'domain' => (new DomainResource($domain))->resolve(),
        ]);
    }
}
