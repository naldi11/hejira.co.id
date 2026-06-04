import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from './Icon';

let nextId = 1;

/**
 * Listens to the shared `flash` prop and renders auto-dismissing toasts.
 * Mount once inside the layout — every Inertia visit that flashes
 * success/error will surface here.
 */
export default function FlashToasts() {
    const { flash } = usePage().props;
    const [toasts, setToasts] = useState([]);

    useEffect(() => {
        const incoming = [];
        if (flash?.success) incoming.push({ type: 'success', message: flash.success });
        if (flash?.error) incoming.push({ type: 'error', message: flash.error });
        if (incoming.length === 0) return;

        const withIds = incoming.map((t) => ({ ...t, id: nextId++ }));
        setToasts((prev) => [...prev, ...withIds]);

        const timers = withIds.map((t) =>
            setTimeout(() => {
                setToasts((prev) => prev.filter((x) => x.id !== t.id));
            }, t.type === 'error' ? 5000 : 4000),
        );

        return () => timers.forEach(clearTimeout);
    }, [flash]);

    if (toasts.length === 0) return null;

    return (
        <div className="fixed right-8 top-24 z-[70] flex w-full max-w-sm flex-col gap-3">
            {toasts.map((toast) => {
                const isError = toast.type === 'error';
                return (
                    <div
                        key={toast.id}
                        className={`flex items-center gap-4 rounded-2xl border bg-white p-5 shadow-2xl ${
                            isError ? 'border-rose-100 shadow-rose-500/10' : 'border-emerald-100 shadow-emerald-500/10'
                        }`}
                    >
                        <div
                            className={`shrink-0 rounded-xl p-2 text-white shadow-lg ${
                                isError ? 'bg-rose-500 shadow-rose-500/20' : 'bg-emerald-500 shadow-emerald-500/20'
                            }`}
                        >
                            <Icon name={isError ? 'error' : 'check_circle'} className="block text-[20px]" />
                        </div>
                        <p className="text-sm font-bold text-slate-800">{toast.message}</p>
                    </div>
                );
            })}
        </div>
    );
}
