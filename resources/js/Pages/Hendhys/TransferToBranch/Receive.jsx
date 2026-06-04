import { Head, useForm } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function TransferToBranchReceive({ transfer: t }) {
    const initialQty = {}; const initialKondisi = {};
    t.details?.forEach(d => { initialQty[d.id] = d.quantity; initialKondisi[d.id] = 'baik'; });
    const { data, setData, post, processing, errors } = useForm({ received_quantities: initialQty, kondisi: initialKondisi, receive_notes: '', receive_kendala: '', receive_received_by_name: '', receive_pengirim_name: '' });
    const submit = (e) => { e.preventDefault(); post(route('hendhys.transfer-to-branch.receive', t.id)); };
    return (
        <HendhysLayout pageTitle="Konfirmasi Penerimaan">
            <Head title={`Terima ${t.transfer_number}`} />
            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6">
                <h2 className="text-2xl font-bold text-gray-800">Konfirmasi Penerimaan: {t.transfer_number}</h2>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                    <table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Qty Kirim</th><th className="px-4 py-3 text-center font-medium">Qty Diterima</th><th className="px-4 py-3 text-center font-medium">Kondisi</th></tr></thead>
                        <tbody className="divide-y">{t.details?.map((d) => (<tr key={d.id}><td className="px-4 py-3 font-medium text-gray-800">{d.product}</td><td className="px-4 py-3 text-center font-bold">{formatQty(d.quantity)}</td><td className="px-4 py-3 text-center"><input type="number" min="0" max={d.quantity} value={data.received_quantities[d.id] ?? 0} onChange={(e) => setData('received_quantities', { ...data.received_quantities, [d.id]: e.target.value })} className="w-20 rounded-lg border-gray-300 text-center text-sm" /></td><td className="px-4 py-3 text-center"><select value={data.kondisi[d.id] ?? 'baik'} onChange={(e) => setData('kondisi', { ...data.kondisi, [d.id]: e.target.value })} className="rounded-lg border-gray-300 text-sm"><option value="baik">Baik</option><option value="rusak">Rusak</option><option value="kurang">Kurang</option></select></td></tr>))}</tbody></table>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 pt-4"><div><label className="block text-sm font-medium text-gray-700">Catatan Penerimaan</label><textarea value={data.receive_notes} onChange={(e) => setData('receive_notes', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" rows="2" /></div><div><label className="block text-sm font-medium text-gray-700">Kendala (jika ada)</label><textarea value={data.receive_kendala} onChange={(e) => setData('receive_kendala', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" rows="2" /></div><div><label className="block text-sm font-medium text-gray-700">Nama Penerima</label><input type="text" value={data.receive_received_by_name} onChange={(e) => setData('receive_received_by_name', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div><div><label className="block text-sm font-medium text-gray-700">Nama Pengirim</label><input type="text" value={data.receive_pengirim_name} onChange={(e) => setData('receive_pengirim_name', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div></div>
                </div>
                <div className="flex justify-end gap-3"><button type="button" onClick={() => window.history.back()} className="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button><button type="submit" disabled={processing} className="rounded-xl bg-green-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700 disabled:opacity-50">{processing ? 'Menyimpan...' : 'Konfirmasi Terima'}</button></div>
            </form>
        </HendhysLayout>
    );
}
