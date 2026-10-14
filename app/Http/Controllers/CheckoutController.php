<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function store(Request $request, CheckoutService $checkout): Response|RedirectResponse
    {
        try {
            $session = $checkout->start(
                $request->user(),
                url('/orders/{ORDER}?checkout=success'),
                route('cart.index'),
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // Full-page redirect: Stripe Checkout is an external site.
        return Inertia::location($session->url);
    }
}
