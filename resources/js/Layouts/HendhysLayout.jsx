import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function HendhysLayout({ pageTitle, children }) {
    const { auth } = usePage().props;
    const isPusat = auth?.user?.branch?.type === 'pusat';
    const roles = auth?.user?.roles || [];
    const isKasir = roles.includes('kasir_hendhys') || roles.includes('super_admin_hendhys');
    const isAdmin = roles.includes('admin_hendhys') || roles.includes('super_admin_hendhys') || roles.includes('owner') || roles.includes('admin_gudang');
    const isSuperAdmin = roles.includes('super_admin_hendhys');

    const navItems = [
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('hendhys.dashboard')
        }
    ];

    if (isKasir) {
        const subItems = [
            { name: 'POS Kasir', path: route('hendhys.pos.index') },
            { name: 'Transaksi Pending', path: route('hendhys.pending.index') },
            { name: 'Riwayat Transaksi', path: route('hendhys.transactions.index') },
            { name: 'Laporan Laci', path: route('hendhys.reports.laci') },
        ];
        if (isSuperAdmin) {
            subItems.push({ name: 'Laporan Bisnis', path: route('hendhys.reports.index') });
        }
        navItems.push({
            name: 'Kasir & Penjualan',
            icon: <Icon name="point_of_sale" className="text-[22px]" />,
            subItems: subItems,
        });
    } else {
        navItems.push({
            name: 'Penjualan & Laporan',
            icon: <Icon name="point_of_sale" className="text-[22px]" />,
            subItems: [
                { name: 'Riwayat Transaksi', path: route('hendhys.transactions.index') },
                { name: 'Laporan Bisnis', path: route('hendhys.reports.index') },
            ],
        });
    }

    if (isAdmin && isPusat) {
        navItems.push({
            name: 'Produksi',
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                { name: 'Produksi Hendhys', path: route('hendhys.productions.index') },
            ],
        });
    }

    const inventorySubItems = [
        { name: 'Stok Tersedia', path: route('hendhys.stock.index') }
    ];

    if (!isPusat) {
        inventorySubItems.push({ name: 'Penerimaan dari Pusat', path: route('hendhys.transfer-to-branch.index') });
        inventorySubItems.push({ name: 'Penerimaan dari Gudang', path: route('hendhys.transfer-to-branch.index', { tab: 'gudang' }) });
    }

    if (isAdmin) {
        if (isPusat) {
            inventorySubItems.push({ name: 'Request ke Gudang', path: route('hendhys.transfer-requests.index') });
            inventorySubItems.push({ name: 'Return ke Gudang', path: route('hendhys.returns-to-gudang.index') });
        } else {
            inventorySubItems.push({ name: 'Request ke Hendhys Pusat', path: route('hendhys.branch-requests.index') });
            inventorySubItems.push({ name: 'Return ke Hendhys Pusat', path: route('hendhys.returns.index') });
        }
    } else if (isKasir && !isPusat) {
        // Kasir cabang — akses Request & Return ke Pusat
        inventorySubItems.push({ name: 'Request ke Hendhys Pusat', path: route('hendhys.branch-requests.index') });
        inventorySubItems.push({ name: 'Return ke Hendhys Pusat', path: route('hendhys.returns.index') });
    }

    navItems.push({
        name: 'Inventori',
        icon: <Icon name="inventory" className="text-[22px]" />,
        subItems: inventorySubItems,
    });

    if (isAdmin && isPusat) {
        navItems.push({
            name: 'Distribusi Cabang',
            icon: <Icon name="local_shipping" className="text-[22px]" />,
            subItems: [
                { name: 'Request Cabang', path: route('hendhys.branch-requests.index') },
                { name: 'Distribusi ke Cabang', path: route('hendhys.transfer-to-branch.index') },
                { name: 'Return dari Cabang', path: route('hendhys.returns.index') },
            ],
        });
        navItems.push({
            name: 'Master Data',
            icon: <Icon name="database" className="text-[22px]" />,
            subItems: [
                { name: 'Daftar Produk', path: route('hendhys.master.products.index') },
                { name: 'Pelanggan', path: route('hendhys.master.customers.index') },
            ],
        });
    }


    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? 'Dashboard Hendhys'}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
