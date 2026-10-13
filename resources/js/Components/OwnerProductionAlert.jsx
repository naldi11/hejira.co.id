import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import Icon from '@/Components/Icon';

const route = window.route;

function AlertPanel({ alert }) {
    const [closed, setClosed] = useState(false);
    const [closing, setClosing] = useState(false);
    const [error, setError] = useState('');

    const dismiss = async () => {
        if (closing) return;
        setClosing(true);
        setError('');
        try {
            await axios.post(route('owner.production-alert.dismiss'), { alert_id: alert.id });
            setClosed(true);
        } catch {
            setError('Notifikasi belum bisa ditutup. Silakan coba lagi.');
        } finally {
            setClosing(false);
        }
    };

    if (closed) return null;
    return <aside role="alert" aria-label="Peringatan sisa stok produksi" className="fixed bottom-4 right-4 z-40 w-[calc(100%_-_2rem)] max-w-md rounded-2xl border border-red-200 bg-white p-5 text-gray-900 shadow-xl dark:border-red-800 dark:bg-gray-900 dark:text-gray-100 print:hidden">
        <div className="flex items-start gap-3">
            <Icon name="warning" className="mt-1 shrink-0 text-2xl text-red-600" />
            <div className="min-w-0 flex-1"><h2 className="font-bold text-red-700 dark:text-red-400">{alert.title}</h2><p className="mt-2 text-sm leading-relaxed">{alert.message}</p></div>
            <button type="button" onClick={dismiss} disabled={closing} aria-label="Tutup notifikasi stok produksi untuk sesi ini" className="shrink-0 rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus-visible:ring-2 focus-visible:ring-red-500 disabled:opacity-50 dark:hover:bg-gray-800"><Icon name="close" className="text-xl" /></button>
        </div>
        <Link href={alert.path} className="mt-3 inline-block text-sm font-semibold text-brand-600 underline dark:text-brand-400">Lihat produksi dan sisa stok →</Link>
        <p className="mt-3 text-xs leading-relaxed text-gray-500 dark:text-gray-400">Tetap tampil sampai ditutup. Menutup hanya menyembunyikan panel untuk peringatan ini selama sesi login; stok tetap perlu ditangani.</p>
        {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
    </aside>;
}

export default function OwnerProductionAlert() {
    const { auth, notifications } = usePage().props;
    if (!auth?.user?.roles?.includes('owner')) return null;
    const alert = notifications?.items?.find(item => item.id?.startsWith('production_stock_'));
    if (!alert || notifications?.dismissed_production_alerts?.includes(alert.id)) return null;
    return <AlertPanel key={`${auth.user.id}:${alert.id}`} alert={alert} />;
}
