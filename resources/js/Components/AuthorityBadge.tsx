export default function AuthorityBadge({ score }: { score: number }) {
    const tone =
        score >= 70 ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
        : score >= 40 ? 'bg-amber-50 text-amber-700 ring-amber-600/20'
        : 'bg-slate-100 text-slate-600 ring-slate-500/20';

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ${tone}`}>
            DA {score}
        </span>
    );
}
