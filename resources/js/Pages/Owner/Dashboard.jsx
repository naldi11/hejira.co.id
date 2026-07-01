import { useState } from 'react';
import { Head } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';

function ConsolidatedCard({ icon, color, label, value, sub, active, onClick }) {
    return (
        <div 
            onClick={onClick}
            className={`cursor-pointer rounded-2xl border p-6 shadow-sm transition-all duration-300 transform hover:-translate-y-1 ${
                active 
                ? 'border-blue-500 bg-blue-50/35 ring-2 ring-blue-500/20 dark:border-blue-500 dark:bg-blue-950/15' 
                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]'
            }`}
        >
            <div className="flex items-center justify-between">
                <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${color}`}>
                    <Icon name={icon} className="text-[24px]" />
                </div>
                {active && (
                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <Icon name="expand_less" className="text-[18px] font-bold" />
                    </span>
                )}
                {!active && (
                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-400 group-hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-500">
                        <Icon name="expand_more" className="text-[18px]" />
                    </span>
                )}
            </div>
            <p className="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-white/95">{value}</p>
            {sub && <p className="mt-1 text-xs text-slate-400">{sub}</p>}
        </div>
    );
}

function SubUnitCard({ icon, color, title, subtitle, value, label, active, onClick }) {
    return (
        <div 
            onClick={onClick}
            className={`cursor-pointer group flex flex-col justify-between rounded-xl border p-5 shadow-sm transition-all duration-200 min-h-[135px] ${
                active
                ? 'border-blue-500 bg-blue-50/10 dark:border-blue-600 dark:bg-blue-950/10 ring-1 ring-blue-500/20'
                : 'border-slate-200 bg-white hover:border-blue-300 hover:shadow dark:border-gray-800 dark:bg-white/[0.01]'
            }`}
        >
            <div className="flex flex-col gap-2">
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
            <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5 dark:border-gray-800/60">
                <span className="text-[11px] text-slate-400 dark:text-gray-500">
                    {active ? 'Sedang Dipilih' : 'Pilih untuk Detail'}
                </span>
                <Icon name={active ? "check_circle" : "arrow_forward"} className={`text-[14px] ${active ? 'text-blue-500' : 'text-slate-300'} transition group-hover:translate-x-1`} />
            </div>
        </div>
    );
}

function InfoBox({ icon, color, title, value, sub, active, onClick }) {
    return (
        <div 
            onClick={onClick}
            className={`cursor-pointer flex items-center gap-4 rounded-xl border p-5 shadow-sm transition-all duration-200 flex-1 min-w-[280px] ${
                active
                ? 'border-blue-500 bg-blue-50/15 dark:border-blue-650 dark:bg-blue-950/15 ring-1 ring-blue-500/20'
                : 'border-slate-100 bg-slate-50/50 hover:border-blue-200 dark:border-gray-800/40 dark:bg-white/[0.01]'
            }`}
        >
            <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ${color}`}>
                <Icon name={icon} className="text-[20px]" />
            </div>
            <div className="flex-1">
                <p className="text-xs font-semibold text-slate-400 dark:text-gray-500">{title}</p>
                <p className="text-base font-bold text-slate-800 dark:text-white/90">{value}</p>
                {sub && <p className="text-[10px] text-slate-400 dark:text-gray-500">{sub}</p>}
            </div>
            <Icon name={active ? "check_circle" : "chevron_right"} className={`text-[18px] ${active ? 'text-blue-500' : 'text-slate-300'}`} />
        </div>
    );
}

