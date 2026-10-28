import { Head, Link, usePage } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { Navigation, Search, Paging, money, status, statusBadgeClass } from './Common';

const route = window.route;

export default function Sales(props) {
    const isKasir = usePage().props.auth?.user?.roles?.includes('kasir_izzi');

    return (
        <IzziLayout pageTitle="Riwayat Transaksi Kasir Izzi">
            <Head title="Riwayat Transaksi" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Riwayat Transaksi — {props.branch?.name}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Daftar seluruh transaksi penjualan dan pembatalan di kasir Izzi Kebab
                        </p>
                    </div>
                    {isKasir && (
                        <Link
                            href={route('izzi.pos')}
                            className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[18px]" />
                            <span>Kembali ke Kasir</span>
                        </Link>
                    )}
                </div>

                <Navigation {...props} />

                {/* Summary Stat Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Total Transaksi</span>
                        <strong className="mt-1 block text-2xl font-bold text-gray-800 dark:text-white/90">
                            {props.summary.transactions} <span className="text-sm font-normal text-gray-500">struk</span>
                        </strong>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <span className="text-xs font-medium text-gray-500 dark:text-gray-400">Penjualan Kotor</span>
                        <strong className="mt-1 block text-xl font-bold text-gray-800 dark:text-white/90">
                            {money(props.summary.original_total)}
                        </strong>
                    </div>
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-theme-xs dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <span className="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            Penjualan Bersih (Setelah Batal)
                        </span>
                        <strong className="mt-1 block text-xl font-extrabold text-emerald-700 dark:text-emerald-300">
                            {money(props.summary.net_total)}
                        </strong>
                    </div>
                </div>

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            filters={props.filters}
                            path="izzi.sales"
                            branch={props.branch}
                            dates
                            placeholder="Cari No. Struk, pelanggan, atau kasir…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">No. Struk</th>
                                    <th className="px-5 py-3.5">Waktu</th>
                                    <th className="px-5 py-3.5">Pelanggan</th>
                                    <th className="px-5 py-3.5">Kasir</th>
                                    <th className="px-5 py-3.5 text-center">Status</th>
                                    <th className="px-5 py-3.5 text-right">Penjualan Bersih</th>
                                    <th className="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {props.sales.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={7}
                                        icon="receipt_long"
                                        message="Belum ada data transaksi penjualan."
                                    />
                                ) : (
                                    props.sales.data.map((s) => (
                                        <tr
                                            key={s.id}
                                            className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="whitespace-nowrap px-5 py-3.5 font-mono font-bold text-gray-800 dark:text-white">
                                                {s.number}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-xs text-gray-500">
                                                {s.time}
                                            </td>
                                            <td className="px-5 py-3.5 font-medium text-gray-700 dark:text-gray-300">
                                                {s.customer_name || 'Pelanggan Umum'}
                                            </td>
                                            <td className="px-5 py-3.5 text-xs text-gray-500">
                                                {s.actor_name}
                                            </td>
                                            <td className="px-5 py-3.5 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                        statusBadgeClass[s.status] || statusBadgeClass.completed
                                                    }`}
                                                >
                                                    {status[s.status]}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-right font-bold text-gray-900 tabular-nums dark:text-white">
                                                {money(s.net_total)}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-3.5 text-right">
                                                <Link
                                                    href={route('izzi.sales.show', {
                                                        sale: s.id,
                                                        branch_id: props.branch.id,
                                                    })}
                                                    className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                >
                                                    <Icon name="visibility" className="text-[15px]" />
                                                    <span>Detail</span>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={props.sales} />
                </div>
            </div>
        </IzziLayout>
    );
}
