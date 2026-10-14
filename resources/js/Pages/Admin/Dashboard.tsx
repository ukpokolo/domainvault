import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { money } from '@/lib/format';

interface Stats {
    by_status: Record<string, number>;
    revenue_cents: number;
    orders_paid: number;
    top_niches: { niche: string; sales: number; revenue_cents: number }[];
}

const BAR: Record<string, string> = { live: 'bg-emerald-500', pending: 'bg-amber-500', draft: 'bg-slate-400', sold: 'bg-indigo-600' };

export default function Dashboard({ stats }: { stats: Stats }) {
    const total = Object.values(stats.by_status).reduce((a, b) => a + b, 0) || 1;
    const maxRevenue = Math.max(1, ...stats.top_niches.map((n) => n.revenue_cents));

    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin dashboard" />

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-sm text-slate-500">Revenue</p>
                    <p className="mt-1 text-3xl font-bold">{money(stats.revenue_cents)}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-sm text-slate-500">Paid orders</p>
                    <p className="mt-1 text-3xl font-bold">{stats.orders_paid}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-sm text-slate-500">Awaiting review</p>
                    <p className="mt-1 text-3xl font-bold">{stats.by_status.pending ?? 0}</p>
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="font-semibold">Listings by status</h2>
                    <div className="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Listings by status">
                        {Object.entries(stats.by_status).map(([s, n]) => (
                            <div key={s} className={BAR[s]} style={{ width: `${(n / total) * 100}%` }} />
                        ))}
                    </div>
                    <ul className="mt-4 space-y-2 text-sm">
                        {Object.entries(stats.by_status).map(([s, n]) => (
                            <li key={s} className="flex items-center justify-between">
                                <span className="flex items-center gap-2 capitalize"><span className={`h-2.5 w-2.5 rounded-full ${BAR[s]}`} />{s}</span>
                                <span className="font-semibold">{n}</span>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="font-semibold">Top niches by revenue</h2>
                    {stats.top_niches.length === 0 ? (
                        <p className="mt-4 text-sm text-slate-500">No sales yet.</p>
                    ) : (
                        <ul className="mt-4 space-y-3">
                            {stats.top_niches.map((n) => (
                                <li key={n.niche}>
                                    <div className="flex justify-between text-sm">
                                        <span className="font-medium">{n.niche} <span className="text-slate-400">· {n.sales} sold</span></span>
                                        <span className="font-semibold">{money(n.revenue_cents)}</span>
                                    </div>
                                    <div className="mt-1 h-2 rounded-full bg-slate-100">
                                        <div className="h-2 rounded-full bg-indigo-600" style={{ width: `${(n.revenue_cents / maxRevenue) * 100}%` }} />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
}
