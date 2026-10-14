import { usePage } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import GudangLayout from '@/Layouts/GudangLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import IzziLayout from '@/Layouts/IzziLayout';

export default function Layout({ children, pageTitle = 'Stock Opname' }) {
    const { auth } = usePage().props;
    const roles = auth?.user?.roles ?? [];
    const names = roles.map(r => typeof r === 'string' ? r : r.name);
    const Shell = names.includes('owner') ? OwnerLayout : names.includes('super_admin') ? GudangLayout : auth?.user?.entity === 'izzi' ? IzziLayout : auth?.user?.entity === 'hendhys' ? HendhysLayout : JihansLayout;
    return <Shell pageTitle={pageTitle}>{children}</Shell>;
}

export const panel = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900';
export const input = 'w-full rounded-lg border border-gray-300 p-3 dark:border-gray-700 dark:bg-gray-800';
export const button = 'rounded-lg bg-brand-500 px-5 py-3 text-white disabled:opacity-50';
export function Errors({ errors }) {
    return Object.keys(errors).length > 0 && <div role="alert" className="rounded-lg bg-red-50 p-4 text-red-700">{[...new Set(Object.values(errors))].map((e, i) => <p key={i}>{e}</p>)}</div>;
}
