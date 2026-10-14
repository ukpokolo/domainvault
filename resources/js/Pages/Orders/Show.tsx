import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import OrderStatusBadge from '@/Components/OrderStatusBadge';
import SiteLayout from '@/Layouts/SiteLayout';
import { money } from '@/lib/format';
import { Order } from '@/types';

export default function Show({ order, just_paid }: { order: Order; just_paid: boolean }) {
    // The webhook may land a moment after the redirect back from Stripe; poll briefly.
    useEffect(() => {
        if (order.status !== 'pending' || !just_paid) return;
        const t = setInterval(() => router.reload({ only: ['order'] }), 2000);
        return () => clearInterval(t);
    }, [order.status, just_paid]);

    return (
        <SiteLayout>
            <Head title={`Order #${order.id}`} />
            <Link href={route('orders.index')} className="text-sm text-indigo-600 hover:underline">← All orders</Link>

            <div className="mt-4 flex items-center gap-3">
                <h1 className="text-2xl font-bold tracking-tight">Order #{order.id}</h1>
                <OrderStatusBadge status={order.status} />
            </div>

            {order.status === 'pending' && just_paid && (
                <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Payment received. We are confirming it with Stripe; this page will update automatically.</p>
            )}
            {order.status === 'failed' && (
                <p className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">{order.failure_reason ?? 'This order could not be completed.'}</p>
            )}

            <ul className="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-sm">
                {order.items.map((item) => (
                    <li key={item.id} className="flex justify-between p-4">
                        <span className="break-all font-medium">{item.name}</span>
                        <span>{money(item.price_cents)}</span>
                    </li>
                ))}
                <li className="flex justify-between bg-slate-50 p-4 font-bold"><span>Total</span><span>{money(order.total_cents)}</span></li>
            </ul>
        </SiteLayout>
    );
}
