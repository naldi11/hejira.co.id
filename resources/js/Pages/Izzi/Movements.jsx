import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { Navigation, Search, Paging } from './Common';

const route = window.route;

const labels = {
    receipt: 'Penerimaan Bahan',
    transfer_gudang: 'Penerimaan Pengiriman Gudang',
    sale_consumption: 'Penjualan POS (Resep)',
    cancellation_restore: 'Batal Pesanan (Kembali)',
    cancellation_waste: 'Batal Pesanan (Terbuang)',
    adjustment: 'Penyesuaian Opname',
};

export default function Movements(props) {
    const params = props.branch ? { branch_id: props.branch.id } : {};

    return (
        <IzziLayout pageTitle="Kartu Stok Bahan Izzi">
            <Head title="Kartu Stok Bahan" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Kartu Stok (Keluar Masuk Bahan) — {props.branch?.name}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Riwayat penambahan dan pemotongan stok bahan baku di cabang
                        </p>
                    </div>
                    <Link
                        href={route('izzi.stock', params)}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <Icon name="arrow_back" className="text-[18px]" />
                        <span>Lihat Saldo Stok</span>
                    </Link>
                </div>

                <Navigation {...props} />

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            filters={props.filters}
                            path="izzi.movements"
                            branch={props.branch}
                            placeholder="Cari nama bahan, kode, petugas, atau catatan…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">Waktu & Petugas</th>
                                    <th className="px-5 py-3.5">Bahan Baku</th>
                                    <th className="px-5 py-3.5">Sumber Pergerakan</th>
                                    <th className="px-5 py-3.5 text-right">Perubahan</th>
                                    <th className="px-5 py-3.5 text-right">Saldo (Sebelum → Sesudah)</th>
                                    <th className="px-5 py-3.5">Catatan / Tautan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {props.movements.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={6}
                                        icon="swap_vert"
                                        message="Belum ada riwayat pergerakan stok bahan."
                                    />
                                ) : (
                                    props.movements.data.map((m) => {
                                        const isOut = m.type === 'out';
                                        const isSame = m.type === 'same';
                                        return (
                                            <tr
                                                key={m.id}
                                                className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                            >
                                                <td className="whitespace-nowrap px-5 py-3.5">
                                                    <p className="text-xs font-semibold text-gray-800 dark:text-white">
                                                        {m.time}
                                                    </p>
                                                    <p className="text-xs text-gray-400">
                                                        {m.actor_name || 'Petugas SO'}
                                                    </p>
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <p className="font-semibold text-gray-800 dark:text-white">
                                                        {m.name}
                                                    </p>
                                                    <p className="font-mono text-xs text-gray-400">{m.code}</p>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5">
                                                    <span className="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                        {m.branch_transfer_id
                                                            ? 'Mutasi Antar Cabang'
                                                            : labels[m.source] || m.source}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-right font-bold tabular-nums">
                                                    <span
                                                        className={
                                                            isOut
                                                                ? 'text-rose-600 dark:text-rose-400'
                                                                : isSame
                                                                  ? 'text-gray-500'
                                                                  : 'text-emerald-600 dark:text-emerald-400'
                                                        }
                                                    >
                                                        {isOut ? '−' : isSame ? '' : '+'}
                                                        {m.quantity} {m.unit}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-right text-xs text-gray-500 tabular-nums">
                                                    {m.before} → <strong className="text-gray-800 dark:text-white">{m.after} {m.unit}</strong>
                                                </td>
                                                <td className="max-w-xs px-5 py-3.5 text-xs text-gray-500">
                                                    {m.notes && <p className="truncate">{m.notes}</p>}
                                                    <div className="mt-0.5 flex flex-wrap gap-3 font-semibold">
                                                        {m.branch_transfer_id && (
                                                            <Link
                                                                href={route('branch-transfers.show', m.branch_transfer_id)}
                                                                className="text-brand-600 hover:underline dark:text-brand-400"
                                                            >
                                                                Detail Mutasi →
                                                            </Link>
                                                        )}
                                                        {m.sale_id && (
                                                            <Link
                                                                href={route('izzi.sales.show', {
                                                                    sale: m.sale_id,
                                                                    branch_id: props.branch.id,
                                                                })}
                                                                className="text-brand-600 hover:underline dark:text-brand-400"
                                                            >
                                                                Detail Struk →
                                                            </Link>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={props.movements} />
                </div>
            </div>
        </IzziLayout>
    );
}
