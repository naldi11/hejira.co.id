import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Modal from '@/Components/Modal';
import Icon from '@/Components/Icon';
import { formatRupiah } from '@/lib/format';
import axios from 'axios';

const route = window.route;

export default function ReportLaci({ rows, filters, activeShift, auth }) {
    const [filterForm, setFilterForm] = useState({ 
        date_from: filters.date_from ?? '', 
        date_to: filters.date_to ?? '' 
    });

    // Export Modal
    const [exportModal, setExportModal] = useState(false);
    const [exportType, setExportType] = useState('shift'); // shift | harian | mingguan | bulanan
    const [exportShiftId, setExportShiftId] = useState('');
    const [exportDateFrom, setExportDateFrom] = useState('');
    const [exportDateTo, setExportDateTo] = useState('');
    const [exportMonth, setExportMonth] = useState(() => {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
    });

    const handleExport = () => {
        let url = '';
        const base = route('hendhys.reports.pdf', exportType === 'shift' ? 'laci' : exportType);
        if (exportType === 'shift') {
            if (!exportShiftId) return alert('Pilih shift terlebih dahulu!');
            url = `${base}?shift_id=${exportShiftId}`;
        } else if (exportType === 'harian') {
            url = `${base}?date_from=${exportDateFrom}&date_to=${exportDateTo}`;
        } else if (exportType === 'mingguan') {
            url = `${base}?date_from=${exportDateFrom}&date_to=${exportDateTo}`;
        } else if (exportType === 'bulanan') {
            // bulanan: send year-month as date_from/date_to
            const [y, m] = exportMonth.split('-');
            const lastDay = new Date(y, m, 0).getDate();
            url = `${base}?date_from=${exportMonth}-01&date_to=${exportMonth}-${lastDay}`;
        }
        window.open(url, '_blank');
        setExportModal(false);
    };

    const EXPORT_TYPES = [
        { id: 'shift', label: 'Per Shift', icon: 'badge' },
        { id: 'harian', label: 'Harian', icon: 'today' },
        { id: 'mingguan', label: 'Mingguan', icon: 'date_range' },
        { id: 'bulanan', label: 'Bulanan', icon: 'calendar_month' },
    ];


    // Modal States
    const [openShiftModal, setOpenShiftModal] = useState(false);
    const [closeShiftModal, setCloseShiftModal] = useState(false);
    const [detailModal, setDetailModal] = useState(false);

    // Form inputs
    const [startingCash, setStartingCash] = useState(0);
    const [actualCash, setActualCash] = useState(0);
    const [closeNote, setCloseNote] = useState('');

    // Detail shift state
    const [detailLoading, setDetailLoading] = useState(false);
    const [selectedShift, setSelectedShift] = useState(null);
    const [detailData, setDetailData] = useState(null);
    const [activeTab, setActiveTab] = useState('payments');

    const roles = auth?.user?.roles || [];
    const isKasir = roles.includes('kasir_hendhys') || roles.includes('super_admin_hendhys');

    const handleFilter = (e) => { 
        e?.preventDefault(); 
        const p = {}; 
        Object.entries(filterForm).forEach(([k, v]) => { if (v) p[k] = v; }); 
        router.get(route('hendhys.reports.laci'), p, { preserveState: true }); 
    };

    const handleOpenShift = (e) => {
        e.preventDefault();
        router.post(route('hendhys.shifts.open'), {
            starting_cash: parseInt(startingCash) || 0
        }, {
            onSuccess: () => {
                setOpenShiftModal(false);
                setStartingCash(0);
            }
        });
    };

    const handleCloseShift = (e) => {
        e.preventDefault();
        router.post(route('hendhys.shifts.close'), {
            actual_cash: parseInt(actualCash) || 0,
            note: closeNote
        }, {
            onSuccess: () => {
                setCloseShiftModal(false);
                setActualCash(0);
                setCloseNote('');
            }
        });
    };

    const handleViewDetail = async (shift) => {
        setSelectedShift(shift);
        setDetailModal(true);
        setDetailLoading(true);
        setActiveTab('payments');

        try {
            const res = await axios.get(`/hendhys/shifts/${shift.id}/details`);
            setDetailData(res.data);
        } catch (err) {
            console.error(err);
            alert('Gagal mengambil detail laci kasir.');
            setDetailModal(false);
        } finally {
            setDetailLoading(false);
        }
    };

    const formatDateTime = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleString('id-ID', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <HendhysLayout pageTitle="Laporan Laci Kasir">
            <Head title="Laci Kasir" />
            <div className="space-y-6">
                
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-800 dark:text-white/90">Laci Kasir (Shift Kerja)</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Kelola buka-tutup shift dan rekapitulasi laci uang kasir</p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setExportModal(true)}
                            className="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03] transition"
                        >
                            <Icon name="picture_as_pdf" /> Export PDF
                        </button>
                    </div>
                </div>

                {/* Shift Controller Card (Only visible to Kasir) */}
                {isKasir && (
                    <div className="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-6 dark:border-amber-950/40 dark:bg-amber-950/10">
                        {activeShift ? (
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <span className="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" />
                                        <h3 className="font-bold text-gray-800 dark:text-amber-100">Laci Kasir Sedang Terbuka (Shift Aktif)</h3>
                                    </div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Dibuka sejak: <span className="font-semibold text-gray-800 dark:text-white">{formatDateTime(activeShift.opened_at)}</span>
                                    </p>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Uang Modal Awal: <span className="font-semibold text-amber-600 dark:text-amber-400">{formatRupiah(activeShift.starting_cash)}</span>
                                    </p>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Uang Diharapkan (Saat Ini): <span className="font-semibold text-emerald-600 dark:text-emerald-400">{formatRupiah(activeShift.expected_cash)}</span>
                                    </p>
                                    <div className="flex flex-wrap gap-x-4 gap-y-1 mt-2 pt-2 border-t border-amber-200/50 dark:border-amber-900/30">
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Transfer Bank: <span className="font-semibold text-gray-700 dark:text-gray-300">{formatRupiah(activeShift.payment_summary?.transfer ?? 0)}</span>
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Kartu Debit: <span className="font-semibold text-gray-700 dark:text-gray-300">{formatRupiah(activeShift.payment_summary?.kartu_debit ?? 0)}</span>
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Kartu Kredit: <span className="font-semibold text-gray-700 dark:text-gray-300">{formatRupiah(activeShift.payment_summary?.kartu_kredit ?? 0)}</span>
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Kredit/Bon: <span className="font-semibold text-rose-600 dark:text-rose-400">{formatRupiah(activeShift.payment_summary?.kredit ?? 0)}</span>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <button 
                                        onClick={() => {
                                            setActualCash(0);
                                            setCloseShiftModal(true);
                                        }}
                                        className="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-sm transition"
                                    >
                                        <Icon name="lock" /> Tutup Laci (Akhiri Shift)
                                    </button>
                                </div>
                            </div>
                        ) : (
                             <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                                 <div className="flex gap-4">
                                     <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-md shadow-rose-500/20">
                                         <Icon name="lock" className="text-[24px]" />
                                     </div>
                                     <div className="space-y-1">
                                         <h3 className="text-lg font-bold text-rose-800 dark:text-rose-200">Akses POS Terkunci — Laci Kasir Belum Dibuka</h3>
                                         <p className="text-sm text-rose-650/90 dark:text-rose-300 max-w-2xl leading-relaxed">
                                             Sesuai Prosedur Operasional Standar (SOP), Anda wajib memulai shift kerja dengan menginput uang modal awal terlebih dahulu. Fitur penjualan dan checkout kasir tetap dikunci hingga laci diaktifkan.
                                         </p>
                                     </div>
                                 </div>
                                 <div className="shrink-0">
                                     <button 
                                         onClick={() => setOpenShiftModal(true)}
                                         className="w-full md:w-auto flex items-center justify-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30 transition-all duration-200"
                                     >
                                         <Icon name="vpn_key" /> Buka Laci & Mulai Shift Kerja
                                     </button>
                                 </div>
                             </div>
                        )}
                    </div>
                )}

                {/* Filter and Table */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b bg-gray-50/50 p-4 dark:bg-white/[0.01]">
                        <form onSubmit={handleFilter} className="flex flex-wrap items-center gap-3">
                            <input 
                                type="date" 
                                value={filterForm.date_from} 
                                onChange={(e) => setFilterForm({...filterForm, date_from: e.target.value})} 
                                onClick={(e) => e.target.showPicker && e.target.showPicker()}
                                className="rounded-lg border-gray-200 py-1.5 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 cursor-pointer" 
                            />
                            <span className="text-gray-400 dark:text-gray-500 text-sm">s/d</span>
                            <input 
                                type="date" 
                                value={filterForm.date_to} 
                                onChange={(e) => setFilterForm({...filterForm, date_to: e.target.value})} 
                                onClick={(e) => e.target.showPicker && e.target.showPicker()}
                                className="rounded-lg border-gray-200 py-1.5 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-880 dark:text-white focus:border-amber-500 focus:ring-amber-500 cursor-pointer" 
                            />
                            <button type="submit" className="rounded-lg bg-amber-500 px-5 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 transition">Filter</button>
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-gray-50 text-gray-500 dark:text-gray-400 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-4 py-3">Kasir</th>
                                    <th className="px-4 py-3">Buka Shift</th>
                                    <th className="px-4 py-3">Tutup Shift</th>
                                    <th className="px-4 py-3 text-center">Status</th>
                                    <th className="px-4 py-3 text-right">Modal Awal</th>
                                    <th className="px-4 py-3 text-right text-emerald-600">Expected Cash</th>
                                    <th className="px-4 py-3 text-right">Transfer</th>
                                    <th className="px-4 py-3 text-right">Debit</th>
                                    <th className="px-4 py-3 text-right text-rose-500">Kredit (Bon)</th>
                                    <th className="px-4 py-3 text-right font-bold">Actual Cash</th>
                                    <th className="px-4 py-3 text-right">Selisih</th>
                                    <th className="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {rows.data?.length === 0 ? (
                                    <EmptyState colSpan={12} icon="assessment" message="Belum ada riwayat shift laci kasir." />
                                ) : (
                                    rows.data?.map((r, i) => {
                                        const isClosed = r.status === 'closed';
                                        const hasDiscrepancy = isClosed && r.discrepancy !== 0;

                                        return (
                                            <tr key={i} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                                <td className="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">{r.user?.name ?? 'Sistem'}</td>
                                                <td className="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{formatDateTime(r.opened_at)}</td>
                                                <td className="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{isClosed ? formatDateTime(r.closed_at) : '-'}</td>
                                                <td className="px-4 py-3 text-center">
                                                    <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${
                                                        isClosed 
                                                            ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' 
                                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                    }`}>
                                                        {isClosed ? 'Selesai' : 'Aktif'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right whitespace-nowrap">{formatRupiah(r.starting_cash)}</td>
                                                <td className="px-4 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                                    {formatRupiah(r.expected_cash)}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                    {formatRupiah(r.payment_summary?.transfer ?? 0)}
                                                </td>
                                                <td className="px-4 py-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                    {formatRupiah(r.payment_summary?.kartu_debit ?? 0)}
                                                </td>
                                                <td className="px-4 py-3 text-right text-rose-500 dark:text-rose-400 whitespace-nowrap">
                                                    {formatRupiah(r.payment_summary?.kredit ?? 0)}
                                                </td>
                                                <td className="px-4 py-3 text-right font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                    {isClosed ? formatRupiah(r.actual_cash) : '-'}
                                                </td>
                                                <td className={`px-4 py-3 text-right font-bold whitespace-nowrap ${
                                                    hasDiscrepancy 
                                                        ? (r.discrepancy > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-rose-600 dark:text-rose-400') 
                                                        : 'text-gray-700 dark:text-gray-300'
                                                }`}>
                                                    {isClosed ? (r.discrepancy === 0 ? 'Pas' : formatRupiah(r.discrepancy)) : '-'}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <button 
                                                        onClick={() => handleViewDetail(r)}
                                                        className="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 font-semibold whitespace-nowrap"
                                                    >
                                                        <Icon name="visibility" className="text-[16px]" /> Detail
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                    {rows.links && <div className="border-t p-4"><Pagination links={rows.links} /></div>}
                </div>
            </div>

            {/* Modal Buka Laci */}
            <Modal show={openShiftModal} onClose={() => setOpenShiftModal(false)} maxWidth="md">
                <form onSubmit={handleOpenShift} className="p-6">
                    <h3 className="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-4">
                        <Icon name="vpn_key" className="text-amber-500" /> Buka Laci Kasir (Mulai Shift)
                    </h3>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1.5">
                                Uang Modal Awal (Starting Cash)
                            </label>
                            <div className="relative">
                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-sm">Rp</span>
                                <input 
                                    type="number"
                                    required
                                    value={startingCash}
                                    onChange={(e) => setStartingCash(e.target.value)}
                                    placeholder="Masukkan uang modal awal"
                                    className="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl outline-none bg-transparent text-gray-800 dark:text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                            </div>
                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">Uang fisik pecahan kecil yang disiapkan di dalam laci kasir untuk kembalian.</p>
                        </div>
                    </div>
                    <div className="flex gap-2 justify-end mt-6">
                        <button 
                            type="button" 
                            onClick={() => setOpenShiftModal(false)}
                            className="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-850 rounded-lg"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            className="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg shadow-sm"
                        >
                            Buka Shift Kerja
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Tutup Laci */}
            <Modal show={closeShiftModal} onClose={() => setCloseShiftModal(false)} maxWidth="md">
                <form onSubmit={handleCloseShift} className="p-6">
                    <h3 className="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-4">
                        <Icon name="lock" className="text-rose-500" /> Akhiri Shift (Tutup Laci Kasir)
                    </h3>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1.5">
                                Uang Tunai Fisik Aktual (Actual Cash)
                            </label>
                            <div className="relative">
                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-sm">Rp</span>
                                <input 
                                    type="number"
                                    required
                                    value={actualCash}
                                    onChange={(e) => setActualCash(e.target.value)}
                                    placeholder="Masukkan total uang fisik di laci"
                                    className="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl outline-none bg-transparent text-gray-800 dark:text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                            </div>
                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Hitung seluruh uang kertas + koin fisik di laci meja kasir lalu input di sini.
                            </p>
                        </div>
                        <div>
                            <label className="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1.5">
                                Catatan Tutup Shift
                            </label>
                            <textarea 
                                value={closeNote}
                                onChange={(e) => setCloseNote(e.target.value)}
                                placeholder="Tulis catatan jika ada selisih atau kendala..."
                                className="w-full p-3 border border-gray-200 dark:border-gray-800 rounded-xl outline-none bg-transparent text-gray-800 dark:text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 h-20 text-sm"
                            />
                        </div>
                    </div>
                    <div className="flex gap-2 justify-end mt-6">
                        <button 
                            type="button" 
                            onClick={() => setCloseShiftModal(false)}
                            className="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-850 rounded-lg"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            className="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg shadow-sm"
                        >
                            Konfirmasi Tutup Shift
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Detail Sesi Shift */}
            <Modal show={detailModal} onClose={() => setDetailModal(false)} maxWidth="xl">
                <div>
                    <div className="flex items-center justify-between border-b pb-4 mb-4">
                        <h3 className="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                            <Icon name="assessment" className="text-amber-500" /> Detail Shift Kasir
                        </h3>
                    </div>

                    {detailLoading ? (
                        <div className="py-12 text-center text-gray-400">
                            <Icon name="cached" className="animate-spin text-3xl mb-2" />
                            <p className="text-sm">Memuat rincian shift...</p>
                        </div>
                    ) : selectedShift && detailData ? (
                        <div className="space-y-6">
                            {/* Summary Grid */}
                            <div className="grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4 dark:bg-white/[0.02] text-sm">
                                <div>
                                    <p className="text-gray-400">Kasir/Operator</p>
                                    <p className="font-bold text-gray-800 dark:text-white">{selectedShift.user?.name ?? 'Sistem'}</p>
                                </div>
                                <div>
                                    <p className="text-gray-400">Status Shift</p>
                                    <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full mt-0.5 ${
                                        selectedShift.status === 'closed' 
                                            ? 'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-400' 
                                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30'
                                    }`}>
                                        {selectedShift.status === 'closed' ? 'Selesai' : 'Aktif'}
                                    </span>
                                </div>
                                <div>
                                    <p className="text-gray-400 font-semibold">Buka Shift</p>
                                    <p className="text-xs text-gray-600 dark:text-gray-300">{formatDateTime(selectedShift.opened_at)}</p>
                                </div>
                                <div>
                                    <p className="text-gray-400 font-semibold">Tutup Shift</p>
                                    <p className="text-xs text-gray-600 dark:text-gray-300">
                                        {selectedShift.status === 'closed' ? formatDateTime(selectedShift.closed_at) : '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-gray-400 font-semibold">Modal Awal</p>
                                    <p className="font-semibold text-gray-700 dark:text-amber-400">{formatRupiah(selectedShift.starting_cash)}</p>
                                </div>
                                <div>
                                    <p className="text-gray-400 font-semibold">Selisih Uang Fisik</p>
                                    <p className={`font-bold ${
                                        selectedShift.discrepancy === 0 
                                            ? 'text-emerald-500' 
                                            : (selectedShift.discrepancy > 0 ? 'text-blue-500' : 'text-rose-500')
                                    }`}>
                                        {selectedShift.status === 'closed' ? (selectedShift.discrepancy === 0 ? 'Pas / Cocok' : formatRupiah(selectedShift.discrepancy)) : '-'}
                                    </p>
                                </div>
                            </div>

                            {/* Tabs */}
                            <div className="flex border-b border-gray-150 dark:border-gray-800">
                                <button 
                                    onClick={() => setActiveTab('payments')}
                                    className={`px-4 py-2 text-sm font-semibold border-b-2 -mb-[2px] transition ${
                                        activeTab === 'payments' 
                                            ? 'border-amber-500 text-amber-500' 
                                            : 'border-transparent text-gray-400 hover:text-gray-600'
                                    }`}
                                >
                                    Metode Pembayaran
                                </button>
                                <button 
                                    onClick={() => setActiveTab('transactions')}
                                    className={`px-4 py-2 text-sm font-semibold border-b-2 -mb-[2px] transition ${
                                        activeTab === 'transactions' 
                                            ? 'border-amber-500 text-amber-500' 
                                            : 'border-transparent text-gray-400 hover:text-gray-600'
                                    }`}
                                >
                                    Daftar Transaksi ({detailData.transactions?.length ?? 0})
                                </button>
                                <button 
                                    onClick={() => setActiveTab('items')}
                                    className={`px-4 py-2 text-sm font-semibold border-b-2 -mb-[2px] transition ${
                                        activeTab === 'items' 
                                            ? 'border-amber-500 text-amber-500' 
                                            : 'border-transparent text-gray-400 hover:text-gray-600'
                                    }`}
                                >
                                    Produk Terjual
                                </button>
                            </div>

                            {/* Tab Contents */}
                            <div>
                                {activeTab === 'payments' && (
                                    <div className="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden text-sm">
                                        <table className="w-full text-left">
                                            <thead className="bg-gray-50 dark:bg-white/[0.02]">
                                                <tr>
                                                    <th className="p-3">Metode Pembayaran</th>
                                                    <th className="p-3 text-right">Total Nominal</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y dark:divide-gray-800">
                                                <tr>
                                                    <td className="p-3 font-medium">Uang Tunai (Cash)</td>
                                                    <td className="p-3 text-right text-emerald-600 dark:text-emerald-400 font-bold">
                                                        {formatRupiah(detailData.payment_summary.tunai)}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="p-3 font-medium">Transfer Bank</td>
                                                    <td className="p-3 text-right font-medium">
                                                        {formatRupiah(detailData.payment_summary.transfer ?? 0)}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="p-3 font-medium">EDC / Kartu Debit</td>
                                                    <td className="p-3 text-right font-medium">
                                                        {formatRupiah(detailData.payment_summary.kartu_debit)}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="p-3 font-medium">Kartu Kredit</td>
                                                    <td className="p-3 text-right font-medium">
                                                        {formatRupiah(detailData.payment_summary.kartu_kredit)}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="p-3 font-medium">Kredit (Bon Pelanggan)</td>
                                                    <td className="p-3 text-right text-rose-500 font-medium">
                                                        {formatRupiah(detailData.payment_summary.kredit)}
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr className="bg-gray-50 dark:bg-white/[0.02] font-bold border-t">
                                                    <td className="p-3">Total Transaksi Omset</td>
                                                    <td className="p-3 text-right">
                                                        {formatRupiah(
                                                            parseFloat(detailData.payment_summary.tunai) +
                                                            parseFloat(detailData.payment_summary.transfer ?? 0) +
                                                            parseFloat(detailData.payment_summary.kartu_debit) +
                                                            parseFloat(detailData.payment_summary.kartu_kredit) +
                                                            parseFloat(detailData.payment_summary.kredit)
                                                        )}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                )}

                                {activeTab === 'transactions' && (
                                    <div className="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden text-sm">
                                        <div className="max-h-60 overflow-y-auto custom-scrollbar">
                                            <table className="w-full text-left">
                                                <thead className="bg-gray-50 dark:bg-white/[0.02] sticky top-0">
                                                    <tr>
                                                        <th className="p-3">No. Transaksi</th>
                                                        <th className="p-3">Waktu</th>
                                                        <th className="p-3">Pelanggan</th>
                                                        <th className="p-3 text-right">Total Belanja</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y dark:divide-gray-800">
                                                    {detailData.transactions?.length === 0 ? (
                                                        <tr>
                                                            <td colSpan="4" className="p-6 text-center text-gray-400">Tidak ada transaksi dalam shift ini.</td>
                                                        </tr>
                                                    ) : (
                                                        detailData.transactions.map((tx) => (
                                                            <tr key={tx.id}>
                                                                <td className="p-3 font-semibold">{tx.transaction_number}</td>
                                                                <td className="p-3 text-xs text-gray-500">{new Date(tx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</td>
                                                                <td className="p-3">{tx.customer_name}</td>
                                                                <td className="p-3 text-right font-bold">{formatRupiah(tx.grand_total)}</td>
                                                            </tr>
                                                        ))
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                {activeTab === 'items' && (
                                    <div className="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden text-sm">
                                        <div className="max-h-60 overflow-y-auto custom-scrollbar">
                                            <table className="w-full text-left">
                                                <thead className="bg-gray-50 dark:bg-white/[0.02] sticky top-0">
                                                    <tr>
                                                        <th className="p-3">Nama Produk</th>
                                                        <th className="p-3 text-center">Jumlah</th>
                                                        <th className="p-3 text-right">Harga Satuan</th>
                                                        <th className="p-3 text-right">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y dark:divide-gray-800">
                                                    {detailData.sold_items?.length === 0 ? (
                                                        <tr>
                                                            <td colSpan="4" className="p-6 text-center text-gray-400">Belum ada item terjual.</td>
                                                        </tr>
                                                    ) : (
                                                        detailData.sold_items.map((item, idx) => (
                                                            <tr key={idx}>
                                                                <td className="p-3 font-medium text-gray-700 dark:text-white/80">{item.product_name}</td>
                                                                <td className="p-3 text-center font-bold text-amber-600">{parseInt(item.total_qty)}</td>
                                                                <td className="p-3 text-right">{formatRupiah(item.price)}</td>
                                                                <td className="p-3 text-right font-bold">{formatRupiah(item.total_amount)}</td>
                                                            </tr>
                                                        ))
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Note / Catatan Selesai */}
                            {selectedShift.note && (
                                <div className="rounded-xl border border-gray-150 p-4 dark:border-gray-800 text-sm">
                                    <p className="font-semibold text-gray-500 mb-1">Catatan Shift:</p>
                                    <p className="text-gray-700 dark:text-gray-300 italic">"{selectedShift.note}"</p>
                                </div>
                            )}
                        </div>
                    ) : null}

                    <div className="flex justify-end border-t pt-4 mt-6">
                        <button 
                            onClick={() => setDetailModal(false)}
                            className="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-lg text-sm"
                        >
                            Tutup Detail
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Export PDF Modal */}
            <Modal show={exportModal} onClose={() => setExportModal(false)} maxWidth="md">
                <div className="p-6">
                    <h3 className="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2 mb-5">
                        <Icon name="picture_as_pdf" className="text-amber-500" /> Export Laporan PDF
                    </h3>

                    {/* Pilihan Tipe Export */}
                    <div className="mb-5">
                        <label className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-2">Jenis Laporan</label>
                        <div className="grid grid-cols-2 gap-2">
                            {EXPORT_TYPES.map(t => (
                                <button
                                    key={t.id}
                                    type="button"
                                    onClick={() => setExportType(t.id)}
                                    className={`flex items-center gap-2 px-4 py-3 rounded-xl border text-sm font-semibold transition-all ${
                                        exportType === t.id
                                            ? 'bg-amber-500 border-amber-500 text-white shadow-md shadow-amber-500/20'
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-amber-400'
                                    }`}
                                >
                                    <Icon name={t.icon} className="text-[18px]" /> {t.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Per Shift: pilih dari daftar shift */}
                    {exportType === 'shift' && (
                        <div className="mb-5">
                            <label className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-2">Pilih Shift</label>
                            <select
                                value={exportShiftId}
                                onChange={e => setExportShiftId(e.target.value)}
                                className="w-full rounded-lg border-gray-200 py-2.5 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">-- Pilih Shift --</option>
                                {rows.data?.filter(r => r.status === 'closed').map((r, i) => (
                                    <option key={r.id} value={r.id}>
                                        {r.user?.name ?? 'Kasir'} — {formatDateTime(r.opened_at)} s/d {formatDateTime(r.closed_at)}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {/* Harian & Mingguan: pilih rentang tanggal */}
                    {(exportType === 'harian' || exportType === 'mingguan') && (
                        <div className="mb-5 space-y-3">
                            <label className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block">Rentang Tanggal</label>
                            <div className="flex items-center gap-2">
                                <input
                                    type="date"
                                    value={exportDateFrom}
                                    onChange={e => {
                                        const val = e.target.value;
                                        setExportDateFrom(val);
                                        if (exportType === 'mingguan' && val) {
                                            const from = new Date(val);
                                            from.setDate(from.getDate() + 6);
                                            setExportDateTo(from.toISOString().split('T')[0]);
                                        }
                                    }}
                                    onClick={e => e.target.showPicker?.()}
                                    className="flex-1 rounded-lg border-gray-200 py-2 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 cursor-pointer"
                                />
                                <span className="text-gray-400 text-sm">s/d</span>
                                <input
                                    type="date"
                                    value={exportDateTo}
                                    onChange={e => setExportDateTo(e.target.value)}
                                    onClick={e => e.target.showPicker?.()}
                                    className="flex-1 rounded-lg border-gray-200 py-2 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 cursor-pointer"
                                />
                            </div>
                        </div>
                    )}

                    {/* Bulanan: pilih bulan */}
                    {exportType === 'bulanan' && (
                        <div className="mb-5">
                            <label className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-2">Pilih Bulan</label>
                            <input
                                type="month"
                                value={exportMonth}
                                onChange={e => setExportMonth(e.target.value)}
                                className="w-full rounded-lg border-gray-200 py-2.5 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 cursor-pointer"
                            />
                        </div>
                    )}

                    <div className="flex gap-2 justify-end">
                        <button
                            type="button"
                            onClick={() => setExportModal(false)}
                            className="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            onClick={handleExport}
                            className="flex items-center gap-1.5 px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg shadow-sm transition"
                        >
                            <Icon name="picture_as_pdf" /> Download PDF
                        </button>
                    </div>
                </div>
            </Modal>
        </HendhysLayout>

    );
}
