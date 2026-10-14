import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import SiteLayout from '@/Layouts/SiteLayout';

const TABS = [
    { label: 'Dashboard', route: 'admin.dashboard' },
    { label: 'Review queue', route: 'admin.review.index' },
    { label: 'Domains', route: 'admin.domains.index' },
    { label: 'CSV import', route: 'admin.imports.index' },
    { label: 'Audit log', route: 'admin.audit-log' },
];

export default function AdminLayout({ title, children }: PropsWithChildren<{ title: string }>) {
    return (
        <SiteLayout>
            <div className="mb-6">
                <p className="text-xs font-semibold uppercase tracking-wide text-indigo-600">Admin</p>
                <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                <nav className="mt-4 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Admin sections">
                    {TABS.map((t) => (
                        <Link
                            key={t.route}
                            href={route(t.route)}
                            className={
                                '-mb-px whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium ' +
                                (route().current(t.route) ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800')
                            }
                        >
                            {t.label}
                        </Link>
                    ))}
                </nav>
            </div>
            {children}
        </SiteLayout>
    );
}
