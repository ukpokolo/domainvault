<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->with('items.domain')
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('Orders/Show', [
            'order' => (new OrderResource($order->load('items.domain')))->resolve(),
            'just_paid' => $request->query('checkout') === 'success',
        ]);
    }
}
