import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';

// Ziggy's global route() helper (injected by the @routes Blade directive).
const route = window.route;

const MASTER_LINKS = [
    { route: 'master.suppliers.index', label: 'Supplier', icon: 'local_shipping' },
    { route: 'master.products.index', label: 'Produk', icon: 'inventory_2' },
    { route: 'master.branches.index', label: 'Cabang', icon: 'store' },
];

const INVENTORY_LINKS = [
    { route: 'gudang.po.index', label: 'Purchase Order', icon: 'shopping_cart_checkout' },
    { route: 'gudang.receiving.index', label: 'Penerimaan Barang', icon: 'input' },
    { route: 'gudang.stock.index', label: 'Stok Gudang', icon: 'inventory' },
    { route: 'gudang.transfer-requests.index', label: 'Transfer Request', icon: 'move_to_inbox', badge: 'gudang_pending' },
    { route: 'gudang.transfer-out.index', label: 'Transfer Keluar', icon: 'output' },
    { route: 'gudang.returns.index', label: 'Penerimaan Retur', icon: 'keyboard_return' },
];

/** Sidebar link that highlights itself when its route family is active. */
function NavLink({ item, badgeValue }) {
    const base = item.route.replace('.index', '');
    const active = route().current(base + '*') || route().current(item.route);

    return (
        <Link
            href={route(item.route)}
            className={`flex items-center justify-between rounded-lg px-4 py-3 transition-colors ${
                active ? 'bg-indigo-600 font-medium text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
            }`}
        >
            <span className="flex items-center gap-3">
                <Icon name={item.icon} className="text-[22px]" />
                <span className="text-sm">{item.label}</span>
            </span>
            {badgeValue > 0 && (
                <span className="rounded-full bg-rose-500 px-2 py-0.5 text-center text-[10px] font-bold text-white">
                    {badgeValue}
                </span>
            )}
        </Link>
    );
}

