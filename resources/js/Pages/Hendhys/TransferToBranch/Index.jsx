import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';

const route = window.route;

export default function TransferToBranchIndex({ transfers, gudangTransfers = [], filters, isPusat }) {
    const [loading, setLoading] = useState(false);
    
    // Get initial tab from query params
    const getTabFromUrl = () => {
        if (typeof window === 'undefined') return 'pusat';
        const params = new URLSearchParams(window.location.search);
        return params.get('tab') === 'gudang' ? 'gudang' : 'pusat';
    };

    const [activeTab, setActiveTab] = useState(getTabFromUrl);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });

    // Sync tab state when URL changes (e.g. clicking sidebar links)
    useEffect(() => {
        setActiveTab(getTabFromUrl());
    }, [window.location.search]);

    const reload = (e) => {
        e?.preventDefault();
        const p = {};
        Object.entries(form).forEach(([k, v]) => {
            if (v) p[k] = v;
        });
        router.get(route('hendhys.transfer-to-branch.index'), p, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false)
        });
    };

    return (
        <HendhysLayout pageTitle="Penerimaan Barang">
            <Head title="Penerimaan Barang" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Penerimaan &amp; Distribusi</h2>
                    {isPusat && (
                        <Link href={route('hendhys.transfer-to-branch.create')} className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700">
                            <Icon name="add" className="text-[20px]" /> Distribusi Manual
                        </Link>
                    )}
                </div>

                {/* Tabs */}
                <div className="flex border-b border-gray-200 dark:border-gray-800">
                    <button
                        onClick={() => setActiveTab('pusat')}
                        className={`px-6 py-3 text-sm font-bold border-b-2 transition ${
                            activeTab === 'pusat'
                                ? 'border-amber-600 text-amber-600'
                                : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'
                        }`}
                    >
                        Dari Pusat (Hendhys Produksi)
                    </button>
                    <button
                        onClick={() => setActiveTab('gudang')}
                        className={`px-6 py-3 text-sm font-bold border-b-2 transition ${
                            activeTab === 'gudang'
                                ? 'border-amber-600 text-amber-600'
                                : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'
                        }`}
                    >
                        Dari Gudang (Gudang Utama)
                    </button>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    {activeTab === 'pusat' && (
                        <div className="border-b border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                            <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                                <div className="relative min-w-[200px] flex-1">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" />
                                    <input
                                        type="text"
                                        value={form.search}
                                        onChange={(e) => setForm({ ...form, search: e.target.value })}
                                        placeholder="Cari no transfer..."
                                        className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                                    />
                                </div>
                                <select
                                    value={form.status}
                                    onChange={(e) => setForm({ ...form, status: e.target.value })}
                                    className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="">Semua</option>
                                    <option value="sent">Dikirim</option>
                                    <option value="received">Diterima</option>
                                </select>
                                <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">
                                    Filter
                                </button>
                            </form>
                        </div>
                    )}

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-6 py-4 font-medium">No. Transfer</th>
                                    <th className="px-6 py-4 font-medium">Tanggal</th>
                                    <th className="px-6 py-4 font-medium">Cabang</th>
                                    <th className="px-6 py-4 text-center font-medium">Status</th>
                                    <th className="px-6 py-4 text-center font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {activeTab === 'pusat' ? (
                                    loading ? (
                                        <SkeletonTableRows rows={6} columns={5} />
                                    ) : transfers.data.length === 0 ? (
                                        <EmptyState colSpan={5} icon="local_shipping" message="Belum ada distribusi dari Produksi." />
                                    ) : (
                                        transfers.data.map((t) => (
                                            <tr key={t.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{t.transfer_number}</td>
                                                <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{t.date}</td>
                                                <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{t.branch ?? '-'}</td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${t.status === 'sent' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'}`}>
                                                        {t.status === 'sent' ? 'Dikirim' : 'Diterima'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    <Link href={route('hendhys.transfer-to-branch.show', t.id)} className="text-sm font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400">
                                                        Detail
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    )
                                ) : (
                                    gudangTransfers.length === 0 ? (
                                        <EmptyState colSpan={5} icon="local_shipping" message="Belum ada penerimaan dari Gudang." />
                                    ) : (
                                        gudangTransfers.map((t) => (
                                            <tr key={t.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{t.transfer_number}</td>
                                                <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{t.date}</td>
                                                <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{t.branch ?? '-'}</td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${t.status === 'sent' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'}`}>
                                                        {t.status === 'sent' ? 'Dikirim' : 'Diterima'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    {t.status === 'sent' ? (
                                                        <Link href={route('hendhys.gudang-transfers.receive-form', t.id)} className="text-sm font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-450">
                                                            Terima Barang
                                                        </Link>
                                                    ) : (
                                                        <Link href={route('hendhys.gudang-transfers.show', t.id)} className="text-sm font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400">
                                                            Detail
                                                        </Link>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )
                                )}
                            </tbody>
                        </table>
                    </div>
                    {activeTab === 'pusat' && transfers.meta?.links && (
                        <div className="border-t border-gray-100 p-4 dark:border-gray-800">
                            <Pagination links={transfers.meta.links} />
                        </div>
                    )}
                </div>
            </div>
        </HendhysLayout>
    );
}
