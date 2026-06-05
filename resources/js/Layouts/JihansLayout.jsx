import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function JihansLayout({ pageTitle, children }) {
    const navItems = [
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('jihans.dashboard')
        },
        {
            name: 'Kasir & Penjualan',
            icon: <Icon name="point_of_sale" className="text-[22px]" />,
            subItems: [
                { name: 'POS Kasir', path: route('jihans.pos.index') },
                { name: 'Transaksi Pending', path: route('jihans.pending.index') },
                { name: 'Riwayat Transaksi', path: route('jihans.transactions.index') },
                { name: 'Laporan', path: route('jihans.reports.index') },
            ],
        },
        {
            name: 'Manufaktur',
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                { name: 'Prediksi Produksi', path: route('jihans.tortilla.prediksi.create') },
                { name: 'Aktual Produksi', path: route('jihans.tortilla.index') },
            ],
        },
        {
            name: "Inventori Jihan's",
            icon: <Icon name="inventory" className="text-[22px]" />,
            subItems: [
                { name: 'Stok Tersedia', path: route('jihans.stock.index') },
                { name: 'Request ke Gudang', path: route('jihans.transfer-requests.index') },
                { name: 'Return ke Gudang', path: route('jihans.returns-to-gudang.index') },
            ],
        },

        {
            name: 'Master Data',
            icon: <Icon name="database" className="text-[22px]" />,
            subItems: [
                { name: 'Daftar Produk', path: route('jihans.master.products.index') },
                { name: 'Pelanggan', path: route('jihans.master.customers.index') },
                { name: 'Karyawan', path: route('jihans.master.karyawan.index') },
                { name: 'Konfigurasi Produksi', path: route('jihans.master.production-config.edit') },
            ],
        }
    ];

    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? "Dashboard Jihan's Food"}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
