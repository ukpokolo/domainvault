<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiDomainIndexRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Services\Search\DomainSearchService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DomainController extends Controller
{
    public function index(ApiDomainIndexRequest $request, DomainSearchService $search): AnonymousResourceCollection
    {
        return DomainResource::collection(
            $search->paginate($request->filters(), $request->perPage())->withQueryString()
        );
    }

    public function show(Domain $domain): DomainResource
    {
        // Hide unpublished listings entirely instead of leaking their existence with a 403.
        abort_if(Gate::denies('view', $domain), 404);

        return new DomainResource($domain);
    }
}
