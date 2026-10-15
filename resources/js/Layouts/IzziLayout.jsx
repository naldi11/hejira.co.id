import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';
import OwnerLayout from './OwnerLayout';
import GudangLayout from './GudangLayout';
const route = window.route;
export default function IzziLayout({ children, pageTitle = 'Izzi Kebab' }) {
    const { auth } = usePage().props;
    const roles = auth.user.roles;
    if (roles.includes('owner')) return <OwnerLayout pageTitle={pageTitle}>{children}</OwnerLayout>;
    if (roles.includes('super_admin')) return <GudangLayout pageTitle={pageTitle}>{children}</GudangLayout>;
    const group = (name, icon, items) => ({ name, icon: <Icon name={icon} />, subItems: items });
    const navItems = [{ name: 'Ringkasan Izzi Kebab', icon: <Icon name="dashboard" />, path: route('izzi.dashboard') }];
    if (roles.includes('kasir_izzi')) navItems.push(group('Kasir', 'point_of_sale', [{ name: 'Catat Penjualan', path: route('izzi.pos') }, { name: 'Laci Kasir', path: route('izzi.shifts') }]));
    navItems.push(group('Penjualan & Riwayat', 'receipt_long', [{ name: 'Riwayat & Pembatalan', path: route('izzi.sales') }, { name: 'Rekap Laci Kasir', path: route('izzi.shifts') }]));
    const stock = [{ name: 'Stok Bahan Cabang Aktif', path: route('izzi.stock') }, { name: 'Riwayat Keluar Masuk', path: route('izzi.movements') }, { name: 'Riwayat Penerimaan Bahan', path: route('izzi.receipts') }, { name: 'Stock Opname (Izin Owner)', path: route('stock-opname.index') }, { name: 'Riwayat SO', path: route('stock-opname.history') }];
    if (roles.includes('admin_izzi')) stock.unshift({ name: 'Terima Bahan', path: route('izzi.receipts.create') });
    stock.unshift({ name:'Mutasi Stok Cabang & Gudang', path:route('branch-transfers.index') });
    navItems.push(group('Stok & Penerimaan', 'inventory_2', stock));
    if (roles.includes('admin_izzi')) navItems.push(group('Menu & Resep', 'restaurant_menu', [{ name: 'Penyesuaian Bahan & Kemasan', path: route('izzi.ingredients') }, { name: 'Menu, Harga & Gramasi', path: route('izzi.menus') }]));
    return <AppLayout navItems={navItems} pageTitle={pageTitle}><FlashToasts />{children}</AppLayout>;
}
