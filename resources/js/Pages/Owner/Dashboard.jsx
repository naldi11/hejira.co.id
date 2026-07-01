import { Head, router } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';

/* ─── Mini Components ────────────────────────────────────────────────────── */
function SubUnitCard({ icon, color, title, subtitle, value, label, onClick }) {
    return (
        <button
            onClick={onClick}
            className="w-full text-left cursor-pointer group flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 min-h-[135px] hover:border-blue-300 hover:shadow dark:border-gray-800 dark:bg-white/[0.01]"
        >
            <div className="flex flex-col gap-2 w-full">
                <div className="flex flex-col overflow-hidden">
                    <div className="flex items-center gap-2">
                        <Icon name={icon} className={`text-[18px] shrink-0 ${color}`} />
                        <h4 className="font-bold text-slate-800 dark:text-white/90 truncate" title={title}>{title}</h4>
                    </div>
                    <p className="text-[10px] text-slate-400 dark:text-gray-500 mt-0.5 truncate">{subtitle}</p>
                </div>
                <div className="mt-1">
                    <span className="text-[10px] font-semibold text-slate-400 dark:text-gray-500 uppercase tracking-wider">{label}</span>
                    <p className="text-xl font-black text-slate-800 dark:text-white/90">{value}</p>
                </div>
            </div>
            <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5 dark:border-gray-800/60 w-full">
                <span className="text-[11px] text-slate-400 dark:text-gray-500">
                    Lihat Detail →
                </span>
                <Icon
                    name="arrow_forward"
                    className="text-[14px] text-slate-300 transition-transform group-hover:translate-x-1"
                />
            </div>
        </button>
    );
}

