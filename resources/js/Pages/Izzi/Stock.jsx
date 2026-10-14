import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import { Navigation, panel, Search, Paging } from './Common';
export default function Stock(props) {
    return <IzziLayout pageTitle="Stok Bahan Izzi"><Head title="Stok Bahan Izzi" /><div className="space-y-5 text-gray-800 dark:text-gray-200"><h1 className="text-2xl font-bold">Stok Bahan Cabang</h1><Navigation {...props} /><div className={`${panel} space-y-4`}><p>Saldo dalam satuan dasar yang dipakai resep. Menu terjual akan mengurangi seluruh bahan secara otomatis.</p><Search filters={props.filters} path="izzi.stock" branch={props.branch} />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{props.stocks.data.map(p => <div key={p.id} className="rounded-xl border p-4 dark:border-gray-700"><strong>{p.name}</strong><p className="text-sm text-gray-500">{p.code} · {p.status === 'active' ? 'Aktif' : 'Nonaktif'}</p><p className={`my-3 text-2xl font-bold ${Number(p.quantity) <= 0 ? 'text-red-600' : 'text-green-600'}`}>{p.quantity} {p.unit}</p><p className="text-sm">1 {p.purchase_unit} = {p.purchase_factor} {p.unit}</p></div>)}</div><Paging rows={props.stocks} />
        {props.opnameAccess.can_count ? <Link className="inline-block text-brand-500 underline" href={route('stock-opname.show', props.opnameAccess.session.id)}>Sesi SO #{props.opnameAccess.session.id} aktif — hitung fisik →</Link> : <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Penyesuaian melalui SO memerlukan sesi yang dibuka Owner untuk cabang ini.</p>}
    </div></div></IzziLayout>;
}
