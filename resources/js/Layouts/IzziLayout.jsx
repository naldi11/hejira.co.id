import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;
const group = (name, icon, subItems) => ({
    name,
    icon: <Icon name={icon} className="text-[22px]" />,
    subItems,
});

export default function IzziLayout({ children, pageTitle = 'Izzi Kebab' }) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles || [];
    const isKasir = roles.includes('kasir_izzi');
    const isAdmin = roles.includes('admin_izzi') || roles.includes('super_admin');
    const isOwner = roles.includes('owner');
    const isSuperAdmin = roles.includes('super_admin');

    const navItems = [];

    if (isOwner) {
        navItems.push({
            name: '← Panel Utama Owner',
            icon: <Icon name="arrow_back" className="text-[22px]" />,
            path: route('owner.dashboard'),
        });
    } else if (isSuperAdmin) {
        navItems.push({
            name: '← Panel Gudang Utama',
            icon: <Icon name="arrow_back" className="text-[22px]" />,
            path: route('gudang.dashboard'),
        });
    }

    navItems.push({
        name: 'Dashboard',
        icon: <Icon name="dashboard" className="text-[22px]" />,
        path: route('izzi.dashboard'),
    });

    const sales = [];
    if (isKasir) {
        sales.push({ name: 'Kasir POS', path: route('izzi.pos') });
    }
    sales.push(
        { name: 'Riwayat Transaksi', path: route('izzi.sales') },
        { name: 'Laci Kasir (Shift)', path: route('izzi.shifts') },
    );
    navItems.push(group('Penjualan & Kasir', 'point_of_sale', sales));

    const stock = [
        { name: 'Stok Bahan Tersedia', path: route('izzi.stock') },
        { name: 'Kartu Stok (Keluar/Masuk)', path: route('izzi.movements') },
        { name: 'Hitung Stok Fisik (Opname)', path: route('stock-opname.index') },
        { name: 'Riwayat SO', path: route('stock-opname.history') },
        ...(!isOwner ? [{ name: 'Stok Rusak / Retur', path: route('return-stock.index') }] : []),
    ];
    navItems.push(group('Stok & Opname', 'inventory_2', stock));

    const receiving = [];
    if (isAdmin) {
        receiving.push({ name: 'Terima Bahan Masuk', path: route('izzi.receipts.create') });
    }
    receiving.push({ name: 'Riwayat Penerimaan', path: route('izzi.receipts') });
    if (!isOwner) {
        receiving.push({ name: 'Terima Pengiriman Gudang', path: route('izzi.warehouse-shipments.index') });
        receiving.push({ name: 'Mutasi Cabang & Gudang', path: route('branch-transfers.index') });
    }
    navItems.push(group('Penerimaan & Mutasi', 'local_shipping', receiving));

    if (isAdmin) {
        navItems.push(
            group('Menu & Resep', 'restaurant_menu', [
                { name: 'Daftar Menu & Resep', path: route('izzi.menus') },
                { name: 'Tambah Menu Baru', path: route('izzi.menus.create') },
                { name: 'Satuan Kemasan Bahan', path: route('izzi.ingredients') },
            ]),
        );
    }

    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
