const TONES: Record<string, string> = {
    paid: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    failed: 'bg-red-50 text-red-700 ring-red-600/20',
};

export default function OrderStatusBadge({ status }: { status: string }) {
    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset ${TONES[status] ?? TONES.pending}`}>
            {status}
        </span>
    );
}
