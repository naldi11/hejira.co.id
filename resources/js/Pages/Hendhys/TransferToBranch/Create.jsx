import { Head, useForm } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function TransferToBranchCreate({ branches, products, branchRequest }) {
    const initial = branchRequest ? { request_id: branchRequest.id, branch_id: branchRequest.branch_id, date: new Date().toISOString().slice(0, 10), notes: '', items: branchRequest.details.map(d => ({ product_id: d.product_id, quantity: d.quantity_requested, unit_id: d.unit_id, detail_id: d.id })) } : { branch_id: '', date: new Date().toISOString().slice(0, 10), notes: '', items: [{ product_id: '', quantity: 1, unit_id: '' }] };
    const { data, setData, post, processing, errors } = useForm(initial);
    const addItem = () => setData('items', [...data.items, { product_id: '', quantity: 1, unit_id: '' }]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));
    const updateItem = (i, field, value) => { const items = [...data.items]; items[i][field] = value; setData('items', items); };
    const submit = (e) => { e.preventDefault(); post(route('hendhys.transfer-to-branch.store')); };
    return (
        <HendhysLayout pageTitle="Distribusi ke Cabang">
            <Head title="Distribusi Baru" />
            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6">
                <h2 className="text-2xl font-bold text-gray-800">{branchRequest ? `Proses Request ${branchRequest.request_number}` : 'Distribusi Manual ke Cabang'}</h2>
                {branchRequest && <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm"><strong>Request dari:</strong> {branchRequest.branch} — {branchRequest.request_number}</div>}
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {!branchRequest && <div><label className="block text-sm font-medium text-gray-700">Cabang Tujuan</label><select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">-- Pilih --</option>{branches?.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select>{errors.branch_id && <p className="mt-1 text-xs text-red-500">{errors.branch_id}</p>}</div>}
                        <div><label className="block text-sm font-medium text-gray-700">Tanggal</label><input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label className="block text-sm font-medium text-gray-700">Catatan</label><input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <div className="pt-4"><h3 className="font-semibold text-gray-800 mb-3">Item Distribusi</h3>{data.items.map((item, i) => (<div key={i} className="mb-3 flex flex-wrap items-end gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3"><div className="min-w-[200px] flex-1"><label className="text-xs text-gray-500">Produk</label>{branchRequest ? <div className="mt-1 text-sm font-medium">{branchRequest.details[i]?.product}</div> : <select value={item.product_id} onChange={(e) => updateItem(i, 'product_id', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">-- Pilih --</option>{products?.map((p) => <option key={p.id} value={p.id}>{p.name} (stok: {formatQty(p.current_stock)})</option>)}</select>}</div><div className="w-28"><label className="text-xs text-gray-500">Qty</label><input type="number" min="0" value={item.quantity} onChange={(e) => updateItem(i, 'quantity', e.target.value)} className="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>{!branchRequest && data.items.length > 1 && <button type="button" onClick={() => removeItem(i)} className="rounded-lg bg-red-50 p-2 text-red-500 hover:bg-red-100"><Icon name="delete" className="text-[20px]" /></button>}</div>))}{!branchRequest && <button type="button" onClick={addItem} className="mt-2 inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"><Icon name="add" className="text-[18px]" /> Tambah</button>}</div>
                </div>
                <div className="flex justify-end gap-3"><button type="button" onClick={() => window.history.back()} className="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button><button type="submit" disabled={processing} className="rounded-xl bg-amber-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50">{processing ? 'Mengirim...' : 'Kirim Distribusi'}</button></div>
            </form>
        </HendhysLayout>
    );
}
