import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function GudangLayout({ title, pageTitle, children }) {
    const { notifications } = usePage().props;
    const gudangPending = notifications?.gudang_pending ?? 0;

    const navItems = [
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('gudang.dashboard')
        },
        {
            name: "Data Master",
            icon: <Icon name="database" className="text-[22px]" />,
            subItems: [
                { name: "Supplier", path: route('master.suppliers.index') },
                { name: "Produk", path: route('master.products.index') },
                { name: "Cabang", path: route('master.branches.index') }
            ]
        },
        {
            name: "Inventori",
            icon: <Icon name="inventory" className="text-[22px]" />,
            subItems: [
                { name: "Purchase Order", path: route('gudang.po.index') },
                { name: "Penerimaan Barang", path: route('gudang.receiving.index') },
                { name: "Stok Gudang", path: route('gudang.stock.index') },
                { name: "Transfer Request", path: route('gudang.transfer-requests.index') },
                { name: "Transfer Keluar", path: route('gudang.transfer-out.index') },
                { name: "Penerimaan Retur", path: route('gudang.returns.index') }
            ]
        },
        {
            name: "Hak Akses",
            icon: <Icon name="manage_accounts" className="text-[22px]" />,
            path: route('master.users.index')
        }
    ];


    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? title ?? 'Gudang Tempua'}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
