import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useOpnameActive } from '@/Components/StockOpnameAccess';
import { dateTimeLocalWib } from '@/lib/date';
import Modal from '@/Components/Modal';
import { input, Errors } from './Layout';
import { uuid } from '../Izzi/Common';

const route = window.route;

export function SessionState({ session }) {
    const active = useOpnameActive(session.active, session);
    const label =
        session.status === 'closed'
            ? 'Ditutup Owner'
            : active
              ? 'Aktif'
              : 'Waktu Habis';
    const cls =
        session.status === 'closed'
            ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
            : active
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
              : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400';

    return <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}`}>{label}</span>;
}

export default function SessionActions({ session }) {
    const active = useOpnameActive(session.active, session);
    const [action, setAction] = useState(null);
    const later = new Date(Date.now() + 2 * 60 * 60 * 1000);
    const form = useForm({
        request_key: uuid(),
        confirmed: false,
        notes: '',
        expires_at: dateTimeLocalWib(later),
    });
    const reopen = !active;

    return (
        <div className="inline-flex items-center gap-2">
            {session.status !== 'closed' && (
                <button
                    type="button"
                    className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-300"
                    onClick={() => {
                        setAction('close');
                        form.setData('confirmed', false);
                    }}
                >
                    Tutup Sesi
                </button>
            )}
            {reopen && (
                <button
                    type="button"
                    className="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400"
                    onClick={() => {
                        setAction('reopen');
                        form.setData('confirmed', false);
                    }}
                >
                    Buka Kembali
                </button>
            )}

            <Modal show={Boolean(action)} onClose={() => setAction(null)} maxWidth="md">
                {action && (
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(route(`owner.stock-opname.${action}`, session.id), {
                                preserveScroll: true,
                                onSuccess: () => {
                                    setAction(null);
                                    form.reset('confirmed', 'notes');
                                    form.setData('request_key', uuid());
                                },
                            });
                        }}
                        className="p-6 text-left"
                    >
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                            {action === 'close' ? `Tutup Sesi Opname #${session.id}` : `Buka Kembali Sesi #${session.id}`}
                        </h3>

                        <div className="mt-4 space-y-4">
                            <Errors errors={form.errors} />
                            {action === 'reopen' && (
                                <>
                                    <label className="block text-sm font-medium">
                                        Batas Waktu Akses Baru (WIB)
                                        <input
                                            required
                                            className={`${input} mt-1.5`}
                                            type="datetime-local"
                                            value={form.data.expires_at}
                                            onChange={(e) => form.setData('expires_at', e.target.value)}
                                        />
                                    </label>
                                    <label className="block text-sm font-medium">
                                        Alasan Dibuka Kembali
                                        <textarea
                                            required
                                            minLength={5}
                                            maxLength={1000}
                                            rows={2}
                                            className={`${input} mt-1.5`}
                                            value={form.data.notes}
                                            onChange={(e) => form.setData('notes', e.target.value)}
                                            placeholder="Contoh: Penghitungan belum selesai."
                                        />
                                    </label>
                                </>
                            )}

                            <label className="flex items-center gap-2 text-xs font-medium">
                                <input
                                    type="checkbox"
                                    checked={form.data.confirmed}
                                    onChange={(e) => form.setData('confirmed', e.target.checked)}
                                />
                                <span>
                                    {action === 'close'
                                        ? 'Saya menghentikan akses SO. Hasil yang sudah disimpan tetap berlaku.'
                                        : 'Saya memberi akses kembali untuk melanjutkan sesi ini.'}
                                </span>
                            </label>
                        </div>

                        <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                onClick={() => setAction(null)}
                                className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={form.processing || !form.data.confirmed}
                                className={`rounded-xl px-5 py-2 text-sm font-bold text-white disabled:opacity-40 ${
                                    action === 'close' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-500 hover:bg-brand-600'
                                }`}
                            >
                                {form.processing
                                    ? 'Menyimpan…'
                                    : action === 'close'
                                      ? 'Ya, Tutup Sesi'
                                      : 'Aktifkan Kembali'}
                            </button>
                        </div>
                    </form>
                )}
            </Modal>
        </div>
    );
}
