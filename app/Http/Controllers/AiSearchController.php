<?php

namespace App\Http\Controllers;

use App\DTOs\DomainFilters;
use App\Exceptions\LlmException;
use App\Http\Requests\AiSearchRequest;
use App\Services\Ai\AiSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class AiSearchController extends Controller
{
    public function __invoke(AiSearchRequest $request, AiSearchService $ai): RedirectResponse
    {
        $query = $request->searchText();

        try {
            $filters = $ai->interpret($query);
            $flash = [];
        } catch (LlmException $e) {
            // Degrade gracefully to plain keyword search rather than failing the page.
            Log::warning('AI search fell back to keyword search', ['reason' => $e->getMessage()]);
            $filters = new DomainFilters(q: $query);
            $flash = ['error' => 'AI search is unavailable right now, so we used a keyword search instead.'];
        }

        return redirect()
            ->route('marketplace.index', $filters->toArray() + ['ai' => $query])
            ->with($flash);
    }
}