export default function GudangLayout({ title, pageTitle, children }) {
    const { auth, notifications } = usePage().props;
    const gudangPending = notifications?.gudang_pending ?? 0;

    const [sidebarOpen, setSidebarOpen] = useState(typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);
    const [isMobile, setIsMobile] = useState(typeof window !== 'undefined' ? window.innerWidth < 1024 : false);
    const [masterOpen, setMasterOpen] = useState(route().current('master.*'));

    useEffect(() => {
        const onResize = () => {
            const mobile = window.innerWidth < 1024;
            setIsMobile(mobile);
            if (!mobile) setSidebarOpen(true);
        };
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    const now = new Date();
    const weekday = now.toLocaleDateString('id-ID', { weekday: 'long' });
    const fullDate = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    const heading = pageTitle || title || 'Dashboard';

    return (
        <div className="flex h-screen w-full overflow-hidden bg-slate-50 font-sans antialiased">
            {sidebarOpen && isMobile && (
                <div
                    className="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* SIDEBAR */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-800 bg-[#0f172a] text-white shadow-2xl transition-all duration-300 ease-in-out lg:static lg:shrink-0 ${
                    sidebarOpen ? 'translate-x-0 lg:ml-0' : '-translate-x-full lg:-ml-72'
                }`}
            >
                <div className="flex shrink-0 items-center gap-4 px-8 py-8">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600">
                        <span className="text-xl font-bold tracking-tight text-white">GT</span>
                    </div>
                    <div>
                        <h1 className="text-xl font-bold leading-none tracking-tight text-white">
                            Gudang<span className="text-indigo-400">Tempua</span>
                        </h1>
                        <p className="mt-1 text-xs text-slate-400">Management System</p>
                    </div>
                </div>

                <nav className="custom-scrollbar flex-1 space-y-1.5 overflow-y-auto px-4 py-4">
                    <Link
                        href={route('gudang.dashboard')}
                        className={`flex items-center gap-3 rounded-lg px-4 py-3 transition-colors ${
                            route().current('gudang.dashboard') ? 'bg-indigo-600 font-medium text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                        }`}
                    >
                        <Icon name="dashboard" className="text-[22px]" />
                        <span className="text-sm">Dashboard</span>
                    </Link>

                    {/* Master Data */}
                    <div className="space-y-1 pt-4">
                        <p className="mb-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Core Data</p>
                        <button
                            type="button"
                            onClick={() => setMasterOpen((o) => !o)}
                            className="flex w-full items-center justify-between rounded-lg px-4 py-3 text-slate-400 transition-colors hover:bg-slate-800 hover:text-white focus:outline-none"
                        >
                            <span className="flex items-center gap-3">
                                <Icon name="database" className="text-[22px]" />
                                <span className="text-sm font-medium">Master Data</span>
                            </span>
                            <Icon name="expand_more" className={`text-[20px] transition-transform ${masterOpen ? 'rotate-180' : ''}`} />
                        </button>
                        {masterOpen && (
                            <div className="mt-1 space-y-1">
                                {MASTER_LINKS.map((item) => {
                                    const active = route().current(item.route.replace('.index', '') + '*');
                                    return (
                                        <Link
                                            key={item.route}
                                            href={route(item.route)}
                                            className={`flex items-center gap-3 rounded-lg py-2.5 pl-12 pr-4 text-sm transition-colors ${
                                                active ? 'font-medium text-indigo-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                            }`}
                                        >
                                            <Icon name={item.icon} className="text-[18px]" />
                                            {item.label}
                                        </Link>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Inventory */}
                    <div className="space-y-1.5 pt-6">
                        <p className="mb-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Inventory</p>
                        {INVENTORY_LINKS.map((item) => (
                            <NavLink key={item.route} item={item} badgeValue={item.badge === 'gudang_pending' ? gudangPending : 0} />
                        ))}
                    </div>

                    {/* Access */}
                    <div className="space-y-1.5 pt-6">
                        <p className="mb-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Access</p>
                        <NavLink item={{ route: 'master.users.index', label: 'Manajemen User', icon: 'manage_accounts' }} badgeValue={0} />
                    </div>
                </nav>

                <div className="shrink-0 border-t border-slate-800 bg-slate-900/30 p-6">
                    <div className="flex items-center gap-4">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-indigo-500/20 bg-indigo-500/10 text-sm font-black text-indigo-400">
                            {(auth?.user?.name ?? '?').charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-xs font-black uppercase tracking-tight text-white">{auth?.user?.name}</p>
                            <p className="mt-0.5 truncate text-[10px] font-bold uppercase tracking-widest text-slate-500">Administrator</p>
                        </div>
                    </div>
                </div>
            </aside>

            {/* MAIN */}
            <main className="relative z-10 flex h-full min-w-0 flex-1 flex-col overflow-hidden bg-slate-50">
                <header className="z-20 flex h-20 shrink-0 items-center justify-between border-b border-slate-200 bg-white/70 px-8 backdrop-blur-xl print:hidden">
                    <div className="flex items-center gap-6">
                        <button
                            onClick={() => setSidebarOpen((o) => !o)}
                            className="flex h-11 w-11 items-center justify-center rounded-2xl text-slate-400 shadow-sm ring-1 ring-slate-200 transition-all hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none"
                        >
                            <Icon name={sidebarOpen ? 'menu_open' : 'menu'} />
                        </button>
                        <div className="h-10 w-px bg-slate-200" />
                        <h1 className="max-w-[200px] truncate font-headline text-xl font-black tracking-tight text-slate-900 sm:max-w-none">{heading}</h1>
                    </div>

                    <div className="flex items-center gap-5">
                        <div className="hidden flex-col text-right md:flex">
                            <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{weekday}</span>
                            <span className="text-xs font-bold text-slate-800">{fullDate}</span>
                        </div>
                        <div className="hidden h-10 w-px bg-slate-200 md:block" />
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="group flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-rose-600"
                            title="Logout"
                        >
                            <span className="mr-2 hidden sm:inline">Keluar</span>
                            <Icon name="logout" className="text-[18px] transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>
                </header>

                <FlashToasts />

                <div className="custom-scrollbar flex-1 overflow-auto p-8">{children}</div>
            </main>
        </div>
    );
}
