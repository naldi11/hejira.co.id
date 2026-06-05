import { Head, Link, useForm } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function TransferToBranchCreate({ branches, products, branchRequest }) {
    const initial = branchRequest
        ? {
              request_id: branchRequest.id,
              branch_id:  branchRequest.branch_id,
              date:       new Date().toISOString().slice(0, 10),
              notes:      '',
              items:      branchRequest.details.map(d => ({
                  product_id: d.product_id,
                  quantity:   d.quantity_requested,
                  unit_id:    d.unit_id,
                  detail_id:  d.id,
              })),
          }
        : {
              branch_id: '',
              date:      new Date().toISOString().slice(0, 10),
              notes:     '',
              items:     [{ product_id: '', quantity: 1, unit_id: '' }],
          };

    const { data, setData, post, processing, errors } = useForm(initial);

    const addItem    = () => setData('items', [...data.items, { product_id: '', quantity: 1, unit_id: '' }]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));
    const updateItem = (i, field, value) => {
        const items = [...data.items];
        items[i][field] = value;
        setData('items', items);
    };

    const submit = (e) => { e.preventDefault(); post(route('hendhys.transfer-to-branch.store')); };

    const fieldClass = 'w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3.5 py-2.5 text-sm text-gray-800 dark:text-white/90 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-sm transition-all';

    return (
        <HendhysLayout pageTitle="Distribusi ke Cabang">
            <Head title="Distribusi Baru" />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-5">
                <h2 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                    {branchRequest ? `Proses Request ${branchRequest.request_number}` : 'Distribusi Manual ke Cabang'}
                </h2>

                {/* Info Banner jika dari request */}
                {branchRequest && (
                    <div className="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                        <Icon name="info" className="shrink-0 text-[20px] text-amber-500" />
                        <span className="text-amber-800 dark:text-amber-300">
                            Memproses request dari <strong>{branchRequest.branch}</strong> — {branchRequest.request_number}
                        </span>
                    </div>
                )}

                {/* Form utama */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    {/* Header fields */}
                    <div className="border-b border-gray-100 dark:border-gray-800 bg-amber-50/10 dark:bg-amber-500/[0.01] p-6">
                        <div className={`grid gap-5 ${!branchRequest ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1 md:grid-cols-2'}`}>
                            {/* Cabang (hanya untuk distribusi manual) */}
                            {!branchRequest && (
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Cabang Tujuan <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={data.branch_id}
                                        onChange={(e) => setData('branch_id', e.target.value)}
                                        className={fieldClass}
                                    >
                                        <option value="" className="dark:bg-gray-800">-- Pilih Cabang --</option>
                                        {branches?.map(b => (
                                            <option key={b.id} value={b.id} className="dark:bg-gray-800">{b.name}</option>
                                        ))}
                                    </select>
                                    {errors.branch_id && <p className="mt-1 text-xs text-red-500">{errors.branch_id}</p>}
                                </div>
                            )}

                            {/* Tanggal */}
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Tanggal <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    required
                                    value={data.date}
                                    onChange={(e) => setData('date', e.target.value)}
                                    className={fieldClass}
                                />
                                {errors.date && <p className="mt-1 text-xs text-red-500">{errors.date}</p>}
                            </div>

                            {/* Catatan */}
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Catatan
                                </label>
                                <input
                                    type="text"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className={fieldClass}
                                    placeholder="Opsional"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Item distribusi */}
                    <div className="p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-base font-bold text-gray-800 dark:text-white/90">Item Distribusi</h3>
                            {!branchRequest && (
                                <button
                                    type="button"
                                    onClick={addItem}
                                    className="flex items-center gap-1 rounded-lg bg-amber-50 dark:bg-amber-500/10 px-3 py-1.5 text-sm font-medium text-amber-600 dark:text-amber-400 transition-colors hover:bg-amber-100 dark:hover:bg-amber-500/20"
                                >
                                    <Icon name="add" className="text-[16px]" /> Tambah Baris
                                </button>
                            )}
                        </div>

                        {errors.items && <p className="mb-3 text-sm text-red-500 dark:text-red-400">{errors.items}</p>}

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-y border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="px-4 py-3">Produk</th>
                                        <th className="w-40 px-4 py-3">Qty Distribusi</th>
                                        {!branchRequest && <th className="w-16 px-4 py-3 text-center">Aksi</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {data.items.map((item, i) => (
                                        <tr key={i} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            {/* Produk */}
                                            <td className="px-4 py-4">
                                                {branchRequest ? (
                                                    <div className="flex flex-col">
                                                        <span className="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                            {branchRequest.details[i]?.product}
                                                        </span>
                                                        <span className="font-mono text-xs text-gray-400 dark:text-gray-500">
                                                            {branchRequest.details[i]?.product_code}
                                                        </span>
                                                        <span className="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                                            Diminta: {formatQty(branchRequest.details[i]?.quantity_requested)} {branchRequest.details[i]?.unit}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <select
                                                        required
                                                        value={item.product_id}
                                                        onChange={(e) => updateItem(i, 'product_id', e.target.value)}
                                                        className={fieldClass}
                                                    >
                                                        <option value="" className="dark:bg-gray-800">-- Pilih Produk --</option>
                                                        {products?.map(p => (
                                                            <option key={p.id} value={p.id} className="dark:bg-gray-800">
                                                                {p.name} (stok: {formatQty(p.current_stock)})
                                                            </option>
                                                        ))}
                                                    </select>
                                                )}
                                            </td>

                                            {/* Qty */}
                                            <td className="px-4 py-4">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    required
                                                    value={item.quantity}
                                                    onChange={(e) => updateItem(i, 'quantity', e.target.value)}
                                                    className={fieldClass}
                                                    placeholder="0"
                                                />
                                                {errors[`items.${i}.quantity`] && (
                                                    <p className="mt-1 text-xs text-red-500">{errors[`items.${i}.quantity`]}</p>
                                                )}
                                            </td>

                                            {/* Hapus (hanya distribusi manual) */}
                                            {!branchRequest && (
                                                <td className="px-4 py-4 text-center">
                                                    {data.items.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeItem(i)}
                                                            className="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-500/10"
                                                        >
                                                            <Icon name="delete" className="text-[20px]" />
                                                        </button>
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {/* Footer actions */}
                <div className="flex justify-end gap-3">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 dark:hover:bg-white/[0.05] transition-colors"
                    >
                        <Icon name="arrow_back" className="text-[18px]" /> Batal
                    </button>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50 transition-colors"
                    >
                        <Icon name="local_shipping" className="text-[18px]" />
                        {processing ? 'Memproses...' : 'Kirim Distribusi'}
                    </button>
                </div>
            </form>
        </HendhysLayout>
    );
}
