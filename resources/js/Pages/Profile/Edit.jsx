import { Head, usePage } from '@inertiajs/react';
import React from 'react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import DeleteUserForm from './Partials/DeleteUserForm';

export default function Edit() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const roles = user?.roles || [];

    // Resolve layout dynamically based on role
    let Layout = ({ children }) => <div className="p-6 max-w-4xl mx-auto">{children}</div>; // fallback

    if (roles.includes('owner')) {
        Layout = ({ children }) => <OwnerLayout pageTitle="Pengaturan Profil">{children}</OwnerLayout>;
    } else if (roles.includes('admin_gudang')) {
        Layout = ({ children }) => <GudangLayout pageTitle="Pengaturan Profil" title="Profil">{children}</GudangLayout>;
    } else if (roles.includes('kasir_jihans') || roles.includes('admin_jihans')) {
        Layout = ({ children }) => <JihansLayout pageTitle="Pengaturan Profil">{children}</JihansLayout>;
    } else if (roles.includes('kasir_hendhys')) {
        Layout = ({ children }) => <HendhysLayout pageTitle="Pengaturan Profil">{children}</HendhysLayout>;
    }

    return (
        <Layout>
            <Head title="Profil" />

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Profile Information Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
                    <UpdateProfileInformationForm />
                </div>

                {/* Change Password Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
                    <UpdatePasswordForm />
                </div>

                {/* Delete Account Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-8 lg:col-span-2">
                    <DeleteUserForm />
                </div>
            </div>
        </Layout>
    );
}
