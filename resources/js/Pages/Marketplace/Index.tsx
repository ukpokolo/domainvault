import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import DomainCard from '@/Components/DomainCard';
import Pagination from '@/Components/Pagination';
import SiteLayout from '@/Layouts/SiteLayout';
import { Domain, Filters, Paginated } from '@/types';

interface Props {
    domains: Paginated<Domain>;
    filters: Filters;
    facets: { tlds: string[]; niches: string[] };
}

const SORTS: Record<string, string> = {
    newest: 'Newest',
    price_asc: 'Price: low to high',
    price_desc: 'Price: high to low',
    authority_desc: 'Highest authority',
    age_desc: 'Oldest first',
    backlinks_desc: 'Most backlinks',
};

const field = 'mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Index({ domains, filters, facets }: Props) {
    const [form, setForm] = useState<Filters>(filters);

    const visit = (next: Filters) => {
        const clean = Object.fromEntries(
            Object.entries(next).filter(([k, v]) => v !== '' && v !== undefined && v !== null && !(k === 'sort' && v === 'newest')),
        );
        router.get(route('marketplace.index'), clean, { preserveState: true, preserveScroll: true, replace: true });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        visit(form);
    };

    const set = (key: keyof Filters, value: string) => setForm((f) => ({ ...f, [key]: value }));
    const reset = () => {
        setForm({ sort: 'newest' });
        visit({});
    };

    return (
        <SiteLayout>
            <Head title="Marketplace" />

            <section className="mb-8 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 px-6 py-10 text-white shadow-lg sm:px-10">
                <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">Find aged domains with real authority</h1>
                <p className="mt-2 max-w-2xl text-indigo-100">
                    Browse {domains.meta.total.toLocaleString()} vetted domains with verified backlink profiles.
                </p>
                <form onSubmit={submit} className="mt-6 flex max-w-2xl gap-2">
                    <input
                        type="search"
                        value={form.q ?? ''}
                        onChange={(e) => set('q', e.target.value)}
                        placeholder="Search by name, niche or keyword…"
                        aria-label="Search domains"
                        className="w-full rounded-lg border-0 px-4 py-3 text-slate-900 shadow-sm placeholder:text-slate-400 focus:ring-2 focus:ring-white"
                    />
                    <button className="rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Search</button>
                </form>
            </section>

            <div className="grid gap-8 lg:grid-cols-[16rem_1fr]">
                <aside>
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Filters</h2>
                        <label className="block text-sm font-medium">
                            TLD
                            <select className={field} value={form.tld ?? ''} onChange={(e) => set('tld', e.target.value)}>
                                <option value="">Any</option>
                                {facets.tlds.map((t) => <option key={t} value={t}>.{t}</option>)}
                            </select>
                        </label>
                        <label className="block text-sm font-medium">
                            Niche
                            <select className={field} value={form.niche ?? ''} onChange={(e) => set('niche', e.target.value)}>
                                <option value="">Any</option>
                                {facets.niches.map((n) => <option key={n}>{n}</option>)}
                            </select>
                        </label>
                        <label className="block text-sm font-medium">
                            Minimum age (years)
                            <input type="number" min={0} max={40} className={field} value={form.min_age ?? ''} onChange={(e) => set('min_age', e.target.value)} />
                        </label>
                        <label className="block text-sm font-medium">
                            Maximum price (USD)
                            <input type="number" min={0} className={field} value={form.max_price ?? ''} onChange={(e) => set('max_price', e.target.value)} />
                        </label>
                        <label className="block text-sm font-medium">
                            Minimum authority (0-100)
                            <input type="number" min={0} max={100} className={field} value={form.min_authority ?? ''} onChange={(e) => set('min_authority', e.target.value)} />
                        </label>
                        <div className="flex gap-2 pt-1">
                            <button className="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply</button>
                            <button type="button" onClick={reset} className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Reset</button>
                        </div>
                    </form>
                </aside>

                <section>
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-slate-600" aria-live="polite">
                            {domains.meta.total === 0
                                ? 'No domains match'
                                : `Showing ${domains.meta.from}-${domains.meta.to} of ${domains.meta.total.toLocaleString()} domains`}
                        </p>
                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            Sort by
                            <select
                                className="rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={filters.sort ?? 'newest'}
                                onChange={(e) => visit({ ...form, sort: e.target.value })}
                            >
                                {Object.entries(SORTS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                            </select>
                        </label>
                    </div>

                    {domains.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                            <p className="font-semibold">No domains found</p>
                            <p className="mt-1 text-sm text-slate-500">Try widening your filters or resetting them.</p>
                            <button onClick={reset} className="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Reset filters</button>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {domains.data.map((d) => <DomainCard key={d.id} domain={d} />)}
                        </div>
                    )}

                    <Pagination meta={domains.meta} />
                </section>
            </div>
        </SiteLayout>
    );
}
