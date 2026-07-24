import { usePage } from '@inertiajs/react';
import Icon from '@/Components/Icon';
import FlashToasts from '@/Components/FlashToasts';
import AppLayout from './AppLayout';

const route = window.route;

export default function OwnerLayout({ pageTitle, children }) {
    const navItems = [
        {
            name: "Konsolidasi Utama",
            icon: <Icon name="dashboard" className="text-[22px]" />,
            path: route('owner.dashboard')
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
