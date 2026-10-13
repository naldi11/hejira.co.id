import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;
const group = (name, icon, subItems) => ({ name, icon: <Icon name={icon} className="text-[22px]" />, subItems });

export default function HendhysLayout({ pageTitle, children }) {
    const { auth } = usePage().props;
    const isPusat = auth?.user?.branch?.type === 'pusat';
    const roles = auth?.user?.roles || [];
    const isKasir = roles.includes('kasir_hendhys');
    const isAdmin = roles.includes('admin_hendhys') || roles.includes('super_admin');
    const isOwner = roles.includes('owner');

    const navItems = [{ name: 'Dashboard', icon: <Icon name="dashboard" className="text-[22px]" />, path: route('hendhys.dashboard') }];
    const sales = isKasir ? [
        { name: 'Kasir / Catat Penjualan', path: route('hendhys.pos.index') },
        { name: 'Transaksi Belum Selesai', path: route('hendhys.pending.index') },
        { name: 'Riwayat Penjualan', path: route('hendhys.transactions.index') },
        { name: 'Laporan Uang Kasir', path: route('hendhys.reports.laci') },
    ] : [{ name: 'Riwayat Penjualan', path: route('hendhys.transactions.index') }];
    if (!isKasir || roles.includes('super_admin')) sales.push({ name: 'Laporan Penjualan', path: route('hendhys.reports.index') });
    navItems.push(group('Penjualan', 'point_of_sale', sales));

    if (isAdmin && isPusat) navItems.push(group('Produksi', 'factory', [
        { name: 'Rencana Produksi (Prediksi)', path: route('hendhys.productions.prediksi.create') },
        { name: 'Catatan & Hasil Produksi', path: route('hendhys.productions.index') },
    ]));
    navItems.push(group('Stok & Opname', 'inventory_2', [
        { name: isPusat ? 'Stok Pusat Produksi' : 'Stok Cabang Aktif', path: route('hendhys.stock.index') },
        { name: 'Riwayat Keluar Masuk Stok', path: route('hendhys.stock.movements') },
        { name: 'Hitung Stok Fisik (Opname)', path: route('stock-opname.index') },
        { name: 'Riwayat SO', path: route('stock-opname.history') },
    ]));

    const incoming = [{ name: 'Minta Barang ke Gudang', path: route('hendhys.transfer-requests.index') }];
    if (!isPusat) incoming.push(
        { name: 'Minta Barang ke Pusat Produksi', path: route('hendhys.branch-requests.index') },
        { name: 'Terima dari Pusat Produksi', path: route('hendhys.transfer-to-branch.index') },
    );
    else if (isAdmin) incoming.push({ name: 'Permintaan dari Cabang', path: route('hendhys.branch-requests.index') });
    incoming.push({ name: 'Terima Barang dari Gudang', path: route('hendhys.transfer-to-branch.index', { tab: 'gudang' }) });
    navItems.push(group('Permintaan & Penerimaan', 'move_to_inbox', incoming));

    const outgoing = [];
    if (!isPusat && !isOwner) outgoing.push({ name: 'Mutasi Stok Antar Cabang', path: route('branch-transfers.index') });
    if (isAdmin && isPusat) outgoing.push({ name: 'Kirim ke Cabang / Jihan’s', path: route('hendhys.transfer-to-branch.index') });
    outgoing.push({ name: 'Kembalikan Barang ke Gudang', path: route('hendhys.returns-to-gudang.index') });
    if (!isPusat) outgoing.push({ name: 'Kembalikan ke Pusat Produksi', path: route('hendhys.returns.index') });
    else if (isAdmin) outgoing.push({ name: 'Terima Retur dari Cabang / Jihan’s', path: route('hendhys.returns.index') });
    navItems.push(group('Pengiriman & Retur', 'local_shipping', outgoing));

    const data = [];
    if (isAdmin && isPusat) data.push(
        { name: 'Produk', path: route('hendhys.master.products.index') },
        { name: 'Pelanggan', path: route('hendhys.master.customers.index') },
    );
    data.push({ name: 'Cetak Label Produk', path: route('products.qr') });
    navItems.push(group('Data & Label', 'database', data));

    return <AppLayout navItems={navItems} pageTitle={pageTitle ?? 'Dashboard Hendhys'}><FlashToasts />{children}</AppLayout>;
}
