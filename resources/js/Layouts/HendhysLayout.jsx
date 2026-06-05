import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function HendhysLayout({ pageTitle, children }) {
    const { auth } = usePage().props;
    const isPusat = auth?.user?.branch?.type === 'pusat';

    const navItems = [
        {
            name: "Dashboard",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('hendhys.dashboard')
        },
        {
            name: 'Kasir & Penjualan',
            icon: <Icon name="point_of_sale" className="text-[22px]" />,
            subItems: [
                { name: 'POS Kasir', path: route('hendhys.pos.index') },
                { name: 'Transaksi Pending', path: route('hendhys.pending.index') },
                { name: 'Riwayat Transaksi', path: route('hendhys.transactions.index') },
                { name: 'Laporan', path: route('hendhys.reports.index') },
            ],
        },
        ...(isPusat ? [{
            name: 'Produksi',
            icon: <Icon name="factory" className="text-[22px]" />,
            subItems: [
                { name: 'Produksi Hendhys', path: route('hendhys.productions.index') },
            ],
        }] : []),
        {
            name: 'Inventori',
            icon: <Icon name="inventory" className="text-[22px]" />,
            subItems: [
                { name: 'Stok Tersedia', path: route('hendhys.stock.index') },
                ...(isPusat ? [
                    { name: 'Request ke Gudang', path: route('hendhys.transfer-requests.index') },
                    { name: 'Return ke Gudang', path: route('hendhys.returns-to-gudang.index') },
                ] : [
                    { name: 'Request ke Hendhys Pusat', path: route('hendhys.branch-requests.index') },
                    { name: 'Return ke Hendhys Pusat', path: route('hendhys.returns.index') },
                ]),
            ],
        },

        ...(isPusat ? [{
            name: 'Distribusi Cabang',
            icon: <Icon name="local_shipping" className="text-[22px]" />,
            subItems: [
                { name: 'Request Cabang', path: route('hendhys.branch-requests.index') },
                { name: 'Distribusi ke Cabang', path: route('hendhys.transfer-to-branch.index') },
                { name: 'Return dari Cabang', path: route('hendhys.returns.index') },
            ],
        }] : []),
        ...(isPusat ? [{
            name: 'Master Data',
            icon: <Icon name="database" className="text-[22px]" />,
            subItems: [
                { name: 'Daftar Produk', path: route('hendhys.master.products.index') },
                { name: 'Pelanggan', path: route('hendhys.master.customers.index') },
            ],
        }] : [])
    ];

    return (
        <AppLayout navItems={navItems} pageTitle={pageTitle ?? 'Dashboard Hendhys'}>
            <FlashToasts />
            {children}
        </AppLayout>
    );
}
