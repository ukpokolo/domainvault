import { Head } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Paginated } from '@/types';

interface Log {
    id: number;
    action: string;
    actor: string;
    subject: string | null;
    meta: Record<string, string> | null;
    created_at: string;
}

export default function AuditLog({ logs }: { logs: Paginated<Log> }) {
    return (
        <AdminLayout title="Audit log">
            <Head title="Audit log" />
            <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th className="px-4 py-3">When</th><th className="px-4 py-3">Admin</th><th className="px-4 py-3">Action</th><th className="px-4 py-3">Subject</th><th className="px-4 py-3">Details</th></tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {logs.data.length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-500">No admin actions recorded yet.</td></tr>}
                        {logs.data.map((l) => (
                            <tr key={l.id}>
                                <td className="whitespace-nowrap px-4 py-3 text-slate-500">{new Date(l.created_at).toLocaleString()}</td>
                                <td className="px-4 py-3">{l.actor}</td>
                                <td className="px-4 py-3"><code className="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{l.action}</code></td>
                                <td className="px-4 py-3 font-medium">{l.subject ?? '–'}</td>
                                <td className="px-4 py-3 text-slate-600">{l.meta ? Object.entries(l.meta).map(([k, v]) => `${k}: ${v}`).join(' · ') : ''}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination meta={logs.meta} />
        </AdminLayout>
    );
}
