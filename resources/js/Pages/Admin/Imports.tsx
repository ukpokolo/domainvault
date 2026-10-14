import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import AdminLayout from '@/Layouts/AdminLayout';
import { Paginated } from '@/types';

interface Batch {
    id: number;
    original_name: string;
    status: string;
    total_rows: number;
    success_rows: number;
    failed_rows: number;
    failure_message: string | null;
    has_error_report: boolean;
    user: string;
    created_at: string;
}

export default function Imports({ batches, columns }: { batches: Paginated<Batch>; columns: { required: string[]; optional: string[] } }) {
    const { setData, post, processing, errors, reset } = useForm<{ file: File | null }>({ file: null });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('admin.imports.store'), { forceFormData: true, onSuccess: () => reset() });
    };

    return (
        <AdminLayout title="CSV import">
            <Head title="CSV import" />

            <form onSubmit={submit} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="font-semibold">Upload a CSV</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Required columns: <code>{columns.required.join(', ')}</code>. Optional: <code>{columns.optional.join(', ')}</code>. Price is in USD. Rows are validated individually; invalid rows are reported, not imported.
                </p>
                <div className="mt-4 flex flex-wrap items-center gap-3">
                    <input type="file" accept=".csv,text/csv" aria-label="CSV file" onChange={(e) => setData('file', e.target.files?.[0] ?? null)} className="text-sm" />
                    <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Import</button>
                    <button type="button" onClick={() => router.reload({ only: ['batches'] })} className="text-sm text-indigo-600 hover:underline">Refresh status</button>
                </div>
                {errors.file && <p className="mt-2 text-sm text-red-600">{errors.file}</p>}
            </form>

            <div className="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr><th className="px-4 py-3">File</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Rows</th><th className="px-4 py-3">Imported</th><th className="px-4 py-3">Failed</th><th className="px-4 py-3">Report</th></tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {batches.data.length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-500">No imports yet.</td></tr>}
                        {batches.data.map((b) => (
                            <tr key={b.id}>
                                <td className="px-4 py-3"><span className="font-medium">{b.original_name}</span><span className="block text-xs text-slate-400">{b.user} · {new Date(b.created_at).toLocaleString()}</span></td>
                                <td className="px-4 py-3"><StatusBadge status={b.status} />{b.failure_message && <span className="mt-1 block text-xs text-red-600">{b.failure_message}</span>}</td>
                                <td className="px-4 py-3">{b.total_rows}</td>
                                <td className="px-4 py-3 text-emerald-700">{b.success_rows}</td>
                                <td className="px-4 py-3 text-red-700">{b.failed_rows}</td>
                                <td className="px-4 py-3">{b.has_error_report ? <a href={route('admin.imports.errors', b.id)} className="text-indigo-600 hover:underline">Download errors</a> : <span className="text-slate-400">–</span>}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination meta={batches.meta} />
        </AdminLayout>
    );
}
