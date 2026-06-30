import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@/Components/ui/button/Button';
import Icon from '@/Components/Icon';

const route = window.route;

export default function SelectBranch({ branches, current_branch_id = null }) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: current_branch_id ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('select-branch.post'));
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-white dark:bg-gray-800';

    return (
        <GuestLayout>
            <Head title="Pilih Cabang Aktif" />

            <div className="w-full max-w-md mx-auto pt-4 pb-6">
                <div className="mb-6 text-center">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400">
                        <Icon name="storefront" className="text-[24px]" />
                    </div>
                    <h1 className="mb-2 font-bold text-gray-800 text-title-sm dark:text-white/90">
                        Pilih Cabang Aktif
                    </h1>
                    <p className="text-sm text-gray-505 dark:text-gray-400">
                        Silakan pilih cabang tempat Anda bertugas saat ini.
                    </p>
                </div>

                <form onSubmit={submit}>
                    <div className="space-y-6">
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Cabang Penempatan
                            </label>
                            <select
                                required
                                value={data.branch_id}
                                onChange={(e) => setData('branch_id', e.target.value)}
                                className={inputClass}
                            >
                                <option value="">-- Pilih Cabang --</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name} ({b.type === 'pusat' ? 'Produksi' : 'Cabang'})
                                    </option>
                                ))}
                            </select>
                            {errors.branch_id && (
                                <p className="mt-2 text-xs font-semibold text-rose-500">
                                    {errors.branch_id}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-between gap-3 pt-2">
                            {current_branch_id ? (
                                <Link
                                    href={route('dashboard')}
                                    className="flex h-11 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition-all"
                                >
                                    Batal
                                </Link>
                            ) : (
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="flex h-11 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-750 hover:bg-gray-50 shadow-sm transition-all"
                                >
                                    Keluar Akun
                                </Link>
                            )}

                            <button
                                type="submit"
                                disabled={processing || !data.branch_id}
                                className="flex-1 flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {processing ? 'Memproses...' : 'Terapkan Cabang'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
