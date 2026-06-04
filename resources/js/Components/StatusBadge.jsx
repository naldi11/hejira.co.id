// Shared status pill. Centralises the colour-per-status mapping used across
// every Gudang document list (transfer requests, returns, POs, transfers).
const STYLES = {
    pending:   'bg-amber-50 text-amber-600 border-amber-100',
    draft:     'bg-slate-50 text-slate-500 border-slate-100',
    sent:      'bg-blue-50 text-blue-600 border-blue-100',
    approved:  'bg-indigo-50 text-indigo-600 border-indigo-100',
    partial:   'bg-violet-50 text-violet-600 border-violet-100',
    completed: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    received:  'bg-emerald-50 text-emerald-600 border-emerald-100',
    rejected:  'bg-rose-50 text-rose-600 border-rose-100',
    cancelled: 'bg-rose-50 text-rose-600 border-rose-100',
};

const LABELS = {
    pending: 'Menunggu',
    draft: 'Draft',
    sent: 'Terkirim',
    approved: 'Disetujui',
    partial: 'Sebagian',
    completed: 'Selesai',
    received: 'Diterima',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
};

export default function StatusBadge({ status, label }) {
    const cls = STYLES[status] ?? 'bg-slate-50 text-slate-500 border-slate-100';
    return (
        <span className={`inline-flex items-center rounded-xl border px-3 py-1 text-[10px] font-black uppercase tracking-widest ${cls}`}>
            {label ?? LABELS[status] ?? status}
        </span>
    );
}