function InfoBox({ icon, color, title, value, sub, onClick }) {
    return (
        <button
            onClick={onClick}
            className="w-full sm:flex-1 cursor-pointer flex items-center gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-5 shadow-sm transition-all duration-200 min-w-[280px] hover:border-blue-250 dark:border-gray-800/40 dark:bg-white/[0.01]"
        >
            <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ${color}`}>
                <Icon name={icon} className="text-[20px]" />
            </div>
            <div className="flex-1 text-left">
                <p className="text-xs font-semibold text-slate-400 dark:text-gray-500">{title}</p>
                <p className="text-base font-bold text-slate-800 dark:text-white/90">{value}</p>
                {sub && <p className="text-[10px] text-slate-400 dark:text-gray-500">{sub}</p>}
            </div>
            <Icon name="chevron_right" className="text-[18px] text-slate-300" />
        </button>
    );
}

/* ─── Main Page ──────────────────────────────────────────────────────────── */
export default function OwnerDashboard({ stats, trends }) {
    // Navigate to detail page
    const openStockDetail = (unit) => {
        router.visit(route('owner.dashboard.detail', { mode: 'stock', unit }));
    };

    const openOmsetDetail = (unit) => {
        router.visit(route('owner.dashboard.detail', { mode: 'omset', unit }));
    };

    // Chart
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
                {/* ── STOK Section ───────────────────────────────────────── */}
                <div className="rounded-2xl border border-blue-105 bg-white p-6 shadow-sm dark:border-blue-950/30 dark:bg-white/[0.02]">
                    <div className="mb-5 flex items-center gap-2">
                        <Icon name="inventory" className="text-blue-500 text-[20px]" />
                        <div>
                            <h2 className="font-bold text-slate-800 dark:text-white text-base">Rincian Stok Unit Bisnis</h2>
                            <p className="text-xs text-slate-400">Klik card untuk melihat detail stok</p>
                        </div>
                        <div className="ml-auto text-right">
                            <p className="text-xs text-slate-400 uppercase tracking-wide font-semibold">Total Stok</p>
                            <p className="text-2xl font-black text-blue-600">{formatQty(stats.stock.total)}</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        <SubUnitCard
                            icon="warehouse" color="text-indigo-500"
                            title="Jihans Gudang" subtitle="Stok utama / bahan mentah"
                            value={formatQty(stats.stock.jihans_gudang)} label="Sisa Stok"
                            onClick={() => openStockDetail('gudang')}
                        />
                        <SubUnitCard
                            icon="storefront" color="text-orange-500"
                            title="Jihans Retail" subtitle="Stok produk kasir & toko"
                            value={formatQty(stats.stock.jihans_retail)} label="Sisa Stok"
                            onClick={() => openStockDetail('retail')}
                        />
                        <SubUnitCard
                            icon="home_work" color="text-amber-500"
                            title="Hendhys Pusat" subtitle="Stok pusat Hendhys"
                            value={formatQty(stats.stock.hendhys_pusat)} label="Sisa Stok"
                            onClick={() => openStockDetail('hendhys_pusat')}
                        />
                        {stats.stock.hendhys_cabang_list.map((cb) => (
                            <SubUnitCard
                                key={cb.id}
                                icon="store" color="text-yellow-600"
                                title={cb.name} subtitle="Cabang Hendhys"
                                value={formatQty(cb.quantity)} label="Sisa Stok"
                                onClick={() => openStockDetail(`hendhys_cabang_${cb.id}`)}
                            />
                        ))}
                    </div>

                    {/* Operational Indicators */}
                    <div className="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row dark:border-gray-800/40">
                        <InfoBox
                            icon="swap_horiz" color="bg-indigo-50 text-indigo-600 dark:bg-indigo-950/20 dark:text-indigo-400"
                            title="Mutasi Pergerakan Stok"
                            value={`${stats.movements.count} Kali Mutasi`}
                            sub={`Total kuantitas barang bergerak: ${formatQty(stats.movements.qty)}`}
                            onClick={() => openStockDetail('movements')}
                        />
                        <InfoBox
                            icon="receipt_long" color="bg-teal-50 text-teal-600 dark:bg-teal-950/20 dark:text-teal-400"
                            title="Purchase Order (PO) Supplier"
                            value={`${stats.po.count} Dokumen PO`}
                            sub={`Total kuantitas dipesan: ${formatQty(stats.po.qty)}`}
                            onClick={() => openStockDetail('po')}
                        />
                    </div>
                </div>

                {/* ── OMSET Section ──────────────────────────────────────── */}
                <div className="rounded-2xl border border-emerald-105 bg-white p-6 shadow-sm dark:border-emerald-950/30 dark:bg-white/[0.02]">
                    <div className="mb-5 flex items-center gap-2">
                        <Icon name="analytics" className="text-emerald-500 text-[20px]" />
                        <div>
                            <h2 className="font-bold text-slate-800 dark:text-white text-base">Rincian Omset Penjualan</h2>
                            <p className="text-xs text-slate-400">Klik card untuk melihat detail transaksi</p>
                        </div>
                        <div className="ml-auto text-right">
                            <p className="text-xs text-slate-400 uppercase tracking-wide font-semibold">Total Omset</p>
                            <p className="text-2xl font-black text-emerald-600">{formatRupiah(stats.total_revenue)}</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        <SubUnitCard
                            icon="done_all" color="text-blue-500"
                            title="Semua Unit" subtitle="Penjualan Konsolidasi"
                            value={formatRupiah(stats.total_revenue)} label="Total Konsolidasi"
                            onClick={() => openOmsetDetail('all_transactions')}
                        />
                        <SubUnitCard
                            icon="storefront" color="text-orange-500"
                            title="Jihan's Food" subtitle="Pendapatan retail Jihan's"
                            value={formatRupiah(stats.jihans_revenue)} label="Total Omset"
                            onClick={() => openOmsetDetail('jihans_transactions')}
                        />
                        <SubUnitCard
                            icon="home_work" color="text-amber-500"
                            title="Hendhys Pusat" subtitle="Pendapatan Hendhys Pusat"
                            value={formatRupiah(stats.hendhys_pusat_revenue)} label="Total Omset"
                            onClick={() => openOmsetDetail('hendhys_pusat')}
                        />
                        {stats.stock.hendhys_cabang_list.map((cb) => (
                            <SubUnitCard
                                key={cb.id}
                                icon="store" color="text-yellow-600"
                                title={cb.name} subtitle="Cabang Hendhys"
                                value={formatRupiah(cb.revenue)} label="Total Omset"
                                onClick={() => openOmsetDetail(`hendhys_cabang_${cb.id}`)}
                            />
                        ))}
                    </div>
                </div>

                {/* ── Sales Chart & Today ─────────────────────────────────── */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-white/95 flex items-center gap-2">
                            <Icon name="trending_up" className="text-blue-500" /> Tren Pendapatan Konsolidasi (7 Hari Terakhir)
                        </h3>
                        <div className="w-full">
                            <svg viewBox={`0 0 ${chartWidth} ${chartHeight}`} className="w-full h-auto overflow-visible">
                                <line x1="20" y1="10" x2={chartWidth - 20} y2="10" stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="20" y1={chartHeight / 2} x2={chartWidth - 20} y2={chartHeight / 2} stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="20" y1={chartHeight - 10} x2={chartWidth - 20} y2={chartHeight - 10} stroke="#cbd5e1" />
                                <path
                                    d={`M 20,${chartHeight - 10} L ${pathData} L ${points[points.length - 1].x},${chartHeight - 10} Z`}
                                    fill="rgba(59, 130, 246, 0.1)"
                                />
                                <polyline fill="none" stroke="#3b82f6" strokeWidth="3" points={pathData} />
                                {points.map((p, idx) => (
                                    <g key={idx} className="group">
                                        <circle cx={p.x} cy={p.y} r="5" fill="#3b82f6" className="cursor-pointer" />
                                        <text x={p.x} y={chartHeight - 2} textAnchor="middle" className="text-[8px] fill-slate-400 font-semibold">{p.date}</text>
                                        <text x={p.x} y={p.y - 10} textAnchor="middle" className="hidden group-hover:block text-[9px] font-bold fill-slate-850">{formatQty(p.total / 1000)}k</text>
                                    </g>
                                ))}
                            </svg>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between">
                        <h3 className="mb-4 flex items-center gap-2 font-bold text-slate-800 dark:text-white/95">
                            <Icon name="today" className="text-[20px] text-blue-500" /> Penjualan Hari Ini
                        </h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between rounded-xl border border-orange-100 bg-orange-50/50 p-4 dark:border-orange-950/20 dark:bg-orange-950/10">
                                <div className="flex items-center gap-2.5">
                                    <Icon name="storefront" className="text-[22px] text-orange-500" />
                                    <span className="text-sm font-semibold text-slate-700 dark:text-gray-300">Jihan's Food</span>
                                </div>
                                <span className="text-base font-bold text-orange-600 dark:text-orange-400">{formatRupiah(stats.jihans_today)}</span>
                            </div>
                            <div className="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/50 p-4 dark:border-amber-950/20 dark:bg-amber-950/10">
                                <div className="flex items-center gap-2.5">
                                    <Icon name="cake" className="text-[22px] text-amber-600" />
                                    <span className="text-sm font-semibold text-slate-700 dark:text-gray-300">Hendhys Brownies</span>
                                </div>
                                <span className="text-base font-bold text-amber-600 dark:text-amber-400">{formatRupiah(stats.hendhys_today)}</span>
                            </div>
                            <div className="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-950/20 dark:bg-blue-950/10 border-t-2">
                                <div className="flex items-center gap-2.5">
                                    <Icon name="done_all" className="text-[22px] text-blue-600" />
                                    <span className="text-sm font-bold text-slate-800 dark:text-white/90">Total Konsolidasi</span>
                                </div>
                                <span className="text-base font-extrabold text-blue-600 dark:text-blue-400">{formatRupiah(stats.total_today)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </OwnerLayout>
    );
}
