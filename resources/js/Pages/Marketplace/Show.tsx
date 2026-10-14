import { Head, Link } from '@inertiajs/react';
import AuthorityBadge from '@/Components/AuthorityBadge';
import SiteLayout from '@/Layouts/SiteLayout';
import { money } from '@/lib/format';
import { Domain } from '@/types';

const Stat = ({ label, value }: { label: string; value: string | number }) => (
    <div className="rounded-xl bg-slate-50 p-4">
        <dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt>
        <dd className="mt-1 text-2xl font-bold">{value}</dd>
    </div>
);

export default function Show({ domain }: { domain: Domain }) {
    const sold = domain.status === 'sold';

    return (
        <SiteLayout>
            <Head title={domain.name} />
            <Link href={route('marketplace.index')} className="text-sm text-indigo-600 hover:underline">← Back to marketplace</Link>

            <div className="mt-4 grid gap-8 lg:grid-cols-[1fr_20rem]">
                <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="break-all text-3xl font-bold tracking-tight">{domain.name}</h1>
                        <AuthorityBadge score={domain.authority_score} />
                        {sold && <span className="rounded-full bg-slate-900 px-2.5 py-0.5 text-xs font-semibold text-white">Sold</span>}
                    </div>
                    <p className="mt-1 text-slate-500">{domain.niche} · registered {domain.registered_year}</p>

                    <dl className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <Stat label="Age" value={`${domain.age} yrs`} />
                        <Stat label="Authority" value={domain.authority_score} />
                        <Stat label="Backlinks" value={domain.backlinks.toLocaleString()} />
                        <Stat label="Ref. domains" value={domain.referring_domains.toLocaleString()} />
                    </dl>

                    <h2 className="mt-8 text-sm font-semibold uppercase tracking-wide text-slate-500">Notes</h2>
                    <p className="mt-2 text-slate-700">{domain.notes ?? 'No additional notes for this domain.'}</p>
                </article>

                <aside className="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm text-slate-500">{sold ? 'Sold for' : 'Price'}</p>
                    <p className="text-4xl font-bold">{money(domain.price_cents)}</p>
                    {sold ? (
                        <p className="mt-4 rounded-lg bg-slate-100 p-3 text-sm text-slate-600">This domain has been sold.</p>
                    ) : (
                        <p className="mt-4 text-sm text-slate-500">Add to cart is enabled once the cart feature lands.</p>
                    )}
                </aside>
            </div>
        </SiteLayout>
    );
}
