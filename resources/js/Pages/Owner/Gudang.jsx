import { Head } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { formatQty } from '@/lib/format';

const COUNTS = [
    { key: 'po_pending', label: 'PO Berjalan', icon: 'shopping_cart', color: 'bg-blue-100 text-blue-600' },
    { key: 'po_received', label: 'PO Selesai', icon: 'task_alt', color: 'bg-green-100 text-green-600' },
    { key: 'receive_month', label: 'Penerimaan Bulan Ini', icon: 'input', color: 'bg-teal-100 text-teal-600' },
    { key: 'transfer_month', label: 'Distribusi Bulan Ini', icon: 'output', color: 'bg-indigo-100 text-indigo-600' },
    { key: 'pending_requests', label: 'Request Butuh Approval', icon: 'pending_actions', color: 'bg-amber-100 text-amber-600' },
];

function StockTable({ title, icon, rows, accent }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center gap-2 border-b border-slate-100 p-4"><Icon name={icon} className={`text-[20px] ${accent}`} /><h3 className="font-bold text-slate-800">{title}</h3></div>
            <table className="w-full text-left text-sm">
                <tbody className="divide-y divide-slate-100">
                    {rows.length === 0 ? <EmptyState colSpan={2} icon="inventory_2" message="Belum ada data stok." />
                        : rows.map((s, i) => (
                            <tr key={i} className="hover:bg-slate-50">
                                <td className="px-4 py-3 font-medium text-slate-700">{s.product}</td>
                                <td className="px-4 py-3 text-right font-bold tabular-nums text-slate-900">{formatQty(s.quantity)}</td>
                            </tr>
                        ))}
                </tbody>
            </table>
        </div>
    );
}

export default function OwnerGudang({ stats, topStocks, lowStocks }) {
    return (
        <OwnerLayout pageTitle="Dashboard Gudang Tempua">
            <Head title="Owner — Gudang" />

            <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    {COUNTS.map((c) => (
                        <div key={c.key} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className={`mb-3 flex h-10 w-10 items-center justify-center rounded-lg ${c.color}`}><Icon name={c.icon} className="text-[20px]" /></div>
                            <p className="text-2xl font-bold text-slate-800">{formatQty(stats[c.key])}</p>
                            <p className="mt-1 text-xs text-slate-400">{c.label}</p>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <StockTable title="Top 5 Stok Terbanyak" icon="trending_up" accent="text-green-500" rows={topStocks} />
                    <StockTable title="5 Stok Terendah" icon="trending_down" accent="text-red-500" rows={lowStocks} />
                </div>
            </div>
        </OwnerLayout>
    );
}
