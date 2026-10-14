<?php

namespace App\Services\Cart;

use App\Exceptions\CheckoutException;
use App\Models\CartItem;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Collection;

class CartService
{
    /** @return Collection<int, CartItem> */
    public function items(User $user): Collection
    {
        return $user->cartItems()->with('domain')->latest('id')->get();
    }

    /** @throws CheckoutException when the domain can no longer be bought */
    public function add(User $user, Domain $domain): CartItem
    {
        if (! $domain->isPurchasable()) {
            throw new CheckoutException("{$domain->name} is no longer available.");
        }

        return CartItem::firstOrCreate(['user_id' => $user->id, 'domain_id' => $domain->id]);
    }

    public function remove(User $user, Domain $domain): void
    {
        $user->cartItems()->where('domain_id', $domain->id)->delete();
    }

    public function clear(User $user): void
    {
        $user->cartItems()->delete();
    }

    /** @param Collection<int, CartItem> $items */
    public function totalCents(Collection $items): int
    {
        return (int) $items->sum(fn (CartItem $i) => $i->domain->price_cents);
    }
}
