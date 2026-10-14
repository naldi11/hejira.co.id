import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import { Navigation, panel, money, status } from './Common';
export default function Dashboard(props) {
    return <IzziLayout pageTitle="Izzi Kebab"><Head title="Izzi Kebab" /><div className="space-y-6 text-gray-800 dark:text-gray-200"><h1 className="text-2xl font-bold">Ringkasan Izzi Kebab</h1>
        {!props.branch ? <div className={panel}><p>Belum ada cabang Izzi Kebab aktif. Superadmin dapat menambah cabang dan akun Izzi melalui Data & Pengguna.</p></div> : <><Navigation {...props} />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{[['Penjualan bersih hari ini', money(props.stats.revenue_today)], ['Transaksi hari ini', props.stats.sales_today], ['Pembatalan tercatat (semua tanggal)', money(props.stats.cancelled_total)], ['Menu aktif', props.stats.active_menus]].map(([label,value]) => <div className={panel} key={label}><p className="text-sm text-gray-500">{label}</p><strong className="mt-2 block text-2xl">{value}</strong></div>)}</div>
            <div className={`${panel} space-y-4`}><h2 className="font-bold">Penjualan terbaru</h2>{props.recent.length === 0 && <p>Belum ada penjualan. Isi bahan, catat penerimaan stok, lalu buat menu dan resep.</p>}{props.recent.map(s => <Link key={s.id} className="block rounded-lg border p-3 dark:border-gray-700" href={route('izzi.sales.show', { sale: s.id, branch_id: s.branch_id })}><strong>{s.number}</strong><p>{s.time} · {s.actor_name} · {status[s.status]}</p><p>Penjualan bersih: {money(s.net_total)}</p></Link>)}</div>
        </>}
    </div></IzziLayout>;
}
