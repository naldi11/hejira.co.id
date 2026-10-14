import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import OwnerProductionAlert from '@/Components/OwnerProductionAlert';
import AppLayout from './AppLayout';

const route = window.route;

export default function OwnerLayout({ pageTitle, children }) {
    const navItems = [
        { name: 'Izzi Kebab', icon: <Icon name="restaurant" className="text-[22px]" />, path: route('izzi.dashboard') },
        { name: 'Konsolidasi Utama', icon: <Icon name="dashboard" className="text-[22px]" />, path: route('owner.dashboard') },
        {
            name: 'Produksi & Stok', icon: <Icon name="inventory_2" className="text-[22px]" />,
            subItems: [
                { name: 'Produksi & Sisa Stok', path: route('owner.production.index') },
                { name: 'Hapus Sisa Produksi', path: route('owner.production-writeoffs.index') },
                { name: 'Stok Rusak / Retur', path: route('return-stock.index') },
            ],
        },
        {
            name: 'Stock Opname', icon: <Icon name="balance" className="text-[22px]" />,
            subItems: [
                { name: 'Buka & Kelola Sesi', path: route('stock-opname.index') },
                { name: 'Riwayat Stock Opname', path: route('stock-opname.history') },
            ],
        },
        { name: 'Laporan Data', icon: <Icon name="assessment" className="text-[22px]" />, path: route('owner.reports') },
    ];

    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? 'Dashboard'}>
            <FlashToasts />
            <OwnerProductionAlert />
            {children}
        </AppLayout>
    );
}
