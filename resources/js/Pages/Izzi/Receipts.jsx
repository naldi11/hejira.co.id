import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { Navigation, Search, Paging } from './Common';

const route = window.route;

export default function Receipts(props) {
    const params = props.branch ? { branch_id: props.branch.id } : {};

    return (
        <IzziLayout pageTitle="Riwayat Penerimaan Bahan">
            <Head title="Riwayat Penerimaan Bahan" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Riwayat Penerimaan Bahan — {props.branch?.name}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Daftar pencatatan bahan baku masuk beserta konversi kemasannya
                        </p>
                    </div>
                    {props.canManage && props.branch?.is_active !== false && (
                        <Link
                            href={route('izzi.receipts.create', params)}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                        >
                            <Icon name="add" className="text-[18px]" />
                            <span>Terima Bahan Masuk</span>
                        </Link>
                    )}
                </div>

                <Navigation {...props} />

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            filters={props.filters}
                            path="izzi.receipts"
                            branch={props.branch}
                            placeholder="Cari nomor penerimaan, pemasok, bahan, atau bukti…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">No. Penerimaan</th>
                                    <th className="px-5 py-3.5">Waktu & Petugas</th>
                                    <th className="px-5 py-3.5">Asal / Pemasok</th>
                                    <th className="px-5 py-3.5">Bukti & Catatan</th>
                                    <th className="px-5 py-3.5">Bahan Diterima</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {props.receipts.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={5}
                                        icon="local_shipping"
                                        message="Belum ada catatan penerimaan bahan di cabang ini."
                                    />
                                ) : (
                                    props.receipts.data.map((r) => (
                                        <tr
                                            key={r.id}
                                            className="align-top hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="whitespace-nowrap px-5 py-4 font-mono font-bold text-gray-800 dark:text-white">
                                                {r.number}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-xs">
                                                <p className="font-semibold text-gray-800 dark:text-white">{r.time}</p>
                                                <p className="text-gray-400">Oleh: {r.actor_name}</p>
                                            </td>
                                            <td className="px-5 py-4 font-medium text-gray-800 dark:text-white">
                                                {r.origin}
                                            </td>
                                            <td className="max-w-xs px-5 py-4 text-xs text-gray-500">
                                                <p className="font-semibold text-gray-700 dark:text-gray-300">
                                                    Bukti: {r.evidence_reference || '—'}
                                                </p>
                                                <p className="mt-0.5">{r.notes}</p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="space-y-1.5">
                                                    {r.items.map((i, n) => (
                                                        <div
                                                            key={n}
                                                            className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-1.5 text-xs dark:bg-gray-800"
                                                        >
                                                            <span className="font-semibold text-gray-800 dark:text-white">
                                                                {i.product_name}{' '}
                                                                <span className="font-mono font-normal text-gray-400">
                                                                    ({i.input_quantity} {i.input_unit})
                                                                </span>
                                                            </span>
                                                            <span className="font-bold text-emerald-600 dark:text-emerald-400">
                                                                +{i.quantity} {i.unit_name}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={props.receipts} />
                </div>
            </div>
        </IzziLayout>
    );
}
