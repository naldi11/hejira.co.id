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
    { label: 'Stok Opname', icon: 'inventory', route: 'gudang.stock.index' },
    { label: 'Buat PO', icon: 'add_shopping_cart', route: 'gudang.po.create' },
    { label: 'Terima Barang', icon: 'input', route: 'gudang.receiving.create' },
    { label: 'Kirim Barang', icon: 'output', route: 'gudang.transfer-out.index' },
];

const ICON_BG = {
    indigo: 'bg-indigo-50 text-indigo-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
};

function StatCard({ card, value }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-start justify-between">
                <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${ICON_BG[card.color]}`}>
                    <Icon name={card.icon} className="text-[24px]" />
                </div>
                <div className="rounded bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{card.tag}</div>
            </div>
            <div className="mt-4">
                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{card.label}</p>
                <h3 className="mt-1 text-2xl font-bold text-slate-900">{formatQty(value)}</h3>
            </div>
        </div>
    );
}

export default function Dashboard({ stats }) {
    const { auth } = usePage().props;
    const serverTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    return (
        <GudangLayout title="Dashboard" pageTitle="Overview Gudang">
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900">Selamat Datang, {auth?.user?.name}!</h2>
                        <p className="mt-1 text-sm text-slate-500">HEJIRA — Sistem Manajemen Inventori Terpadu</p>
                    </div>
                    <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div className="text-right">
                            <p className="text-xs font-semibold uppercase text-slate-500">Server Time</p>
                            <p className="text-sm font-medium text-slate-900">{serverTime} <span className="text-xs text-slate-500">WIB</span></p>
                        </div>
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <Icon name="schedule" className="text-[20px]" />
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {STAT_CARDS.map((card) => (
                        <StatCard key={card.key} card={card} value={stats[card.key]} />
                    ))}
                </div>

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-slate-900">Quick Actions</h3>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        {QUICK_ACTIONS.map((act) => (
                            <Link
                                key={act.label}
                                href={route(act.route)}
                                className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-colors hover:bg-slate-50"
                            >
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-slate-50 text-slate-600">
                                    <Icon name={act.icon} className="text-[24px]" />
                                </div>
                                <span className="text-center text-sm font-medium text-slate-700">{act.label}</span>
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </GudangLayout>
    );
}
