import { Link } from '@inertiajs/react';
import AuthorityBadge from '@/Components/AuthorityBadge';
import { compact, money } from '@/lib/format';
import { Domain } from '@/types';

export default function DomainCard({ domain }: { domain: Domain }) {
    return (
        <Link
            href={route('marketplace.show', domain.id)}
            className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
        >
            <div className="flex items-start justify-between gap-2">
                <h3 className="break-all text-lg font-semibold text-slate-900 group-hover:text-indigo-600">{domain.name}</h3>
                <AuthorityBadge score={domain.authority_score} />
            </div>
            <p className="mt-1 text-sm text-slate-500">{domain.niche}</p>

            <dl className="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                <div className="rounded-lg bg-slate-50 py-2">
                    <dt className="text-slate-500">Age</dt>
                    <dd className="mt-0.5 text-sm font-semibold">{domain.age}y</dd>
                </div>
                <div className="rounded-lg bg-slate-50 py-2">
                    <dt className="text-slate-500">Backlinks</dt>
                    <dd className="mt-0.5 text-sm font-semibold">{compact(domain.backlinks)}</dd>
                </div>
                <div className="rounded-lg bg-slate-50 py-2">
                    <dt className="text-slate-500">Ref. domains</dt>
                    <dd className="mt-0.5 text-sm font-semibold">{compact(domain.referring_domains)}</dd>
                </div>
            </dl>

            <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                <span className="text-xl font-bold text-slate-900">{money(domain.price_cents)}</span>
                <span className="text-sm font-medium text-indigo-600">View →</span>
            </div>
        </Link>
    );
}
