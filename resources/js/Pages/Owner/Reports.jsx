import { Head } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';

export default function OwnerReports() {
    return (
        <OwnerLayout pageTitle="Data Reports (Eksport)">
            <Head title="Owner — Reports" />

            <div className="flex min-h-[60vh] items-center justify-center">
                <div className="max-w-md rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-purple-100 text-purple-600">
                        <Icon name="assessment" className="text-[32px]" />
                    </div>
                    <h2 className="text-lg font-bold text-slate-800">Pusat Laporan & Ekspor</h2>
                    <p className="mt-2 text-sm text-slate-500">
                        Modul konsolidasi laporan lintas-entitas (Gudang, Jihan's, Hendhys) beserta ekspor PDF/Excel akan tersedia di sini.
                    </p>
                    <span className="mt-4 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Segera Hadir</span>
                </div>
            </div>
        </OwnerLayout>
    );
}
