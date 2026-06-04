import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';

const route = window.route;

const SECTIONS = [
    {
        title: 'Kasir & Penjualan',
        links: [
            { route: 'hendhys.pos.index', label: 'POS Kasir', icon: 'point_of_sale' },
            { route: 'hendhys.pending.index', label: 'Transaksi Pending', icon: 'schedule' },
            { route: 'hendhys.transactions.index', label: 'Riwayat Transaksi', icon: 'receipt_long' },
            { route: 'hendhys.reports.index', label: 'Laporan', icon: 'assessment', match: 'hendhys.reports.*' },
        ],
    },
    {
        title: 'Produksi',
        links: [
            { route: 'hendhys.productions.index', label: 'Produksi Hendhys', icon: 'factory', match: 'hendhys.productions.*' },
        ],
    },
    {
        title: 'Inventory',
        links: [
            { route: 'hendhys.stock.index', label: 'Stok Tersedia', icon: 'inventory', match: 'hendhys.stock.*' },
            { route: 'hendhys.transfer-requests.index', label: 'Request ke Gudang', icon: 'sync_alt', match: 'hendhys.transfer-requests.*' },
            { route: 'hendhys.returns-to-gudang.index', label: 'Return ke Gudang', icon: 'assignment_return', match: 'hendhys.returns-to-gudang.*' },
        ],
    },
    {
        title: 'Distribusi Cabang',
        links: [
            { route: 'hendhys.branch-requests.index', label: 'Request Cabang', icon: 'move_to_inbox', match: 'hendhys.branch-requests.*' },
            { route: 'hendhys.transfer-to-branch.index', label: 'Distribusi ke Cabang', icon: 'local_shipping', match: 'hendhys.transfer-to-branch.*' },
            { route: 'hendhys.returns.index', label: 'Return dari Cabang', icon: 'undo', match: 'hendhys.returns.*' },
        ],
    },
];

const MASTER_LINKS = [
    { route: 'hendhys.master.products.index', label: 'Daftar Produk' },
    { route: 'hendhys.master.suppliers.index', label: 'Supplier' },
];

function NavLink({ item }) {
    const base = item.match ?? item.route.replace('.index', '') + '*';
    const active = route().current(base) || route().current(item.route);
    return (
        <Link
            href={route(item.route)}
            className={`flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all ${
                active ? 'bg-amber-800 font-medium text-white shadow-md' : 'text-amber-100 hover:bg-amber-600/50 hover:text-white'
            }`}
        >
            <Icon name={item.icon} className="text-[22px] opacity-90" />
            <span className="text-sm">{item.label}</span>
        </Link>
    );
}

export default function HendhysLayout({ pageTitle, children }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);
    const [isMobile, setIsMobile] = useState(typeof window !== 'undefined' ? window.innerWidth < 1024 : false);
    const [masterOpen, setMasterOpen] = useState(route().current('hendhys.master.*'));

    useEffect(() => {
        const onResize = () => {
            const mobile = window.innerWidth < 1024;
            setIsMobile(mobile);
            if (!mobile) setSidebarOpen(true);
        };
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    return (
        <div className="flex h-screen w-full overflow-hidden bg-gray-50 font-sans antialiased">
            {sidebarOpen && isMobile && <div className="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden" onClick={() => setSidebarOpen(false)} />}

            <aside className={`fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-amber-800 bg-amber-700 text-amber-50 shadow-2xl transition-all duration-300 ease-in-out lg:static lg:shrink-0 ${sidebarOpen ? 'translate-x-0 lg:ml-0' : '-translate-x-full lg:-ml-64'} print:hidden`}>
                <div className="flex shrink-0 items-center gap-3 border-b border-amber-600/50 bg-amber-800/30 px-6 py-5">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-200 font-black text-amber-800">HB</div>
                    <span className="text-[18px] font-bold tracking-wide text-white">Hendhys Brownies</span>
                </div>

                <nav className="custom-scrollbar flex-1 space-y-1 overflow-y-auto px-4 py-6">
                    <NavLink item={{ route: 'hendhys.dashboard', label: 'Dashboard', icon: 'dashboard', match: 'hendhys.dashboard' }} />

                    {SECTIONS.map((section) => (
                        <div key={section.title}>
                            <div className="pb-2 pt-5"><p className="px-3 text-[10px] font-bold uppercase tracking-widest text-amber-300">{section.title}</p></div>
                            {section.links.map((item) => <NavLink key={item.route} item={item} />)}
                        </div>
                    ))}

                    <div className="pb-2 pt-5"><p className="px-3 text-[10px] font-bold uppercase tracking-widest text-amber-200/90">Master Data</p></div>
                    <button onClick={() => setMasterOpen((o) => !o)} className="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-amber-100 transition-all hover:bg-amber-600/50 focus:outline-none">
                        <span className="flex items-center gap-3"><Icon name="database" className="text-[22px] opacity-90" /><span className="text-sm font-medium">Master Data</span></span>
                        <Icon name="expand_more" className={`text-[20px] transition-transform ${masterOpen ? 'rotate-180 text-white' : ''}`} />
                    </button>
                    {masterOpen && (
                        <div className="space-y-1 pb-2 pt-1">
                            {MASTER_LINKS.map((item) => {
                                const active = route().current(item.route);
                                return (
                                    <Link key={item.route} href={route(item.route)} className={`flex items-center gap-3 rounded-lg py-2 pl-10 pr-3 text-[13px] transition-all ${active ? 'bg-amber-800/80 font-semibold text-white' : 'text-amber-200 hover:bg-amber-800/40 hover:text-white'}`}>
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </nav>

                <div className="shrink-0 border-t border-amber-600/50 bg-amber-800/20 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-200 font-bold text-amber-800 shadow-inner">{(auth?.user?.name ?? '?').charAt(0)}</div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-white">{auth?.user?.name}</p>
                            <p className="truncate text-[11px] text-amber-200">{auth?.user?.roles?.[0]}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <main className="relative z-10 flex h-full min-w-0 flex-1 flex-col overflow-hidden bg-gray-50">
                <header className="z-20 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-6 print:hidden">
                    <div className="flex items-center gap-4">
                        <button onClick={() => setSidebarOpen((o) => !o)} className="rounded-xl p-2 text-gray-500 transition-all hover:bg-amber-50 hover:text-amber-600 focus:outline-none"><Icon name="menu" /></button>
                        <h1 className="max-w-[200px] truncate text-lg font-bold tracking-tight text-gray-800 sm:max-w-none">{pageTitle ?? 'Dashboard Hendhys'}</h1>
                    </div>
                    <Link href={route('logout')} method="post" as="button" className="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition-all hover:bg-red-50 hover:text-red-600">
                        <span className="hidden sm:inline">Logout</span><Icon name="logout" className="text-[18px]" />
                    </Link>
                </header>

                <FlashToasts />

                <div className="custom-scrollbar flex-1 overflow-auto p-6">{children}</div>
            </main>
        </div>
    );
}
