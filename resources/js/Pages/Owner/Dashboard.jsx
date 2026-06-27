import { Head, Link } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';

function StatCard({ icon, color, label, value, sub }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div className={`mb-4 flex h-12 w-12 items-center justify-center rounded-xl ${color}`}><Icon name={icon} className="text-[24px]" /></div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-white/95">{value}</p>
            {sub && <p className="mt-1 text-xs text-slate-400">{sub}</p>}
        </div>
    );
}

export default function OwnerDashboard({ stats, trends }) {
    // Generate simple SVG line chart points
    const maxVal = Math.max(...trends.map(t => t.total), 1);
    const chartHeight = 120;
    const chartWidth = 500;
    const points = trends.map((t, idx) => {
        const x = (idx / (trends.length - 1)) * (chartWidth - 40) + 20;
        const y = chartHeight - (t.total / maxVal) * (chartHeight - 20) - 10;
        return { x, y, ...t };
    });

    const pathData = points.map(p => `${p.x},${p.y}`).join(' ');

    return (
        <OwnerLayout pageTitle="Konsolidasi Utama">
            <Head title="Owner — Konsolidasi" />

            <div className="space-y-6">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <StatCard icon="storefront" color="bg-orange-100 text-orange-600 dark:bg-orange-950/20 dark:text-orange-400" label="Total Pendapatan Jihan's" value={formatRupiah(stats.jihans_revenue)} sub="Akumulasi transaksi lunas" />
                    <StatCard icon="cake" color="bg-amber-100 text-amber-600 dark:bg-amber-950/20 dark:text-amber-400" label="Total Pendapatan Hendhys" value={formatRupiah(stats.hendhys_revenue)} sub="Akumulasi transaksi lunas" />
                    <StatCard icon="inventory_2" color="bg-teal-100 text-teal-600 dark:bg-teal-950/20 dark:text-teal-400" label="Total Item di Gudang" value={formatQty(stats.total_items_gudang)} sub="Saldo kuantitas inventori" />
                </div>

                {/* Entity Navigation Cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Link href={route('owner.jihans')} className="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-orange-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-orange-700">
                        <div>
                            <div className="flex items-center gap-3"><Icon name="storefront" className="text-[28px] text-orange-500" /><h3 className="font-bold text-slate-800 dark:text-white/95">Jihan's Food</h3></div>
                            <p className="mt-3 text-sm text-slate-500 dark:text-gray-400">Pantau detail saldo stok dan riwayat transaksi ritel/kasir Jihan's Food.</p>
                        </div>
                        <span className="mt-4 inline-flex items-center gap-1 text-xs font-bold text-orange-600 group-hover:text-orange-500">Lihat Detail <Icon name="arrow_forward" className="text-[14px]" /></span>
                    </Link>
                    <Link href={route('owner.hendhys')} className="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-amber-700">
                        <div>
                            <div className="flex items-center gap-3"><Icon name="cake" className="text-[28px] text-amber-500" /><h3 className="font-bold text-slate-800 dark:text-white/95">Hendhys Brownies</h3></div>
                            <p className="mt-3 text-sm text-slate-500 dark:text-gray-400">Pantau pergerakan stok, transaksi penjualan cabang, dan pusat Hendhys.</p>
                        </div>
                        <span className="mt-4 inline-flex items-center gap-1 text-xs font-bold text-amber-600 group-hover:text-amber-500">Lihat Detail <Icon name="arrow_forward" className="text-[14px]" /></span>
                    </Link>
                    <Link href={route('owner.gudang')} className="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-teal-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-teal-700">
                        <div>
                            <div className="flex items-center gap-3"><Icon name="warehouse" className="text-[28px] text-teal-500" /><h3 className="font-bold text-slate-800 dark:text-white/95">Gudang Utama</h3></div>
                            <p className="mt-3 text-sm text-slate-500 dark:text-gray-400">Kelola inventori pusat, mutasi keluar masuk barang, dan purchase order (PO) supplier.</p>
                        </div>
                        <span className="mt-4 inline-flex items-center gap-1 text-xs font-bold text-teal-600 group-hover:text-teal-500">Lihat Detail <Icon name="arrow_forward" className="text-[14px]" /></span>
                    </Link>
                </div>

                {/* Sales Chart & Today Stats */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-white/95 flex items-center gap-2"><Icon name="trending_up" className="text-blue-500" /> Tren Pendapatan Konsolidasi (7 Hari Terakhir)</h3>
                        <div className="w-full">
                            <svg viewBox={`0 0 ${chartWidth} ${chartHeight}`} className="w-full h-auto overflow-visible">
                                {/* Grid lines */}
                                <line x1="20" y1="10" x2={chartWidth - 20} y2="10" stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="20" y1={chartHeight / 2} x2={chartWidth - 20} y2={chartHeight / 2} stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="20" y1={chartHeight - 10} x2={chartWidth - 20} y2={chartHeight - 10} stroke="#cbd5e1" />
                                
                                {/* Area graph */}
                                <path
                                    d={`M 20,${chartHeight - 10} L ${pathData} L ${points[points.length - 1].x},${chartHeight - 10} Z`}
                                    fill="rgba(59, 130, 246, 0.1)"
                                />

                                {/* Line graph */}
                                <polyline
                                    fill="none"
                                    stroke="#3b82f6"
                                    strokeWidth="3"
                                    points={pathData}
                                />

                                {/* Points and Tooltip data */}
                                {points.map((p, idx) => (
                                    <g key={idx} className="group">
                                        <circle cx={p.x} cy={p.y} r="5" fill="#3b82f6" className="cursor-pointer hover:r-7 transition-all" />
                                        {/* Label text */}
                                        <text x={p.x} y={chartHeight - 2} textAnchor="middle" className="text-[8px] fill-slate-400 dark:fill-gray-500 font-semibold">{p.date}</text>
                                        {/* Tooltip value */}
                                        <text x={p.x} y={p.y - 10} textAnchor="middle" className="hidden group-hover:block text-[9px] font-bold fill-slate-850 dark:fill-white bg-slate-900 px-1 rounded">{formatQty(p.total / 1000)}k</text>
                                    </g>
                                ))}
                            </svg>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between">
                        <div>
                            <h3 className="mb-4 flex items-center gap-2 font-bold text-slate-800 dark:text-white/95"><Icon name="today" className="text-[20px] text-blue-500" /> Penjualan Hari Ini</h3>
                            <div className="space-y-4">
                                <div className="flex items-center justify-between rounded-xl border border-orange-100 bg-orange-50/50 p-4 dark:border-orange-950/20 dark:bg-orange-950/10">
                                    <div className="flex items-center gap-2.5"><Icon name="storefront" className="text-[22px] text-orange-500" /><span className="text-sm font-semibold text-slate-700 dark:text-gray-300">Jihan's Food</span></div>
                                    <span className="text-base font-bold text-orange-600 dark:text-orange-400">{formatRupiah(stats.jihans_today)}</span>
                                </div>
                                <div className="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/50 p-4 dark:border-amber-950/20 dark:bg-amber-950/10">
                                    <div className="flex items-center gap-2.5"><Icon name="cake" className="text-[22px] text-amber-600" /><span className="text-sm font-semibold text-slate-700 dark:text-gray-300">Hendhys Brownies</span></div>
                                    <span className="text-base font-bold text-amber-600 dark:text-amber-400">{formatRupiah(stats.hendhys_today)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </OwnerLayout>
    );
}
