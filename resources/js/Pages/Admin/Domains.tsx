import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import AdminLayout from '@/Layouts/AdminLayout';
import { money } from '@/lib/format';
import { Domain, Paginated } from '@/types';

interface Props {
    domains: Paginated<Domain>;
    filters: { status: string | null; q: string | null };
    transitions: Record<string, string[]>;
}

export default function Domains({ domains, filters, transitions }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const visit = (params: Record<string, string | null>) =>
        router.get(route('admin.domains.index'), Object.fromEntries(Object.entries(params).filter(([, v]) => v)), { preserveState: true });

    return (
        <AdminLayout title="Domains">
            <Head title="Manage domains" />
            <form onSubmit={(e: FormEvent) => { e.preventDefault(); visit({ ...filters, q }); }} className="mb-4 flex flex-wrap gap-2">
                <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search by name" aria-label="Search by name" className="rounded-md border-slate-300 text-sm" />
                <select aria-label="Filter by status" value={filters.status ?? ''} onChange={(e) => visit({ q: filters.q, status: e.target.value })} className="rounded-md border-slate-300 text-sm">
                    <option value="">All statuses</option>
                    {Object.keys(transitions).map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <button className="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Search</button>
            </form>

            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th className="px-4 py-3">Domain</th><th className="px-4 py-3">Niche</th><th className="px-4 py-3">Price</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Change status</th></tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {domains.data.map((d) => (
                            <tr key={d.id}>
                                <td className="px-4 py-3 font-medium">{d.name}</td>
                                <td className="px-4 py-3 text-slate-600">{d.niche}</td>
                                <td className="px-4 py-3">{money(d.price_cents)}</td>
                                <td className="px-4 py-3"><StatusBadge status={d.status} /></td>
                                <td className="px-4 py-3">
                                    {transitions[d.status].length === 0 ? (
                                        <span className="text-slate-400">Locked</span>
                                    ) : (
                                        <select
                                            aria-label={`Change status of ${d.name}`}
                                            value=""
                                            onChange={(e) => e.target.value && router.patch(route('admin.domains.status', d.id), { status: e.target.value }, { preserveScroll: true })}
                                            className="rounded-md border-slate-300 py-1 text-sm"
                                        >
                                            <option value="">Move to…</option>
                                            {transitions[d.status].map((s) => <option key={s} value={s}>{s}</option>)}
                                        </select>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination meta={domains.meta} />
        </AdminLayout>
    );
}
