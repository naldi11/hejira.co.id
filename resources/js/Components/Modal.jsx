import { useEffect } from 'react';
import Icon from './Icon';

/**
 * Glassmorphism modal with backdrop blur and enter/leave transitions.
 * Closes on backdrop click and the Escape key. Body scroll is locked while open.
 */
export default function Modal({ show, onClose, title, subtitle, icon, children, maxWidth = 'max-w-lg' }) {
    useEffect(() => {
        if (!show) return;

        const onKey = (e) => e.key === 'Escape' && onClose?.();
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [show, onClose]);

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
            <div
                className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                onClick={onClose}
            />

            <div className={`relative w-full ${maxWidth} overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-2xl`}>
                <div className="absolute right-0 top-0 p-6">
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex h-10 w-10 items-center justify-center rounded-2xl text-slate-400 transition-all hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Tutup"
                    >
                        <Icon name="close" />
                    </button>
                </div>

                <div className="p-8 sm:p-10">
                    {(title || icon) && (
                        <div className="mb-8 flex items-center gap-4">
                            {icon && (
                                <div className="flex h-14 w-14 items-center justify-center rounded-[1.25rem] bg-indigo-50 text-indigo-600 shadow-inner">
                                    <Icon name={icon} className="text-[28px]" />
                                </div>
                            )}
                            <div>
                                {title && <h3 className="font-headline text-xl font-black tracking-tight text-slate-900">{title}</h3>}
                                {subtitle && <p className="mt-1 text-xs font-bold uppercase tracking-widest text-slate-500">{subtitle}</p>}
                            </div>
                        </div>
                    )}

                    {children}
                </div>
            </div>
        </div>
    );
}
