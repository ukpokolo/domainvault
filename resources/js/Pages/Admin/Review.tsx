import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { compact, money } from '@/lib/format';
import { Domain, Paginated } from '@/types';

export default function Review({ domains }: { domains: Paginated<Domain> }) {
    const [reasons, setReasons] = useState<Record<number, string>>({});

    return (
        <AdminLayout title="Review queue">
            <Head title="Review queue" />
            {domains.data.length === 0 ? (
                <p className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">Nothing is waiting for review. 🎉</p>
            ) : (
                <ul className="space-y-4">
                    {domains.data.map((d) => (
                        <li key={d.id} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 className="break-all text-lg font-semibold">{d.name}</h2>
                                    <p className="text-sm text-slate-500">
                                        {d.niche} · {d.age} yrs · DA {d.authority_score} · {compact(d.backlinks)} backlinks · {compact(d.referring_domains)} ref. domains
                                    </p>
                                    {d.notes && <p className="mt-2 text-sm text-slate-600">{d.notes}</p>}
                                </div>
                                <p className="text-xl font-bold">{money(d.price_cents)}</p>
                            </div>
                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                <button onClick={() => router.post(route('admin.review.approve', d.id), {}, { preserveScroll: true })} className="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500">Approve</button>
                                <input
                                    value={reasons[d.id] ?? ''}
                                    onChange={(e) => setReasons({ ...reasons, [d.id]: e.target.value })}
                                    placeholder="Rejection reason (optional)"
                                    aria-label={`Rejection reason for ${d.name}`}
                                    className="min-w-0 flex-1 rounded-md border-slate-300 text-sm"
                                />
                                <button onClick={() => router.post(route('admin.review.reject', d.id), { reason: reasons[d.id] ?? '' }, { preserveScroll: true })} className="rounded-md border border-red-300 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50">Reject</button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <Pagination meta={domains.meta} />
        </AdminLayout>
    );
}
