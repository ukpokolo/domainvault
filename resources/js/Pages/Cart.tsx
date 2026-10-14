import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import SiteLayout from '@/Layouts/SiteLayout';
import { money } from '@/lib/format';
import { Domain } from '@/types';

interface Props {
    items: { id: number; domain: Domain }[];
    total_cents: number;
}

export default function Cart({ items, total_cents }: Props) {
    const [busy, setBusy] = useState(false);

    return (
        <SiteLayout>
            <Head title="Your cart" />
            <h1 className="text-2xl font-bold tracking-tight">Your cart</h1>

            {items.length === 0 ? (
                <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p className="font-semibold">Your cart is empty</p>
                    <Link href={route('marketplace.index')} className="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Browse domains
                    </Link>
                </div>
            ) : (
                <div className="mt-6 grid gap-8 lg:grid-cols-[1fr_20rem]">
                    <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
                        {items.map(({ id, domain }) => (
                            <li key={id} className="flex items-center justify-between gap-4 p-5">
                                <div>
                                    <Link href={route('marketplace.show', domain.id)} className="break-all font-semibold hover:text-indigo-600">{domain.name}</Link>
                                    <p className="text-sm text-slate-500">{domain.niche} · {domain.age} yrs · DA {domain.authority_score}</p>
                                </div>
                                <div className="flex items-center gap-4">
                                    <span className="font-bold">{money(domain.price_cents)}</span>
                                    <button
                                        onClick={() => router.delete(route('cart.destroy', domain.id), { preserveScroll: true })}
                                        className="text-sm text-slate-500 hover:text-red-600"
                                        aria-label={`Remove ${domain.name}`}
                                    >
                                        Remove
                                    </button>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <aside className="h-fit rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex justify-between text-sm text-slate-600"><span>Domains</span><span>{items.length}</span></div>
                        <div className="mt-2 flex justify-between text-lg font-bold"><span>Total</span><span>{money(total_cents)}</span></div>
                        <button
                            disabled={busy}
                            onClick={() => router.post(route('checkout.store'), {}, { onStart: () => setBusy(true), onFinish: () => setBusy(false) })}
                            className="mt-5 w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                        >
                            {busy ? 'Redirecting to Stripe…' : 'Checkout with Stripe'}
                        </button>
                        <p className="mt-3 text-xs text-slate-500">Test mode: use card 4242 4242 4242 4242 with any future date and CVC.</p>
                    </aside>
                </div>
            )}
        </SiteLayout>
    );
}
