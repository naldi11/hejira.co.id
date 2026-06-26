import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function JihansLayout({ pageTitle, children }) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles || [];
    const isKasir = roles.includes('kasir_jihans');
    const isAdmin = roles.includes('admin_jihans') || roles.includes('owner') || roles.includes('admin_gudang');

    const navItems = [
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('jihans.dashboard')
        }
    ];

    if (isKasir) {
        navItems.push({
            name: 'Kasir & Penjualan',
            icon: <Icon name="point_of_sale" className="text-[22px]" />,
            subItems: [
                { name: 'POS Kasir', path: route('jihans.pos.index') },
                { name: 'Transaksi Pending', path: route('jihans.pending.index') },
                { name: 'Riwayat Transaksi', path: route('jihans.transactions.index') },
                { name: 'Laporan Laci', path: route('jihans.reports.laci') },
            ],
        });
    } else {
        navItems.push({
            name: 'Penjualan & Laporan',
            icon: <Icon name="point_of_sale" className="text-[22px]" />,
            subItems: [
                { name: 'Riwayat Transaksi', path: route('jihans.transactions.index') },
                { name: 'Laporan Bisnis', path: route('jihans.reports.index') },
            ],
        });
    }

    if (isAdmin) {
        navItems.push({
            name: 'Manufaktur',
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                { name: 'Prediksi Produksi', path: route('jihans.tortilla.prediksi.create') },
                { name: 'Aktual Produksi', path: route('jihans.tortilla.index') },
            ],
        });
    }

    const inventorySubItems = [
        { name: 'Stok Tersedia', path: route('jihans.stock.index') }
    ];

    if (isAdmin) {
        inventorySubItems.push({ name: 'Request ke Gudang', path: route('jihans.transfer-requests.index') });
        inventorySubItems.push({ name: 'Return ke Gudang', path: route('jihans.returns-to-gudang.index') });
    }

    navItems.push({
        name: "Inventori Jihan's",
        icon: <Icon name="inventory" className="text-[22px]" />,
        subItems: inventorySubItems,
    });

    if (isAdmin) {
        navItems.push({
            name: 'Master Data',
            icon: <Icon name="database" className="text-[22px]" />,
            subItems: [
                { name: 'Daftar Produk', path: route('jihans.master.products.index') },
                { name: 'Pelanggan', path: route('jihans.master.customers.index') },
                { name: 'Karyawan', path: route('jihans.master.karyawan.index') },
                { name: 'Konfigurasi Produksi', path: route('jihans.master.production-config.edit') },
            ],
        });
    }


    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? "Dashboard Jihan's Food"}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
