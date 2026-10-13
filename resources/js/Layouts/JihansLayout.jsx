import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;
const group = (name, icon, subItems) => ({ name, icon: <Icon name={icon} className="text-[22px]" />, subItems });

export default function JihansLayout({ pageTitle, children }) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles || [];
    const isKasir = roles.includes('kasir_jihans');
    const isAdmin = roles.includes('admin_jihans') || roles.includes('super_admin');
    const isOwner = roles.includes('owner');

    const navItems = [{ name: 'Dashboard', icon: <Icon name="dashboard" className="text-[22px]" />, path: route('jihans.dashboard') }];
    const sales = isKasir ? [
        { name: 'Kasir / Catat Penjualan', path: route('jihans.pos.index') },
        { name: 'Transaksi Belum Selesai', path: route('jihans.pending.index') },
        { name: 'Riwayat Penjualan', path: route('jihans.transactions.index') },
        { name: 'Laporan Uang Kasir', path: route('jihans.reports.laci') },
    ] : [{ name: 'Riwayat Penjualan', path: route('jihans.transactions.index') }];
    if (!isKasir || roles.includes('super_admin')) sales.push({ name: 'Laporan Penjualan', path: route('jihans.reports.index') });
    navItems.push(group('Penjualan', 'point_of_sale', sales));

    if (isAdmin) navItems.push(group('Produksi', 'factory', [
        { name: 'Rencana Produksi (Prediksi)', path: route('jihans.production.prediksi.create') },
        { name: 'Catatan & Hasil Produksi', path: route('jihans.production.index') },
    ]));
    navItems.push(group('Stok & Opname', 'inventory_2', [
        { name: 'Stok Penjualan Bersama', path: route('jihans.stock.index') },
        { name: 'Riwayat Keluar Masuk Stok', path: route('jihans.stock.movements') },
        { name: 'Hitung Stok Fisik (Opname)', path: route('stock-opname.index') },
        { name: 'Riwayat SO', path: route('stock-opname.history') },
        { name: 'Stok Rusak / Retur', path: route('return-stock.index') },
    ]));

    if (isKasir || isAdmin) {
        navItems.push(group('Permintaan & Penerimaan', 'move_to_inbox', [
            { name: 'Minta & Terima Barang Gudang', path: route('jihans.transfer-requests.index') },
            { name: 'Terima dari Hendhys Produksi', path: route('jihans.transfer-from-hendhys.index') },
        ]));
        const outgoing = [];
        if (!isOwner) outgoing.push({ name: 'Mutasi Stok ke Cabang Hendhys', path: route('branch-transfers.index') });
        outgoing.push(
            { name: 'Kembalikan Barang ke Gudang', path: route('jihans.returns-to-gudang.index') },
            { name: 'Kembalikan ke Hendhys Produksi', path: route('jihans.returns-to-hendhys.index') },
        );
        navItems.push(group('Pengiriman & Retur', 'local_shipping', outgoing));
    }

    const data = [];
    if (isAdmin) data.push(
        { name: 'Produk', path: route('jihans.master.products.index') },
        { name: 'Pelanggan', path: route('jihans.master.customers.index') },
        { name: 'Karyawan', path: route('jihans.master.karyawan.index') },
    );
    data.push({ name: 'Cetak Label Produk', path: route('products.qr') });
    navItems.push(group('Data & Label', 'database', data));

    return <AppLayout navItems={navItems} pageTitle={pageTitle ?? "Dashboard Jihan's Food"}><FlashToasts />{children}</AppLayout>;
}
