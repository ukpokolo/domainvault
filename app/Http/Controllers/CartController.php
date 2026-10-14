<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Http\Resources\DomainResource;
use App\Models\CartItem;
use App\Models\Domain;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(Request $request): Response
    {
        $items = $this->cart->items($request->user());

        return Inertia::render('Cart', [
            'items' => $items->map(fn (CartItem $i) => [
                'id' => $i->id,
                'domain' => (new DomainResource($i->domain))->resolve(),
            ])->values(),
            'total_cents' => $this->cart->totalCents($items),
        ]);
    }

    public function store(Request $request, Domain $domain): RedirectResponse
    {
        try {
            $this->cart->add($request->user(), $domain);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$domain->name} was added to your cart.");
    }

    public function destroy(Request $request, Domain $domain): RedirectResponse
    {
        $this->cart->remove($request->user(), $domain);

        return back()->with('success', "{$domain->name} was removed from your cart.");
    }
}
