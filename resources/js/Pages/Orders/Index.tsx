import { Head, Link } from '@inertiajs/react';
import OrderStatusBadge from '@/Components/OrderStatusBadge';
import Pagination from '@/Components/Pagination';
import SiteLayout from '@/Layouts/SiteLayout';
import { money } from '@/lib/format';
import { Order, Paginated } from '@/types';

export default function Index({ orders }: { orders: Paginated<Order> }) {
    return (
        <SiteLayout>
            <Head title="Order history" />
            <h1 className="text-2xl font-bold tracking-tight">Order history</h1>

            {orders.data.length === 0 ? (
                <p className="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">
                    You have not placed any orders yet.
                </p>
            ) : (
                <div className="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-4 py-3">Order</th>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Domains</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {orders.data.map((o) => (
                                <tr key={o.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3 font-medium">
                                        <Link href={route('orders.show', o.id)} className="text-indigo-600 hover:underline">#{o.id}</Link>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{new Date(o.created_at).toLocaleDateString()}</td>
                                    <td className="px-4 py-3 text-slate-600">{o.items.map((i) => i.name).join(', ')}</td>
                                    <td className="px-4 py-3"><OrderStatusBadge status={o.status} /></td>
                                    <td className="px-4 py-3 text-right font-semibold">{money(o.total_cents)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <Pagination meta={orders.meta} />
        </SiteLayout>
    );
}
