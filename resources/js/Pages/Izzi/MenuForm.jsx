import { Head, Link, useForm } from '@inertiajs/react';
import { useIngredients } from './IngredientCatalogue';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import SelectField from '@/Components/SelectField';
import { input, Errors, money } from './Common';

const route = window.route;
const empty = () => ({ product_id: '', quantity: '' });

export default function MenuForm({ menu, ingredients: initial, catalogue: page, selectedIngredients = [], versions }) {
    const form = useForm(
        menu
            ? {
                  code: menu.code,
                  name: menu.name,
                  category: menu.category,
                  price: menu.price,
                  is_active: Boolean(menu.is_active),
                  recipe: menu.recipe,
              }
            : { code: '', name: '', category: '', price: '', is_active: true, recipe: [empty()] },
    );

    const { catalogue, ingredients } = useIngredients(
        initial,
        page,
        form.data.recipe.map((r) => r.product_id),
        selectedIngredients,
    );

    const set = (index, key, value) =>
        form.setData(
            'recipe',
            form.data.recipe.map((r, i) => (i === index ? { ...r, [key]: value } : r)),
        );

    return (
        <IzziLayout pageTitle={menu ? 'Edit Menu & Resep' : 'Tambah Menu Baru'}>
            <Head title={menu ? 'Edit Menu & Resep' : 'Tambah Menu Baru'} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <Link
                            href={route('izzi.menus')}
                            className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[15px]" />
                            <span>Kembali ke Daftar Menu</span>
                        </Link>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            {menu ? `Edit Menu — ${menu.name}` : 'Tambah Menu & Resep Baru'}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Atur harga jual menu dan takaran bahan baku untuk setiap 1 porsi
                        </p>
                    </div>
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        menu
                            ? form.put(route('izzi.menus.update', menu.id))
                            : form.post(route('izzi.menus.store'));
                    }}
                    className="space-y-6"
                >
                    <Errors errors={form.errors} />

                    {/* Card 1: Identitas & Harga Menu */}
                    <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="mb-4 text-sm font-bold uppercase tracking-wider text-gray-400">
                            Informasi Menu & Harga Jual
                        </h3>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Kode Menu
                                <input
                                    required
                                    placeholder="Contoh: KBB-01"
                                    className={`${input} mt-1.5`}
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value)}
                                />
                            </label>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nama Menu
                                <input
                                    required
                                    placeholder="Contoh: Kebab Sapi Spesial"
                                    className={`${input} mt-1.5`}
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                />
                            </label>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Kategori
                                <input
                                    required
                                    placeholder="Contoh: Kebab / Minuman"
                                    className={`${input} mt-1.5`}
                                    value={form.data.category}
                                    onChange={(e) => form.setData('category', e.target.value)}
                                />
                            </label>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Harga Jual (Rp)
                                <input
                                    required
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="18000"
                                    className={`${input} mt-1.5 font-bold`}
                                    value={form.data.price}
                                    onChange={(e) => form.setData('price', e.target.value)}
                                />
                            </label>
                        </div>

                        <label className="mt-4 inline-flex cursor-pointer items-center gap-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                            />
                            <span>Tampilkan menu ini sebagai menu aktif di Kasir POS</span>
                        </label>
                    </div>

                    {/* Card 2: Komposisi Resep Bahan Baku */}
                    <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <div>
                                <h3 className="font-semibold text-gray-800 dark:text-white/90">
                                    Resep Bahan Baku (Takaran untuk 1 Porsi)
                                </h3>
                                <p className="text-xs text-gray-500">
                                    Ketik nama bahan pada kolom pilihan untuk mencari dari Master Produk bersama
                                </p>
                            </div>
                            <button
                                type="button"
                                disabled={form.data.recipe.length >= 100}
                                onClick={() => form.setData('recipe', [...form.data.recipe, empty()])}
                                className="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3.5 py-2 text-xs font-bold text-brand-600 transition hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400"
                            >
                                <Icon name="add" className="text-[16px]" />
                                <span>Tambah Bahan Resep</span>
                            </button>
                        </div>

                        <div className="divide-y divide-gray-100 p-6 dark:divide-gray-800">
                            {form.data.recipe.map((r, i) => {
                                const ingredient = ingredients.find((p) => String(p.id) === String(r.product_id));
                                return (
                                    <div
                                        key={i}
                                        className="grid grid-cols-1 items-start gap-4 py-4 first:pt-0 last:pb-0 lg:grid-cols-12"
                                    >
                                        <div className="lg:col-span-7">
                                            <label className="mb-1 block text-xs font-semibold text-gray-500">
                                                Bahan Resep #{i + 1}
                                            </label>
                                            <SelectField
                                                required
                                                isLoading={catalogue.loading}
                                                filterOption={() => true}
                                                onInputChange={(val, meta) => {
                                                    if (meta.action === 'input-change') catalogue.setQuery(val);
                                                }}
                                                options={ingredients.map((p) => ({
                                                    value: p.id,
                                                    label: `${p.name} (${p.code})`,
                                                    sublabel: `Satuan resep: ${p.unit}`,
                                                    isDisabled:
                                                        p.status !== 'active' ||
                                                        form.data.recipe.some(
                                                            (other, n) =>
                                                                n !== i && String(other.product_id) === String(p.id),
                                                        ),
                                                }))}
                                                value={r.product_id}
                                                onChange={(value) => set(i, 'product_id', value)}
                                                placeholder="Ketik & pilih bahan baku…"
                                            />
                                        </div>

                                        <div className="lg:col-span-4">
                                            <label className="mb-1 block text-xs font-semibold text-gray-500">
                                                Takaran per 1 Porsi ({ingredient?.unit || 'Satuan Dasar'})
                                            </label>
                                            <div className="relative">
                                                <input
                                                    required
                                                    type="number"
                                                    min="0.001"
                                                    step="0.001"
                                                    placeholder="Contoh: 45"
                                                    className={`${input} pr-16 font-bold`}
                                                    value={r.quantity}
                                                    onChange={(e) => set(i, 'quantity', e.target.value)}
                                                />
                                                {ingredient?.unit && (
                                                    <span className="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">
                                                        {ingredient.unit}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-end justify-end pt-6 lg:col-span-1">
                                            {form.data.recipe.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        form.setData(
                                                            'recipe',
                                                            form.data.recipe.filter((_, n) => n !== i),
                                                        )
                                                    }
                                                    className="rounded-lg p-2 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                    title="Hapus bahan"
                                                >
                                                    <Icon name="delete" className="text-[20px]" />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.01]">
                            <Link
                                href={route('izzi.menus')}
                                className="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-40"
                            >
                                <Icon name="check_circle" className="text-[18px]" />
                                <span>{form.processing ? 'Menyimpan…' : 'Simpan Menu & Resep'}</span>
                            </button>
                        </div>
                    </div>
                </form>

                {/* Riwayat Versi Resep */}
                {versions.length > 0 && (
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <h3 className="font-semibold text-gray-800 dark:text-white/90">
                                Riwayat Perubahan Versi Resep
                            </h3>
                        </div>
                        <div className="divide-y divide-gray-100 p-4 dark:divide-gray-800">
                            {versions.map((v) => (
                                <details key={v.version} className="group py-2.5 first:pt-0 last:pb-0">
                                    <summary className="flex cursor-pointer items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        <span>
                                            Versi {v.version} • {v.menu_name} ({money(v.price)})
                                        </span>
                                        <span className="text-gray-400">
                                            {v.actor_name} • {v.created_at} WIB
                                        </span>
                                    </summary>
                                    <div className="mt-2 flex flex-wrap gap-2 pl-2 pt-1 text-xs text-gray-500">
                                        {v.recipe.map((r) => (
                                            <span
                                                key={r.product_id}
                                                className="rounded-lg bg-gray-100 px-2.5 py-1 dark:bg-gray-800"
                                            >
                                                {r.product_name || `Bahan #${r.product_id}`}:{' '}
                                                <strong>
                                                    {r.quantity} {r.unit_name || ''}
                                                </strong>
                                            </span>
                                        ))}
                                    </div>
                                </details>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </IzziLayout>
    );
}
