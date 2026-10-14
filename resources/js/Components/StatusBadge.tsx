const TONES: Record<string, string> = {
    live: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    draft: 'bg-slate-100 text-slate-600 ring-slate-500/20',
    sold: 'bg-slate-900 text-white ring-slate-900',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    queued: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    processing: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    failed: 'bg-red-50 text-red-700 ring-red-600/20',
};

export default function StatusBadge({ status }: { status: string }) {
    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset ${TONES[status] ?? TONES.draft}`}>
            {status}
        </span>
    );
}
