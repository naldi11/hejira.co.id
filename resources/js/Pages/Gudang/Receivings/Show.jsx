import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';

const route = window.route;

const KONDISI = { baik: 'bg-green-100 text-green-700', rusak: 'bg-red-100 text-red-700', kurang: 'bg-yellow-100 text-yellow-700' };

function EditForm({ receiving, onCancel }) {
    const hasOrdered = receiving.details.some((d) => d.quantity_ordered !== null);
    const { data, setData, put, processing } = useForm({
        notes: receiving.notes ?? '',
        received_by_name: receiving.received_by_name ?? '',
        supplier_rep_name: receiving.supplier_rep_name ?? '',
        kendala: receiving.kendala ?? '',
        items: receiving.details.map((d) => ({ detail_id: d.id, quantity: d.quantity, kondisi: d.kondisi ?? '', hpp_price: d.hpp_price, notes: d.notes ?? '' })),
    });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const submit = (e) => { e.preventDefault(); put(route('gudang.receiving.update', receiving.id), { preserveScroll: true, onSuccess: onCancel }); };
    const inp = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300';

    return (
        <form onSubmit={submit}>
            <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div className="space-y-3 rounded-xl border border-indigo-200 bg-white p-5 shadow-sm">
                    <h3 className="text-xs font-semibold uppercase tracking-wider text-indigo-400">Edit Header</h3>
                    <div><label className="mb-1 block text-xs text-slate-500">Catatan / No. Surat Jalan</label><input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={inp} /></div>
                    <div><label className="mb-1 block text-xs text-slate-500">Nama Penerima Gudang</label><input type="text" value={data.received_by_name} onChange={(e) => setData('received_by_name', e.target.value)} placeholder="Wajib sebelum Selesaikan GRN" className={inp} /></div>
                    <div><label className="mb-1 block text-xs text-slate-500">Perwakilan Supplier</label><input type="text" value={data.supplier_rep_name} onChange={(e) => setData('supplier_rep_name', e.target.value)} placeholder="Wajib sebelum Selesaikan GRN" className={inp} /></div>
                    <div><label className="mb-1 block text-xs text-slate-500">Kendala / Catatan Masalah</label><textarea rows={2} value={data.kendala} onChange={(e) => setData('kendala', e.target.value)} className={inp} /></div>
                </div>
                <div className="space-y-2 rounded-xl border border-indigo-100 bg-indigo-50 p-5 text-sm text-indigo-700">
                    <p className="font-semibold">Catatan Edit GRN:</p>
                    <ul className="list-inside list-disc space-y-1 text-xs">
                        <li>Stok gudang otomatis disesuaikan berdasarkan selisih qty</li>
                        <li>Qty tidak boleh dikurangi melebihi stok yang tersedia</li>
                        <li>Selesaikan GRN membutuhkan Nama Penerima &amp; Perwakilan Supplier</li>
                    </ul>
                </div>
            </div>

            <div className="mb-4 overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 bg-indigo-50 p-4"><h3 className="text-sm font-semibold text-indigo-700">Edit Item Diterima</h3></div>
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Produk</th>
                                {hasOrdered && <th className="px-4 py-3 text-center font-medium">Qty PO</th>}
                                <th className="px-4 py-3 text-center font-medium">Qty Diterima</th>
                                <th className="px-4 py-3 text-center font-medium">Satuan</th>
                                <th className="px-4 py-3 text-center font-medium">Kondisi</th>
                                <th className="px-4 py-3 text-right font-medium">Harga Beli</th>
                                <th className="px-4 py-3 text-left font-medium">Catatan</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {receiving.details.map((d, i) => (
                                <tr key={d.id}>
                                    <td className="px-4 py-2 font-medium text-slate-800">{d.product}</td>
                                    {hasOrdered && <td className="px-4 py-2 text-center text-xs text-slate-400">{d.quantity_ordered !== null ? formatQty(d.quantity_ordered) : '-'}</td>}
                                    <td className="px-4 py-2"><input type="number" min="0" step="0.001" required value={data.items[i].quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} className="w-24 rounded-lg border border-slate-200 px-2 py-1.5 text-center text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300" /></td>
                                    <td className="px-4 py-2 text-center text-xs text-slate-500">{d.unit}</td>
                                    <td className="px-4 py-2">
                                        <select value={data.items[i].kondisi} onChange={(e) => setItem(i, { kondisi: e.target.value })} className="rounded-lg border border-slate-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                            <option value="">-</option><option value="baik">Baik</option><option value="rusak">Rusak</option><option value="kurang">Kurang</option>
                                        </select>
                                    </td>
                                    <td className="px-4 py-2"><input type="number" min="0" step="0.01" required value={data.items[i].hpp_price} onChange={(e) => setItem(i, { hpp_price: e.target.value })} className="w-32 rounded-lg border border-slate-200 px-2 py-1.5 text-right text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300" /></td>
                                    <td className="px-4 py-2"><input type="text" value={data.items[i].notes} onChange={(e) => setItem(i, { notes: e.target.value })} placeholder="Catatan..." className="w-40 rounded-lg border border-slate-200 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-300" /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="flex gap-3">
                <button type="submit" disabled={processing} className="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Simpan Perubahan</button>
                <button type="button" onClick={onCancel} className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Batal</button>
            </div>
        </form>
    );
}

function PhotoUpload({ receiving }) {
    const { data, setData, post, processing, errors, reset } = useForm({ photos: [], caption: '' });
    const submit = (e) => { e.preventDefault(); post(route('gudang.receiving.photos.store', receiving.id), { forceFormData: true, preserveScroll: true, onSuccess: () => reset() }); };
    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
            <div>
                <label className="mb-1 block text-xs text-slate-500">Upload Foto (maks. {10 - receiving.photos.length} lagi)</label>
                <input type="file" accept="image/*" multiple required onChange={(e) => setData('photos', Array.from(e.target.files))} className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm" />
            </div>
            <div>
                <label className="mb-1 block text-xs text-slate-500">Keterangan (opsional)</label>
                <input type="text" value={data.caption} onChange={(e) => setData('caption', e.target.value)} placeholder="Contoh: Kondisi kardus..." className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
            </div>
            <button type="submit" disabled={processing} className="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">Upload Foto</button>
            {errors.photos && <p className="w-full text-xs text-rose-500">{errors.photos}</p>}
        </form>
    );
}

export default function ReceivingShow({ receiving }) {
    const [editMode, setEditMode] = useState(false);
    const { errors } = usePage().props;
    const hasOrdered = receiving.details.some((d) => d.quantity_ordered !== null);

    const close = () => {
        if (window.confirm('Tutup GRN ini? Setelah ditutup tidak dapat diedit lagi.')) {
            router.post(route('gudang.receiving.close', receiving.id), {}, { preserveScroll: true });
        }
    };
    const deletePhoto = (photoId) => {
        if (window.confirm('Hapus foto ini?')) {
            router.delete(route('gudang.receiving.photos.destroy', [receiving.id, photoId]), { preserveScroll: true });
        }
    };

    const btn = 'rounded-lg px-3 py-1.5 text-sm font-medium text-white flex items-center gap-1.5 transition-colors';

    return (
        <GudangLayout title={`Detail GRN ${receiving.grn_number}`} pageTitle={`Penerimaan Barang — ${receiving.grn_number}`}>
            <Head title={receiving.grn_number} />

            <div className="mx-auto max-w-5xl space-y-4">
                {errors?.close && <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{errors.close}</div>}

                <div className="flex flex-wrap items-center gap-2">
                    <Link href={route('gudang.receiving.index')} className="text-sm text-slate-500 hover:text-slate-700">← Kembali</Link>
                    <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ${receiving.is_open ? 'border border-yellow-300 bg-yellow-100 text-yellow-700' : 'border border-green-300 bg-green-100 text-green-700'}`}>{receiving.is_open ? 'TERBUKA' : 'SELESAI'}</span>
                    {receiving.is_open && (
                        <button onClick={() => setEditMode((v) => !v)} className={`${btn} ${editMode ? 'bg-slate-600 hover:bg-slate-700' : 'bg-indigo-600 hover:bg-indigo-700'}`}>{editMode ? 'Batal Edit' : 'Edit GRN'}</button>
                    )}
                    <a href={route('gudang.receiving.print', receiving.id)} target="_blank" rel="noreferrer" className={`${btn} bg-slate-800 hover:bg-slate-900`}><Icon name="print" className="text-[16px]" /> Cetak BAST</a>
                    {receiving.is_open ? (
                        <button onClick={close} className={`${btn} bg-green-600 hover:bg-green-700`}><Icon name="check" className="text-[16px]" /> Selesaikan GRN</button>
                    ) : (
                        <span className="text-xs text-slate-400">Ditutup: {receiving.closed_at} oleh {receiving.closed_by}</span>
                    )}
                </div>

                {editMode ? (
                    <EditForm receiving={receiving} onCancel={() => setEditMode(false)} />
                ) : (
                    <>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Supplier</h3>
                                <div className="space-y-2 text-sm">
                                    <div><p className="text-xs text-slate-400">Supplier</p><p className="font-medium text-slate-800">{receiving.supplier}</p></div>
                                    <div><p className="text-xs text-slate-400">Perwakilan Supplier</p><p className="text-slate-800">{receiving.supplier_rep_name || '-'}</p></div>
                                </div>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Penerimaan</h3>
                                <div className="space-y-2 text-sm">
                                    <div><p className="text-xs text-slate-400">No. GRN</p><p className="font-bold text-slate-800">{receiving.grn_number}</p></div>
                                    <div><p className="text-xs text-slate-400">Tanggal</p><p className="text-slate-800">{formatDate(receiving.date)}</p></div>
                                    <div><p className="text-xs text-slate-400">Referensi PO</p><p className="text-slate-800">{receiving.po ? <Link href={route('gudang.po.show', receiving.po.id)} className="text-indigo-600 hover:underline">{receiving.po.po_number}</Link> : 'Penerimaan Langsung'}</p></div>
                                    <div><p className="text-xs text-slate-400">Catatan / No. Surat Jalan</p><p className="text-slate-800">{receiving.notes || '-'}</p></div>
                                    <div><p className="text-xs text-slate-400">Diterima Oleh (Gudang)</p><p className="text-slate-800">{receiving.received_by_name || '-'}</p></div>
                                </div>
                            </div>
                        </div>

                        {receiving.kendala && (
                            <div className="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm">
                                <p className="mb-1 text-xs font-semibold uppercase tracking-wider text-orange-500">Kendala / Catatan Masalah</p>
                                <p className="text-orange-800">{receiving.kendala}</p>
                            </div>
                        )}

                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex items-center justify-between border-b border-slate-100 p-4">
                                <h3 className="text-sm font-semibold text-slate-700">Item Produk Diterima</h3>
                                <span className="text-sm font-bold text-slate-900">{formatRupiah(receiving.grand_total)}</span>
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 text-xs text-slate-500">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Produk</th>
                                            {hasOrdered && <th className="px-4 py-3 text-center font-medium">Qty PO</th>}
                                            <th className="px-4 py-3 text-center font-medium">Qty Terima</th>
                                            <th className="px-4 py-3 text-center font-medium">Satuan</th>
                                            <th className="px-4 py-3 text-center font-medium">Kondisi</th>
                                            <th className="px-4 py-3 text-right font-medium">Harga Beli</th>
                                            <th className="px-4 py-3 text-right font-medium">Total</th>
                                            <th className="px-4 py-3 font-medium">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {receiving.details.map((item) => (
                                            <tr key={item.id} className="hover:bg-slate-50">
                                                <td className="px-4 py-3 font-medium text-slate-800">{item.product}</td>
                                                {hasOrdered && <td className="px-4 py-3 text-center text-xs text-slate-400">{item.quantity_ordered !== null ? formatQty(item.quantity_ordered) : '-'}</td>}
                                                <td className="px-4 py-3 text-center font-semibold">{formatQty(item.quantity)}</td>
                                                <td className="px-4 py-3 text-center text-slate-500">{item.unit}</td>
                                                <td className="px-4 py-3 text-center">{item.kondisi ? <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${KONDISI[item.kondisi] ?? ''}`}>{item.kondisi}</span> : <span className="text-slate-300">—</span>}</td>
                                                <td className="px-4 py-3 text-right">{formatRupiah(item.hpp_price)}</td>
                                                <td className="px-4 py-3 text-right font-medium text-slate-800">{formatRupiah(item.total)}</td>
                                                <td className="px-4 py-3 text-xs text-slate-500">{item.notes || '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </>
                )}

                {/* Photos */}
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 p-4">
                        <h3 className="text-sm font-semibold text-slate-700">Foto Bukti Penerimaan</h3>
                        <span className="text-xs text-slate-400">{receiving.photos.length} / 10 foto</span>
                    </div>
                    {receiving.photos.length > 0 ? (
                        <div className="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4">
                            {receiving.photos.map((photo) => (
                                <div key={photo.id} className="group relative">
                                    <img src={photo.url} alt={photo.caption ?? ''} onClick={() => window.open(photo.url, '_blank')} className="h-28 w-full cursor-pointer rounded-lg border border-slate-200 object-cover" />
                                    {photo.caption && <p className="mt-1 truncate text-xs text-slate-500">{photo.caption}</p>}
                                    {receiving.is_open && (
                                        <button onClick={() => deletePhoto(photo.id)} className="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-xs text-white opacity-0 transition-opacity hover:bg-rose-700 group-hover:opacity-100">✕</button>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-6 text-center text-sm text-slate-400">Belum ada foto bukti.</div>
                    )}
                    {receiving.is_open && receiving.photos.length < 10 && <PhotoUpload receiving={receiving} />}
                </div>
            </div>
        </GudangLayout>
    );
}
