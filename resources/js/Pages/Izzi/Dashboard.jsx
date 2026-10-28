import { Head, Link, usePage } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import { Navigation, money, status, statusBadgeClass } from './Common';

const route = window.route;

export default function Dashboard(props) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles || [];
    const isCashier = roles.includes('kasir_izzi');
    const params = props.branch ? { branch_id: props.branch.id } : {};

    const kpis = [
        {
            label: 'Omset Bersih Hari Ini',
            value: money(props.stats?.revenue_today),
            icon: 'payments',
            color: 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400',
            money: true,
        },
        {
            label: 'Transaksi Hari Ini',
            value: props.stats?.sales_today ?? 0,
            suffix: 'struk',
            icon: 'receipt_long',
            color: 'bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
        },
        {
            label: 'Menu Aktif',
            value: props.stats?.active_menus ?? 0,
            suffix: 'menu',
            icon: 'restaurant_menu',
            color: 'bg-brand-100 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400',
        },
        {
            label: 'Total Pembatalan',
            value: money(props.stats?.cancelled_total),
            icon: 'cancel',
            color: 'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
            money: true,
        },
    ];

    const quickLinks = [
        ...(isCashier
            ? [
                  {
                      title: 'Kasir POS',
                      desc: 'Catat pesanan & pembayaran pelanggan',
                      href: route('izzi.pos'),
                      icon: 'point_of_sale',
                      color: 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400',
                  },
              ]
            : []),
        {
            title: 'Laci Kasir (Shift)',
            desc: 'Buka/tutup shift & cek uang tunai laci',
            href: route('izzi.shifts', params),
            icon: 'account_balance_wallet',
            color: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        },
        {
            title: 'Stok Bahan Tersedia',
            desc: 'Cek sisa bahan baku resep di cabang',
            href: route('izzi.stock', params),
            icon: 'inventory_2',
            color: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
        },
        ...(props.canManage
            ? [
                  {
                      title: 'Terima Bahan Masuk',
                      desc: 'Catat kedatangan bahan baku baru',
                      href: route('izzi.receipts.create', params),
                      icon: 'local_shipping',
                      color: 'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400',
                  },
                  {
                      title: 'Daftar Menu & Resep',
                      desc: 'Atur harga jual & gramasi bahan per porsi',
                      href: route('izzi.menus'),
                      icon: 'restaurant_menu',
                      color: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                  },
              ]
            : []),
    ];

    return (
        <IzziLayout pageTitle="Dashboard Izzi Kebab">
            <Head title="Dashboard Izzi Kebab" />

            {!props.branch ? (
                <div className="rounded-2xl border border-amber-200 bg-amber-50/70 p-6 dark:border-amber-500/30 dark:bg-amber-500/10">
                    <h2 className="font-bold text-amber-900 dark:text-amber-200">Belum Ada Cabang Izzi Kebab</h2>
                    <p className="mt-1 text-sm text-amber-800 dark:text-amber-300">
                        Tambahkan cabang dengan unit bisnis <strong>Izzi</strong> melalui menu <strong>Data & Pengguna → Cabang</strong>.
                    </p>
                </div>
            ) : (
                <div className="space-y-6">
                    <Navigation {...props} />

                    {/* Stat Cards (Same clean style as Jihan's Dashboard) */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {kpis.map((c) => (
                            <div
                                key={c.label}
                                className="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]"
                            >
                                <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-full ${c.color}`}>
                                    <Icon name={c.icon} className="text-[24px]" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{c.label}</p>
                                    <p className={`truncate font-bold text-gray-800 dark:text-white/90 ${c.money ? 'text-xl' : 'text-2xl'}`}>
                                        {c.value}
                                        {c.suffix && (
                                            <span className="text-sm font-normal text-gray-500 dark:text-gray-400"> {c.suffix}</span>
                                        )}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Two-column layout: Recent Transactions + Quick Access */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        {/* Left: Penjualan Terakhir */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs lg:col-span-2 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-800">
                                <h3 className="font-semibold text-gray-800 dark:text-white/90">Penjualan Terakhir</h3>
                                <Link
                                    href={route('izzi.sales', params)}
                                    className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400"
                                >
                                    Semua Transaksi →
                                </Link>
                            </div>

                            {props.recent.length === 0 ? (
                                <div className="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada transaksi penjualan di cabang ini.
                                </div>
                            ) : (
                                <div className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {props.recent.map((s) => (
                                        <Link
                                            key={s.id}
                                            href={route('izzi.sales.show', { sale: s.id, branch_id: s.branch_id })}
                                            className="flex items-center justify-between p-4 transition hover:bg-gray-50/60 dark:hover:bg-white/[0.02]"
                                        >
                                            <div>
                                                <p className="font-medium text-gray-800 dark:text-white/90">{s.number}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {s.time} • Kasir: {s.actor_name}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="font-bold text-gray-900 dark:text-white/90">{money(s.net_total)}</p>
                                                <span
                                                    className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${
                                                        statusBadgeClass[s.status] || statusBadgeClass.completed
                                                    }`}
                                                >
                                                    {status[s.status]}
                                                </span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Right: Menu Pintas Operasional */}
                        <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-200 p-5 dark:border-gray-800">
                                <h3 className="font-semibold text-gray-800 dark:text-white/90">Menu Pintas</h3>
                            </div>
                            <div className="divide-y divide-gray-100 dark:divide-gray-800">
                                {quickLinks.map((item) => (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="flex items-center justify-between p-4 transition hover:bg-gray-50/60 dark:hover:bg-white/[0.02]"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${item.color}`}>
                                                <Icon name={item.icon} className="text-[20px]" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{item.title}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">{item.desc}</p>
                                            </div>
                                        </div>
                                        <Icon name="chevron_right" className="text-[20px] text-gray-400" />
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </IzziLayout>
    );
}
