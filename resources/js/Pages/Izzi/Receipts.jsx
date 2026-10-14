import { Head } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import { Navigation, panel, Search, Paging } from './Common';
export default function Receipts(props) {
    return <IzziLayout pageTitle="Riwayat Penerimaan"><Head title="Riwayat Penerimaan Bahan" /><div className="space-y-5 text-gray-800 dark:text-gray-200"><h1 className="text-2xl font-bold">Riwayat Penerimaan Bahan</h1><Navigation {...props} /><div className={`${panel} space-y-4`}><Search filters={props.filters} path="izzi.receipts" branch={props.branch} />{props.receipts.data.map(r => <article className="space-y-3 rounded-xl border p-4 dark:border-gray-700" key={r.id}><strong>{r.number}</strong><p>Diterima {r.time} dari {r.origin} · {r.branch_name}</p><p>Dicatat {r.recorded} oleh {r.actor_name}</p><p>Bukti: {r.evidence_reference || '—'} · Catatan: {r.notes}</p>{r.items.map((i,n) => <p key={n} className="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">{i.product_name} · {i.product_code}<br />Diterima {i.input_quantity} {i.input_unit} = {i.quantity} {i.unit_name}<br />Stok: {i.before} → {i.after} {i.unit_name}</p>)}</article>)}<Paging rows={props.receipts} /></div></div></IzziLayout>;
}
