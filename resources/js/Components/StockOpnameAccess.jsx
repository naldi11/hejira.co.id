import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from '@/Components/Icon';

export function useOpnameActive(permitted, session) {
    const [now, setNow] = useState(Date.now);
    useEffect(() => {
        if (!permitted) return;
        const timer = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(timer);
    }, [permitted, session?.expires_at_iso]);
    return Boolean(permitted && session?.active && Date.parse(session.expires_at_iso) > now);
}

export default function StockOpnameAccess({ access }) {
    const session = access?.session;
    const active = useOpnameActive(access?.can_count, session);
    return <div role="status" className={`flex items-start gap-3 rounded-xl border p-4 ${active ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200'}`}>
        <Icon name={active ? 'lock_open' : 'lock'} className="text-2xl" />
        <div>
            <p className="font-bold">{active ? 'Stock opname dibuka oleh Owner' : 'Stock opname terkunci'}</p>
            {active ? <>
                <p className="mt-1 text-sm">Sesi #{session.id} · {session.location_name}. Dibuka oleh {session.opened_by_name}, berlaku sampai {session.expires_at}.</p>
                <Link className="mt-2 inline-block font-semibold underline" href={window.route('stock-opname.show', session.id)}>Masuk sesi dan hitung stok fisik →</Link>
            </> : <p className="mt-1 text-sm">Belum ada sesi aktif dari Owner untuk lokasi ini. Anda tetap dapat melihat stok. Untuk menghitung dan menyesuaikan saldo, minta Owner membuka sesi melalui menu Sesi Stock Opname.</p>}
        </div>
    </div>;
}
