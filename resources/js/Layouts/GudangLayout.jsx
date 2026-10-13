import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function GudangLayout({ title, pageTitle, children }) {
    const navItems = [
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('gudang.dashboard')
        },
        {
            name: "Mutasi Stok Antar Cabang",
            icon: <Icon name="swap_horiz" className="text-[22px]" />,
            path: route('branch-transfers.index')
        },
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
            name: "Barang untuk Produksi",
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                { name: "Catat Barang Produksi", path: route('production-materials.create') },
                { name: "Riwayat Barang Produksi", path: route('production-materials.index') }
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
