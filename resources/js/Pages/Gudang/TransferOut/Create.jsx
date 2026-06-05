import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

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

    const selectClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    
    const cellSelect = 'w-full h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-xs text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';
    const cellInput = 'w-full h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-center text-xs font-bold text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';

    return (
        <GudangLayout title="Buat Transfer Keluar" pageTitle="Gudang — Buat Transfer Keluar">
            <Head title="Buat Transfer Keluar" />

            <form onSubmit={submit} className="max-w-5xl space-y-6">
                <Link href={route('gudang.transfer-out.index')} className="group inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 transition-colors hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    <Icon name="arrow_back" className="text-[18px] transition-transform group-hover:-translate-x-1" /> Batal &amp; Kembali
                </Link>

                {errors.items && (
                    <div className="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/30 dark:bg-rose-500/5">
                        <Icon name="error" className="mt-0.5 shrink-0 text-[20px] text-rose-500 dark:text-rose-455" />
                        <p className="text-sm font-semibold text-rose-600 dark:text-rose-350">{errors.items}</p>
                    </div>
                )}

                {/* Header Information */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Informasi Pengiriman (DO)</h3>
                        {fromRequest ? (
                            <span className="rounded-md border border-brand-100 bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-650 dark:border-brand-900/20 dark:bg-brand-500/10 dark:text-brand-400">
                                Request: {transferRequest.request_number}
                            </span>
                        ) : (
                            <span className="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-bold text-gray-550 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                Transfer Langsung
                            </span>
                        )}
                    </div>
                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Entitas Tujuan <span className="text-rose-500">*</span></label>
                            {fromRequest ? (
                                <div className="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm font-semibold capitalize text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                                    {data.to_entity === 'hendhys' ? 'Hendhys (Cabang)' : 'Jihans (Produksi)'}
                                </div>
                            ) : (
                                <select value={data.to_entity} onChange={(e) => setData('to_entity', e.target.value)} required className={selectClass}>
                                    <option value="">Pilih Tujuan</option>
                                    <option value="hendhys">Hendhys (Cabang/Outlet)</option>
                                    <option value="jihans">Jihans (Produksi)</option>
                                </select>
                            )}
                            {errors.to_entity && <p className="mt-1.5 text-xs font-bold text-rose-600 dark:text-rose-455">{errors.to_entity}</p>}
                        </div>

                        {data.to_entity === 'hendhys' && (
                            <div>
                                <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cabang Hendhys <span className="text-rose-500">*</span></label>
                                {fromRequest && transferRequest.from_entity === 'hendhys' ? (
                                    <div className="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                                        {transferRequest.branch ?? '-'}
                                    </div>
                                ) : (
                                    <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} required className={selectClass}>
                                        <option value="">Pilih Cabang</option>
                                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name} ({b.type})</option>)}
                                    </select>
                                )}
                                {errors.branch_id && <p className="mt-1.5 text-xs font-bold text-rose-600 dark:text-rose-455">{errors.branch_id}</p>}
                            </div>
                        )}

                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal Transfer <span className="text-rose-500">*</span></label>
                            <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required className={inputClass} />
                        </div>

                        <div className="md:col-span-3">
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan</label>
                            <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Catatan pengiriman (opsional)..." className={inputClass} />
                        </div>
                    </div>
                </div>

                {/* Line Items */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Item Produk yang Dikirim</h3>
                        {!fromRequest && (
                            <Button type="button" onClick={addItem} size="sm" startIcon={<Icon name="add" className="text-[16px]" />}>
                                Tambah Item
                            </Button>
                        )}
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-3.5 text-left w-64">Produk</th>
                                    {fromRequest && <th className="px-4 py-3.5 text-center w-32">Sisa Disetujui</th>}
                                    <th className="px-4 py-3.5 text-center w-32">Stok Gudang</th>
                                    <th className="px-4 py-3.5 text-center w-36">Qty Dikirim <span className="text-rose-500">*</span></th>
                                    <th className="px-6 py-3.5 w-24">Satuan</th>
                                    {!fromRequest && <th className="px-4 py-3.5 w-12" />}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {data.items.length === 0 ? (
                                    <tr>
                                        <td colSpan={fromRequest ? 5 : 5} className="py-12 text-center">
                                            <Icon name="inventory_2" className="mb-3 block text-[48px] text-gray-300 dark:text-gray-650" />
                                            <p className="text-sm font-bold text-gray-400 dark:text-gray-500">Belum ada item produk. Silakan tambahkan produk di atas.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    data.items.map((item, i) => {
                                        const over = Number(item.quantity) > item.stock;
                                        return (
                                            <tr key={i} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4">
                                                    {fromRequest ? (
                                                        <div className="text-sm font-semibold text-gray-850 dark:text-white/90">{item.product_name}</div>
                                                    ) : (
                                                        <select value={item.product_id} onChange={(e) => onProductChange(i, e.target.value)} required className={cellSelect}>
                                                            <option value="">Pilih produk...</option>
                                                            {products.map((p) => <option key={p.id} value={p.id}>{p.name} (Stok: {p.stock})</option>)}
                                                        </select>
                                                    )}
                                                </td>
                                                {fromRequest && (
                                                    <td className="px-4 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 tabular-nums">
                                                        {item.quantity_approved - item.quantity_sent}
                                                    </td>
                                                )}
                                                <td className="px-4 py-4 text-center">
                                                    <span className={`inline-flex rounded-lg px-2.5 py-0.5 text-xs font-bold ${over ? 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-900/30' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}`}>
                                                        {item.stock}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <input type="number" min="1" max={item.stock} step="1" required value={item.quantity}
                                                        onChange={(e) => setItem(i, { quantity: e.target.value })}
                                                        className={`${cellInput} ${over ? 'border-rose-505 focus:ring-rose-500 dark:border-rose-505 dark:focus:ring-rose-500' : ''}`} />
                                                </td>
                                                <td className="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{item.unit_name || '-'}</td>
                                                {!fromRequest && (
                                                    <td className="px-4 py-4 text-center">
                                                        <button type="button" onClick={() => removeItem(i)} className="text-gray-300 hover:text-rose-600 dark:text-gray-650 dark:hover:text-rose-455">
                                                            <Icon name="close" className="text-[18px]" />
                                                        </button>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex gap-3 items-start border-t border-gray-150 bg-gray-50/50 p-5 dark:border-gray-850 dark:bg-white/[0.01] text-xs font-semibold text-gray-500 dark:text-gray-400 leading-relaxed">
                        <Icon name="info" className="shrink-0 text-[16px] text-brand-500" />
                        <p>
                            <strong>Catatan Sistem:</strong> Menyimpan transfer keluar akan otomatis <strong>mengurangi stok Gudang Utama</strong> dan <strong>menambah stok di entitas tujuan</strong> menggunakan harga HPP terakhir.
                        </p>
                    </div>
                </div>

                <div className="flex gap-3">
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Memproses...' : 'Proses Transfer Keluar'}
                    </Button>
                    <Link href={route('gudang.transfer-out.index')}>
                        <Button variant="outline">Batal</Button>
                    </Link>
                </div>
            </form>
        </GudangLayout>
    );
}
