import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import EmptyState from '@/Components/EmptyState';
import { input, Errors, Search, Paging } from './Common';

const route = window.route;

function EditPackagingModal({ ingredient, onClose }) {
    const form = useForm({
        product_id: ingredient.id,
        purchase_unit: ingredient.purchase_unit,
        purchase_factor: ingredient.purchase_factor,
    });

    return (
        <Modal show={true} onClose={onClose} maxWidth="md">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(route('izzi.ingredients.update', ingredient.id), { onSuccess: onClose });
                }}
                className="p-6"
            >
                <div className="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">Atur Kemasan Bahan Baku</h3>
                        <p className="text-xs text-gray-500">
                            {ingredient.name} ({ingredient.code}) • Satuan dasar: <strong>{ingredient.unit}</strong>
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <Icon name="close" className="text-[20px]" />
                    </button>
                </div>

                <div className="mt-4 space-y-4">
                    <Errors errors={form.errors} />

                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama Satuan Kemasan Beli
                        <input
                            required
                            maxLength={40}
                            placeholder="Contoh: Pak, Dus, Botol, Karung"
                            className={`${input} mt-1.5`}
                            value={form.data.purchase_unit}
                            onChange={(e) => form.setData('purchase_unit', e.target.value)}
                        />
                    </label>

                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Isi per 1 Kemasan (dalam {ingredient.unit})
                        <input
                            required
                            type="number"
                            min="0.001"
                            step="0.001"
                            placeholder="Contoh: 2000"
                            className={`${input} mt-1.5 font-bold`}
                            value={form.data.purchase_factor}
                            onChange={(e) => form.setData('purchase_factor', e.target.value)}
                        />
                    </label>

                    <div className="rounded-xl bg-brand-50 p-3 text-xs text-brand-800 dark:bg-brand-500/10 dark:text-brand-300">
                        Konversi: <strong>1 {form.data.purchase_unit || 'Kemasan'} = {form.data.purchase_factor || '?'} {ingredient.unit}</strong>
                    </div>
                </div>

                <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-xl bg-brand-500 px-5 py-2 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-40"
                    >
                        {form.processing ? 'Menyimpan…' : 'Simpan Kemasan'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

export default function Ingredients({ ingredients, filters }) {
    const [editing, setEditing] = useState(null);

    return (
        <IzziLayout pageTitle="Satuan Kemasan Bahan Izzi">
            <Head title="Satuan Kemasan Bahan" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                        Satuan Kemasan Bahan Baku
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Atur konversi kemasan beli (misal: 1 Pak = 2000 gram) untuk memudahkan input penerimaan bahan
                    </p>
                </div>

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            path="izzi.ingredients"
                            filters={filters}
                            placeholder="Cari nama atau kode produk bahan…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-3.5">Bahan Baku</th>
                                    <th className="px-6 py-3.5">Satuan Dasar Resep</th>
                                    <th className="px-6 py-3.5">Konversi Kemasan Beli</th>
                                    <th className="px-6 py-3.5 text-center">Status</th>
                                    <th className="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {ingredients.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={5}
                                        icon="tune"
                                        message="Tidak ada data bahan baku ditemukan."
                                    />
                                ) : (
                                    ingredients.data.map((p) => (
                                        <tr
                                            key={p.id}
                                            className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="px-6 py-4">
                                                <p className="font-semibold text-gray-800 dark:text-white">{p.name}</p>
                                                <p className="font-mono text-xs text-gray-400">{p.code}</p>
                                            </td>
                                            <td className="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                                {p.unit}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                {p.configured ? (
                                                    <span className="inline-flex rounded-lg bg-brand-50 px-2.5 py-1 font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                                        1 {p.purchase_unit} = {p.purchase_factor} {p.unit}
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-400">
                                                        Mengikuti satuan dasar ({p.unit})
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                        p.status === 'active'
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-800'
                                                    }`}
                                                >
                                                    {p.status === 'active' ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right">
                                                <button
                                                    type="button"
                                                    disabled={p.status !== 'active'}
                                                    onClick={() => setEditing(p)}
                                                    className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                >
                                                    <Icon name="tune" className="text-[15px]" />
                                                    <span>Atur Kemasan</span>
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={ingredients} />
                </div>
            </div>

            {editing && <EditPackagingModal key={editing.id} ingredient={editing} onClose={() => setEditing(null)} />}
        </IzziLayout>
    );
}
