import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';

const route = window.route;

const todayISO = () => new Date().toISOString().slice(0, 10);

export default function TransferOutCreate({ products, branches, transferRequest }) {
    const fromRequest = !!transferRequest;

    const { data, setData, post, processing, errors } = useForm({
        request_id: transferRequest?.id ?? null,
        to_entity: transferRequest?.from_entity ?? '',
        branch_id: transferRequest?.from_entity === 'hendhys' ? (transferRequest?.branch_id ?? '') : '',
        date: todayISO(),
        notes: '',
        items: fromRequest ? transferRequest.items.map((it) => ({ ...it })) : [],
    });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const addItem = () => setData('items', [...data.items, { product_id: '', product_name: '', stock: 0, quantity: 1, unit_id: '', unit_name: '', hpp_price: 0 }]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));

    const onProductChange = (i, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        setItem(i, p
            ? { product_id: p.id, product_name: p.name, stock: p.stock, unit_id: p.unit_id, unit_name: p.unit_name, hpp_price: p.hpp }
            : { product_id: '', product_name: '', stock: 0, unit_id: '', unit_name: '', hpp_price: 0 });
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('gudang.transfer-out.store'));
    };

    const inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300';

    return (
        <GudangLayout title="Buat Transfer Keluar" pageTitle="Gudang — Buat Transfer Keluar">
            <Head title="Buat Transfer Keluar" />

            <form onSubmit={submit} className="max-w-5xl space-y-4">
                {errors.items && (
                    <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{errors.items}</div>
                )}

                {/* Header */}
                <div className="rounded-xl border border-slate-200 bg-white p-5">
                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Pengiriman (DO)</p>
                        {fromRequest ? (
                            <span className="rounded border border-indigo-100 bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">Berdasarkan Request: {transferRequest.request_number}</span>
                        ) : (
                            <span className="rounded border border-slate-200 bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">Transfer Langsung</span>
                        )}
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">Entitas Tujuan <span className="text-rose-500">*</span></label>
                            {fromRequest ? (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm capitalize text-slate-700">{data.to_entity === 'hendhys' ? 'Hendhys (Cabang)' : 'Jihans (Produksi)'}</div>
                            ) : (
                                <select value={data.to_entity} onChange={(e) => setData('to_entity', e.target.value)} required className={inputClass}>
                                    <option value="">Pilih Tujuan</option>
                                    <option value="hendhys">Hendhys (Cabang/Outlet)</option>
                                    <option value="jihans">Jihans (Produksi)</option>
                                </select>
                            )}
                            {errors.to_entity && <p className="mt-1 text-xs font-bold text-rose-600">{errors.to_entity}</p>}
                        </div>

                        {data.to_entity === 'hendhys' && (
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">Cabang Hendhys <span className="text-rose-500">*</span></label>
                                {fromRequest && transferRequest.from_entity === 'hendhys' ? (
                                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">{transferRequest.branch ?? '-'}</div>
                                ) : (
                                    <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} required className={inputClass}>
                                        <option value="">Pilih Cabang</option>
                                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name} ({b.type})</option>)}
                                    </select>
                                )}
                                {errors.branch_id && <p className="mt-1 text-xs font-bold text-rose-600">{errors.branch_id}</p>}
                            </div>
                        )}

                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">Tanggal Transfer <span className="text-rose-500">*</span></label>
                            <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required className={inputClass} />
                        </div>

                        <div className="col-span-3">
                            <label className="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                            <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Catatan pengiriman (opsional)..." className={inputClass} />
                        </div>
                    </div>
                </div>

                {/* Line items */}
                <div className="rounded-xl border border-slate-200 bg-white p-5">
                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Item Produk yang Dikirim</p>
                        {!fromRequest && (
                            <button type="button" onClick={addItem} className="flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                <Icon name="add" className="text-[16px]" /> Tambah Item
                            </button>
                        )}
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-xs text-slate-500">
                                    <th className="w-64 pb-2 text-left">Produk</th>
                                    {fromRequest && <th className="w-32 pb-2 text-center">Sisa Disetujui</th>}
                                    <th className="w-32 pb-2 text-center">Stok Gudang</th>
                                    <th className="w-32 pb-2 text-center">Qty Dikirim <span className="text-rose-500">*</span></th>
                                    <th className="w-20 pb-2 text-left">Satuan</th>
                                    {!fromRequest && <th className="w-8 pb-2" />}
                                </tr>
                            </thead>
                            <tbody>
                                {data.items.length === 0 ? (
                                    <tr><td colSpan={5} className="py-4 text-center text-xs text-slate-400">Belum ada item produk.</td></tr>
                                ) : (
                                    data.items.map((item, i) => {
                                        const over = Number(item.quantity) > item.stock;
                                        return (
                                            <tr key={i} className="border-b border-slate-50">
                                                <td className="py-2 pr-2">
                                                    {fromRequest ? (
                                                        <div className="text-sm font-medium text-slate-800">{item.product_name}</div>
                                                    ) : (
                                                        <select value={item.product_id} onChange={(e) => onProductChange(i, e.target.value)} required
                                                            className="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                                            <option value="">Pilih produk...</option>
                                                            {products.map((p) => <option key={p.id} value={p.id}>{p.name} (Stok: {p.stock})</option>)}
                                                        </select>
                                                    )}
                                                </td>
                                                {fromRequest && (
                                                    <td className="px-2 py-2 text-center text-xs font-medium text-slate-600">{item.quantity_approved - item.quantity_sent}</td>
                                                )}
                                                <td className="px-2 py-2 text-center">
                                                    <span className={`rounded px-2 py-0.5 text-xs font-medium ${over ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'}`}>{item.stock}</span>
                                                </td>
                                                <td className="px-2 py-2">
                                                    <input type="number" min="1" max={item.stock} step="1" required value={item.quantity}
                                                        onChange={(e) => setItem(i, { quantity: e.target.value })}
                                                        className={`w-full rounded-lg border px-2 py-1.5 text-center text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300 ${over ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 focus:border-indigo-500'}`} />
                                                </td>
                                                <td className="px-2 py-2"><span className="font-mono text-xs text-slate-500">{item.unit_name || '-'}</span></td>
                                                {!fromRequest && (
                                                    <td className="py-2 pl-2">
                                                        <button type="button" onClick={() => removeItem(i)} className="text-rose-400 hover:text-rose-600"><Icon name="close" className="text-[18px]" /></button>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
                        <strong>Catatan Sistem:</strong> Menyimpan transfer keluar akan otomatis <strong>mengurangi stok Gudang Utama</strong> dan <strong>menambah stok di entitas tujuan</strong> menggunakan harga HPP terakhir.
                    </div>
                </div>

                <div className="flex gap-3">
                    <button type="submit" disabled={processing} className="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-50">
                        {processing ? 'Memproses...' : 'Proses Transfer Keluar'}
                    </button>
                    <Link href={route('gudang.transfer-out.index')} className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-50">Batal</Link>
                </div>
            </form>
        </GudangLayout>
    );
}
