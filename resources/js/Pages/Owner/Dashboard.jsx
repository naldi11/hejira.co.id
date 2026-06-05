import { Head } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';

function StatCard({ icon, color, label, value, sub }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className={`mb-4 flex h-12 w-12 items-center justify-center rounded-xl ${color}`}><Icon name={icon} className="text-[24px]" /></div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-1 text-2xl font-bold text-slate-800">{value}</p>
            {sub && <p className="mt-1 text-xs text-slate-400">{sub}</p>}
        </div>
    );
}

export default function OwnerDashboard({ stats }) {
    return (
        <OwnerLayout pageTitle="Konsolidasi Utama">
            <Head title="Owner — Konsolidasi" />

            <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <StatCard icon="storefront" color="bg-orange-100 text-orange-600" label="Total Pendapatan Jihan's" value={formatRupiah(stats.jihans_revenue)} sub="Akumulasi transaksi lunas" />
                    <StatCard icon="cake" color="bg-amber-100 text-amber-600" label="Total Pendapatan Hendhys" value={formatRupiah(stats.hendhys_revenue)} sub="Akumulasi transaksi lunas" />
                    <StatCard icon="inventory_2" color="bg-teal-100 text-teal-600" label="Total Item di Gudang" value={formatQty(stats.total_items_gudang)} sub="Saldo kuantitas inventori" />
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 className="mb-4 flex items-center gap-2 font-bold text-slate-800"><Icon name="today" className="text-[20px] text-blue-500" /> Performa Penjualan Hari Ini</h3>
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="flex items-center justify-between rounded-xl border border-orange-100 bg-orange-50/50 p-5">
                            <div className="flex items-center gap-3"><Icon name="storefront" className="text-[28px] text-orange-500" /><span className="font-semibold text-slate-700">Jihan's Food</span></div>
                            <span className="text-xl font-bold text-orange-600">{formatRupiah(stats.jihans_today)}</span>
                        </div>
                        <div className="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/50 p-5">
                            <div className="flex items-center gap-3"><Icon name="cake" className="text-[28px] text-amber-600" /><span className="font-semibold text-slate-700">Hendhys Brownies</span></div>
                            <span className="text-xl font-bold text-amber-600">{formatRupiah(stats.hendhys_today)}</span>
                        </div>
                    </div>
                </div>
            </div>
        </OwnerLayout>
    );
}
