import { Link } from '@inertiajs/react';
import { Paginated } from '@/types';

export default function Pagination({ meta }: { meta: Paginated<unknown>['meta'] }) {
    if (meta.last_page <= 1) return null;

    return (
        <nav className="mt-8 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
            {meta.links.map((link, i) =>
                link.url ? (
                    <Link
                        key={i}
                        href={link.url}
                        preserveScroll
                        className={
                            'rounded-md px-3 py-1.5 text-sm ' +
                            (link.active ? 'bg-indigo-600 font-semibold text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100')
                        }
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span key={i} className="rounded-md px-3 py-1.5 text-sm text-slate-400" dangerouslySetInnerHTML={{ __html: link.label }} />
                ),
            )}
        </nav>
    );
}
