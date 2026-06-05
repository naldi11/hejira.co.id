import { Head, Link, usePage } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';

const route = window.route;

const STAT_CARDS = [
    { key: 'total_produk', label: 'Jenis Produk', tag: 'Total Assets', icon: 'inventory_2', color: 'indigo' },
    { key: 'pending_po', label: 'Pesanan Aktif', tag: 'Pending PO', icon: 'shopping_cart_checkout', color: 'emerald' },
    { key: 'pending_request', label: 'Permintaan Stok', tag: 'Needs Approval', icon: 'move_to_inbox', color: 'amber' },
    { key: 'total_cabang', label: 'Jaringan Cabang', tag: 'Connected', icon: 'storefront', color: 'rose' },
];

const QUICK_ACTIONS = [
    { label: 'Stok Opname', icon: 'inventory', route: 'gudang.stock.index', desc: 'Lakukan penyesuaian & cek stok fisik' },
    { label: 'Buat PO', icon: 'add_shopping_cart', route: 'gudang.po.create', desc: 'Pesan stok barang baru ke supplier' },
    { label: 'Terima Barang', icon: 'input', route: 'gudang.receiving.create', desc: 'Verifikasi & catat kiriman supplier' },
    { label: 'Kirim Barang', icon: 'output', route: 'gudang.transfer-out.index', desc: 'Kirim mutasi stok barang ke cabang' },
];

const COLOR_MAP = {
    indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
    emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
};

function StatCard({ card, value }) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-theme-xs">
            <div className="flex items-center justify-between">
                <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${COLOR_MAP[card.color]}`}>
                    <Icon name={card.icon} className="text-[24px]" />
                </div>
                <div className="rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">
                    {card.tag}
                </div>
            </div>
            <div className="mt-5">
                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {card.label}
                </span>
                <h4 className="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">
                    {formatQty(value)}
                </h4>
            </div>
        </div>
    );
}

export default function Dashboard({ stats }) {
    return (
        <GudangLayout title="Dashboard" pageTitle="Overview Gudang">
            <Head title="Dashboard" />

            <div className="space-y-6">

                {/* Metrics Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
                    {STAT_CARDS.map((card) => (
                        <StatCard key={card.key} card={card} value={stats[card.key]} />
                    ))}
                </div>

                {/* Quick Actions Section */}
                <div className="space-y-4">
                    <h3 className="text-lg font-bold text-gray-800 dark:text-white/90">Aksi Cepat</h3>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
                        {QUICK_ACTIONS.map((act) => (
                            <Link
                                key={act.label}
                                href={route(act.route)}
                                className="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-lg dark:border-gray-800 dark:bg-white/[0.03] dark:hover:bg-white/[0.05] group"
                            >
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-500 transition-colors group-hover:bg-brand-500 group-hover:text-white dark:bg-gray-800 dark:text-gray-400">
                                    <Icon name={act.icon} className="text-[22px]" />
                                </div>
                                <div className="flex-1">
                                    <h4 className="text-sm font-semibold text-gray-800 dark:text-white/90 group-hover:text-brand-500 dark:group-hover:text-brand-400">
                                        {act.label}
                                    </h4>
                                    <p className="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                        {act.desc}
                                    </p>
                                </div>
                                <Icon name="arrow_forward" className="text-gray-300 opacity-0 transition-all -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-brand-500 dark:group-hover:text-brand-400" />
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </GudangLayout>
    );
}

