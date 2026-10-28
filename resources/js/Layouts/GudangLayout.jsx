import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function GudangLayout({ title, pageTitle, children }) {
    const { auth } = usePage().props;
    const navItems = [
        ...(auth.user.entity === 'all' ? [{ name: 'Izzi Kebab', icon: <Icon name="restaurant" className="text-[22px]" />, path: route('izzi.dashboard') }] : []),
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('gudang.dashboard')
        },
        ...(['all', 'hendhys', 'jihans'].includes(auth?.user?.entity) ? [{
            name: 'Hasil Produksi',
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                ...(['all', 'hendhys'].includes(auth.user.entity) ? [
                    { name: 'Catat Hasil Produksi Hendhys', path: route('hendhys.productions.create') },
                    { name: 'Riwayat Produksi Hendhys', path: route('hendhys.productions.index') },
                ] : []),
                ...(['all', 'jihans'].includes(auth.user.entity) ? [
                    { name: 'Catat Hasil Produksi Jihan’s', path: route('jihans.production.create') },
                    { name: 'Riwayat Produksi Jihan’s', path: route('jihans.production.index') },
                ] : []),
            ],
        }] : []),
        {
            name: "Mutasi Stok Cabang & Gudang",
            icon: <Icon name="swap_horiz" className="text-[22px]" />,
            path: route('branch-transfers.index')
        },
        ...(['hendhys', 'all'].includes(auth?.user?.entity) ? [{
            name: "Pemakaian untuk Produksi",
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                { name: "Gunakan Stok untuk Produksi", path: route('production-materials.create') },
                { name: "Riwayat Pemakaian Stok", path: route('production-materials.index') }
            ]
        }] : []),
        {
            name: "Pembelian",
            icon: <Icon name="shopping_cart" className="text-[22px]" />,
            subItems: [
                { name: "Pesanan ke Supplier (PO)", path: route('gudang.po.index') },
                { name: "Terima Barang dari Supplier", path: route('gudang.receiving.index') }
            ]
        },
        {
            name: "Stok & Opname",
            icon: <Icon name="inventory_2" className="text-[22px]" />,
            subItems: [
                { name: "Lihat Stok Gudang", path: route('gudang.stock.index') },
                { name: "Hitung Stok Fisik (Opname)", path: route('stock-opname.index') },
                { name: "Riwayat SO", path: route('stock-opname.history') },
                { name: 'Stok Rusak / Retur', path: route('return-stock.index') }
            ]
        },
        {
            name: "Pengiriman Barang",
            icon: <Icon name="local_shipping" className="text-[22px]" />,
            subItems: [
                { name: "Permintaan Barang ke Gudang", path: route('gudang.transfer-requests.index') },
                { name: "Kirim Barang dari Gudang", path: route('gudang.transfer-out.index') }
            ]
        },
        {
            name: "Retur Barang",
            icon: <Icon name="assignment_return" className="text-[22px]" />,
            path: route('gudang.returns.index')
        },
        {
            name: "Data & Pengguna",
            icon: <Icon name="database" className="text-[22px]" />,
            subItems: [
                { name: "Produk", path: route('master.products.index') },
                { name: "Supplier", path: route('master.suppliers.index') },
                { name: "Cabang", path: route('master.branches.index') },
                { name: "Akun Pengguna", path: route('master.users.index') },
                { name: "Cetak Label Produk", path: route('products.qr') }
            ]
        }
    ];


    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? title ?? 'Gudang Tempua'}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
