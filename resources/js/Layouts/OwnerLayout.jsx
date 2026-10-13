import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function OwnerLayout({ pageTitle, children }) {
    const navItems = [
        { name: 'Sesi Stock Opname', icon: <Icon name="balance" className="text-[22px]" />, path: route('stock-opname.index') },
        { name: 'Riwayat SO', icon: <Icon name="history" className="text-[22px]" />, path: route('stock-opname.history') },
        {
            name: "Konsolidasi Utama",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('owner.dashboard')
        },
        {
            name: "Produksi & Sisa Stok",
            icon: <Icon name="inventory_2" className="text-[22px]" />,
            path: route('owner.production.index')
        },
        {
            name: "Hapus Sisa Produksi",
            icon: <Icon name="inventory" className="text-[22px]" />,
            path: route('owner.production-writeoffs.index')
        },
        {
            name: "Laporan Data",
            icon: <Icon name="assessment" className="text-[22px]" />,
            path: route('owner.reports')
        }
    ];

    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? 'Dashboard'}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