export default function OwnerDashboard({ stats, trends, details }) {
    const [activeTab, setActiveTab] = useState(null);
    const [activeStockUnit, setActiveStockUnit] = useState('gudang'); // gudang, retail, hendhys_pusat, hendhys_cabang_{id}, movements, po
    const [activeOmsetUnit, setActiveOmsetUnit] = useState('jihans_transactions'); // jihans_transactions, hendhys_transactions
    const [searchQuery, setSearchQuery] = useState('');

    const toggleTab = (tab) => {
        if (activeTab === tab) {
            setActiveTab(null);
        } else {
            setActiveTab(tab);
            setSearchQuery('');
            if (tab === 'stock') {
                setActiveStockUnit('gudang');
            } else {
                setActiveOmsetUnit('all_transactions');
            }
        }
    };

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

    // Filter Helper
    const getFilteredData = () => {
        let list = [];
        if (activeTab === 'stock') {
            if (activeStockUnit === 'gudang') {
                list = details.gudang_stocks;
            } else if (activeStockUnit === 'retail') {
                list = details.jihans_stocks;
            } else if (activeStockUnit === 'hendhys_pusat') {
                list = details.hendhys_stocks.map(h => {
                    const pusatBranch = h.branches.find(b => b.branch_id === 'pusat');
                    return {
                        code: h.code,
                        name: h.name,
                        quantity: pusatBranch ? pusatBranch.quantity : 0,
                        unit: 'PCS'
                    };
                }).filter(p => p.quantity > 0);
            } else if (activeStockUnit.startsWith('hendhys_cabang_')) {
                const targetBranchId = activeStockUnit.replace('hendhys_cabang_', '');
                list = [];
                details.hendhys_stocks.forEach(h => {
                    h.branches.forEach(b => {
                        if (String(b.branch_id) === targetBranchId) {
                            list.push({
                                code: h.code,
                                name: h.name,
                                quantity: b.quantity,
                                quantity_return: b.quantity_return,
                                unit: 'PCS'
                            });
                        }
                    });
                });
            } else if (activeStockUnit === 'movements') {
                list = details.movements;
            } else if (activeStockUnit === 'po') {
                list = details.purchase_orders;
            }
        } else if (activeTab === 'omset') {
            if (activeOmsetUnit === 'all_transactions') {
                const jihans = details.jihans_transactions.map(t => ({...t, type_unit: "Jihan's Food"}));
                const hendhys = details.hendhys_transactions.map(t => ({...t, type_unit: t.branch}));
                list = [...jihans, ...hendhys].sort((a,b) => new Date(b.date) - new Date(a.date)).slice(0, 50);
            } else if (activeOmsetUnit === 'jihans_transactions') {
                list = details.jihans_transactions;
            } else if (activeOmsetUnit === 'hendhys_pusat') {
                list = details.hendhys_transactions.filter(t => t.branch === 'Hendhys Produksi (Pusat)');
            } else if (activeOmsetUnit.startsWith('hendhys_cabang_')) {
                const targetBranchName = stats.stock.hendhys_cabang_list.find(c => String(c.id) === activeOmsetUnit.replace('hendhys_cabang_', ''))?.name;
                list = details.hendhys_transactions.filter(t => t.branch === targetBranchName);
            }
        }

        if (!searchQuery) return list;

        const q = searchQuery.toLowerCase();
        return list.filter(item => {
            return (
                (item.name && item.name.toLowerCase().includes(q)) ||
                (item.code && item.code.toLowerCase().includes(q)) ||
                (item.po_number && item.po_number.toLowerCase().includes(q)) ||
                (item.supplier && item.supplier.toLowerCase().includes(q)) ||
                (item.transaction_number && item.transaction_number.toLowerCase().includes(q)) ||
                (item.customer && item.customer.toLowerCase().includes(q)) ||
                (item.notes && item.notes.toLowerCase().includes(q)) ||
                (item.branch && item.branch.toLowerCase().includes(q))
            );
        });
    };

    const filteredData = getFilteredData();

    return (
        <OwnerLayout pageTitle="Konsolidasi Utama">
            <Head title="Owner — Konsolidasi" />

            <div className="space-y-6">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <ConsolidatedCard 
                        icon="inventory_2" 
                        color="bg-blue-100 text-blue-600 dark:bg-blue-950/20 dark:text-blue-400" 
                        label="Total Stok Sisa (Semua Unit Bisnis)" 
                        value={formatQty(stats.stock.total)} 
                        sub="Klik untuk melihat rincian stok per unit"
                        active={activeTab === 'stock'}
                        onClick={() => toggleTab('stock')}
                    />
                    <ConsolidatedCard 
                        icon="payments" 
                        color="bg-emerald-100 text-emerald-600 dark:bg-emerald-950/20 dark:text-emerald-400" 
                        label="Total Omset Penjualan (Semua Unit Bisnis)" 
                        value={formatRupiah(stats.total_revenue)} 
                        sub="Klik untuk melihat rincian omset per unit"
                        active={activeTab === 'omset'}
                        onClick={() => toggleTab('omset')}
                    />
                </div>

                {/* Sub Cards Section - STOK */}
                {activeTab === 'stock' && (
                    <div className="rounded-2xl border border-blue-200 bg-blue-50/5 p-6 shadow-inner dark:border-blue-950/30 dark:bg-blue-950/[0.02] space-y-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-bold text-slate-800 dark:text-white/90 flex items-center gap-2">
                                <Icon name="inventory" className="text-blue-500" /> Rincian Stok & Operasional Unit Bisnis
                            </h3>
                            <span className="text-[11px] text-slate-400 dark:text-gray-500">Pilih unit untuk menampilkan tabel detail di bawah</span>
                        </div>
                        
                        {/* Grid layout of Business Units Stocks to prevent vertical overlaps and broken alignments */}
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                            <SubUnitCard 
                                icon="warehouse"
                                color="text-indigo-500"
                                title="Jihans Gudang"
                                subtitle="Stok utama / bahan mentah"
                                value={formatQty(stats.stock.jihans_gudang)}
                                label="Sisa Stok"
                                active={activeStockUnit === 'gudang'}
                                onClick={() => { setActiveStockUnit('gudang'); setSearchQuery(''); }}
                            />
                            <SubUnitCard 
                                icon="storefront"
                                color="text-orange-500"
                                title="Jihans Retail"
                                subtitle="Stok produk kasir & toko"
                                value={formatQty(stats.stock.jihans_retail)}
                                label="Sisa Stok"
                                active={activeStockUnit === 'retail'}
                                onClick={() => { setActiveStockUnit('retail'); setSearchQuery(''); }}
                            />
                            <SubUnitCard 
                                icon="home_work"
                                color="text-amber-500"
                                title="Hendhys Pusat"
                                subtitle="Stok pusat Hendhys"
                                value={formatQty(stats.stock.hendhys_pusat)}
                                label="Sisa Stok"
                                active={activeStockUnit === 'hendhys_pusat'}
                                onClick={() => { setActiveStockUnit('hendhys_pusat'); setSearchQuery(''); }}
                            />
                            
                            {/* Dynamically render individual Hendhys branches */}
                            {stats.stock.hendhys_cabang_list.map((cb) => (
                                <SubUnitCard 
                                    key={cb.id}
                                    icon="store"
                                    color="text-yellow-600"
                                    title={cb.name}
                                    subtitle="Cabang Hendhys"
                                    value={formatQty(cb.quantity)}
                                    label="Sisa Stok"
                                    active={activeStockUnit === `hendhys_cabang_${cb.id}`}
                                    onClick={() => { setActiveStockUnit(`hendhys_cabang_${cb.id}`); setSearchQuery(''); }}
                                />
                            ))}
                        </div>

                        {/* Operational Indicators: Movements & PO */}
                        <div className="flex flex-wrap gap-4 border-t border-slate-200/50 pt-5 dark:border-gray-800/40">
                            <InfoBox 
                                icon="swap_horiz" 
                                color="bg-indigo-50 text-indigo-600 dark:bg-indigo-950/20 dark:text-indigo-400" 
                                title="Mutasi Pergerakan Stok" 
                                value={`${stats.movements.count} Kali Mutasi`} 
                                sub={`Total kuantitas barang bergerak: ${formatQty(stats.movements.qty)}`}
                                active={activeStockUnit === 'movements'}
                                onClick={() => { setActiveStockUnit('movements'); setSearchQuery(''); }}
                            />
                            <InfoBox 
                                icon="receipt_long" 
                                color="bg-teal-50 text-teal-600 dark:bg-teal-950/20 dark:text-teal-400" 
                                title="Purchase Order (PO) Supplier" 
                                value={`${stats.po.count} Dokumen PO`} 
                                sub={`Total kuantitas dipesan: ${formatQty(stats.po.qty)}`}
                                active={activeStockUnit === 'po'}
                                onClick={() => { setActiveStockUnit('po'); setSearchQuery(''); }}
                            />
                        </div>
                    </div>
                )}

                {/* Sub Cards Section - OMSET */}
                {activeTab === 'omset' && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50/5 p-6 shadow-inner dark:border-emerald-950/30 dark:bg-emerald-950/[0.02] space-y-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-bold text-slate-800 dark:text-white/90 flex items-center gap-2">
                                <Icon name="analytics" className="text-emerald-500" /> Rincian Omset Pendapatan Unit Bisnis
                            </h3>
                            <span className="text-[11px] text-slate-400 dark:text-gray-500">Pilih unit untuk menampilkan rincian transaksi di bawah</span>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <SubUnitCard 
                                icon="done_all"
                                color="text-blue-500"
                                title="Semua Unit Bisnis"
                                subtitle="Penjualan Konsolidasi Utama"
                                value={formatRupiah(stats.total_revenue)}
                                label="Total Konsolidasi"
                                active={activeOmsetUnit === 'all_transactions'}
                                onClick={() => { setActiveOmsetUnit('all_transactions'); setSearchQuery(''); }}
                            />
                            <SubUnitCard 
                                icon="storefront"
                                color="text-orange-500"
                                title="Jihan's Food"
                                subtitle="Pendapatan retail Jihan's"
                                value={formatRupiah(stats.jihans_revenue)}
                                label="Total Omset"
                                active={activeOmsetUnit === 'jihans_transactions'}
                                onClick={() => { setActiveOmsetUnit('jihans_transactions'); setSearchQuery(''); }}
                            />
                            <SubUnitCard 
                                icon="home_work"
                                color="text-amber-500"
                                title="Hendhys Pusat"
                                subtitle="Pendapatan Hendhys Pusat"
                                value={formatRupiah(stats.hendhys_pusat_revenue)}
                                label="Total Omset"
                                active={activeOmsetUnit === 'hendhys_pusat'}
                                onClick={() => { setActiveOmsetUnit('hendhys_pusat'); setSearchQuery(''); }}
                            />
                            {/* Dynamically render individual Hendhys branches omset */}
                            {stats.stock.hendhys_cabang_list.map((cb) => (
                                <SubUnitCard 
                                    key={cb.id}
                                    icon="store"
                                    color="text-yellow-600"
                                    title={cb.name}
                                    subtitle="Cabang Hendhys"
                                    value={formatRupiah(cb.revenue)}
                                    label="Total Omset"
                                    active={activeOmsetUnit === `hendhys_cabang_${cb.id}`}
                                    onClick={() => { setActiveOmsetUnit(`hendhys_cabang_${cb.id}`); setSearchQuery(''); }}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Detailed Table Section (Consolidated Detail Viewer) */}
                {activeTab && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 className="font-bold text-slate-800 dark:text-white text-base capitalize flex items-center gap-2">
                                    <Icon name="list_alt" className="text-blue-500" />
                                    Detail: {activeTab === 'stock' 
                                        ? (activeStockUnit.startsWith('hendhys_cabang_') 
                                            ? stats.stock.hendhys_cabang_list.find(c => String(c.id) === activeStockUnit.replace('hendhys_cabang_', ''))?.name ?? 'Hendhys Cabang'
                                            : activeStockUnit.replace('_', ' '))
                                        : (activeOmsetUnit.startsWith('hendhys_cabang_')
                                            ? stats.stock.hendhys_cabang_list.find(c => String(c.id) === activeOmsetUnit.replace('hendhys_cabang_', ''))?.name ?? 'Hendhys Cabang'
                                            : activeOmsetUnit.replace('_', ' '))}
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">Menampilkan data real-time unit bisnis pilihan Anda.</p>
                            </div>
                            <div className="relative min-w-[245px]">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]" />
                                <input 
                                    type="text" 
                                    placeholder="Cari data..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                />
                            </div>
                        </div>

                        {/* Rendering Table Dynamically based on Selection */}
                        <div className="custom-scrollbar overflow-x-auto">
                            {filteredData.length === 0 ? (
                                <div className="py-12 text-center text-slate-400 text-sm flex flex-col items-center justify-center gap-2">
                                    <Icon name="search_off" className="text-[32px] text-slate-350" />
                                    <span>Tidak ada data yang cocok dengan pencarian Anda.</span>
                                </div>
                            ) : (
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        {/* TABLE HEADERS */}
                                        {activeTab === 'stock' && (activeStockUnit === 'gudang' || activeStockUnit === 'retail' || activeStockUnit === 'hendhys_pusat') && (
                                            <tr>
                                                <th className="px-4 py-3">Kode</th>
                                                <th className="px-4 py-3">Nama Produk</th>
                                                <th className="px-4 py-3 text-right">Stok Tersisa</th>
                                            </tr>
                                        )}
                                        {activeTab === 'stock' && activeStockUnit.startsWith('hendhys_cabang_') && (
                                            <tr>
                                                <th className="px-4 py-3">Kode</th>
                                                <th className="px-4 py-3">Nama Produk</th>
                                                <th className="px-4 py-3 text-right">Stok Baik</th>
                                                <th className="px-4 py-3 text-right">Stok Rusak/Return</th>
                                            </tr>
                                        )}
                                        {activeTab === 'stock' && activeStockUnit === 'movements' && (
                                            <tr>
                                                <th className="px-4 py-3">Tanggal/Waktu</th>
                                                <th className="px-4 py-3">Nama Produk</th>
                                                <th className="px-4 py-3">Tipe</th>
                                                <th className="px-4 py-3 text-right">Kuantitas</th>
                                                <th className="px-4 py-3">Catatan</th>
                                                <th className="px-4 py-3">Petugas</th>
                                            </tr>
                                        )}
                                        {activeTab === 'stock' && activeStockUnit === 'po' && (
                                            <tr>
                                                <th className="px-4 py-3">No. PO</th>
                                                <th className="px-4 py-3">Supplier</th>
                                                <th className="px-4 py-3">Tanggal Pesan</th>
                                                <th className="px-4 py-3">Status</th>
                                                <th className="px-4 py-3 text-right">Total Nominal</th>
                                                <th className="px-4 py-3">Pembuat</th>
                                            </tr>
                                        )}
                                        {activeTab === 'omset' && (
                                            <tr>
                                                <th className="px-4 py-3">Tanggal</th>
                                                <th className="px-4 py-3">No. Transaksi</th>
                                                {(activeOmsetUnit === 'all_transactions' || activeOmsetUnit === 'hendhys_pusat' || activeOmsetUnit.startsWith('hendhys_cabang_')) && (
                                                    <th className="px-4 py-3">Cabang / Unit</th>
                                                )}
                                                <th className="px-4 py-3">Pelanggan</th>
                                                <th className="px-4 py-3">Status</th>
                                                <th className="px-4 py-3 text-right">Total Transaksi</th>
                                                <th className="px-4 py-3">Kasir</th>
                                            </tr>
                                        )}
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {/* TABLE BODY ROWS */}
                                        {activeTab === 'stock' && (activeStockUnit === 'gudang' || activeStockUnit === 'retail' || activeStockUnit === 'hendhys_pusat') && (
                                            filteredData.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-500">{row.code}</td>
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white/90">{row.name}</td>
                                                    <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">
                                                        {formatQty(row.quantity)} <span className="text-[10px] font-normal text-slate-400">{row.unit}</span>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                        {activeTab === 'stock' && activeStockUnit.startsWith('hendhys_cabang_') && (
                                            filteredData.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-500">{row.code}</td>
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white/90">{row.name}</td>
                                                    <td className="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{formatQty(row.quantity)}</td>
                                                    <td className="px-4 py-3 text-right font-bold text-red-650 dark:text-red-400">{formatQty(row.quantity_return)}</td>
                                                </tr>
                                            ))
                                        )}
                                        {activeTab === 'stock' && activeStockUnit === 'movements' && (
                                            filteredData.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 text-slate-500 font-medium text-xs whitespace-nowrap">{row.date}</td>
                                                    <td className="px-4 py-3 font-bold text-slate-800 dark:text-white">{row.product_name}</td>
                                                    <td className="px-4 py-3">
                                                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${row.type === 'in' ? 'bg-green-100 text-green-700 dark:bg-green-950/20 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-950/20 dark:text-red-400'}`}>
                                                            {row.type}
                                                        </span>
                                                    </td>
                                                    <td className={`px-4 py-3 text-right font-black ${row.type === 'in' ? 'text-green-600' : 'text-red-605'}`}>{formatQty(row.quantity)}</td>
                                                    <td className="px-4 py-3 text-xs text-slate-500 max-w-[200px] truncate" title={row.notes}>{row.notes ?? '-'}</td>
                                                    <td className="px-4 py-3 text-slate-600 dark:text-gray-400 text-xs font-semibold">{row.user}</td>
                                                </tr>
                                            ))
                                        )}
                                        {activeTab === 'stock' && activeStockUnit === 'po' && (
                                            filteredData.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-700 dark:text-white">{row.po_number}</td>
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white">{row.supplier}</td>
                                                    <td className="px-4 py-3 text-slate-500 text-xs">{row.date}</td>
                                                    <td className="px-4 py-3">
                                                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${row.status === 'received' ? 'bg-green-100 text-green-700 dark:bg-green-950/20' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/20'}`}>
                                                            {row.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{formatRupiah(row.total_amount)}</td>
                                                    <td className="px-4 py-3 text-slate-605 dark:text-gray-400 text-xs">{row.user}</td>
                                                </tr>
                                            ))
                                        )}
                                        {activeTab === 'omset' && (
                                            filteredData.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">{row.date}</td>
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-750 dark:text-white">{row.transaction_number}</td>
                                                    {(activeOmsetUnit === 'all_transactions' || activeOmsetUnit === 'hendhys_pusat' || activeOmsetUnit.startsWith('hendhys_cabang_')) && (
                                                        <td className="px-4 py-3 font-semibold text-slate-600 dark:text-gray-300 text-xs">{row.type_unit || row.branch || "Jihan's Food"}</td>
                                                    )}
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white">{row.customer}</td>
                                                    <td className="px-4 py-3">
                                                        <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-100 text-green-700 dark:bg-green-950/20">
                                                            {row.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{formatRupiah(row.grand_total)}</td>
                                                    <td className="px-4 py-3 text-slate-605 dark:text-gray-400 text-xs">{row.user}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                )}

                {/* Sales Chart & Today Stats */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-white/95 flex items-center gap-2">
                            <Icon name="trending_up" className="text-blue-500" /> Tren Pendapatan Konsolidasi (7 Hari Terakhir)
                        </h3>
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
                            <h3 className="mb-4 flex items-center gap-2 font-bold text-slate-800 dark:text-white/95">
                                <Icon name="today" className="text-[20px] text-blue-500" /> Penjualan Hari Ini (Konsolidasi)
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
            </div>
        </OwnerLayout>
    );
}
