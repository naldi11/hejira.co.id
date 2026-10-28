import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import StockOpnameAccess from '@/Components/StockOpnameAccess';
import { Navigation, Search, Paging } from './Common';

const route = window.route;

export default function Stock(props) {
    const params = props.branch ? { branch_id: props.branch.id } : {};

    return (
        <IzziLayout pageTitle="Stok Bahan Tersedia">
            <Head title="Stok Bahan Tersedia" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Stok Bahan Tersedia — {props.branch?.name}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Saldo bahan baku dalam satuan dasar resep di cabang
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <Link
                            href={route('izzi.movements', params)}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            <Icon name="history" className="text-[18px]" />
                            <span>Kartu Stok</span>
                        </Link>
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
                </div>

                <Navigation {...props} />

                <StockOpnameAccess access={props.opnameAccess} />

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            filters={props.filters}
                            path="izzi.stock"
                            branch={props.branch}
                            placeholder="Cari nama bahan atau kode produk…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">Info Bahan Baku</th>
                                    <th className="px-6 py-4 font-semibold">Satuan Dasar Resep</th>
                                    <th className="px-6 py-4 font-semibold">Konversi Kemasan Beli</th>
                                    <th className="px-6 py-4 text-right font-semibold">Saldo Tersedia</th>
                                    <th className="px-6 py-4 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {props.stocks.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={5}
                                        icon="inventory_2"
                                        message="Tidak ada data stok bahan baku."
                                    />
                                ) : (
                                    props.stocks.data.map((p) => {
                                        const empty = Number(p.quantity) <= 0;
                                        return (
                                            <tr
                                                key={p.id}
                                                className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-brand-100 bg-brand-50 dark:border-brand-500/20 dark:bg-brand-500/10">
                                                            <Icon
                                                                name="inventory_2"
                                                                className="text-[20px] text-brand-500 dark:text-brand-400"
                                                            />
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-gray-800 dark:text-white/90">
                                                                {p.name}
                                                            </p>
                                                            <p className="font-mono text-xs text-gray-400">{p.code}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 font-medium text-gray-600 dark:text-gray-300">
                                                    {p.unit}
                                                </td>
                                                <td className="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                                    1 {p.purchase_unit} = <strong>{p.purchase_factor} {p.unit}</strong>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-right">
                                                    <span
                                                        className={`text-base font-extrabold tabular-nums ${
                                                            empty
                                                                ? 'text-rose-600 dark:text-rose-400'
                                                                : 'text-emerald-600 dark:text-emerald-400'
                                                        }`}
                                                    >
                                                        {p.quantity}
                                                    </span>{' '}
                                                    <span className="text-xs font-medium text-gray-500">{p.unit}</span>
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                            empty
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400'
                                                                : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                        }`}
                                                    >
                                                        {empty ? 'Habis' : 'Tersedia'}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={props.stocks} />
                </div>
            </div>
        </IzziLayout>
    );
}
