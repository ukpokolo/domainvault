<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DomainStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectListingRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Services\Admin\ListingReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(private readonly ListingReviewService $review) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Review', [
            'domains' => DomainResource::collection(
                Domain::withStatus(DomainStatus::Pending)->oldest('id')->paginate(15)
            ),
        ]);
    }

    public function approve(Request $request, Domain $domain): RedirectResponse
    {
        $this->authorize('review', $domain);

        return $this->run(fn () => $this->review->approve($request->user(), $domain), "{$domain->name} approved and live.");
    }

    public function reject(RejectListingRequest $request, Domain $domain): RedirectResponse
    {
        $this->authorize('review', $domain);

        return $this->run(
            fn () => $this->review->reject($request->user(), $domain, $request->validated('reason')),
            "{$domain->name} rejected and returned to draft."
        );
    }

    private function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidStatusTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
